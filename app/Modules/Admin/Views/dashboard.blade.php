@extends('admin::layout')

@section('title', 'Dashboard')

@section('content')
<div class="p-8">
    <h1 class="page-title mb-6">Dashboard</h1>

    <div class="dashboard-grid-2">
        <div class="admin-card">
            <p class="stat-label mb-1">Usuarios totales</p>
            <p class="stat-value-primary">{{ $stats['total_users'] }}</p>
        </div>
        <div class="admin-card">
            <p class="stat-label mb-1">Administradores</p>
            <p class="stat-value-primary">{{ $stats['admin_users'] }}</p>
        </div>
    </div>
</div>
@endsection
