<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminApiController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total_users' => User::count(),
            'admin_users' => User::where('is_admin', true)->count(),
            'total_modules_active' => DB::table('user_modules')->where('is_active', true)->count(),
            'premium_users' => DB::table('user_modules')->where('plan', 'premium')->distinct('user_id')->count('user_id'),
        ]);
    }

    public function users()
    {
        $users = User::withCount(['modules as active_modules_count' => function ($q) {
            $q->where('is_active', true);
        }])->orderBy('name')->get();

        return response()->json($users->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'is_admin' => $u->is_admin,
            'active_modules_count' => $u->active_modules_count,
            'created_at' => $u->created_at->toDateString(),
        ]));
    }

    public function user(User $user)
    {
        $availableModules = ModuleRegistry::all();

        $modules = collect($availableModules)->map(function ($info, $slug) use ($user) {
            $userModule = $user->modules()->where('module', $slug)->first();
            return [
                'slug' => $slug,
                'name' => $info['name'],
                'description' => $info['description'],
                'is_active' => $userModule ? $userModule->is_active : false,
                'plan' => $userModule->plan ?? 'free',
                'limits' => $userModule->limits ?? $info['free_limits'],
                'free_limits' => $info['free_limits'],
                'free_features' => $info['free_features'] ?? [],
                'features' => $userModule ? $userModule->resolvedFeatures() : ($info['free_features'] ?? []),
            ];
        })->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at->toDateString(),
            ],
            'modules' => $modules,
        ]);
    }

    public function toggleModule(Request $request, User $user)
    {
        $request->validate([
            'module' => 'required|string|in:' . implode(',', ModuleRegistry::slugs()),
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
            $isActive = !$existing->is_active;
        } else {
            $freeLimits = ModuleRegistry::freeLimits($module);
            DB::table('user_modules')->insert([
                'user_id' => $user->id,
                'module' => $module,
                'is_active' => true,
                'plan' => 'free',
                'limits' => json_encode($freeLimits),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $isActive = true;
        }

        return response()->json(['success' => true, 'is_active' => $isActive]);
    }

    public function updatePlan(Request $request, User $user)
    {
        $request->validate([
            'modules' => 'required|array',
            'modules.*.plan' => 'required|in:free,premium,custom',
            'modules.*.limits' => 'nullable|array',
            'modules.*.features' => 'nullable|array',
        ]);

        foreach ($request->input('modules') as $slug => $data) {
            if (!ModuleRegistry::get($slug)) continue;

            $plan = $data['plan'];
            $limits = null;
            $features = null;

            if ($plan === 'free') {
                $limits = ModuleRegistry::freeLimits($slug);
                $features = null;
            } elseif ($plan === 'custom') {
                $limits = array_map('intval', $data['limits'] ?? []);
                $freeFeatures = ModuleRegistry::freeFeatures($slug);
                if (!empty($freeFeatures) && isset($data['features'])) {
                    $features = [];
                    foreach ($freeFeatures as $key => $defaultValue) {
                        $features[$key] = isset($data['features'][$key]);
                    }
                }
            }

            $user->modules()
                ->where('module', $slug)
                ->update([
                    'plan' => $plan,
                    'limits' => $limits ? json_encode($limits) : null,
                    'features' => $features ? json_encode($features) : null,
                ]);
        }

        return response()->json(['success' => true]);
    }

    public function modules()
    {
        return response()->json(ModuleRegistry::all());
    }
}
