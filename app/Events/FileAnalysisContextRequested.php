<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class FileAnalysisContextRequested
{
    use Dispatchable;

    public array $contextFragments = [];

    public function __construct(
        public User $user,
    ) {}

    public function addContext(string $context): void
    {
        $this->contextFragments[] = $context;
    }

    public function getContext(): string
    {
        return implode("\n\n", $this->contextFragments);
    }
}
