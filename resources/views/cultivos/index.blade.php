@extends('layouts.app')

@section('title', 'Cultivos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-3.5 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Header Section & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Gestión de Cultivos y Lotes</h1>
            <p class="text-xs text-slate-500 mt-1">Supervisión del ciclo de vida, lecturas de sensores en tiempo real y cosecha programada.</p>
        </div>
        <div class="flex items-center gap-3">
            <span id="js-estado-sync" class="text-[11px] font-medium text-slate-400 hidden sm:inline">Sincronización automática cada 30s</span>
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
                <span class="text-[11px] text-slate-500 font-medium mt-1 truncate max-w-[180px] block">{{ $sistemasActivos->take(2)->implode(', ') }}</span>
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

                $variablesSensor = [
                    ['campo' => 'temperatura',      'label' => 'Temp.',    'valor' => $cultivo['temperatura'],      'unidad' => '°C',    'bg' => 'bg-blue-50'],
                    ['campo' => 'humedad',          'label' => 'Humedad',  'valor' => $cultivo['humedad'],          'unidad' => '%',     'bg' => 'bg-cyan-50'],
                    ['campo' => 'ph',               'label' => 'pH',       'valor' => $cultivo['ph'],               'unidad' => '',      'bg' => 'bg-emerald-50'],
                    ['campo' => 'conductividad_ec', 'label' => 'CE',       'valor' => $cultivo['conductividad_ec'], 'unidad' => 'µS/cm', 'bg' => 'bg-teal-50'],
                    ['campo' => 'nitrogeno',        'label' => 'Nitrógeno','valor' => $cultivo['nitrogeno'],        'unidad' => 'mg/kg', 'bg' => 'bg-lime-50'],
                    ['campo' => 'fosforo',          'label' => 'Fósforo',  'valor' => $cultivo['fosforo'],          'unidad' => 'mg/kg', 'bg' => 'bg-amber-50'],
                    ['campo' => 'potasio',          'label' => 'Potasio',  'valor' => $cultivo['potasio'],          'unidad' => 'mg/kg', 'bg' => 'bg-indigo-50'],
                ];

                $historica = $cultivo['ultima_lectura_historica'] ?? null;
                $variablesHistoricas = $historica ? [
                    ['label' => 'Temp.',    'valor' => $historica['temperatura'],      'unidad' => '°C'],
                    ['label' => 'Humedad',  'valor' => $historica['humedad'],          'unidad' => '%'],
                    ['label' => 'pH',       'valor' => $historica['ph'],               'unidad' => ''],
                    ['label' => 'CE',       'valor' => $historica['conductividad_ec'], 'unidad' => 'µS/cm'],
                    ['label' => 'Nitrógeno','valor' => $historica['nitrogeno'],        'unidad' => 'mg/kg'],
                    ['label' => 'Fósforo',  'valor' => $historica['fosforo'],          'unidad' => 'mg/kg'],
                    ['label' => 'Potasio',  'valor' => $historica['potasio'],          'unidad' => 'mg/kg'],
                ] : [];
            @endphp
            <div class="cultivo-card bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between"
                 data-cultivo-id="{{ $cultivo['id'] }}"
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

                    <!-- Progress & Sensor Section -->
                    <div class="p-5 space-y-4">
                        @if($cultivo['sensor_activo'])
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-500 flex items-center gap-1">
                                    <i class="fa-solid fa-satellite-dish text-emerald-600"></i>
                                    {{ $cultivo['nodo_vinculado_nombre'] ?? 'Sensor' }} • Última lectura:
                                    @if(!empty($cultivo['ultima_lectura']))
                                        <strong class="text-slate-700 js-ultima-lectura">{{ \Carbon\Carbon::parse($cultivo['ultima_lectura'])->diffForHumans() }}</strong>
                                    @else
                                        <strong class="text-slate-500 js-ultima-lectura">Sin registro</strong>
                                    @endif
                                </span>
                                <span class="js-lectura-antigua {{ !empty($cultivo['lectura_antigua']) ? '' : 'hidden' }} px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">
                                    <i class="fa-solid fa-circle-exclamation mr-0.5"></i> Sin señal
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @foreach($variablesSensor as $var)
                                    <div class="p-2 {{ $var['bg'] }} rounded-lg text-center">
                                        <span class="text-[9px] text-slate-500 font-medium block">{{ $var['label'] }}</span>
                                        <span class="text-xs font-bold text-slate-800 js-valor-{{ $var['campo'] }}" data-unidad="{{ $var['unidad'] }}">
                                            @if($var['valor'] !== null)
                                                {{ $var['valor'] }}@if($var['unidad']) <span class="text-[10px] font-semibold text-slate-600">{{ $var['unidad'] }}</span>@endif
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(!empty($cultivo['esperando_lectura']))
                            <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-200/70 text-center space-y-1">
                                <p class="text-xs font-bold text-emerald-900">
                                    <i class="fa-solid fa-rotate mr-1"></i> Esperando primera lectura ({{ $cultivo['nodo_vinculado_nombre'] ?? 'Sensor' }})
                                </p>
                                <p class="text-[10px] text-emerald-700">
                                    Se muestran solo lecturas posteriores a la vinculación actual para evitar datos mezclados.
                                </p>
                            </div>
                        @else
                            <div class="p-2.5 bg-slate-50 rounded-lg text-center text-[11px] text-slate-400 border border-slate-100">
                                <i class="fa-solid fa-plug-circle-xmark mr-1"></i> Sin sensor conectado a este cultivo
                            </div>
                        @endif

                        @if(!$cultivo['sensor_activo'] && $historica)
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2">
                                <div class="flex items-center justify-between text-[10px] text-slate-500">
                                    <span class="font-bold uppercase tracking-wider text-slate-600">
                                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> Última medición (histórica)
                                    </span>
                                    @if(!empty($historica['fecha_lectura']))
                                        <span>{{ \Carbon\Carbon::parse($historica['fecha_lectura'])->diffForHumans() }}</span>
                                    @endif
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                    @foreach($variablesHistoricas as $vh)
                                        <div class="px-2 py-1 bg-white rounded border border-slate-100 text-center">
                                            <span class="text-[8px] text-slate-400 block">{{ $vh['label'] }}</span>
                                            <span class="text-[11px] font-semibold text-slate-600">
                                                @if($vh['valor'] !== null)
                                                    {{ $vh['valor'] }}@if($vh['unidad']) {{ $vh['unidad'] }}@endif
                                                @else
                                                    —
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Sensor Assignment Controls -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                            <div class="text-[11px] text-slate-500">
                                @if(!empty($cultivo['tiene_nodo']))
                                    Sensor activo: <strong class="text-slate-800">{{ $cultivo['nodo_vinculado_nombre'] }}</strong>
                                @else
                                    Sin nodo asignado
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                        onclick="abrirModalVincularSensor('{{ $cultivo['id'] }}', @js($cultivo['nombre']), '{{ $cultivo['nodo_vinculado_id'] ?? '' }}')"
                                        class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200/80 rounded-lg text-[11px] font-bold transition">
                                    <i class="fa-solid fa-link mr-1"></i>
                                    {{ !empty($cultivo['tiene_nodo']) ? 'Cambiar sensor' : 'Vincular sensor' }}
                                </button>

                                @if(!empty($cultivo['tiene_nodo']))
                                    <form method="POST"
                                          action="{{ route('cultivos.desvincular.sensor', $cultivo['id']) }}"
                                          onsubmit="return confirm('¿Desvincular {{ $cultivo['nodo_vinculado_nombre'] }} de {{ $cultivo['nombre'] }}? El sensor quedará libre.');">
                                        @csrf
                                        <input type="hidden" name="nodo_id" value="{{ $cultivo['nodo_vinculado_id'] }}">
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-[11px] font-semibold transition">
                                            Desvincular
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-700 mb-1.5">
                                <span>Progreso del Ciclo (Día {{ $cultivo['dia_actual'] }} de {{ $cultivo['dia_total'] }})</span>
                                <span class="text-{{ $color['bg'] }}-600">{{ $cultivo['progreso'] }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-{{ $color['bg'] }}-500 h-full rounded-full" style="width: {{ $cultivo['progreso'] }}%"></div>
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

<!-- Modal Vincular / Cambiar Sensor -->
<div id="modal-vincular-sensor" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Vincular Sensor al Cultivo</h3>
                <p id="modal-vincular-subtitulo" class="text-xs text-slate-500 mt-0.5">Selecciona el nodo que medirá este lote.</p>
            </div>
            <button type="button" onclick="cerrarModalVincularSensor()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="form-vincular-sensor" method="POST" action="" class="p-5 space-y-4">
            @csrf
            <div>
                <label for="select-nodo-sensor" class="block text-xs font-bold text-slate-700 mb-1.5">Nodo de sensor</label>
                <select id="select-nodo-sensor" name="nodo_id" required onchange="actualizarAvisoMovimientoSensor()"
                        class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none focus:border-emerald-600">
                    @forelse($nodosDisponibles as $nodo)
                        <option value="{{ $nodo['id'] }}"
                                data-cultivo-id="{{ $nodo['cultivo_id'] ?? '' }}"
                                data-cultivo-nombre="{{ $nodo['cultivo_nombre'] ?? '' }}"
                                data-nodo-nombre="{{ $nodo['nombre'] }}">
                            {{ $nodo['nombre'] }} —
                            @if(!empty($nodo['cultivo_nombre']))
                                mide ahora: {{ $nodo['cultivo_nombre'] }}
                            @else
                                libre
                            @endif
                        </option>
                    @empty
                        <option value="" disabled selected>No hay nodos registrados en nodos_sensores</option>
                    @endforelse
                </select>
            </div>

            <div id="aviso-movimiento-sensor" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-800">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                <span id="texto-aviso-movimiento-sensor"></span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="cerrarModalVincularSensor()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-[#063221] hover:bg-emerald-900 text-white text-xs font-bold rounded-xl transition">
                    Confirmar vinculación
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let cultivoDestinoActualId = '';
    let cultivoDestinoActualNombre = '';

    function abrirModalVincularSensor(cultivoId, cultivoNombre, nodoActualId) {
        cultivoDestinoActualId = cultivoId;
        cultivoDestinoActualNombre = cultivoNombre;

        const modal = document.getElementById('modal-vincular-sensor');
        const form = document.getElementById('form-vincular-sensor');
        const subtitulo = document.getElementById('modal-vincular-subtitulo');
        const select = document.getElementById('select-nodo-sensor');

        form.action = "{{ url('/cultivos') }}/" + encodeURIComponent(cultivoId) + "/vincular-sensor";
        subtitulo.textContent = 'Cultivo destino: ' + cultivoNombre;

        if (nodoActualId) {
            select.value = nodoActualId;
        }

        actualizarAvisoMovimientoSensor();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalVincularSensor() {
        const modal = document.getElementById('modal-vincular-sensor');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function actualizarAvisoMovimientoSensor() {
        const select = document.getElementById('select-nodo-sensor');
        const aviso = document.getElementById('aviso-movimiento-sensor');
        const texto = document.getElementById('texto-aviso-movimiento-sensor');

        if (!select || !select.selectedOptions.length) {
            aviso.classList.add('hidden');
            return;
        }

        const opt = select.selectedOptions[0];
        const origenId = opt.dataset.cultivoId || '';
        const origenNombre = opt.dataset.cultivoNombre || '';
        const nodoNombre = opt.dataset.nodoNombre || 'El sensor';

        if (origenId && origenId !== cultivoDestinoActualId) {
            texto.textContent = nodoNombre + ' dejará de medir ' + (origenNombre || 'su cultivo actual') + ' y medirá ' + cultivoDestinoActualNombre + '. Su última lectura quedará guardada como histórica en el cultivo anterior.';
            aviso.classList.remove('hidden');
        } else {
            aviso.classList.add('hidden');
        }
    }

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

    (function () {
        const INTERVALO_MS = 30000; // 30 segundos
        let consultando = false;

        function formatearRelativo(isoStr) {
            if (!isoStr) return 'Sin registro';
            const fecha = new Date(isoStr);
            if (isNaN(fecha.getTime())) return 'Sin registro';

            const seg = Math.max(0, Math.floor((Date.now() - fecha.getTime()) / 1000));
            if (seg < 10) return 'hace unos segundos';
            if (seg < 60) return `hace ${seg} seg`;
            const min = Math.floor(seg / 60);
            if (min < 60) return `hace ${min} min`;
            const hrs = Math.floor(min / 60);
            if (hrs < 24) return `hace ${hrs} h`;
            const dias = Math.floor(hrs / 24);
            return `hace ${dias} d`;
        }

        async function refrescarLecturasEnVivo() {
            if (document.hidden || consultando) return;

            const modalAbierto = !document.getElementById('modal-vincular-sensor')?.classList.contains('hidden')
                || !document.getElementById('modal-nuevo-cultivo')?.classList.contains('hidden');
            if (modalAbierto) return;

            consultando = true;
            try {
                const resp = await fetch("{{ route('cultivos.lecturas-en-vivo') }}", {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                if (!resp.ok) return;

                const data = await resp.json();
                const lista = Array.isArray(data.cultivos) ? data.cultivos : [];

                lista.forEach((c) => {
                    const card = document.querySelector(`.cultivo-card[data-cultivo-id="${CSS.escape(String(c.id))}"]`);
                    if (!card) return;

                    const textoUltima = card.querySelector('.js-ultima-lectura');
                    if (textoUltima && c.ultima_lectura) {
                        textoUltima.textContent = formatearRelativo(c.ultima_lectura);
                    }

                    const badgeAntigua = card.querySelector('.js-lectura-antigua');
                    if (badgeAntigua) {
                        badgeAntigua.classList.toggle('hidden', !c.lectura_antigua);
                    }

                    const mapaValores = {
                        temperatura: c.temperatura,
                        humedad: c.humedad,
                        ph: c.ph,
                        conductividad_ec: c.conductividad_ec,
                        nitrogeno: c.nitrogeno,
                        fosforo: c.fosforo,
                        potasio: c.potasio,
                    };

                    Object.entries(mapaValores).forEach(([campo, val]) => {
                        const el = card.querySelector(`.js-valor-${campo}`);
                        if (!el) return;
                        const unidad = el.dataset.unidad ? ` ${el.dataset.unidad}` : '';
                        el.textContent = (val !== null && val !== undefined && val !== '')
                            ? `${val}${unidad}`
                            : '—';
                    });
                });

                const badgeSync = document.getElementById('js-estado-sync');
                if (badgeSync) {
                    const hora = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    badgeSync.textContent = `Actualizado ${hora}`;
                }
            } catch (err) {
                console.warn('Refresco en vivo omitido:', err);
            } finally {
                consultando = false;
            }
        }

        setInterval(refrescarLecturasEnVivo, INTERVALO_MS);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                refrescarLecturasEnVivo();
            }
        });
    })();
</script>
@endsection