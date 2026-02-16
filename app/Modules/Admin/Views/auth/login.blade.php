@extends('admin::layout')

@section('title', 'Admin Login')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold">
                <span class="text-primary-400">life</span>OS
            </h1>
            <p class="mt-2 text-gray-400 text-sm">Panel de administración</p>
        </div>

        <form method="POST" action="{{ route('admin.login') }}" class="bg-surface-900 rounded-2xl p-6 border border-gray-800 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-3.5 py-2.5 bg-surface-800 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    placeholder="admin@lifeos.test" />
                @error('email')
                    <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Contraseña</label>
                <input name="password" type="password" required
                    class="w-full px-3.5 py-2.5 bg-surface-800 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    placeholder="••••••••" />
            </div>

            <button type="submit"
                class="w-full py-2.5 px-4 bg-primary-600 hover:bg-primary-500 text-white font-medium rounded-lg transition-colors">
                Entrar
            </button>
        </form>
    </div>
</div>
@endsection
