 Here is Claude's plan:
╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
 Plan: Refactorizar HabitCoach → AiCoach Modular

 Contexto

 El agente HabitCoach tiene instrucciones y tools hardcodeadas para hábitos + custom entities. Queremos un AiCoach
 genérico que se componga dinámicamente según los módulos activos del usuario. Cada módulo contribuye su fragmento de
 instrucciones + sus tools. Así cuando se añada un módulo nuevo, solo implementa la interfaz y se registra — sin tocar
 el agente.

 Ya implementado (plan anterior): persistencia de conversaciones con RemembersConversations + ConversationController +
 ChatPanel con lista de conversaciones.

 ---
 Arquitectura: Contract + Registry

 Cada módulo implementa AiSpecialization (interfaz) y lo registra en AiCoachRegistry (singleton) desde su
 ServiceProvider. El agente AiCoach consulta el registry para componer instrucciones y tools del usuario actual.

 AiSpecialization (interfaz)
 ├── HabitsAiSpecialization      → instructions de hábitos + 4 tools
 ├── CustomEntitiesAiSpecialization → instructions de entidades + 1 tool
 └── TasksAiSpecialization       → instructions de tareas + 4 tools nuevas


 ---
 Paso 1: Crear interfaz AiSpecialization

 Nuevo: app/Modules/Ai/Contracts/AiSpecialization.php

 interface AiSpecialization {
     public function moduleSlug(): string;      // 'habits', 'tasks', etc.
     public function instructions(): string;    // fragmento de prompt
     public function tools(User $user): array;  // Tool[] instanciadas
 }

 ---
 Paso 2: Crear AiCoachRegistry

 Nuevo: app/Modules/Ai/AiCoachRegistry.php

 - register(AiSpecialization $spec) — llamado desde ServiceProviders
 - forUser(User $user): array — filtra por $user->hasModule($slug)
 - composeInstructions(User $user): string — junta fragmentos
 - composeTools(User $user): array — junta tools

 ---
 Paso 3: Crear agente AiCoach

 Nuevo: app/Modules/Ai/Agents/AiCoach.php

 - Mismas interfaces que HabitCoach: Agent, Conversational, HasTools
 - Mismos traits: Promptable, RemembersConversations
 - instructions(): prompt base genérico + $registry->composeInstructions($user)
 - tools(): $registry->composeTools($user)

 Prompt base:

 Eres un coach personal y asistente de productividad. Tu nombre es Coach.
 Tienes acceso a los datos reales del usuario según los módulos activos.
 Reglas generales: español, conciso, empático, no inventar datos, usar herramientas.


 ---
 Paso 4: Crear 3 especializaciones

 4a. HabitsAiSpecialization

 Nuevo: app/Modules/Habits/HabitsAiSpecialization.php
 - moduleSlug(): 'habits'
 - instructions(): capacidades de hábitos (ver, stats, crear, toggle)
 - tools(): GetUserHabits, GetHabitStats, CreateHabit, ToggleHabit (reutiliza los existentes en app/Modules/Ai/Tools/)

 4b. CustomEntitiesAiSpecialization

 Nuevo: app/Modules/CustomEntities/CustomEntitiesAiSpecialization.php
 - moduleSlug(): 'custom_entities'
 - instructions(): capacidades de entidades personalizadas
 - tools(): CreateCustomEntity (reutiliza el existente)

 4c. TasksAiSpecialization

 Nuevo: app/Modules/Tasks/TasksAiSpecialization.php
 - moduleSlug(): 'tasks'
 - instructions(): capacidades Kanban (ver tableros, tareas, crear, mover)
 - tools(): 4 tools nuevas (ver paso 5)

 ---
 Paso 5: Crear 4 tools nuevas para Tasks

 Todas en app/Modules/Tasks/Tools/, siguiendo el patrón existente (implements Tool, constructor con User,
 description(), schema(), handle()).

 GetUserBoards

 - Sin params
 - Lista tableros del usuario con columnas y count de tareas
 - Usa: $user->boards()->with('columns')

 GetBoardTasks

 - Param: board_id (integer, required)
 - Lista tareas por columna con prioridad y due_date
 - Usa: Board::where(...)->with(['columns.tasks'])

 CreateTask

 - Params: column_id (required), title (required), description, priority (low/medium/high), due_date
 - Valida que la columna pertenece al usuario
 - Usa: TaskService::createTask()

 MoveTask

 - Params: task_id (required), target_column_id (required)
 - Valida ownership y mismo board
 - Usa: TaskService::moveTask()

 ---
 Paso 6: Registrar todo

 6a. app/Modules/Ai/AiServiceProvider.php

 - register(): $this->app->singleton(AiCoachRegistry::class)

 6b. app/Modules/Habits/HabitServiceProvider.php

 - En boot(): $this->app->booted(fn() => app(AiCoachRegistry::class)->register(new HabitsAiSpecialization()))

 6c. app/Modules/CustomEntities/CustomEntityServiceProvider.php

 - En boot(): igual, registra CustomEntitiesAiSpecialization

 6d. app/Modules/Tasks/TaskServiceProvider.php

 - En boot(): igual, registra TasksAiSpecialization

 (booted() callback evita problemas de orden de boot entre providers)

 ---
 Paso 7: Actualizar AiChatController + eliminar HabitCoach

 Modificar: app/Modules/Ai/Controllers/AiChatController.php
 - use HabitCoach → use AiCoach
 - resolveAgent(): new AiCoach($user) en lugar de new HabitCoach($user)

 Eliminar: app/Modules/Ai/Agents/HabitCoach.php

 ---
 Resumen de archivos

 ┌───────────────────────────────────────────────────────────────┬───────────────────────────────────────┐
 │                            Archivo                            │                Acción                 │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Ai/Contracts/AiSpecialization.php                 │ Nuevo — interfaz                      │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Ai/AiCoachRegistry.php                            │ Nuevo — registro de especializaciones │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Ai/Agents/AiCoach.php                             │ Nuevo — agente genérico               │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Habits/HabitsAiSpecialization.php                 │ Nuevo — espec. hábitos                │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/CustomEntities/CustomEntitiesAiSpecialization.php │ Nuevo — espec. entidades              │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Tasks/TasksAiSpecialization.php                   │ Nuevo — espec. tareas                 │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Tasks/Tools/GetUserBoards.php                     │ Nuevo — tool                          │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Tasks/Tools/GetBoardTasks.php                     │ Nuevo — tool                          │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Tasks/Tools/CreateTask.php                        │ Nuevo — tool                          │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Tasks/Tools/MoveTask.php                          │ Nuevo — tool                          │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Ai/AiServiceProvider.php                          │ Modificar — registrar singleton       │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Ai/Controllers/AiChatController.php               │ Modificar — HabitCoach → AiCoach      │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Habits/HabitServiceProvider.php                   │ Modificar — registrar espec.          │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/CustomEntities/CustomEntityServiceProvider.php    │ Modificar — registrar espec.          │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Tasks/TaskServiceProvider.php                     │ Modificar — registrar espec.          │
 ├───────────────────────────────────────────────────────────────┼───────────────────────────────────────┤
 │ app/Modules/Ai/Agents/HabitCoach.php                          │ Eliminar                              │
 └───────────────────────────────────────────────────────────────┴───────────────────────────────────────┘

 ---
 Verificación

 1. php artisan route:list --path=ai — rutas siguen funcionando
 2. npx vite build — frontend compila sin errores
 3. Chat con módulo hábitos activo → Coach responde sobre hábitos, usa tools de hábitos
 4. Chat con módulo tasks activo → Coach puede ver tableros, crear tareas, moverlas
 5. Chat con ambos módulos activos → Coach tiene todas las herramientas
 6. Conversaciones persistidas siguen funcionando (no se toca esa parte)
 7. Un usuario sin módulo tasks no ve tools de tareas en el Coach
╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
