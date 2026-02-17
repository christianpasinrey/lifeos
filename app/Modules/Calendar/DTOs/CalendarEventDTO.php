<?php

namespace App\Modules\Calendar\DTOs;

class CalendarEventDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $start,
        public readonly ?string $end,
        public readonly bool $allDay,
        public readonly string $source,
        public readonly string $color,
        public readonly ?string $icon,
        public readonly array $meta = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start' => $this->start,
            'end' => $this->end,
            'all_day' => $this->allDay,
            'source' => $this->source,
            'color' => $this->color,
            'icon' => $this->icon,
            'meta' => $this->meta,
        ];
    }
}
