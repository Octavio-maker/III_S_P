<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Pouani - {{ $generadoEn }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body class="bg-white text-gray-800 p-10 max-w-4xl mx-auto">

    <div class="no-print flex justify-end gap-3 mb-6">
        <button onclick="window.print()" class="px-4 py-2 bg-[#063221] text-white text-xs font-bold rounded-xl">
            Imprimir / Guardar PDF
        </button>
        <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl">
            Volver al Panel
        </a>
    </div>

    <div class="flex items-center justify-between border-b-2 border-[#063221] pb-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#063221]">Pouani · Informe de Cultivos</h1>
            <p class="text-xs text-gray-500">Generado el {{ $generadoEn }} por {{ $usuario }}</p>
        </div>
        <div class="text-right text-xs text-gray-400">
            <p>Total de cultivos: {{ count($cultivos) }}</p>
        </div>
    </div>

    <table class="w-full text-xs border-collapse">
        <thead>
            <tr class="bg-gray-50 text-left text-gray-500 uppercase text-[10px]">
                <th class="py-2 px-3 border-b">Cultivo</th>
                <th class="py-2 px-3 border-b">Lote / Sector</th>
                <th class="py-2 px-3 border-b">Estado</th>
                <th class="py-2 px-3 border-b">Progreso</th>
                <th class="py-2 px-3 border-b">pH Objetivo</th>
                <th class="py-2 px-3 border-b">CE Objetivo</th>
                <th class="py-2 px-3 border-b">Cosecha Estimada</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cultivos as $cultivo)
                <tr class="border-b">
                    <td class="py-2 px-3 font-bold">{{ $cultivo['nombre'] }}</td>
                    <td class="py-2 px-3">{{ $cultivo['lote'] }} · {{ $cultivo['sector'] }}</td>
                    <td class="py-2 px-3">
                        <span class="{{ $cultivo['requiere_atencion'] ? 'text-red-600 font-bold' : 'text-emerald-700' }}">
                            {{ $cultivo['estado'] }}
                        </span>
                    </td>
                    <td class="py-2 px-3">Día {{ $cultivo['dia_actual'] }}/{{ $cultivo['dia_total'] }} ({{ $cultivo['progreso'] }}%)</td>
                    <td class="py-2 px-3">{{ $cultivo['ph_min'] }} - {{ $cultivo['ph_max'] }}</td>
                    <td class="py-2 px-3">{{ $cultivo['ce_min'] }} - {{ $cultivo['ce_max'] }} mS/cm</td>
                    <td class="py-2 px-3">{{ $cultivo['cosecha_estimada'] ? \Carbon\Carbon::parse($cultivo['cosecha_estimada'])->translatedFormat('d M Y') : 'Sin definir' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="text-[10px] text-gray-400 mt-8 text-center">Pouani Agrotech Platform &copy; {{ date('Y') }}. Documento generado automáticamente.</p>

</body>
</html>
