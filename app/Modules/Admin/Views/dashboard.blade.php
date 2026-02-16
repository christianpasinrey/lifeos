@extends('admin::layout')

@section('title', 'Dashboard')

@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold text-white mb-6">Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-surface-900 border border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-400 mb-1">Usuarios totales</p>
            <p class="text-3xl font-bold text-white">{{ $stats['total_users'] }}</p>
        </div>
        <div class="bg-surface-900 border border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-400 mb-1">Administradores</p>
            <p class="text-3xl font-bold text-primary-400">{{ $stats['admin_users'] }}</p>
        </div>
    </div>
</div>
@endsection
