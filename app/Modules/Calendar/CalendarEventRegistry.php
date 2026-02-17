<?php

namespace App\Modules\Calendar;

use App\Models\User;
use App\Modules\Calendar\Contracts\CalendarEventProvider;
use Carbon\Carbon;

class CalendarEventRegistry
{
    /** @var array<string, CalendarEventProvider> */
    private array $providers = [];

    public function register(CalendarEventProvider $provider): void
    {
        $this->providers[$provider->source()] = $provider;
    }

    /**
     * @return \App\Modules\Calendar\DTOs\CalendarEventDTO[]
     */
    public function getEvents(User $user, Carbon $start, Carbon $end, ?array $sources = null): array
    {
        $activeModules = $user->modules->pluck('module')->toArray();
        $events = [];

        foreach ($this->providers as $slug => $provider) {
            if ($sources && !in_array($slug, $sources)) {
                continue;
            }

            // 'calendar' source is always available if the module is active
            // Other sources require their own module to be active
            $moduleSlug = $slug === 'calendar' ? 'calendar' : $slug;
            if (!in_array($moduleSlug, $activeModules)) {
                continue;
            }

            foreach ($provider->events($user, $start, $end) as $event) {
                $events[] = $event;
            }
        }

        usort($events, fn ($a, $b) => strcmp($a->start, $b->start));

        return $events;
    }

    /**
     * @return \App\Modules\Calendar\DTOs\CalendarEventDTO[]
     */
    public function getEventsForSource(User $user, string $source, Carbon $start, Carbon $end): array
    {
        if (!isset($this->providers[$source])) {
            return [];
        }

        $activeModules = $user->modules->pluck('module')->toArray();
        $moduleSlug = $source === 'calendar' ? 'calendar' : $source;

        if (!in_array($moduleSlug, $activeModules)) {
            return [];
        }

        $events = [];
        foreach ($this->providers[$source]->events($user, $start, $end) as $event) {
            $events[] = $event;
        }

        usort($events, fn ($a, $b) => strcmp($a->start, $b->start));

        return $events;
    }

    public function availableSources(User $user): array
    {
        $activeModules = $user->modules->pluck('module')->toArray();
        $sources = [];

        foreach ($this->providers as $slug => $provider) {
            $moduleSlug = $slug === 'calendar' ? 'calendar' : $slug;
            if (in_array($moduleSlug, $activeModules)) {
                $sources[] = [
                    'slug' => $slug,
                    'label' => $provider->label(),
                    'color' => $provider->color(),
                ];
            }
        }

        return $sources;
    }
}
