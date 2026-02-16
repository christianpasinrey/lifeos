@extends('admin::layout')

@section('title', 'Admin Login')

@section('content')
<div class="login-container">
    <div class="login-box">
        <div class="login-logo">
            <h1 class="text-3xl font-bold">
                <span class="text-primary-400">life</span>OS
            </h1>
            <p class="mt-2 text-gray-400 text-sm">Panel de administración</p>
        </div>

        <form method="POST" action="{{ route('admin.login') }}" class="admin-login-form">
            @csrf

            <div>
                <label class="admin-form-label">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="admin-form-input"
                    placeholder="admin@lifeos.test" />
                @error('email')
                    <p class="mt-1 form-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="admin-form-label">Contraseña</label>
                <input name="password" type="password" required
                    class="admin-form-input"
                    placeholder="••••••••" />
            </div>

            <button type="submit" class="admin-btn-primary">
                Entrar
            </button>
        </form>
    </div>
</div>
@endsection
