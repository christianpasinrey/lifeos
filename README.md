# LifeOS

Plataforma modular de productividad personal construida con Laravel 12 y Vue 3. Cada funcionalidad (hábitos, tareas, finanzas, calendario, almacenamiento, IA) es un módulo independiente que se registra, comunica y despliega de forma aislada.

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2+, Laravel 12, Laravel Sanctum |
| Frontend | Vue 3 (Composition API), Vue Router 4, Pinia, TanStack Vue Query |
| Estilos | Tailwind CSS 4 |
| Build | Vite 7 |
| IA | Laravel AI (Prism PHP) |
| Archivos | Spatie Media Library |
| Base de datos | SQLite (desarrollo) / MySQL / PostgreSQL |

## Módulos disponibles

| Módulo | Slug | Descripción |
|---|---|---|
| Hábitos | `habits` | Tracking de hábitos diarios con rachas, rutinas y estadísticas |
| Tareas | `tasks` | Tableros Kanban con columnas arrastrables |
| Finanzas | `finance` | ERP financiero personal: transacciones, cuentas, impuestos, facturas, presupuestos, recurrentes |
| Calendario | `calendar` | Calendario unificado que agrega eventos de todos los módulos |
| Almacenamiento | `storage` | Drive personal con previews y análisis IA de archivos |
| Coach IA | `ai_coach` | Asistente IA contextual que se especializa según los módulos activos |
| Entidades Custom | `custom_entities` | Entidades dinámicas creadas por la IA |
| Admin | `admin` | Panel de administración de usuarios y módulos |

## Estructura del proyecto

```
lifeos/
├── app/
│   ├── Events/                    # Eventos globales (ActionRequested, etc.)
│   ├── Http/Middleware/           # Middleware (RequireModule, etc.)
│   ├── Models/User.php           # Modelo User con relación a módulos
│   ├── Modules/                   # <-- Todos los módulos viven aquí
│   │   ├── Admin/                 #     Panel de administración
│   │   ├── Ai/                    #     Coach IA con registries
│   │   ├── Calendar/              #     Calendario multi-fuente
│   │   ├── CustomEntities/        #     Entidades dinámicas
│   │   ├── Finance/               #     ERP financiero
│   │   ├── Habits/                #     Tracking de hábitos
│   │   ├── Storage/               #     Drive personal
│   │   └── Tasks/                 #     Tableros Kanban
│   └── Providers/                 # Providers globales (ModuleGateProvider)
├── bootstrap/
│   └── providers.php              # Registro de Service Providers
├── database/migrations/           # Migraciones con timestamp global
├── resources/js/
│   ├── app.js                     # Entry point Vue
│   ├── composables/               # Hooks de Vue Query por módulo
│   ├── components/                # Componentes organizados por dominio
│   ├── layouts/                   # AppLayout, AdminLayout
│   ├── modules/                   # Manifiestos de módulos frontend
│   │   ├── registry.js            # Sistema de registro
│   │   └── {modulo}.js            # Un archivo por módulo
│   ├── pages/                     # Páginas organizadas por módulo
│   ├── router/index.js            # Rutas SPA con guards de módulo
│   └── stores/auth.js             # Store Pinia de autenticación
└── routes/
    ├── web.php                    # Catch-all SPA
    └── api.php                    # Rutas API globales (/me, /login)
```

## Arquitectura modular

### Anatomía de un módulo (backend)

Cada módulo vive en `app/Modules/{NombreModulo}/` y sigue esta estructura:

```
app/Modules/MiModulo/
├── Controllers/
│   └── MiModuloController.php
├── Models/
│   └── MiModelo.php
├── Listeners/                     # (opcional) Listeners de eventos
│   └── HandleActionFromAi.php
├── Providers/                     # (opcional) Provider de calendario
│   └── MiModuloCalendarProvider.php
├── DTOs/                          # (opcional) Data Transfer Objects
├── Requests/                      # (opcional) Form Requests
├── Resources/                     # (opcional) API Resources
├── Tools/                         # (opcional) Herramientas para el Coach IA
├── MiModuloService.php            # Lógica de negocio (singleton)
├── MiModuloServiceProvider.php    # Registro y boot del módulo
├── MiModuloAiSpecialization.php   # (opcional) Especialización IA
└── routes.php                     # Rutas API del módulo
```

