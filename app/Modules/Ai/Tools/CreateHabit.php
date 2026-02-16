<?php

namespace App\Modules\Ai\Tools;

use App\Models\User;
use App\Modules\Habits\HabitService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateHabit implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Crea un nuevo hábito para el usuario. Usa esto cuando el usuario quiera empezar a trackear un nuevo hábito.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Nombre del hábito')
                ->required(),
            'type' => $schema->string()
                ->description('Tipo: "boolean" para sí/no, "numeric" para valor numérico')
                ->enum(['boolean', 'numeric'])
                ->default('boolean'),
            'unit' => $schema->string()
                ->description('Unidad de medida para hábitos numéricos (pasos, litros, min, km, kg, horas)'),
            'target_value' => $schema->number()
                ->description('Valor objetivo diario para hábitos numéricos'),
            'frequency' => $schema->string()
                ->description('Frecuencia: "daily", "weekly", "custom"')
                ->enum(['daily', 'weekly', 'custom'])
                ->default('daily'),
            'target_days' => $schema->string()
                ->description('Días objetivo separados por coma (mon,tue,wed,thu,fri,sat,sun). Solo para weekly/custom.'),
            'color' => $schema->string()
                ->description('Color hex del hábito')
                ->default('#6366f1'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $data = [
            'name' => $request->get('name'),
            'type' => $request->get('type', 'boolean'),
            'frequency' => $request->get('frequency', 'daily'),
            'color' => $request->get('color', '#6366f1'),
        ];

        if ($request->get('unit')) {
            $data['unit'] = $request->get('unit');
        }
        if ($request->get('target_value')) {
            $data['target_value'] = $request->get('target_value');
        }
        if ($request->get('target_days')) {
            $data['target_days'] = explode(',', $request->get('target_days'));
        }

        $service = app(HabitService::class);
        $habit = $service->create($this->user, $data);

        return "Hábito '{$habit->name}' creado con éxito (tipo: {$habit->type}, frecuencia: {$habit->frequency}).";
    }
}
