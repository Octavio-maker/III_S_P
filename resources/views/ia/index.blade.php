@extends('layouts.app')

@section('title', 'Inteligencia IA')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Banner + Selector de cultivo -->
    <div class="bg-gradient-to-r from-[#063221] via-emerald-950 to-slate-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 text-emerald-300 pointer-events-none">
            <i class="fa-solid fa-brain text-[12rem]"></i>
        </div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 inline-block mb-2">
                    <i class="fa-solid fa-sparkles mr-1"></i> Diagnóstico Agrotech
                </span>
                <h1 class="text-2xl font-bold tracking-tight">Centro de Diagnóstico de Cultivos</h1>
                <p class="text-xs text-emerald-100/80 mt-1 max-w-2xl">
                    Analiza en tiempo real las 7 variables del sensor contra el perfil de referencia del tipo de cultivo.
                </p>
            </div>

            <form method="GET" action="{{ route('ia.index') }}" class="w-full md:w-72">
                <label class="text-[10px] uppercase tracking-wider text-emerald-200 font-bold block mb-1">Cultivo a analizar</label>
                <select name="cultivo" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-900 bg-white border-none focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    @forelse($cultivos as $c)
                        <option value="{{ $c['id'] }}" {{ $seleccionado && $seleccionado['id'] === $c['id'] ? 'selected' : '' }}>
                            {{ $c['nombre'] }} ({{ $c['lote'] }})
                        </option>
                    @empty
                        <option value="">Sin cultivos registrados</option>
                    @endforelse
                </select>
            </form>
        </div>
    </div>

    @if($seleccionado)
    @php
        $badgeEstado = [
            'Saludable'         => 'bg-emerald-100 text-emerald-800',
            'Estable'           => 'bg-blue-100 text-blue-800',
            'Requiere Atención' => 'bg-amber-100 text-amber-800',
            'Crítico'           => 'bg-red-100 text-red-800',
        ];
        $nivelColor = [
            'ok'      => ['texto' => 'text-emerald-800', 'fondo' => 'bg-emerald-50', 'borde' => 'border-emerald-200'],
            'info'    => ['texto' => 'text-blue-800',    'fondo' => 'bg-blue-50',    'borde' => 'border-blue-200'],
            'warning' => ['texto' => 'text-amber-800',   'fondo' => 'bg-amber-50',   'borde' => 'border-amber-200'],
            'danger'  => ['texto' => 'text-red-800',     'fondo' => 'bg-red-50',     'borde' => 'border-red-200'],
        ];

        $rangosResumen = [
            'temperatura'      => ['label' => 'Temperatura', 'rango' => $perfilUmbrales['temperatura'] ?? null],
            'humedad'          => ['label' => 'Humedad',     'rango' => $perfilUmbrales['humedad'] ?? null],
            'ph'               => ['label' => 'pH',          'rango' => $perfilUmbrales['ph'] ?? null],
            'conductividad_ec' => ['label' => 'CE',          'rango' => $perfilUmbrales['conductividad_ec'] ?? null],
            'nitrogeno'        => ['label' => 'Nitrógeno',   'rango' => $perfilUmbrales['nitrogeno'] ?? null],
            'fosforo'          => ['label' => 'Fósforo',     'rango' => $perfilUmbrales['fosforo'] ?? null],
            'potasio'          => ['label' => 'Potasio',     'rango' => $perfilUmbrales['potasio'] ?? null],
        ];

        $datosParaChat = [
            'nombre'           => $seleccionado['nombre'],
            'perfil_label'     => $perfilUmbrales['label'] ?? 'General Hidropónico',
            'estado'           => $estadoGeneral,
            'sensor_activo'    => $seleccionado['sensor_activo'],
            'temperatura'      => $seleccionado['temperatura'],
            'humedad'          => $seleccionado['humedad'],
            'ph'               => $seleccionado['ph'],
            'conductividad_ec' => $seleccionado['conductividad_ec'],
            'nitrogeno'        => $seleccionado['nitrogeno'],
            'fosforo'          => $seleccionado['fosforo'],
            'potasio'          => $seleccionado['potasio'],
            'umbrales'         => $rangosResumen,
            'progreso'         => $seleccionado['progreso'],
            'dia_actual'       => $seleccionado['dia_actual'],
            'dia_total'        => $seleccionado['dia_total'],
            'cosecha_estimada' => $seleccionado['cosecha_estimada'] ? \Carbon\Carbon::parse($seleccionado['cosecha_estimada'])->translatedFormat('d M Y') : null,
            'ultima_lectura'   => $seleccionado['ultima_lectura'] ? \Carbon\Carbon::parse($seleccionado['ultima_lectura'])->diffForHumans() : null,
            'tips'             => array_map(fn($t) => ['nivel' => $t['nivel'], 'titulo' => $t['titulo'], 'mensaje' => $t['mensaje']], $diagnostico),
        ];
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Diagnóstico + Chat (2 columnas) -->
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">{{ $seleccionado['nombre'] }} <span class="text-slate-400 font-normal">({{ $seleccionado['lote'] }})</span></h2>
                        <p class="text-[11px] text-slate-500">
                            {{ $seleccionado['sector'] }} • {{ $seleccionado['sistema'] }} • Perfil: {{ $perfilUmbrales['label'] ?? 'General' }}
                            @if($datosParaChat['ultima_lectura']) • Última lectura: {{ $datosParaChat['ultima_lectura'] }} @endif
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-[10px] font-bold {{ $badgeEstado[$estadoGeneral] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $estadoGeneral }}
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($diagnostico as $tip)
                        @php $c = $nivelColor[$tip['nivel']] ?? $nivelColor['info']; @endphp
                        <div class="p-3.5 {{ $c['fondo'] }} border {{ $c['borde'] }} rounded-xl flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center {{ $c['texto'] }} flex-shrink-0">
                                <i class="fa-solid {{ $tip['icono'] }}"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold {{ $c['texto'] }}">{{ $tip['titulo'] }}</p>
                                <p class="text-[11px] text-slate-600 mt-0.5">{{ $tip['mensaje'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-8">No hay observaciones para este cultivo por ahora.</p>
                    @endforelse
                </div>
            </div>

            <!-- Asistente (chat con datos reales, no inventa nada) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm flex flex-col h-[420px]">
                <div class="p-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50 rounded-t-2xl">
                    <div class="w-9 h-9 rounded-xl bg-[#063221] text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900">Asistente de {{ $seleccionado['nombre'] }}</h3>
                        <p class="text-[10px] text-slate-500">Responde con las 7 lecturas reales y el perfil {{ $perfilUmbrales['label'] ?? 'General' }}.</p>
                    </div>
                </div>

                <div id="chat-messages" class="flex-1 p-4 overflow-y-auto space-y-3 text-xs"></div>

                <form id="chat-form" class="p-3 border-t border-slate-100 flex items-center gap-2">
                    <input id="chat-input" type="text" autocomplete="off" placeholder="Ej. ¿Cómo va mi planta? ¿Cuál es el pH o la CE?"
                           class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-none focus:border-emerald-500 transition">
                    <button type="submit" class="w-9 h-9 rounded-xl bg-[#063221] hover:bg-emerald-900 text-white flex items-center justify-center transition">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Progreso + rangos de referencia -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-emerald-600"></i> Progreso del Ciclo
                </h3>
                <div class="flex items-end justify-between mb-1.5">
                    <span class="text-3xl font-extrabold text-slate-900">{{ $seleccionado['progreso'] }}%</span>
                    <span class="text-[11px] text-slate-500">Día {{ $seleccionado['dia_actual'] }} de {{ $seleccionado['dia_total'] }}</span>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden mb-3">
                    <div class="bg-emerald-500 h-full rounded-full" style="width: {{ $seleccionado['progreso'] }}%"></div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-500 font-medium">Cosecha estimada</span>
                    <span class="text-xs font-bold text-slate-800">{{ $datosParaChat['cosecha_estimada'] ?? 'Sin definir' }}</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-indigo-600"></i> Rangos de Referencia
                </h3>
                <p class="text-[10px] text-slate-400 mb-3">Perfil: {{ $perfilUmbrales['label'] ?? 'General Hidropónico' }}</p>
                <div class="space-y-2 text-xs">
                    @foreach($rangosResumen as $item)
                        @php $r = $item['rango']; @endphp
                        <div class="px-3 py-2 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
                            <span class="text-slate-600 font-semibold">{{ $item['label'] }}</span>
                            @if($r)
                                @php $u = !empty($r['unidad']) ? ' ' . $r['unidad'] : ''; @endphp
                                <span class="font-bold text-slate-800">{{ $r['min'] }} - {{ $r['max'] }}{{ $u }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        const datosCultivo = @json($datosParaChat);

        document.addEventListener('DOMContentLoaded', function () {
            const chatMessages = document.getElementById('chat-messages');
            const chatForm = document.getElementById('chat-form');
            const chatInput = document.getElementById('chat-input');

            agregarMensajeBot(mensajeResumenInicial());

            chatForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const texto = chatInput.value.trim();
                if (!texto) return;
                agregarMensajeUsuario(texto);
                chatInput.value = '';
                setTimeout(() => agregarMensajeBot(responder(texto)), 300);
            });

            function fmtRango(key) {
                const item = datosCultivo.umbrales?.[key];
                if (!item || !item.rango) return '';
                const u = item.rango.unidad ? ` ${item.rango.unidad}` : '';
                return `${item.rango.min}–${item.rango.max}${u}`;
            }

            function mensajeResumenInicial() {
                if (!datosCultivo.sensor_activo) {
                    return `Hola, soy el asistente de <strong>${datosCultivo.nombre}</strong> (perfil <strong>${datosCultivo.perfil_label}</strong>). Este cultivo no tiene una lectura activa de sensor todavía, así que puedo contarte su progreso de ciclo o rangos de referencia.`;
                }
                let resumen = `Hola, soy el asistente de <strong>${datosCultivo.nombre}</strong> (perfil <strong>${datosCultivo.perfil_label}</strong>). Estado general: <strong>${datosCultivo.estado}</strong>. `;
                if (datosCultivo.tips.some(t => t.nivel === 'danger' || t.nivel === 'warning')) {
                    resumen += 'Hay variables fuera de rango o avisos; pregúntame "¿cómo va mi planta?" para el detalle.';
                } else {
                    resumen += 'Las variables evaluadas están dentro de los rangos de referencia.';
                }
                return resumen;
            }

            function responder(pregunta) {
                const q = pregunta.toLowerCase();

                if (/(c[oó]mo va|c[oó]mo est[aá]|bien|salud)/.test(q)) {
                    if (datosCultivo.tips.length === 0) {
                        return `Tu <strong>${datosCultivo.nombre}</strong> está <strong>${datosCultivo.estado}</strong> y no tengo observaciones que reportar.`;
                    }
                    const lista = datosCultivo.tips.map(t => `• <strong>${t.titulo}:</strong> ${t.mensaje}`).join('<br>');
                    return `Estado general: <strong>${datosCultivo.estado}</strong>.<br>${lista}`;
                }

                if (/temperatura/.test(q)) {
                    const r = fmtRango('temperatura');
                    return datosCultivo.temperatura !== null
                        ? `La temperatura actual es de <strong>${datosCultivo.temperatura}°C</strong>${r ? ` (referencia ${datosCultivo.perfil_label}: ${r})` : ''}.`
                        : `No hay lectura de temperatura activa para este cultivo.`;
                }

                if (/humedad/.test(q)) {
                    const r = fmtRango('humedad');
                    return datosCultivo.humedad !== null
                        ? `La humedad del sustrato actual es de <strong>${datosCultivo.humedad}%</strong>${r ? ` (referencia ${datosCultivo.perfil_label}: ${r})` : ''}.`
                        : `No hay lectura de humedad activa para este cultivo.`;
                }

                if (/\bph\b/.test(q)) {
                    const r = fmtRango('ph');
                    return datosCultivo.ph !== null
                        ? `La lectura actual de pH es <strong>${datosCultivo.ph}</strong>${r ? ` (referencia ${datosCultivo.perfil_label}: ${r})` : ''}.`
                        : `No hay lectura de pH activa para este cultivo${r ? ` (referencia ${datosCultivo.perfil_label}: ${r})` : ''}.`;
                }

                if (/\bce\b|conductividad/.test(q)) {
                    const r = fmtRango('conductividad_ec');
                    return datosCultivo.conductividad_ec !== null
                        ? `La conductividad eléctrica (CE) actual es de <strong>${datosCultivo.conductividad_ec} µS/cm</strong>${r ? ` (referencia ${datosCultivo.perfil_label}: ${r})` : ''}.`
                        : `No hay lectura de CE activa para este cultivo${r ? ` (referencia ${datosCultivo.perfil_label}: ${r})` : ''}.`;
                }

                if (/nitr[oó]geno|\bn\b/.test(q)) {
                    const r = fmtRango('nitrogeno');
                    return datosCultivo.nitrogeno !== null
                        ? `El nitrógeno (N) actual es de <strong>${datosCultivo.nitrogeno} mg/kg</strong>${r ? ` (referencia ${datosCultivo.perfil_label}: ${r})` : ''}.`
                        : `No hay lectura de nitrógeno activa para este cultivo.`;
                }

                if (/f[oó]sforo|\bp\b/.test(q)) {
                    const r = fmtRango('fosforo');
                    return datosCultivo.fosforo !== null
                        ? `El fósforo (P) actual es de <strong>${datosCultivo.fosforo} mg/kg</strong>${r ? ` (referencia ${datosCultivo.perfil_label}: ${r})` : ''}.`
                        : `No hay lectura de fósforo activa para este cultivo.`;
                }

                if (/potasio|\bk\b|npk/.test(q)) {
                    const r = fmtRango('potasio');
                    if (/npk/.test(q)) {
                        return `Lecturas NPK actuales: N = <strong>${datosCultivo.nitrogeno ?? '—'} mg/kg</strong>, P = <strong>${datosCultivo.fosforo ?? '—'} mg/kg</strong>, K = <strong>${datosCultivo.potasio ?? '—'} mg/kg</strong>.`;
                    }
                    return datosCultivo.potasio !== null
                        ? `El potasio (K) actual es de <strong>${datosCultivo.potasio} mg/kg</strong>${r ? ` (referencia ${datosCultivo.perfil_label}: ${r})` : ''}.`
                        : `No hay lectura de potasio activa para este cultivo.`;
                }

                if (/cosech/.test(q)) {
                    return datosCultivo.cosecha_estimada
                        ? `Cosecha estimada: <strong>${datosCultivo.cosecha_estimada}</strong>. Va en el día ${datosCultivo.dia_actual} de ${datosCultivo.dia_total} (${datosCultivo.progreso}% del ciclo).`
                        : `Todavía no tengo suficiente información para calcular la fecha de cosecha de este cultivo.`;
                }

                if (/sensor/.test(q)) {
                    return datosCultivo.sensor_activo
                        ? `Sí, este cultivo tiene lecturas activas de sensor. Última lectura: ${datosCultivo.ultima_lectura ?? 'hace poco'}.`
                        : `No hay lecturas activas de sensor vinculadas a este cultivo en este momento.`;
                }

                return `Puedes preguntarme sobre: temperatura, humedad, pH, CE, nitrógeno, fósforo, potasio, NPK, cosecha, sensor, o "¿cómo va mi planta?".`;
            }

            function agregarMensajeBot(html) {
                chatMessages.insertAdjacentHTML('beforeend', `
                    <div class="flex gap-2 max-w-[90%]">
                        <div class="w-7 h-7 rounded-lg bg-[#063221] text-emerald-400 flex-shrink-0 flex items-center justify-center text-[10px]">
                            <i class="fa-solid fa-leaf"></i>
                        </div>
                        <div class="bg-slate-100 p-3 rounded-2xl rounded-tl-none text-slate-800 leading-relaxed border border-slate-200/60">${html}</div>
                    </div>
                `);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function agregarMensajeUsuario(texto) {
                const div = document.createElement('div');
                div.className = 'flex gap-2 max-w-[90%] ml-auto justify-end';
                const bubble = document.createElement('div');
                bubble.className = 'bg-[#063221] p-3 rounded-2xl rounded-tr-none text-white leading-relaxed shadow-sm';
                bubble.textContent = texto;
                div.appendChild(bubble);
                chatMessages.appendChild(div);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    </script>
    @else
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-16 text-center">
        <i class="fa-solid fa-seedling text-3xl text-slate-300 mb-3"></i>
        <p class="text-xs text-slate-400">Aún no hay cultivos registrados. Ve a "Cultivos" para agregar el primero.</p>
    </div>
    @endif

</div>
@endsection