### El Service Provider

El Service Provider es el punto de entrada de cada módulo. Tiene dos responsabilidades:

1. **`register()`** - Registrar servicios como singletons en el container
2. **`boot()`** - Cargar rutas, registrar listeners, e integrarse con otros módulos

```php
<?php

namespace App\Modules\MiModulo;

use App\Events\ActionRequested;
use App\Modules\Ai\AiCoachRegistry;
use App\Modules\MiModulo\Listeners\HandleActionFromAi;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MiModuloServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MiModuloService::class);
    }

    public function boot(): void
    {
        // Cargar rutas del módulo
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        // Escuchar eventos globales
        Event::listen(ActionRequested::class, HandleActionFromAi::class);

        // Registrarse en otros módulos (en booted para asegurar que existen)
        $this->app->booted(function () {
            // Especialización IA
            app(AiCoachRegistry::class)->register(new MiModuloAiSpecialization());

            // Provider de calendario (solo si Calendar está cargado)
            if ($this->app->bound(\App\Modules\Calendar\CalendarEventRegistry::class)) {
                app(\App\Modules\Calendar\CalendarEventRegistry::class)
                    ->register(new \App\Modules\MiModulo\Providers\MiModuloCalendarProvider());
            }
        });
    }
}
```

### Rutas del módulo

Cada módulo define sus rutas en `routes.php`, protegidas con autenticación Sanctum y autorización de módulo:

```php
Route::middleware(['api', 'auth:sanctum', 'module:mi_modulo'])
    ->prefix('api/mi-modulo')
    ->group(function () {
        Route::get('/', [MiModuloController::class, 'index']);
        Route::post('/', [MiModuloController::class, 'store']);
        Route::put('/{item}', [MiModuloController::class, 'update']);
        Route::delete('/{item}', [MiModuloController::class, 'destroy']);
    });
```

El middleware `module:mi_modulo` verifica que el usuario tiene el módulo activo a través del sistema de Gates en `ModuleGateProvider`.

### Control de acceso y planes

Cada módulo declara sus límites free y features premium en `ModuleRegistry`:

```php
// app/Modules/Admin/ModuleRegistry.php
'mi_modulo' => [
    'name' => 'Mi Módulo',
    'description' => 'Descripción del módulo',
    'free_limits' => ['max_items' => 10],
    'free_features' => [
        'feature_basica' => true,    // disponible en free
        'feature_avanzada' => false, // solo premium
    ],
],
```

Verificar límites en controllers:

```php
$module = $request->user()->getModule('mi_modulo');
if (!$module->isPremium() && $count >= $module->getLimit('max_items')) {
    abort(403, 'Límite alcanzado');
}
```

## Sistema de eventos

Los módulos se comunican entre sí mediante eventos desacoplados. Cada módulo registra sus propios listeners en su Service Provider (no hay un EventServiceProvider centralizado).

### ActionRequested

Evento principal para comunicación inter-módulo. Se dispara cuando la IA sugiere crear una entidad:

```php
// Disparar (desde cualquier módulo)
$event = new ActionRequested(
    user: $user,
    type: 'task',                    // 'task', 'habit', etc.
    title: 'Revisar presupuesto',
    description: 'Detalle opcional',
    meta: ['priority' => 'high'],
);
event($event);
$created = $event->results['tasks'] ?? null;  // Recoger resultados
```

```php
// Escuchar (en el módulo receptor)
class CreateTaskFromAction
{
    public function handle(ActionRequested $event): void
    {
        if ($event->type !== 'task') return;
        if (!Gate::forUser($event->user)->allows('module:tasks')) return;

        $task = /* crear la entidad */;
        $event->addResult('tasks', $task);
    }
}
```

