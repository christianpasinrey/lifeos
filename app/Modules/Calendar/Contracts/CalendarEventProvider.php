<?php

namespace App\Modules\Calendar\Contracts;

use App\Models\User;
use Carbon\Carbon;

interface CalendarEventProvider
{
    public function source(): string;

    public function label(): string;

    public function color(): string;

    /**
     * @return \App\Modules\Calendar\DTOs\CalendarEventDTO[]
     */
    public function events(User $user, Carbon $start, Carbon $end): array;
}
