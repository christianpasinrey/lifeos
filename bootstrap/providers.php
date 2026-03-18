<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleGateProvider::class,
    App\Modules\Habits\HabitServiceProvider::class,
    App\Modules\CustomEntities\CustomEntityServiceProvider::class,
    App\Modules\Ai\AiServiceProvider::class,
    App\Modules\Admin\AdminServiceProvider::class,
    App\Modules\Tasks\TaskServiceProvider::class,
    App\Modules\Finance\FinanceServiceProvider::class,
    App\Modules\Calendar\CalendarServiceProvider::class,
    App\Modules\Storage\StorageServiceProvider::class,
    App\Modules\Notes\NoteServiceProvider::class,
];
