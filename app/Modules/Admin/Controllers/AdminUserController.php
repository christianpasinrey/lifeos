<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    protected array $availableModules = [
        'habits' => ['name' => 'Hábitos', 'description' => 'Tracking de hábitos diarios con rachas y estadísticas'],
        'custom_entities' => ['name' => 'Entidades Custom', 'description' => 'Entidades dinámicas creadas por la IA'],
        'ai_coach' => ['name' => 'Coach IA', 'description' => 'Asistente IA con acceso a datos del usuario'],
    ];

    public function index()
    {
        $users = User::withCount(['modules as active_modules_count' => function ($q) {
            $q->where('is_active', true);
        }])->orderBy('name')->get();

        return view('admin::users.index', compact('users'));
    }

    public function modules(User $user)
    {
        $modules = collect($this->availableModules)->map(function ($info, $slug) use ($user) {
            $userModule = $user->modules()->where('module', $slug)->first();
            return [
                'slug' => $slug,
                'name' => $info['name'],
                'description' => $info['description'],
                'is_active' => $userModule ? $userModule->is_active : false,
            ];
        });

        return view('admin::users.modules', compact('user', 'modules'));
    }

    public function toggleModule(Request $request, User $user)
    {
        $request->validate([
            'module' => 'required|string|in:' . implode(',', array_keys($this->availableModules)),
        ]);

        $module = $request->input('module');
        $existing = DB::table('user_modules')
            ->where('user_id', $user->id)
            ->where('module', $module)
            ->first();

        if ($existing) {
            DB::table('user_modules')
                ->where('id', $existing->id)
                ->update(['is_active' => !$existing->is_active, 'updated_at' => now()]);
        } else {
            DB::table('user_modules')->insert([
                'user_id' => $user->id,
                'module' => $module,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Módulo actualizado.');
    }
}
