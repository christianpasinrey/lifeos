@extends('admin::layout')

@section('title', 'Módulos de ' . $user->name)

@section('content')
<div class="p-8">
    <a href="{{ route('admin.users') }}" class="back-link">
        ← Volver a usuarios
    </a>
    <h1 class="page-title mb-1">{{ $user->name }}</h1>
    <p class="page-subtitle mb-6">{{ $user->email }}</p>

    <div class="space-y-3">
        @foreach($modules as $module)
        <div class="flex items-center justify-between admin-card">
            <div>
                <h3 class="text-sm font-medium text-white">{{ $module['name'] }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $module['description'] }}</p>
            </div>
            <form method="POST" action="{{ route('admin.users.toggle-module', $user) }}">
                @csrf
                <input type="hidden" name="module" value="{{ $module['slug'] }}">
                <button type="submit"
                    class="toggle-switch {{ $module['is_active'] ? 'toggle-switch-on' : 'toggle-switch-off' }}">
                    <span class="toggle-knob {{ $module['is_active'] ? 'left-6' : 'left-0.5' }}"></span>
                </button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endsection
