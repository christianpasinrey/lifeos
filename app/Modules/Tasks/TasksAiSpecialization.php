<?php

namespace App\Modules\Tasks;

use App\Models\User;
use App\Modules\Ai\Contracts\AiSpecialization;
use App\Modules\Tasks\Tools\CreateTask;
use App\Modules\Tasks\Tools\GetBoardTasks;
use App\Modules\Tasks\Tools\GetUserBoards;
use App\Modules\Tasks\Tools\MoveTask;

class TasksAiSpecialization implements AiSpecialization
{
    public function moduleSlug(): string
    {
        return 'tasks';
    }

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        ## Capacidades de Gestión de Tareas (Kanban)
        
        Puedes ayudar al usuario a gestionar sus tareas y proyectos:
        - Ver los tableros Kanban del usuario con sus columnas
        - Ver las tareas organizadas por columnas (Por hacer, En curso, Hecho, etc.)
        - Crear nuevas tareas en las columnas especificadas
        - Mover tareas entre columnas (por ejemplo, pasar de "Por hacer" a "En curso")
        
        Las tareas pueden tener prioridades (low, medium, high) y fechas de vencimiento.
        Usa estas herramientas para organizar el trabajo del usuario.
        INSTRUCTIONS;
    }

    public function tools(User $user): array
    {
        return [
            new GetUserBoards($user),
            new GetBoardTasks($user),
            new CreateTask($user),
            new MoveTask($user),
        ];
    }
}
