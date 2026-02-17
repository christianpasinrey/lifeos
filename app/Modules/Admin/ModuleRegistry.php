<?php

namespace App\Modules\Admin;

class ModuleRegistry
{
    private static array $modules = [
        'habits' => [
            'name' => 'Hábitos',
            'description' => 'Tracking de hábitos diarios con rachas y estadísticas',
            'free_limits' => ['max_habits' => 5],
        ],
        'custom_entities' => [
            'name' => 'Entidades Custom',
            'description' => 'Entidades dinámicas creadas por la IA',
            'free_limits' => ['max_entities' => 3],
        ],
        'ai_coach' => [
            'name' => 'Coach IA',
            'description' => 'Asistente IA con acceso a datos del usuario',
            'free_limits' => ['max_messages_per_day' => 10, 'max_conversations' => 10],
        ],
        'tasks' => [
            'name' => 'Tareas',
            'description' => 'Tableros Kanban para gestión de tareas',
            'free_limits' => ['max_boards' => 3, 'max_columns_per_board' => 5],
        ],
    ];

    public static function all(): array
    {
        return self::$modules;
    }

    public static function get(string $slug): ?array
    {
        return self::$modules[$slug] ?? null;
    }

    public static function slugs(): array
    {
        return array_keys(self::$modules);
    }

    public static function freeLimits(string $slug): array
    {
        return self::$modules[$slug]['free_limits'] ?? [];
    }
}
