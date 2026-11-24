<!-- Modal: Agregar Nuevo Cultivo (compartido por Panel y Cultivos) -->
<div id="modal-nuevo-cultivo" class="hidden fixed inset-0 bg-black/50 z-40 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-seedling text-emerald-600"></i> Registrar Nuevo Cultivo
            </h3>
            <button type="button" onclick="cerrarModalCultivo()" class="text-gray-400 hover:text-gray-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('cultivos.store') }}" class="px-6 py-5 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Nombre del cultivo *</label>
                    <input type="text" name="nombre" required placeholder="Ej. Papa Alpha / Lechuga"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Tipo de cultivo *</label>
                    <select name="tipo_cultivo" required
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                        @foreach(config('umbrales.perfiles', []) as $clave => $perfil)
                            <option value="{{ $clave }}">{{ $perfil['label'] ?? ucfirst($clave) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Sector (opcional)</label>
                    <input type="text" name="sector" placeholder="Ej. Sector A"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Sistema</label>
                    <input type="text" name="sistema" placeholder="Ej. Sistema NFT"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Número de plantas</label>
                    <input type="number" min="0" name="plantas" placeholder="Ej. 200"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Duración del ciclo (días)</label>
                    <input type="number" min="1" name="dia_total" placeholder="Ej. 35"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Fecha de siembra</label>
                    <input type="date" name="fecha_inicio"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                    <p class="text-[10px] text-gray-400 mt-1">Si la dejas vacía, se usará hoy.</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Día actual del ciclo (opcional)</label>
                    <input type="number" min="0" name="dia_actual" placeholder="0 si es recién sembrado"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-800/20">
                </div>
            </div>

            <div class="p-3 bg-emerald-50/70 border border-emerald-200/70 rounded-xl text-[11px] text-emerald-900">
                <i class="fa-solid fa-sliders mr-1 text-emerald-700"></i>
                Los rangos ideales de temperatura, humedad, pH, CE y NPK se asignan automáticamente según el <strong>tipo de cultivo</strong> y se comparan contra las lecturas reales del sensor que vincules.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="cerrarModalCultivo()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-[#063221] hover:bg-[#042317] text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-check"></i> Guardar Cultivo
                </button>
            </div>
        </form>
    </div>
</div>