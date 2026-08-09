@extends('layouts.app')

@section('title', 'Panel de Control')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header Bienvenida -->
    <div class="flex justify-between items-start">
        <div>
            <span class="text-[10px] font-bold tracking-wider text-gray-400 uppercase">RESUMEN</span>
            <h1 class="text-3xl font-bold text-gray-900 mt-1">Bienvenida de nuevo, {{ session('user_name', 'Usuario') }}.</h1>
            <p class="text-xs text-gray-500 mt-1 max-w-2xl">
                @if($alertas->count() > 0)
                    Tienes <strong>{{ $alertas->count() }}</strong> cultivo(s) que requieren atención:
                    {{ $alertas->pluck('nombre')->join(', ') }}.
                @else
                    Todos tus cultivos presentan condiciones de crecimiento óptimas hoy.
                @endif
                @unless($firestoreActivo)
                    <span class="text-amber-600 font-semibold">(mostrando datos de demostración, Firestore no está conectado todavía)</span>
                @endunless
            </p>
        </div>
        <a href="{{ route('reportes.generar') }}" target="_blank" class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-xl transition shadow-sm flex items-center gap-2">
            <i class="fa-solid fa-file-lines"></i> Generar Informe
        </a>
    </div>

    <!-- Secciones Cultivos Activos -->
    <div>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-sm font-bold text-gray-800">Sectores de Cultivo Activos ({{ $totalCultivos }})</h2>
            <a href="{{ route('cultivos.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800">Ver Todo &rarr;</a>
        </div>

        <!-- Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            @foreach($cultivos as $cultivo)
            <div class="bg-gradient-to-b {{ $cultivo['requiere_atencion'] ? 'from-red-50/50 border-red-100' : 'from-emerald-50/50 border-emerald-100' }} to-white border rounded-2xl p-5 shadow-sm relative">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $cultivo['requiere_atencion'] ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }} text-[10px] font-bold mb-4">
                    <span class="w-1.5 h-1.5 rounded-full {{ $cultivo['requiere_atencion'] ? 'bg-red-600' : 'bg-emerald-600' }}"></span>
                    {{ $cultivo['requiere_atencion'] ? 'Requiere Atención' : 'Salud Óptima' }}
                </span>

                <h3 class="text-base font-bold text-gray-900">{{ $cultivo['nombre'] }}</h3>
                <p class="text-xs text-gray-400 mb-4">{{ $cultivo['sector'] }} • Día {{ $cultivo['dia_actual'] }} de {{ $cultivo['dia_total'] }} ({{ $cultivo['progreso'] }}%)</p>

                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div class="bg-white/80 p-2.5 rounded-xl border border-gray-100">
                        <span class="text-[10px] text-gray-400 block"><i class="fa-solid fa-temperature-half text-xs mr-1"></i> Temp.</span>
                        <span class="text-sm font-bold {{ $cultivo['requiere_atencion'] ? 'text-red-600' : 'text-gray-800' }}">{{ $cultivo['temperatura'] }}°C</span>
                    </div>
                    <div class="bg-white/80 p-2.5 rounded-xl border border-gray-100">
                        <span class="text-[10px] text-gray-400 block"><i class="fa-solid fa-droplet text-xs mr-1"></i> Humedad</span>
                        <span class="text-sm font-bold text-gray-800">{{ $cultivo['humedad'] }}%</span>
                    </div>
                    <div class="bg-white/80 p-2.5 rounded-xl border border-gray-100">
                        <span class="text-[10px] text-gray-400 block"><i class="fa-solid fa-vial text-xs mr-1"></i> pH Objetivo</span>
                        <span class="text-sm font-bold text-gray-800">{{ $cultivo['ph_min'] }} - {{ $cultivo['ph_max'] }}</span>
                    </div>
                    <div class="bg-white/80 p-2.5 rounded-xl border border-gray-100">
                        <span class="text-[10px] text-gray-400 block"><i class="fa-solid fa-water text-xs mr-1"></i> CE Objetivo</span>
                        <span class="text-sm font-bold text-gray-800">{{ $cultivo['ce_min'] }} - {{ $cultivo['ce_max'] }}</span>
                    </div>
                </div>

                @if($cultivo['requiere_atencion'])
                    <a href="{{ route('cultivos.index') }}" class="block w-full py-2 bg-white border border-red-200 hover:bg-red-50 text-red-700 text-xs font-bold text-center rounded-xl transition">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Revisar Alerta
                    </a>
                @else
                    <a href="{{ route('cultivos.index') }}" class="block w-full py-2 bg-emerald-100/60 hover:bg-emerald-100 text-emerald-900 text-xs font-bold text-center rounded-xl transition">
                        Ver Métricas Detalladas
                    </a>
                @endif
            </div>
            @endforeach

            <!-- Card: Agregar Nuevo Cultivo -->
            <button type="button" onclick="abrirModalCultivo()" class="border-2 border-dashed border-gray-200 rounded-2xl p-6 flex flex-col items-center justify-center text-center hover:border-emerald-700 transition cursor-pointer group">
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-800 flex items-center justify-center mb-3 group-hover:bg-emerald-800 group-hover:text-white transition">
                    <i class="fa-solid fa-plus text-lg"></i>
                </div>
                <h3 class="text-sm font-bold text-gray-800 mb-1">Agregar Nuevo Cultivo</h3>
                <p class="text-xs text-gray-400 max-w-xs">
                    Configura un nuevo sector y conecta los sensores para comenzar el seguimiento.
                </p>
            </button>

        </div>
    </div>

</div>
@endsection