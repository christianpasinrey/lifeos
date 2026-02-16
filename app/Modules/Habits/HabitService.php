<?php

namespace App\Modules\Habits;

use App\Models\User;
use App\Modules\Habits\Models\Habit;
use App\Modules\Habits\Models\HabitLog;
use Carbon\Carbon;

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
            ->filter(fn(Habit $habit) => $habit->isDueToday());
    }

    public function create(User $user, array $data): Habit
    {
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

            return ['completed' => true, 'value' => $log->value, 'streak' => $habit->fresh()->current_streak];
        }

        // Boolean toggle
        if ($existingLog) {
            $existingLog->delete();
            $this->recalculateStreaks($habit);
            return ['completed' => false, 'streak' => $habit->fresh()->current_streak];
        }

        $habit->logs()->create(['completed_at' => $date, 'notes' => $notes]);
        $this->recalculateStreaks($habit);

        return ['completed' => true, 'streak' => $habit->fresh()->current_streak];
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

        if (!$logs->contains(fn($d) => $d->isSameDay($date))) {
            $date = $date->subDay();
        }

        foreach ($logs as $log) {
            if ($habit->frequency !== 'daily') {
                while (!$habit->isDueOnDate($date) && $date->gte($log)) {
                    $date = $date->subDay();
                }
            }

            if ($log->isSameDay($date)) {
                $streak++;
                $date = $date->subDay();
            } else {
                break;
            }
        }

        $habit->update([
            'current_streak' => $streak,
            'best_streak' => max($streak, $habit->best_streak),
        ]);
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

        $response = [
            'current_streak' => $habit->current_streak,
            'best_streak' => $habit->best_streak,
            'total_completions' => $totalLogs,
            'last_7_days' => $last7,
            'last_30_days' => $last30,
            'rate_7' => round($last7 / 7 * 100),
            'rate_30' => round($last30 / 30 * 100),
            'calendar' => $calendarData,
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