## Puntos de integración entre módulos

### 1. Calendario unificado

Cualquier módulo puede mostrar datos en el calendario implementando `CalendarEventProvider`:

```php
use App\Modules\Calendar\Contracts\CalendarEventProvider;
use App\Modules\Calendar\DTOs\CalendarEventDTO;

class MiModuloCalendarProvider implements CalendarEventProvider
{
    public function source(): string { return 'mi_modulo'; }
    public function label(): string  { return 'Mi Módulo'; }
    public function color(): string  { return '#f59e0b'; }

    public function events(User $user, Carbon $start, Carbon $end): array
    {
        return $user->miModelos()
            ->whereBetween('fecha', [$start, $end])
            ->get()
            ->map(fn ($item) => new CalendarEventDTO(
                id: "mi_modulo_{$item->id}",
                title: $item->nombre,
                description: $item->descripcion,
                start: $item->fecha->toIso8601String(),
                end: null,
                allDay: true,
                source: 'mi_modulo',
                color: $this->color(),
                icon: null,
            ))
            ->toArray();
    }
}
```

Registrar en el Service Provider:

```php
if ($this->app->bound(CalendarEventRegistry::class)) {
    app(CalendarEventRegistry::class)->register(new MiModuloCalendarProvider());
}
```

### 2. Coach IA

Los módulos extienden las capacidades de la IA implementando `AiSpecialization`:

```php
use App\Modules\Ai\Contracts\AiSpecialization;

class MiModuloAiSpecialization implements AiSpecialization
{
    public function moduleSlug(): string { return 'mi_modulo'; }

    public function instructions(): string
    {
        return 'Puedes gestionar los items del usuario con las herramientas disponibles.';
    }

    public function tools(User $user): array
    {
        return [
            new Tools\ListItemsTool($user),
            new Tools\CreateItemTool($user),
        ];
    }
}
```

### 3. Acciones cross-module (frontend)

Los módulos pueden inyectar acciones en slots de otros módulos mediante el manifiesto:

```javascript
actions: [
    {
        slot: 'drive-file-actions',      // Aparece en el Drive al analizar archivos
        label: 'Extraer items',
        prompt: 'Analiza este archivo y crea items...',
        order: 30,
    },
]
```

## Frontend modular

### Manifiesto de módulo

Cada módulo frontend se auto-registra en `resources/js/modules/{modulo}.js`:

```javascript
import { defineAsyncComponent, markRaw } from 'vue'
import { PuzzlePieceIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

registerModule({
    module: 'mi_modulo',

    // Navegación lateral
    navItems: [
        { to: '/mi-modulo', label: 'Mi Módulo', icon: markRaw(PuzzlePieceIcon), order: 50 },
    ],

    // Widget en dashboard (opcional)
    dashboardWidgets: [
        {
            component: defineAsyncComponent(() => import('@/components/mi-modulo/Widget.vue')),
            order: 30,
        },
    ],

    // Slot de calendario (opcional)
    calendarSlot: {
        source: 'mi_modulo',
        label: 'Item',
        icon: markRaw(PuzzlePieceIcon),
        color: '#f59e0b',
        order: 40,
        detailComponent: defineAsyncComponent(() =>
            import('@/components/calendar/slots/MiModuloDayDetail.vue')),
        quickCreateComponent: defineAsyncComponent(() =>
            import('@/components/calendar/slots/MiModuloQuickCreate.vue')),
    },
})
```

El registry filtra automáticamente por módulos activos del usuario y expone: `navItems`, `dashboardWidgets`, `sidebarWidgets`, `calendarSlots`, y `actionsForSlot(slot)`.

### Composables (Vue Query)

Cada módulo expone sus operaciones de datos en `resources/js/composables/`:

```javascript
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from '@/lib/api'

export function useItems() {
    return useQuery({
        queryKey: ['mi-modulo', 'items'],
        queryFn: () => api.get('/mi-modulo').then(r => r.data),
    })
}

export function useCreateItem() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/mi-modulo', data).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['mi-modulo'] }),
    })
}
```

