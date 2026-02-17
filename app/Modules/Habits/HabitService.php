<?php

namespace App\Modules\Habits;

use App\Models\User;
use App\Modules\Habits\Models\Habit;
use App\Modules\Habits\Models\HabitBadge;
use App\Modules\Habits\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class HabitService
{
    public function getAll(User $user)
    {
        return $user->habits()
            ->with(['logs' => fn($q) => $q->where('completed_at', today())])
            ->orderBy('sort_order')
            ->get();
    }

    public function getToday(User $user)
    {
        return $user->habits()
            ->where('is_active', true)
            ->with(['logs' => fn($q) => $q->where('completed_at', today())])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn(Habit $habit) => $habit->isDueToday() && !$habit->isOnVacationOn(today()));
    }

    public function create(User $user, array $data): Habit
    {
        $maxHabits = $user->getModuleLimit('habits', 'max_habits');

        if ($maxHabits !== null && $user->habits()->count() >= $maxHabits) {
            abort(403, "Has alcanzado el límite de {$maxHabits} hábitos en tu plan actual.");
        }

        return $user->habits()->create($data);
    }

    public function update(Habit $habit, array $data): Habit
    {
        $habit->update($data);
        return $habit->fresh();
    }

    public function delete(Habit $habit): void
    {
        $habit->delete();
    }

    public function toggle(Habit $habit, ?float $value = null, ?string $notes = null, ?string $date = null): array
    {
        $date = $date ?? today()->toDateString();
        $existingLog = $habit->logs()->where('completed_at', $date)->first();

        if ($habit->type === 'numeric') {
            if ($existingLog) {
                $existingLog->update(['value' => $value, 'notes' => $notes]);
                $log = $existingLog;
            } else {
                $log = $habit->logs()->create([
                    'completed_at' => $date,
                    'value' => $value,
                    'notes' => $notes,
                ]);
            }
            $this->recalculateStreaks($habit);
            $newBadges = $this->checkAndAwardBadges($habit);

            return ['completed' => true, 'value' => $log->value, 'streak' => $habit->fresh()->current_streak, 'new_badges' => $newBadges];
        }

        // Boolean toggle
        if ($existingLog) {
            $existingLog->delete();
            $this->recalculateStreaks($habit);
            return ['completed' => false, 'streak' => $habit->fresh()->current_streak, 'new_badges' => []];
        }

        $habit->logs()->create(['completed_at' => $date, 'notes' => $notes]);
        $this->recalculateStreaks($habit);
        $newBadges = $this->checkAndAwardBadges($habit);

        return ['completed' => true, 'streak' => $habit->fresh()->current_streak, 'new_badges' => $newBadges];
    }

    public function recalculateStreaks(Habit $habit): void
    {
        $logs = $habit->logs()
            ->orderBy('completed_at', 'desc')
            ->pluck('completed_at')
            ->map(fn($d) => Carbon::parse($d));

        if ($logs->isEmpty()) {
            $habit->update(['current_streak' => 0]);
            return;
        }

        $streak = 0;
        $date = now()->startOfDay();
        $hasFreezeFeature = Gate::forUser($habit->user)->allows('feature:habits.streak_freeze');
        $hasVacationFeature = Gate::forUser($habit->user)->allows('feature:habits.vacation_mode');
        $freezeUsed = false;

        // Preload vacation periods for efficiency
        $vacations = $hasVacationFeature
            ? $habit->vacations()->orderBy('starts_at', 'desc')->get()
            : collect();

        $isOnVacation = function ($d) use ($vacations) {
            $ds = $d->format('Y-m-d');
            return $vacations->contains(fn($v) => $v->starts_at->format('Y-m-d') <= $ds && $v->ends_at->format('Y-m-d') >= $ds);
        };

        if (!$logs->contains(fn($d) => $d->isSameDay($date))) {
            $date = $date->subDay();
        }

        $logDates = $logs->map(fn($d) => $d->format('Y-m-d'))->flip();
        $maxLookback = 400; // safety limit

        while ($maxLookback-- > 0) {
            // Skip non-due dates
            if ($habit->frequency !== 'daily') {
                while (!$habit->isDueOnDate($date)) {
                    $date = $date->subDay();
                }
            }

            $dateStr = $date->format('Y-m-d');

            // Skip vacation days — they don't break or add to streak
            if ($hasVacationFeature && $isOnVacation($date)) {
                $date = $date->subDay();
                continue;
            }

            if ($logDates->has($dateStr)) {
                $streak++;
                $date = $date->subDay();
            } elseif ($hasFreezeFeature && !$freezeUsed && $habit->streak_freeze_count > 0) {
                // Use freeze for 1 missed day
                $freezeUsed = true;
                $date = $date->subDay();
            } else {
                break;
            }
        }

        // Award freeze: 1 freeze per 7 days of completed streak
        $freezeCount = $freezeUsed
            ? max(0, intdiv($streak, 7) - 1)
            : intdiv($streak, 7);

        $updates = [
            'current_streak' => $streak,
            'best_streak' => max($streak, $habit->best_streak),
        ];

        if ($hasFreezeFeature) {
            $updates['streak_freeze_count'] = $freezeCount;
            if ($freezeUsed) {
                $updates['streak_freeze_used_at'] = now()->toDateString();
            }
        }

        $habit->update($updates);
    }

    public function getStats(Habit $habit): array
    {
        $logs = $habit->logs()->orderBy('completed_at', 'desc')->get();

        $thirtyDaysAgo = now()->subDays(30);
        $sevenDaysAgo = now()->subDays(7);

        $totalLogs = $logs->count();
        $last30 = $logs->where('completed_at', '>=', $thirtyDaysAgo)->count();
        $last7 = $logs->where('completed_at', '>=', $sevenDaysAgo)->count();

        $yearAgo = now()->subYear();
        $calendarData = $habit->logs()
            ->where('completed_at', '>=', $yearAgo)
            ->get()
            ->mapWithKeys(fn($log) => [
                $log->completed_at->format('Y-m-d') => [
                    'completed' => true,
                    'value' => $log->value,
                ]
            ]);

        $recentNotes = $habit->logs()
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'date' => $log->completed_at->format('Y-m-d'),
                'notes' => $log->notes,
            ]);

        // Day-of-week consistency (last 30 days)
        $dayOfWeek = $habit->logs()
            ->where('completed_at', '>=', $thirtyDaysAgo)
            ->get()
            ->groupBy(fn($log) => strtolower($log->completed_at->format('D')))
            ->map(fn($group) => $group->count());

        $daysInMonth = ['mon' => 0, 'tue' => 0, 'wed' => 0, 'thu' => 0, 'fri' => 0, 'sat' => 0, 'sun' => 0];
        $d = now()->subDays(29)->startOfDay();
        for ($i = 0; $i < 30; $i++) {
            $daysInMonth[strtolower($d->copy()->addDays($i)->format('D'))]++;
        }

        $dayOfWeekRate = [];
        foreach ($daysInMonth as $day => $total) {
            $completed = $dayOfWeek->get($day, 0);
            $dayOfWeekRate[$day] = $total > 0 ? round($completed / $total * 100) : 0;
        }

        $response = [
            'current_streak' => $habit->current_streak,
            'best_streak' => $habit->best_streak,
            'total_completions' => $totalLogs,
            'last_7_days' => $last7,
            'last_30_days' => $last30,
            'rate_7' => round($last7 / 7 * 100),
            'rate_30' => round($last30 / 30 * 100),
            'calendar' => $calendarData,
            'recent_notes' => $recentNotes,
            'day_of_week' => $dayOfWeekRate,
        ];

        if ($habit->type === 'numeric') {
            $trendData = $habit->logs()
                ->where('completed_at', '>=', $thirtyDaysAgo)
                ->whereNotNull('value')
                ->orderBy('completed_at')
                ->get()
                ->map(fn($log) => [
                    'date' => $log->completed_at->format('Y-m-d'),
                    'value' => (float) $log->value,
                ]);

            $values = $trendData->pluck('value');
            $response['trend'] = $trendData;
            $response['average'] = $values->isNotEmpty() ? round($values->average(), 2) : null;
            $response['min'] = $values->min();
            $response['max'] = $values->max();
        }

        return $response;
    }

    public function checkAndAwardBadges(Habit $habit): array
    {
        $user = $habit->user;
        if (!Gate::forUser($user)->allows('feature:habits.badges')) {
            return [];
        }

        $streak = $habit->fresh()->current_streak;
        $existingBadges = $habit->badges()->pluck('badge_key')->toArray();
        $newBadges = [];

        foreach (HabitBadge::$milestones as $threshold => $milestone) {
            if ($streak >= $threshold && !in_array($milestone['key'], $existingBadges)) {
                $habit->badges()->create([
                    'badge_key' => $milestone['key'],
                    'streak_value' => $streak,
                    'earned_at' => now(),
                ]);
                $newBadges[] = $milestone;
            }
        }

        return $newBadges;
    }

    public function getSparklines(User $user): array
    {
        $sevenDaysAgo = now()->subDays(6)->startOfDay();

        $habits = $user->habits()
            ->where('is_active', true)
            ->get();

        $logs = HabitLog::whereIn('habit_id', $habits->pluck('id'))
            ->where('completed_at', '>=', $sevenDaysAgo)
            ->get()
            ->groupBy('habit_id');

        $dates = collect(range(0, 6))->map(fn($i) => now()->subDays(6 - $i)->format('Y-m-d'));

        $result = [];
        foreach ($habits as $habit) {
            $habitLogs = $logs->get($habit->id, collect())->keyBy(fn($l) => $l->completed_at->format('Y-m-d'));

            $result[$habit->id] = $dates->map(function ($date) use ($habit, $habitLogs) {
                $log = $habitLogs->get($date);
                return [
                    'date' => $date,
                    'completed' => (bool) $log,
                    'value' => $log?->value,
                ];
            })->values()->all();
        }

        return $result;
    }

    public function getSummaryForAi(User $user): string
    {
        $habits = $this->getToday($user);
        $completed = $habits->filter(fn($h) => $h->isCompletedOn(today()->toDateString()));

        $lines = ["Hábitos de hoy ({$completed->count()}/{$habits->count()} completados):"];
        foreach ($habits as $habit) {
            $status = $habit->isCompletedOn(today()->toDateString()) ? '✓' : '✗';
            $streak = $habit->current_streak > 0 ? " (racha: {$habit->current_streak} días)" : '';
            $value = '';
            if ($habit->type === 'numeric') {
                $log = $habit->logs()->where('completed_at', today())->first();
                $value = $log ? " → {$log->value} {$habit->unit}" : " → sin registrar";
                if ($habit->target_value) $value .= " / {$habit->target_value} {$habit->unit}";
            }
            $lines[] = "  [{$status}] {$habit->name}{$value}{$streak}";
        }

        return implode("\n", $lines);
    }
}
