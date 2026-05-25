<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Public self-service registration.
     *
     * Only reachable when REGISTER_ROUTE=true (see config/auth.php). New users
     * are always created without admin privileges.
     */
    public function register(Request $request)
    {
        abort_unless(config('auth.registration_enabled'), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => false,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($this->userWithModules($user), 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json($this->userWithModules(Auth::user()));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function me(Request $request)
    {
        return response()->json($this->userWithModules($request->user()));
    }

    private function userWithModules(User $user): array
    {
        $userData = $user->toArray();

        $modules = $user->modules()
            ->where('is_active', true)
            ->get()
            ->keyBy('module')
            ->map(fn ($m) => [
                'plan' => $m->plan,
                'limits' => $m->limits,
                'features' => $m->resolvedFeatures(),
            ])
            ->toArray();

        $userData['modules'] = $modules;

        return $userData;
    }
}
