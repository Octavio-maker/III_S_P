<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pouani - @yield('title', 'Agrotech Precision')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #f8fafc; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 flex min-h-screen text-gray-800">

    <!-- SIDEBAR LEFT -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between p-5 fixed h-full z-10">
        <div>
            <!-- Logo Brand -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-2 mb-8">
                <div class="w-10 h-10 rounded-xl bg-[#063221] text-white flex items-center justify-center">
                    <i class="fa-solid fa-leaf text-xl"></i>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-900 leading-none">Pouani</h2>
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Agrotech Precision</span>
                </div>
            </a>

            <!-- Botón Agregar Nuevo Cultivo (abre el modal, funciona en cualquier página) -->
            <button type="button" onclick="abrirModalCultivo()" class="w-full py-2.5 px-4 bg-[#063221] hover:bg-[#042317] text-white text-xs font-semibold rounded-xl flex items-center justify-center gap-2 mb-6 transition shadow-sm">
                <i class="fa-solid fa-plus"></i>
                <span>Agregar Nuevo Cultivo</span>
            </button>

            <!-- Menú de Navegación -->
            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-medium {{ request()->routeIs('dashboard') ? 'bg-[#063221] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="fa-solid fa-border-all text-sm"></i>
                    <span>Panel</span>
                </a>
                <a href="{{ route('cultivos.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-medium {{ request()->routeIs('cultivos.*') ? 'bg-[#063221] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="fa-solid fa-wheat-awn text-sm"></i>
                    <span>Cultivos</span>
                </a>
                <a href="{{ route('ia.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-medium {{ request()->routeIs('ia.*') ? 'bg-[#063221] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="fa-solid fa-brain text-sm"></i>
                    <span>Análisis IA</span>
                </a>
                @if(session('user_role') === 'Administrador')
                <a href="{{ route('admin.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-medium {{ request()->routeIs('admin.*') ? 'bg-[#063221] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="fa-solid fa-shield-halved text-sm"></i>
                    <span>Administración</span>
                </a>
                @endif
            </nav>
        </div>

        <!-- Footer Sidebar -->
        <div class="space-y-1 border-t border-gray-100 pt-4">
            <a href="mailto:soporte@pouani.com" class="flex items-center gap-3 px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-900">
                <i class="fa-regular fa-circle-question"></i>
                <span>Soporte</span>
            </a>
            <a href="{{ route('logout') }}" class="flex items-center gap-3 px-4 py-2 text-xs font-medium text-gray-500 hover:text-red-600">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT CONTAINER -->
    <div class="flex-1 ml-64 flex flex-col min-h-screen">

        <!-- NAVBAR TOP -->
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-gray-200 px-8 flex items-center justify-between sticky top-0 z-20">
            <!-- Buscador -->
            <div class="relative w-96">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" placeholder="Buscar cultivos, sensores..." class="w-full pl-9 pr-4 py-2 bg-gray-100/80 border-none rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
            </div>

            <!-- Profile & Notifications -->
            <div class="flex items-center gap-4">

                <!-- Notificaciones -->
                <div class="relative">
                    <button type="button" onclick="toggleDropdown('dropdown-notif')" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-gray-200 transition relative">
                        <i class="fa-regular fa-bell text-xs"></i>
                        @if(($navAlertas ?? collect())->count() > 0)
                            <span class="w-2 h-2 bg-red-500 rounded-full absolute top-1 right-1"></span>
                        @endif
                    </button>
                    <div id="dropdown-notif" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-100 z-30 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-900">Notificaciones</span>
                            <span class="text-[10px] text-gray-400">{{ ($navAlertas ?? collect())->count() }} activa(s)</span>
                        </div>
                        <div class="max-h-72 overflow-y-auto divide-y divide-gray-50">
                            @forelse(($navAlertas ?? collect()) as $alerta)
                                <a href="{{ route('cultivos.index') }}" class="block px-4 py-3 hover:bg-gray-50 transition">
                                    <p class="text-xs font-bold text-red-600 flex items-center gap-1.5">
                                        <i class="fa-solid fa-triangle-exclamation"></i> {{ $alerta['nombre'] }}
                                    </p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $alerta['lote'] }} · {{ $alerta['sector'] }} requiere atención</p>
                                </a>
                            @empty
                                <div class="px-4 py-6 text-center text-xs text-gray-400">
                                    <i class="fa-regular fa-circle-check text-emerald-400 text-lg block mb-1"></i>
                                    Sin alertas por ahora.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Configuración / Perfil -->
                <div class="relative">
                    <button type="button" onclick="toggleDropdown('dropdown-config')" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-gray-200 transition">
                        <i class="fa-solid fa-gear text-xs"></i>
                    </button>
                    <div id="dropdown-config" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 z-30 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-xs font-bold text-gray-900">{{ session('user_name', 'Usuario') }}</p>
                            <p class="text-[11px] text-gray-500">{{ session('user_email', '') }}</p>
                            <span class="inline-block mt-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">{{ session('user_role', 'Encargado') }}</span>
                        </div>
                        <div class="py-1">
                            @if(session('user_role') === 'Administrador')
                            <a href="{{ route('admin.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-gray-600 hover:bg-gray-50">
                                <i class="fa-solid fa-shield-halved w-4"></i> Administración
                            </a>
                            @endif
                            <a href="{{ route('logout') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-red-600 hover:bg-red-50">
                                <i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 pl-2 border-l border-gray-200">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(session('user_name', 'Usuario')) }}&background=063221&color=fff" class="w-8 h-8 rounded-full object-cover">
                </div>
            </div>
        </header>

        <!-- DINAMIC PAGE CONTENT -->
        <main class="p-8 flex-1">

            @if(session('success'))
                <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @include('partials.modal-nuevo-cultivo')

    <script>
        function toggleDropdown(id) {
            const el = document.getElementById(id);
            const isOpen = !el.classList.contains('hidden');
            document.querySelectorAll('[id^="dropdown-"]').forEach(d => d.classList.add('hidden'));
            document.getElementById('modal-nuevo-cultivo')?.classList.add('hidden');
            if (!isOpen) el.classList.remove('hidden');
        }
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.relative')) {
                document.querySelectorAll('[id^="dropdown-"]').forEach(d => d.classList.add('hidden'));
            }
        });
        function abrirModalCultivo() {
            document.querySelectorAll('[id^="dropdown-"]').forEach(d => d.classList.add('hidden'));
            document.getElementById('modal-nuevo-cultivo').classList.remove('hidden');
        }
        function cerrarModalCultivo() {
            document.getElementById('modal-nuevo-cultivo').classList.add('hidden');
        }
    </script>

    @yield('scripts')
</body>
</html>
