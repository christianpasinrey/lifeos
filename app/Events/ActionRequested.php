<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ActionRequested
{
    use Dispatchable;

    public array $results = [];

    public function __construct(
        public User $user,
        public string $type,
        public string $title,
        public ?string $description = null,
        public array $meta = [],
    ) {}

    public function addResult(string $module, mixed $result): void
    {
        $this->results[$module] = $result;
    }
}
