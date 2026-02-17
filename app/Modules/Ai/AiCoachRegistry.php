<?php

namespace App\Modules\Ai;

use App\Models\User;
use App\Modules\Ai\Contracts\AiSpecialization;

class AiCoachRegistry
{
    /** @var array<AiSpecialization> */
    private array $specializations = [];

    /**
     * Register a new AI specialization
     */
    public function register(AiSpecialization $specialization): void
    {
        $this->specializations[$specialization->moduleSlug()] = $specialization;
    }

    /**
     * Get all specializations available for the given user (filtered by active modules)
     *
     * @return array<AiSpecialization>
     */
    public function forUser(User $user): array
    {
        return array_filter(
            $this->specializations,
            fn(AiSpecialization $spec) => $user->hasModule($spec->moduleSlug())
        );
    }

    /**
     * Compose the full instructions by concatenating all active specialization instructions
     */
    public function composeInstructions(User $user): string
    {
        $fragments = array_map(
            fn(AiSpecialization $spec) => $spec->instructions(),
            $this->forUser($user)
        );

        return implode("\n\n", $fragments);
    }

    /**
     * Compose all tools from active specializations
     *
     * @return array<\Laravel\Ai\Contracts\Tool>
     */
    public function composeTools(User $user): array
    {
        $tools = [];

        foreach ($this->forUser($user) as $spec) {
            $tools = array_merge($tools, $spec->tools($user));
        }

        return $tools;
    }
}
