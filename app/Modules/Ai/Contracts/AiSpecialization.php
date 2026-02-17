<?php

namespace App\Modules\Ai\Contracts;

use App\Models\User;

interface AiSpecialization
{
    /**
     * The unique slug identifier for this module (e.g., 'habits', 'tasks', 'custom_entities')
     */
    public function moduleSlug(): string;

    /**
     * The instruction fragment to append to the AI agent's system prompt
     */
    public function instructions(): string;

    /**
     * The tools available for this specialization, instantiated for the given user
     *
     * @return array<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(User $user): array;
}