### Rutas frontend

Agregar en `resources/js/router/index.js` dentro de los children del layout principal:

```javascript
{
    path: 'mi-modulo',
    name: 'mi-modulo',
    component: () => import('@/pages/mi-modulo/MiModuloPage.vue'),
    meta: { module: 'mi_modulo' },
},
```

El guard `meta: { module: 'mi_modulo' }` redirige al dashboard si el usuario no tiene acceso.

## Guía: Crear un nuevo módulo

### Checklist

#### 1. Backend (obligatorio)

- [ ] Crear `app/Modules/MiModulo/` con Service Provider, Service, Controller, Model y routes
- [ ] Crear migraciones en `database/migrations/`
- [ ] Registrar provider en `bootstrap/providers.php`
- [ ] Registrar módulo en `app/Modules/Admin/ModuleRegistry.php`

#### 2. Frontend (obligatorio)

- [ ] Crear manifiesto en `resources/js/modules/mi_modulo.js`
- [ ] Importarlo en `resources/js/modules/index.js`
- [ ] Crear composable en `resources/js/composables/useMiModulo.js`
- [ ] Crear páginas en `resources/js/pages/mi-modulo/`
- [ ] Agregar ruta en `resources/js/router/index.js`

#### 3. Integraciones opcionales

- [ ] **Calendario**: Implementar `CalendarEventProvider` y registrar en `CalendarEventRegistry`
- [ ] **Coach IA**: Implementar `AiSpecialization` y registrar en `AiCoachRegistry`
- [ ] **Eventos**: Listener de `ActionRequested` para reaccionar a acciones de la IA
- [ ] **Dashboard**: Widget declarado en `dashboardWidgets` del manifiesto
- [ ] **Cross-module**: Acciones en `actions` del manifiesto apuntando a slots de otros módulos

## Desarrollo local

```bash
# Instalar dependencias
composer install && npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate
php artisan migrate

# Crear el primer usuario administrador (interactivo)
php artisan admin:create

# Arrancar todo (server + queue + logs + vite)
composer dev
```

`composer dev` levanta concurrentemente:
- **server** - `php artisan serve`
- **queue** - `php artisan queue:listen`
- **logs** - `php artisan pail` (logs en tiempo real)
- **vite** - `npm run dev`

## Cuentas y registro

LifeOS está pensado como una instancia personal, así que **no expone un endpoint de registro público por defecto** y no incluye una ruta `/register`.

- **Crear usuarios**: usa el comando Artisan. De forma interactiva pedirá nombre, email y contraseña:

  ```bash
  php artisan admin:create
  # o de forma no interactiva:
  php artisan admin:create --name="Tu Nombre" --email=tu@email.com --password=secreto
  # añade --no-admin para crear un usuario sin privilegios de administrador
  ```

- **Habilitar el registro público** (opcional): pon `REGISTER_ROUTE=true` en tu `.env`. Eso activa `POST /api/register`, que crea usuarios **sin** privilegios de administrador. Con `REGISTER_ROUTE=false` (valor por defecto) la ruta no se registra y responde 404.

## Convenciones

- **Namespacing**: `App\Modules\{Modulo}\` para todo el código del módulo
- **Rutas API**: Prefijo `api/{modulo-slug}`, middleware `['api', 'auth:sanctum', 'module:{slug}']`
- **Servicios**: Un `{Modulo}Service.php` registrado como singleton
- **Modelos**: Siempre con `user_id` y relación `belongsTo(User::class)`
- **Migraciones**: En `database/migrations/` con timestamp global (no dentro del módulo)
- **Frontend**: Composables con Vue Query, componentes lazy con `defineAsyncComponent`
- **Eventos**: Registrados en `boot()` del Service Provider, no centralizados
- **Integración condicional**: Usar `$this->app->bound(...)` antes de registrarse en registries de otros módulos
