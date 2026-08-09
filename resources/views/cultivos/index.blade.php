@extends('layouts.app')

@section('title', 'Cultivos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header Section & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Gestión de Cultivos y Lotes</h1>
            <p class="text-xs text-slate-500 mt-1">Supervisión del ciclo de vida, parámetros nutricionales objetivo y cosecha programada.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="abrirModalCultivo()" class="px-4 py-2.5 bg-[#063221] hover:bg-emerald-900 text-white font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-plus text-emerald-400"></i> Registrar Nuevo Lote
            </button>
        </div>
    </div>

    <!-- Metrics Overview Cards -->
    @php
        $proximaCosecha = collect($cultivos)->filter(fn($c) => !empty($c['cosecha_estimada']))->sortBy('cosecha_estimada')->first();
        $sistemasActivos = collect($cultivos)->pluck('sistema')->unique();
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Lotes Activos</span>
                <span class="text-2xl font-extrabold text-slate-900 mt-1 block">{{ count($cultivos) }} Lotes</span>
                <span class="text-[11px] text-emerald-600 font-medium mt-1 inline-block">{{ number_format($totalPlantas) }} Plantas totales</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-wheat-awn"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Sistemas Activos</span>
                <span class="text-2xl font-extrabold text-slate-900 mt-1 block">{{ $sistemasActivos->count() }} Módulos</span>
                <span class="text-[11px] text-slate-500 font-medium mt-1 inline-block truncate max-w-[180px] block">{{ $sistemasActivos->take(2)->implode(', ') }}</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-network-wired"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Próxima Cosecha</span>
                <span class="text-2xl font-extrabold text-slate-900 mt-1 block">
                    {{ $proximaCosecha ? \Carbon\Carbon::parse($proximaCosecha['cosecha_estimada'])->diffForHumans(null, true) : '—' }}
                </span>
                <span class="text-[11px] text-amber-600 font-medium mt-1 inline-block">{{ $proximaCosecha['lote'] ?? '' }} - {{ $proximaCosecha['nombre'] ?? 'Sin datos' }}</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Conexión de Datos</span>
                <span class="text-2xl font-extrabold {{ $firestoreActivo ? 'text-emerald-600' : 'text-amber-600' }} mt-1 block">{{ $firestoreActivo ? 'Firestore' : 'Demo' }}</span>
                <span class="text-[11px] text-slate-500 font-medium mt-1 inline-block">
                    @if($firestoreActivo)
                        Datos en tiempo real
                    @else
                        {{ $errorConexion ?: 'Sin datos en la colección' }}
                    @endif
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-database"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="relative w-full md:w-80">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input id="buscador-cultivos" type="text" placeholder="Buscar por cultivo, lote o sistema..." onkeyup="filtrarCultivos()" class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-none focus:border-emerald-500 transition">
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0" id="filtros-estado">
            <button onclick="filtrarPorEstado('Todos', this)" class="filtro-btn px-3 py-1.5 bg-[#063221] text-white text-xs font-semibold rounded-lg shadow-sm">Todos</button>
            <button onclick="filtrarPorEstado('Crecimiento', this)" class="filtro-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-lg transition">Crecimiento</button>
            <button onclick="filtrarPorEstado('Cosechable', this)" class="filtro-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-lg transition">Cosechable</button>
            <button onclick="filtrarPorEstado('Requiere Atención', this)" class="filtro-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-lg transition">Requiere Atención</button>
        </div>
    </div>

    <!-- Active Crop Batches Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="grid-cultivos">
        @forelse($cultivos as $cultivo)
            @php
                $colorMap = [
                    'Crecimiento'        => ['bg' => 'emerald', 'icon' => 'fa-seedling'],
                    'Cosechable'         => ['bg' => 'amber',   'icon' => 'fa-spa'],
                    'Requiere Atención'  => ['bg' => 'red',     'icon' => 'fa-triangle-exclamation'],
                    'Germinación'        => ['bg' => 'cyan',    'icon' => 'fa-water'],
                    'Floración'          => ['bg' => 'red',     'icon' => 'fa-apple-whole'],
                ];
                $etiquetaEstado = $cultivo['requiere_atencion'] ? 'Requiere Atención' : $cultivo['estado'];
                $color = $cultivo['requiere_atencion']
                    ? ['bg' => 'red', 'icon' => 'fa-triangle-exclamation']
                    : ($colorMap[$cultivo['estado']] ?? ['bg' => 'slate', 'icon' => 'fa-leaf']);
            @endphp
            <div class="cultivo-card bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between"
                 data-nombre="{{ strtolower($cultivo['nombre'].' '.$cultivo['lote'].' '.$cultivo['sistema']) }}"
                 data-estado="{{ $cultivo['estado'] }}">
                <div>
                    <!-- Card Top -->
                    <div class="p-5 border-b border-slate-100 flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-{{ $color['bg'] }}-100 text-{{ $color['bg'] }}-800 flex items-center justify-center text-xl font-bold">
                                <i class="fa-solid {{ $color['icon'] }}"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-{{ $color['bg'] }}-700 bg-{{ $color['bg'] }}-50 px-2 py-0.5 rounded-md border border-{{ $color['bg'] }}-200/60">{{ $cultivo['sistema'] }}</span>
                                <h3 class="text-lg font-bold text-slate-900 mt-1">{{ $cultivo['nombre'] }} ({{ $cultivo['lote'] }})</h3>
                                <p class="text-xs text-slate-500">{{ number_format($cultivo['plantas']) }} Plantas
                                    @if($cultivo['sembrado']) • Sembrado el {{ \Carbon\Carbon::parse($cultivo['sembrado'])->translatedFormat('d M Y') }} @endif
                                </p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-{{ $color['bg'] }}-100 text-{{ $color['bg'] }}-800 whitespace-nowrap">{{ $etiquetaEstado }}</span>
                    </div>

                    @if($cultivo['requiere_atencion'])
                    <div class="mx-5 mt-4 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between gap-3">
                        <p class="text-[11px] text-red-700 font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation"></i> {{ $cultivo['alerta_mensaje'] }}
                        </p>
                        <form method="POST" action="{{ route('cultivos.alertas.resolver', $cultivo['alerta_id']) }}" onsubmit="return confirm('¿Marcar esta alerta como revisada?');">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 bg-white border border-red-300 text-red-700 text-[10px] font-bold rounded-lg hover:bg-red-100 transition whitespace-nowrap">
                                Marcar revisada
                            </button>
                        </form>
                    </div>
                    @endif

                    <!-- Progress Section -->
                    <div class="p-5 space-y-4">
                        @if($cultivo['sensor_activo'])
                        <div class="grid grid-cols-4 gap-2">
                            <div class="p-2 bg-blue-50 rounded-lg text-center">
                                <span class="text-[9px] text-slate-400 block">Temp.</span>
                                <span class="text-xs font-bold text-slate-800">{{ $cultivo['temperatura'] }}°C</span>
                            </div>
                            <div class="p-2 bg-cyan-50 rounded-lg text-center">
                                <span class="text-[9px] text-slate-400 block">Humedad</span>
                                <span class="text-xs font-bold text-slate-800">{{ $cultivo['humedad'] }}%</span>
                            </div>
                            <div class="p-2 bg-emerald-50 rounded-lg text-center">
                                <span class="text-[9px] text-slate-400 block">Nutrientes</span>
                                <span class="text-xs font-bold text-slate-800">{{ $cultivo['nutrientes'] ?? '—' }}</span>
                            </div>
                            <div class="p-2 bg-indigo-50 rounded-lg text-center">
                                <span class="text-[9px] text-slate-400 block">Oxígeno</span>
                                <span class="text-xs font-bold text-slate-800">{{ $cultivo['oxigeno'] ?? '—' }}</span>
                            </div>
                        </div>
                        @else
                        <div class="p-2 bg-slate-50 rounded-lg text-center text-[10px] text-slate-400">
                            <i class="fa-solid fa-plug-circle-xmark mr-1"></i> Sin sensor conectado a este cultivo
                        </div>
                        @endif
                        <div>
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-700 mb-1.5">
                                <span>Progreso del Ciclo (Día {{ $cultivo['dia_actual'] }} de {{ $cultivo['dia_total'] }})</span>
                                <span class="text-{{ $color['bg'] }}-600">{{ $cultivo['progreso'] }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-{{ $color['bg'] }}-500 h-full rounded-full" style="width: {{ $cultivo['progreso'] }}%"></div>
                            </div>
                        </div>

                        <!-- Ideal Nutrient Targets -->
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">pH Objetivo</span>
                                <span class="text-sm font-extrabold text-slate-800">{{ $cultivo['ph_min'] }} - {{ $cultivo['ph_max'] }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">CE Objetivo</span>
                                <span class="text-sm font-extrabold text-slate-800">{{ $cultivo['ce_min'] }} - {{ $cultivo['ce_max'] }} mS/cm</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="p-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="{{ $cultivo['requiere_atencion'] ? 'text-red-700 font-bold' : 'text-slate-500' }}">
                        <i class="fa-solid {{ $cultivo['requiere_atencion'] ? 'fa-triangle-exclamation' : 'fa-clock' }} {{ $cultivo['requiere_atencion'] ? '' : 'text-slate-400' }} mr-1"></i>
                        Cosecha: {{ $cultivo['cosecha_estimada'] ? \Carbon\Carbon::parse($cultivo['cosecha_estimada'])->translatedFormat('d M Y') : 'Sin definir' }}
                    </span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('ia.index', ['cultivo' => $cultivo['id']]) }}" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold rounded-lg transition shadow-sm inline-block text-center">
                            Detalles Lote
                        </a>
                        <form method="POST" action="{{ route('cultivos.destroy', $cultivo['id']) }}" onsubmit="return confirm('¿Eliminar {{ $cultivo['nombre'] }} de Firestore? Esta acción no se puede deshacer.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 bg-white border border-red-200 text-red-600 hover:bg-red-50 font-bold rounded-lg transition shadow-sm">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-2 text-center py-16 text-slate-400 text-xs">
                Aún no hay cultivos registrados. Usa "Registrar Nuevo Lote" para agregar el primero.
            </div>
        @endforelse
    </div>

</div>

<script>
    function filtrarCultivos() {
        const texto = document.getElementById('buscador-cultivos').value.toLowerCase();
        document.querySelectorAll('.cultivo-card').forEach(card => {
            card.style.display = card.dataset.nombre.includes(texto) ? '' : 'none';
        });
    }

    function filtrarPorEstado(estado, btn) {
        document.querySelectorAll('.filtro-btn').forEach(b => {
            b.classList.remove('bg-[#063221]', 'text-white', 'shadow-sm');
            b.classList.add('bg-slate-100', 'text-slate-600');
        });
        btn.classList.add('bg-[#063221]', 'text-white', 'shadow-sm');
        btn.classList.remove('bg-slate-100', 'text-slate-600');

        document.querySelectorAll('.cultivo-card').forEach(card => {
            card.style.display = (estado === 'Todos' || card.dataset.estado === estado) ? '' : 'none';
        });
    }
</script>
@endsection