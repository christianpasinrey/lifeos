<?php

namespace App\Modules\Finance\Providers;

use App\Models\User;
use App\Modules\Calendar\Contracts\CalendarEventProvider;
use App\Modules\Calendar\DTOs\CalendarEventDTO;
use App\Modules\Finance\Models\RecurringTransaction;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\RecurringService;
use Carbon\Carbon;

class FinanceCalendarProvider implements CalendarEventProvider
{
    public function source(): string
    {
        return 'finance';
    }

    public function label(): string
    {
        return 'Finanzas';
    }

    public function color(): string
    {
        return '#fbbf24';
    }

    public function events(User $user, Carbon $start, Carbon $end): array
    {
        $events = [];

        // Real transactions in range
        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('category')
            ->get();

        foreach ($transactions as $tx) {
            $isIncome = $tx->type === 'income';
            $events[] = new CalendarEventDTO(
                id: "finance_tx_{$tx->id}",
                title: ($isIncome ? '+' : '-') . ' $' . number_format($tx->amount, 2) . ' ' . ($tx->description ?? ''),
                description: $tx->notes,
                start: $tx->date->format('Y-m-d'),
                end: null,
                allDay: true,
                source: 'finance',
                color: $isIncome ? '#10b981' : '#fbbf24',
                icon: null,
                meta: [
                    'transaction_id' => $tx->id,
                    'type' => $tx->type,
                    'amount' => $tx->amount,
                    'category' => $tx->category?->name,
                ],
            );
        }

        // Future occurrences of recurring transactions
        $recurringService = app(RecurringService::class);
        $recurrings = RecurringTransaction::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNotNull('next_due_date')
            ->get();

        foreach ($recurrings as $recurring) {
            $occurrences = $recurringService->getNextOccurrences($recurring, 60);

            foreach ($occurrences as $dateStr) {
                $date = Carbon::parse($dateStr);
                if ($date->lt($start) || $date->gt($end)) {
                    continue;
                }

                $isIncome = $recurring->type === 'income';
                $events[] = new CalendarEventDTO(
                    id: "finance_recurring_{$recurring->id}_{$dateStr}",
                    title: ($isIncome ? '+' : '-') . ' $' . number_format($recurring->amount, 2) . ' ' . ($recurring->description ?? ''),
                    description: $recurring->notes,
                    start: $dateStr,
                    end: null,
                    allDay: true,
                    source: 'finance',
                    color: $isIncome ? '#10b981' : '#f59e0b',
                    icon: null,
                    meta: [
                        'recurring_id' => $recurring->id,
                        'type' => $recurring->type,
                        'amount' => $recurring->amount,
                        'is_recurring' => true,
                        'frequency' => $recurring->frequency,
                    ],
                );
            }
        }

        return $events;
    }
}
