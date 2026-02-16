@extends('admin::layout')

@section('title', 'Usuarios')

@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold text-white mb-6">Usuarios</h1>

    <div class="bg-surface-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-800">
                    <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Usuario</th>
                    <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Email</th>
                    <th class="text-center px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Admin</th>
                    <th class="text-center px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Módulos</th>
                    <th class="text-right px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @foreach($users as $user)
                <tr class="hover:bg-surface-800/50">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary-500/20 flex items-center justify-center">
                                <span class="text-sm font-medium text-primary-400">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-200">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-400">{{ $user->email }}</td>
                    <td class="px-5 py-4 text-center">
                        @if($user->is_admin)
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-primary-500/10 text-primary-400">Admin</span>
                        @else
                            <span class="text-xs text-gray-500">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-center text-sm text-gray-400">{{ $user->active_modules_count ?? 0 }}</td>
                    <td class="px-5 py-4 text-right">
                        <a href="{{ route('admin.users.modules', $user) }}"
                           class="text-sm text-primary-400 hover:text-primary-300">
                            Módulos →
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
