<!-- Top Navigation Bar -->
<header class="bg-[#063221] text-white sticky top-0 z-50 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo & Brand -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-leaf text-xl"></i>
                </div>
                <div>
                    <span class="text-xl font-bold tracking-tight text-white block leading-none">Pouani</span>
                    <span class="text-[10px] text-emerald-300 font-medium tracking-wider uppercase">Plataforma Agrotech</span>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <nav class="hidden md:flex items-center space-x-1">
                <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-xs font-semibold bg-emerald-800/60 text-emerald-200 border border-emerald-600/30">
                    <i class="fa-solid fa-chart-line mr-1.5"></i> Dashboard
                </a>
                <a href="{{ route('cultivos.index') }}" class="px-3 py-2 rounded-lg text-xs font-semibold text-emerald-100/80 hover:bg-emerald-800/40 hover:text-white transition">
                    <i class="fa-solid fa-seedling mr-1.5"></i> Cultivos
                </a>
                <a href="{{ route('ia.index') }}" class="px-3 py-2 rounded-lg text-xs font-semibold text-emerald-100/80 hover:bg-emerald-800/40 hover:text-white transition">
                    <i class="fa-solid fa-brain mr-1.5"></i> IA Agrotech
                </a>
                @if(session('user_role') === 'Administrador')
                <a href="{{ route('admin.index') }}" class="px-3 py-2 rounded-lg text-xs font-semibold text-amber-300/90 hover:bg-emerald-800/40 hover:text-amber-200 transition">
                    <i class="fa-solid fa-user-shield mr-1.5"></i> Administración
                </a>
                @endif
            </nav>

            <!-- User Profile & Logout -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex flex-col text-right">
                    <span class="text-xs font-bold text-white">{{ session('user_name', 'Usuario Agrotech') }}</span>
                    <div class="flex items-center justify-end gap-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="text-[10px] text-emerald-200 font-medium">{{ session('user_role', 'Encargado') }}</span>
                    </div>
                </div>
                <a href="{{ route('logout') }}" title="Cerrar Sesión" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-red-500/20 hover:text-red-300 text-emerald-100 flex items-center justify-center transition border border-white/10">
                    <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                </a>
            </div>

        </div>
    </div>
</header>

