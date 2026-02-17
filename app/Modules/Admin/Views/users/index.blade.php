@extends('admin::layout')

@section('title', 'Usuarios')

@section('content')
<div class="p-8">
    <h1 class="page-title mb-6">Usuarios</h1>

    <div class="admin-card !p-0 overflow-hidden">
        <table class="admin-table">
            <thead>
                <tr class="border-b border-gray-800">
                    <th class="table-th">Usuario</th>
                    <th class="table-th">Email</th>
                    <th class="table-th text-center">Admin</th>
                    <th class="table-th text-center">Módulos</th>
                    <th class="table-th text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @foreach($users as $user)
                <tr class="table-row">
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary-500/20 flex items-center justify-center">
                                <span class="text-sm font-medium text-primary-400">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-200">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="table-td text-sm text-gray-400">{{ $user->email }}</td>
                    <td class="table-td text-center">
                        @if($user->is_admin)
                            <span class="badge-primary">Admin</span>
                        @else
                            <span class="text-xs text-gray-500">—</span>
                        @endif
                    </td>
                    <td class="table-td text-center text-sm text-gray-400">{{ $user->active_modules_count ?? 0 }}</td>
                    <td class="table-td text-right space-x-3">
                        <a href="{{ route('admin.users.modules', $user) }}"
                           class="text-sm text-primary-400 hover:text-primary-300">
                            Módulos →
                        </a>
                        <a href="{{ route('admin.users.plan', $user) }}"
                           class="text-sm text-primary-400 hover:text-primary-300">
                            Plan →
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
