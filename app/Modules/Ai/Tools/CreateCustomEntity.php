<?php

namespace App\Modules\Ai\Tools;

use App\Models\User;
use App\Modules\CustomEntities\CustomEntityService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateCustomEntity implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Crea una nueva entidad personalizada para trackear cualquier cosa que no encaje en hábitos (ej: libros leídos, gastos, comidas, estado de ánimo). Define la estructura con atributos personalizados.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Nombre de la entidad (ej: "Libros", "Gastos", "Comidas")')
                ->required(),
            'description' => $schema->string()
                ->description('Descripción breve de para qué sirve esta entidad'),
            'attributes' => $schema->string()
                ->description('Atributos en formato JSON array. Cada atributo: {"name":"titulo","type":"string"}. Tipos: string, text, number, decimal, boolean, date, datetime, select, json')
                ->required(),
            'color' => $schema->string()
                ->description('Color hex')
                ->default('#6366f1'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $attributes = json_decode($request->get('attributes'), true);

        if (!is_array($attributes)) {
            return "Error: los atributos deben ser un JSON válido.";
        }

        /** @var CustomEntityService $service */
        $service = app(CustomEntityService::class);
        $model = $service->createModel(
            user: $this->user,
            name: (string) $request->get('name'),
            description: (string) $request->get('description', ''),
            attributes: $attributes,
            icon: null,
            color: (string) $request->get('color', '#6366f1'),
        );

        $attrNames = $model->attributes->pluck('name')->join(', ');
        return "Entidad '{$model->name}' creada con atributos: {$attrNames}. El usuario ya puede registrar datos en ella.";
    }
}
