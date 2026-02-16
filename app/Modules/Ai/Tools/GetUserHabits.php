<?php

namespace App\Modules\Ai\Tools;

use App\Models\User;
use App\Modules\Habits\HabitService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetUserHabits implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene los hábitos del usuario para hoy con su estado actual (completados, rachas, valores).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(HabitService::class);
        return $service->getSummaryForAi($this->user);
    }
}
