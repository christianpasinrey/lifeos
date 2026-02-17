<?php

namespace Database\Seeders;

use App\Modules\Habits\Models\HabitTemplate;
use Illuminate\Database\Seeder;

class HabitTemplateSeeder extends Seeder
{
    public function run(): void
    {
        HabitTemplate::truncate();

        $templates = [
            // Productividad
            ['name' => 'Leer 30 minutos', 'category' => 'Productividad', 'icon' => '📚', 'color' => '#6366f1', 'type' => 'numeric', 'unit' => 'min', 'target_value' => 30, 'frequency' => 'daily', 'description' => 'Leer al menos 30 minutos al día', 'sort_order' => 1],
            ['name' => 'Escribir en diario', 'category' => 'Productividad', 'icon' => '✍️', 'color' => '#8b5cf6', 'type' => 'boolean', 'frequency' => 'daily', 'description' => 'Escribir al menos una entrada en tu diario', 'sort_order' => 2],
            ['name' => 'Aprender algo nuevo', 'category' => 'Productividad', 'icon' => '🧠', 'color' => '#06b6d4', 'type' => 'boolean', 'frequency' => 'daily', 'description' => 'Dedicar tiempo a aprender una habilidad nueva', 'sort_order' => 3],
            ['name' => 'Estudiar idiomas', 'category' => 'Productividad', 'icon' => '🌍', 'color' => '#3b82f6', 'type' => 'numeric', 'unit' => 'min', 'target_value' => 15, 'frequency' => 'daily', 'description' => 'Practicar un idioma', 'sort_order' => 4],
            ['name' => 'Sin redes sociales', 'category' => 'Productividad', 'icon' => '📵', 'color' => '#f43f5e', 'type' => 'boolean', 'frequency' => 'daily', 'description' => 'No usar redes sociales durante el día', 'sort_order' => 5],

            // Salud
            ['name' => 'Tomar 2L de agua', 'category' => 'Salud', 'icon' => '💧', 'color' => '#06b6d4', 'type' => 'numeric', 'unit' => 'L', 'target_value' => 2, 'frequency' => 'daily', 'description' => 'Beber al menos 2 litros de agua', 'sort_order' => 6],
            ['name' => 'Dormir 8 horas', 'category' => 'Salud', 'icon' => '😴', 'color' => '#8b5cf6', 'type' => 'numeric', 'unit' => 'h', 'target_value' => 8, 'frequency' => 'daily', 'description' => 'Dormir al menos 8 horas', 'sort_order' => 7],
            ['name' => 'No azúcar', 'category' => 'Salud', 'icon' => '🍬', 'color' => '#f97316', 'type' => 'boolean', 'frequency' => 'daily', 'description' => 'Evitar azúcar añadida', 'sort_order' => 8],
            ['name' => 'Tomar vitaminas', 'category' => 'Salud', 'icon' => '💊', 'color' => '#22c55e', 'type' => 'boolean', 'frequency' => 'daily', 'description' => 'Tomar suplementos diarios', 'sort_order' => 9],
            ['name' => 'Comer frutas/verduras', 'category' => 'Salud', 'icon' => '🥗', 'color' => '#10b981', 'type' => 'numeric', 'unit' => 'porciones', 'target_value' => 5, 'frequency' => 'daily', 'description' => '5 porciones de frutas y verduras', 'sort_order' => 10],

            // Mindfulness
            ['name' => 'Meditar', 'category' => 'Mindfulness', 'icon' => '🧘', 'color' => '#ec4899', 'type' => 'numeric', 'unit' => 'min', 'target_value' => 10, 'frequency' => 'daily', 'description' => 'Sesión de meditación', 'sort_order' => 11],
            ['name' => 'Gratitud', 'category' => 'Mindfulness', 'icon' => '🙏', 'color' => '#eab308', 'type' => 'boolean', 'frequency' => 'daily', 'description' => 'Escribir 3 cosas por las que estás agradecido', 'sort_order' => 12],
            ['name' => 'Respiración profunda', 'category' => 'Mindfulness', 'icon' => '🌬️', 'color' => '#06b6d4', 'type' => 'boolean', 'frequency' => 'daily', 'description' => 'Ejercicio de respiración de 5 minutos', 'sort_order' => 13],
            ['name' => 'Desconexión digital', 'category' => 'Mindfulness', 'icon' => '📴', 'color' => '#6366f1', 'type' => 'boolean', 'frequency' => 'daily', 'description' => '1 hora sin pantallas antes de dormir', 'sort_order' => 14],
            ['name' => 'Caminar al aire libre', 'category' => 'Mindfulness', 'icon' => '🚶', 'color' => '#22c55e', 'type' => 'numeric', 'unit' => 'min', 'target_value' => 20, 'frequency' => 'daily', 'description' => 'Caminar al menos 20 minutos', 'sort_order' => 15],

            // Fitness
            ['name' => '10,000 pasos', 'category' => 'Fitness', 'icon' => '👟', 'color' => '#f97316', 'type' => 'numeric', 'unit' => 'pasos', 'target_value' => 10000, 'frequency' => 'daily', 'description' => 'Caminar 10,000 pasos', 'sort_order' => 16],
            ['name' => 'Ejercicio', 'category' => 'Fitness', 'icon' => '💪', 'color' => '#f43f5e', 'type' => 'numeric', 'unit' => 'min', 'target_value' => 30, 'frequency' => 'custom', 'target_days' => ['mon', 'wed', 'fri'], 'description' => '30 minutos de ejercicio', 'sort_order' => 17],
            ['name' => 'Estiramientos', 'category' => 'Fitness', 'icon' => '🤸', 'color' => '#ec4899', 'type' => 'boolean', 'frequency' => 'daily', 'description' => 'Rutina de estiramientos matutinos', 'sort_order' => 18],
            ['name' => 'Flexiones', 'category' => 'Fitness', 'icon' => '🏋️', 'color' => '#f97316', 'type' => 'numeric', 'unit' => 'reps', 'target_value' => 50, 'frequency' => 'daily', 'description' => '50 flexiones al día', 'sort_order' => 19],
            ['name' => 'Correr', 'category' => 'Fitness', 'icon' => '🏃', 'color' => '#22c55e', 'type' => 'numeric', 'unit' => 'km', 'target_value' => 5, 'frequency' => 'custom', 'target_days' => ['tue', 'thu', 'sat'], 'description' => 'Correr 5 km', 'sort_order' => 20],
        ];

        foreach ($templates as $template) {
            HabitTemplate::create($template);
        }
    }
}