<!-- Main Content Container -->
<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-[#063221] to-emerald-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-6 -bottom-6 opacity-10 text-emerald-300 pointer-events-none">
            <i class="fa-solid fa-wheat-awn text-9xl"></i>
        </div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 inline-block mb-2">
                    Estado del Sistema: En Línea
                </span>
                <h1 class="text-2xl font-bold tracking-tight">Monitoreo Hidropónico en Tiempo Real</h1>
                <p class="text-xs text-emerald-100/80 mt-1 max-w-2xl">
                    Supervisión automatizada de variables físico-químicas en módulos NFT y automatización de dosificación nutricia.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-[#063221] font-bold text-xs rounded-xl transition shadow-md flex items-center gap-2">
                    <i class="fa-solid fa-arrows-rotate"></i> Sincronizar Sensores
                </button>
            </div>
        </div>
    </div>

    <!-- Telemetry Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- Card pH -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider">Potencial de Hidrógeno</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="fa-solid fa-vial"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">6.2</span>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Óptimo</span>
            </div>
            <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                <span>Rango: 5.8 - 6.5</span>
                <span class="text-slate-400">pH</span>
            </div>
        </div>

        <!-- Card EC -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider">Cond. Eléctrica</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="fa-solid fa-bolt"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">1.8</span>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Estable</span>
            </div>
            <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                <span>Rango: 1.2 - 2.2</span>
                <span class="text-slate-400">mS/cm</span>
            </div>
        </div>

        <!-- Card Water Temp -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider">Temp. Agua</span>
                <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center">
                    <i class="fa-solid fa-temperature-half"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">21.5</span>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Ideal</span>
            </div>
            <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                <span>Rango: 18 - 23</span>
                <span class="text-slate-400">°C</span>
            </div>
        </div>

        <!-- Card Air Humidity -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider">Humedad Amb.</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="fa-solid fa-droplet"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">68%</span>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Lig. Alto</span>
            </div>
            <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                <span>Rango: 50 - 65%</span>
                <span class="text-slate-400">RH</span>
            </div>
        </div>

        <!-- Card Tank Level -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider">Nivel Tanque</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i class="fa-solid fa-water"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">82%</span>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Adecuado</span>
            </div>
            <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                <span>Capacidad: 250 L</span>
                <span class="text-slate-400">205 L</span>
            </div>
        </div>

    </div>

    <!-- Analytics & Actuators Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Historical Chart (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Histórico de Muestreo (pH vs CE)</h2>
                    <p class="text-xs text-slate-500">Últimas 24 horas de lectura continua</p>
                </div>
                <select class="text-xs bg-slate-100 border border-slate-200 rounded-lg px-3 py-1.5 font-medium text-slate-700 outline-none">
                    <option>Últimas 24 Horas</option>
                    <option>Últimos 7 Días</option>
                    <option>Último Mes</option>
                </select>
            </div>
            <div class="h-64 relative w-full">
                <canvas id="telemetryChart"></canvas>
            </div>
        </div>

        <!-- Actuators Control & Active Alerts (1 Column) -->
        <div class="space-y-6">
            
            <!-- Actuators Panel -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center justify-between">
                    <span>Control de Actuadores</span>
                    <span class="text-[10px] bg-slate-100 text-slate-600 font-semibold px-2 py-0.5 rounded-md">Modo Auto</span>
                </h2>

                <div class="space-y-3">
                    <!-- Actuator item -->
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <i class="fa-solid fa-[#063221] fa-pump-soap"></i>
                                <i class="fa-solid fa-faucet-drip"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Bomba Principal Riego</p>
                                <p class="text-[10px] text-slate-500">Ciclo Activo: 15min On / 45min Off</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">ENCENDIDO</span>
                    </div>

                    <!-- Actuator item -->
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                                <i class="fa-solid fa-lightbulb"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Luces Grow LED</p>
                                <p class="text-[10px] text-slate-500">Fotoperiodo: 16h Luz</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">ENCENDIDO</span>
                    </div>

                    <!-- Actuator item -->
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center">
                                <i class="fa-solid fa-fan"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Extractor de Aire</p>
                                <p class="text-[10px] text-slate-500">Activado por Temp > 26°C</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700">EN ESPERA</span>
                    </div>
                </div>
            </div>

            <!-- Recent System Alerts -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-3 flex items-center justify-between">
                    <span>Notificaciones Recientes</span>
                    <i class="fa-solid fa-bell text-slate-400 text-sm"></i>
                </h2>
                
                <div class="space-y-2 text-xs">
                    <div class="p-3 bg-amber-50 border border-amber-200/60 rounded-xl flex items-start gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                        <div>
                            <p class="font-bold text-amber-900">Alerta de Humedad</p>
                            <p class="text-[11px] text-amber-800/80">Humedad ambiental superior al 65%. Se recomienda ventilación.</p>
                            <span class="text-[9px] text-amber-600 font-medium mt-1 block">Hace 12 minutos</span>
                        </div>
                    </div>

                    <div class="p-3 bg-emerald-50 border border-emerald-200/60 rounded-xl flex items-start gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
                        <div>
                            <p class="font-bold text-emerald-900">Dosificación Exitosa</p>
                            <p class="text-[11px] text-emerald-800/80">Ajuste automático de solución A/B completado a las 08:00 AM.</p>
                            <span class="text-[9px] text-emerald-600 font-medium mt-1 block">Hace 2 horas</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</main>

<!-- Footer -->
<footer class="bg-white border-t border-slate-200 py-4 mt-auto">
    <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
        Pouani Agrotech Platform &copy; 2026. Todos los derechos reservados.
    </div>
</footer>

<!-- Chart JS Setup -->
<script>
    const ctx = document.getElementById('telemetryChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '21:00'],
            datasets: [
                {
                    label: 'pH',
                    data: [6.0, 6.1, 6.1, 6.3, 6.2, 6.2, 6.4, 6.2],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Conductividad Eléctrica (EC)',
                    data: [1.7, 1.7, 1.8, 1.9, 1.8, 1.8, 1.8, 1.8],
                    borderColor: '#3b82f6',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { size: 11, family: 'Plus Jakarta Sans' } }
                }
            },
            scales: {
                y: {
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { size: 10, family: 'Plus Jakarta Sans' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10, family: 'Plus Jakarta Sans' } }
                }
            }
        }
    });
</script>
