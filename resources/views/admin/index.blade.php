@extends('layouts.app')

@section('title', 'Administración')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Welcome / Admin Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-[#063221] to-slate-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-6 -bottom-6 opacity-10 text-amber-300 pointer-events-none">
            <i class="fa-solid fa-user-shield text-9xl"></i>
        </div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-amber-500/20 text-amber-300 border border-amber-400/30 inline-block mb-2">
                    <i class="fa-solid fa-lock mr-1"></i> Área Restringida
                </span>
                <h1 class="text-2xl font-bold tracking-tight">Panel de Control de Usuarios y Configuración</h1>
                <p class="text-xs text-slate-300 mt-1 max-w-2xl">
                    Gestión de accesos reales de Firestore, roles de personal y estado de los sensores IoT.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="abrirModalUsuario()" class="px-4 py-2.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs rounded-xl transition shadow-md flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Registrar Usuario
                </button>
            </div>
        </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Usuarios Registrados</span>
                <span class="text-2xl font-extrabold text-slate-900 mt-1 block">{{ $totalUsuarios }}</span>
                <span class="text-[11px] text-emerald-600 font-medium mt-1 inline-block">{{ $usuariosActivos }} Activos</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Dispositivos IoT</span>
                <span class="text-2xl font-extrabold text-slate-900 mt-1 block">{{ $totalSensores }} Sensores</span>
                <span class="text-[11px] text-emerald-600 font-medium mt-1 inline-block">{{ $sensoresActivos }} Encendidos</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-microchip"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Conexión Firestore</span>
                <span class="text-2xl font-extrabold {{ $firestoreActivo ? 'text-emerald-600' : 'text-red-600' }} mt-1 block">{{ $firestoreActivo ? 'Conectado' : 'Error' }}</span>
                <span class="text-[11px] text-slate-500 font-medium mt-1 inline-block truncate max-w-[160px] block">{{ $firestoreActivo ? 'proyecto: sistema-hidroponico' : $errorConexion }}</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-database"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Alertas Activas</span>
                <span class="text-2xl font-extrabold {{ $alertasActivas > 0 ? 'text-red-600' : 'text-slate-900' }} mt-1 block">{{ $alertasActivas }}</span>
                <span class="text-[11px] text-slate-500 font-medium mt-1 inline-block">Sin revisar</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>

    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Users Management Table (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between">

            <div>
                <div class="p-5 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900">Gestión de Usuarios</h2>
                    <p class="text-[11px] text-slate-500">Administra accesos y roles guardados en Firestore.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-left text-slate-500 uppercase text-[10px]">
                                <th class="py-3 px-4">Usuario</th>
                                <th class="py-3 px-4">Correo</th>
                                <th class="py-3 px-4">Rol</th>
                                <th class="py-3 px-4">Estado</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usuarios as $usuario)
                                @php
                                    $iniciales = collect(explode(' ', $usuario['nombre']))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition border-t border-slate-100">
                                    <td class="py-3 px-4 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-[10px]">
                                            {{ strtoupper($iniciales) }}
                                        </div>
                                        <span class="font-bold text-slate-900">{{ $usuario['nombre'] }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500">{{ $usuario['correo'] }}</td>
                                    <td class="py-3 px-4">
                                        @if($usuario['correo'] === session('user_email'))
                                            <span class="text-[10px] font-bold rounded-md border bg-amber-50 text-amber-800 border-amber-200 px-2 py-1 inline-block">
                                                {{ $usuario['rol'] }}
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('admin.usuarios.rol', $usuario['id']) }}" onsubmit="return confirm('¿Cambiar el rol de {{ $usuario['nombre'] }}?');">
                                                @csrf
                                                <select name="rol" onchange="this.form.submit()" class="text-[10px] font-bold rounded-md border {{ $usuario['rol'] === 'Administrador' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-900 border-emerald-300/60' }} px-2 py-1 focus:outline-none">
                                                    <option value="Encargado" {{ $usuario['rol'] === 'Encargado' ? 'selected' : '' }}>Encargado</option>
                                                    <option value="Administrador" {{ $usuario['rol'] === 'Administrador' ? 'selected' : '' }}>Administrador</option>
                                                </select>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1 font-bold {{ $usuario['estado'] === 'Activo' ? 'text-emerald-600' : 'text-slate-400' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $usuario['estado'] === 'Activo' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span> {{ $usuario['estado'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($usuario['correo'] === session('user_email'))
                                            <span class="text-[10px] text-slate-400 font-semibold inline-flex items-center gap-1" title="No puedes modificar tu propio usuario">
                                                <i class="fa-solid fa-lock"></i> Tú
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('admin.usuarios.estado', $usuario['id']) }}" class="inline" onsubmit="return confirm('¿{{ $usuario['estado'] === 'Activo' ? 'Revocar acceso a' : 'Reactivar a' }} {{ $usuario['nombre'] }}?');">
                                                @csrf
                                                <button type="submit" class="p-1.5 {{ $usuario['estado'] === 'Activo' ? 'text-slate-400 hover:text-red-600' : 'text-slate-400 hover:text-emerald-600' }} rounded-md transition" title="{{ $usuario['estado'] === 'Activo' ? 'Revocar acceso' : 'Reactivar acceso' }}">
                                                    <i class="fa-solid {{ $usuario['estado'] === 'Activo' ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-400">
                                        @if($firestoreActivo)
                                            No hay usuarios registrados todavía.
                                        @else
                                            No se pudo leer la colección "usuarios": {{ $errorConexion }}
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50/50 text-xs text-slate-500">
                Mostrando {{ count($usuarios) }} de {{ count($usuarios) }} usuarios
            </div>

        </div>

        <!-- Right Sidebar: System Credentials & Alerts -->
        <div class="space-y-6">

            <!-- Firebase Status Box -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-key text-amber-500"></i> Credenciales de Firebase
                </h3>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Proyecto:</span>
                        <span class="font-mono font-bold text-slate-800">sistema-hidroponico-97751</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Conexión:</span>
                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded {{ $firestoreActivo ? 'text-emerald-700 bg-emerald-50' : 'text-red-700 bg-red-50' }}">
                            {{ $firestoreActivo ? 'Activa' : 'Con errores' }}
                        </span>
                    </div>
                </div>

                <a href="{{ route('admin.index') }}" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrows-rotate"></i> Revalidar Conexión Firestore
                </a>
            </div>

            <!-- Alertas Recientes (reales, de la colección 'alertas') -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-blue-600"></i> Alertas Recientes
                </h3>

                <div class="space-y-2.5 text-xs max-h-72 overflow-y-auto">
                    @forelse($alertas as $alerta)
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="flex items-center justify-between font-bold text-slate-800">
                                <span class="{{ $alerta['revisada'] ? 'text-slate-500' : 'text-red-600' }}">
                                    {{ $alerta['revisada'] ? 'Revisada' : 'Pendiente' }}
                                </span>
                                <span class="text-[9px] text-slate-400 font-normal">{{ $alerta['fecha'] ?? '' }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $alerta['mensaje'] }}</p>
                        </div>
                    @empty
                        <p class="text-center text-slate-400 py-6">No hay alertas registradas.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Modal: Registrar Usuario -->
<div id="modal-nuevo-usuario" class="hidden fixed inset-0 bg-black/50 z-40 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-amber-500"></i> Registrar Usuario
            </h3>
            <button type="button" onclick="cerrarModalUsuario()" class="text-gray-400 hover:text-gray-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.usuarios.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold text-gray-700 block mb-1">Nombre completo *</label>
                <input type="text" name="nombre" required placeholder="Ej. Juan Pérez"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-700 block mb-1">Correo electrónico *</label>
                <input type="email" name="correo" required placeholder="usuario@pouani.com"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                <p class="text-[10px] text-gray-400 mt-1">Debe coincidir con la cuenta de Google que usará para iniciar sesión.</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-700 block mb-1">Rol *</label>
                <select name="rol" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                    <option value="Encargado">Encargado</option>
                    <option value="Administrador">Administrador</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="cerrarModalUsuario()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-check"></i> Guardar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalUsuario() {
        document.getElementById('modal-nuevo-usuario').classList.remove('hidden');
    }
    function cerrarModalUsuario() {
        document.getElementById('modal-nuevo-usuario').classList.add('hidden');
    }
</script>
@endsection