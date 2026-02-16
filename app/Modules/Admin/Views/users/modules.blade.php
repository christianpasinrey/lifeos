@extends('admin::layout')

@section('title', 'Módulos de ' . $user->name)

@section('content')
<div class="p-8">
    <a href="{{ route('admin.users') }}" class="text-sm text-gray-400 hover:text-gray-200 mb-2 inline-flex items-center gap-1">
        ← Volver a usuarios
    </a>
    <h1 class="text-2xl font-bold text-white mb-1">{{ $user->name }}</h1>
    <p class="text-gray-400 text-sm mb-6">{{ $user->email }}</p>

    <div class="space-y-3">
        @foreach($modules as $module)
        <div class="flex items-center justify-between bg-surface-900 border border-gray-800 rounded-xl p-5">
            <div>
                <h3 class="text-sm font-medium text-white">{{ $module['name'] }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $module['description'] }}</p>
            </div>
            <form method="POST" action="{{ route('admin.users.toggle-module', $user) }}">
                @csrf
                <input type="hidden" name="module" value="{{ $module['slug'] }}">
                <button type="submit"
                    class="relative w-12 h-6 rounded-full transition-colors {{ $module['is_active'] ? 'bg-primary-600' : 'bg-gray-700' }}">
                    <span class="absolute top-0.5 {{ $module['is_active'] ? 'left-6' : 'left-0.5' }} w-5 h-5 bg-white rounded-full transition-all shadow"></span>
                </button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endsection
