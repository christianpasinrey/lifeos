<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::withCount(['modules as active_modules_count' => function ($q) {
            $q->where('is_active', true);
        }])->orderBy('name')->get();

        return view('admin::users.index', compact('users'));
    }

    public function modules(User $user)
    {
        $availableModules = ModuleRegistry::all();

        $modules = collect($availableModules)->map(function ($info, $slug) use ($user) {
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
        }

        return back()->with('success', 'Módulo actualizado.');
    }

    public function plan(User $user)
    {
        $availableModules = ModuleRegistry::all();

        $modules = $user->modules()
            ->where('is_active', true)
            ->get()
            ->map(function ($userModule) use ($availableModules) {
                $info = $availableModules[$userModule->module] ?? null;
                if (!$info) return null;

                return [
                    'slug' => $userModule->module,
                    'name' => $info['name'],
                    'plan' => $userModule->plan,
                    'limits' => $userModule->limits ?? $info['free_limits'],
                    'free_limits' => $info['free_limits'],
                    'free_features' => $info['free_features'] ?? [],
                    'features' => $userModule->resolvedFeatures(),
                    'custom_features' => $userModule->features,
                ];
            })
            ->filter();

        return view('admin::users.plan', compact('user', 'modules'));
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
                // Free plan: no custom features (use defaults from registry)
                $features = null;
            } elseif ($plan === 'custom') {
                $limits = array_map('intval', $data['limits'] ?? []);
                // Custom plan: save feature overrides
                $freeFeatures = ModuleRegistry::freeFeatures($slug);
                if (!empty($freeFeatures) && isset($data['features'])) {
                    $features = [];
                    foreach ($freeFeatures as $key => $defaultValue) {
                        $features[$key] = isset($data['features'][$key]);
                    }
                }
            }
            // premium: limits = null, features = null (all enabled via isPremium)

            $user->modules()
                ->where('module', $slug)
                ->update([
                    'plan' => $plan,
                    'limits' => $limits ? json_encode($limits) : null,
                    'features' => $features ? json_encode($features) : null,
                ]);
        }

        return back()->with('success', 'Planes actualizados correctamente.');
    }
}
