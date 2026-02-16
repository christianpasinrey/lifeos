<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - lifeOS</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-surface-950 text-gray-100">
    <div class="admin-shell">
        @auth
        @if(auth()->user()->is_admin)
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <h1 class="text-lg font-bold">
                    <span class="text-primary-400">life</span>OS
                    <span class="text-xs text-gray-500 ml-1">admin</span>
                </h1>
            </div>
            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}"
                   class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'admin-nav-link-active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.users') }}"
                   class="admin-nav-link {{ request()->routeIs('admin.users*') ? 'admin-nav-link-active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Usuarios
                </a>
            </nav>
            <div class="admin-footer">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full admin-nav-link hover:!text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Salir
                    </button>
                </form>
            </div>
        </aside>
        @endif
        @endauth

        <!-- Content -->
        <main class="admin-content">
            @if(session('success'))
            <div class="admin-alert-success">
                {{ session('success') }}
            </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
