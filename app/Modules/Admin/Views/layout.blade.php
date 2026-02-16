<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - lifeOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca' },
                        surface: { 700: '#334155', 800: '#1e293b', 900: '#0f172a', 950: '#020617' },
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full bg-surface-950 text-gray-100">
    <div class="flex h-full">
        @auth
        @if(auth()->user()->is_admin)
        <!-- Sidebar -->
        <aside class="w-56 bg-surface-900 border-r border-gray-800 flex flex-col">
            <div class="p-5">
                <h1 class="text-lg font-bold">
                    <span class="text-primary-400">life</span>OS
                    <span class="text-xs text-gray-500 ml-1">admin</span>
                </h1>
            </div>
            <nav class="flex-1 px-3 space-y-1">
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-primary-500/10 text-primary-400' : 'text-gray-400 hover:bg-surface-800 hover:text-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.users') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.users*') ? 'bg-primary-500/10 text-primary-400' : 'text-gray-400 hover:bg-surface-800 hover:text-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Usuarios
                </a>
            </nav>
            <div class="p-3 border-t border-gray-800">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-surface-800 hover:text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Salir
                    </button>
                </form>
            </div>
        </aside>
        @endif
        @endauth

        <!-- Content -->
        <main class="flex-1 overflow-y-auto">
            @if(session('success'))
            <div class="mx-8 mt-4 px-4 py-3 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400 text-sm">
                {{ session('success') }}
            </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
