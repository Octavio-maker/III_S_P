<?php

namespace App\Http\Controllers;

use App\Services\CultivoService;
use Illuminate\Http\Request;

class CultivoController extends Controller
{
    public function __construct(protected CultivoService $cultivoService)
    {
    }

    public function index(Request $request)
    {
        $cultivos = $this->cultivoService->all();

        return view('cultivos.index', [
            'cultivos'         => $cultivos,
            'nodosDisponibles' => $this->cultivoService->nodosDisponibles(),
            'totalPlantas'     => array_sum(array_column($cultivos, 'plantas')),
            'firestoreActivo'  => !$this->cultivoService->usandoDemo(),
            'errorConexion'    => $this->cultivoService->ultimoError(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'       => 'required|string|max:100',
            'tipo_cultivo' => 'nullable|string|max:50',
            'fecha_inicio' => 'nullable|date',
            'sector'       => 'nullable|string|max:50',
            'sistema'      => 'nullable|string|max:100',
            'plantas'      => 'nullable|integer|min:0',
            'dia_actual'   => 'nullable|integer|min:0',
            'dia_total'    => 'nullable|integer|min:1|max:365',
        ]);

        $guardado = $this->cultivoService->create($validated);

        if (!$guardado) {
            return redirect()->route('cultivos.index')
                ->with('error', 'No se pudo guardar el cultivo en Firestore. Verifica la conexión (ver Administración).');
        }

        return redirect()->route('cultivos.index')
            ->with('success', 'Cultivo "' . $validated['nombre'] . '" registrado correctamente.');
    }

    public function destroy(string $id)
    {
        $eliminado = $this->cultivoService->delete($id);

        if (!$eliminado) {
            return redirect()->route('cultivos.index')
                ->with('error', 'No se pudo eliminar (es un dato demo o falló Firestore).');
        }

        return redirect()->route('cultivos.index')
            ->with('success', 'Cultivo eliminado correctamente.');
    }

    public function resolverAlerta(Request $request, string $alertaId)
    {
        $ok = $this->cultivoService->resolverAlerta($alertaId);

        return redirect()->route('cultivos.index')
            ->with($ok ? 'success' : 'error', $ok ? 'Alerta marcada como revisada.' : 'No se pudo actualizar la alerta.');
    }

    public function vincularSensor(Request $request, string $id)
    {
        $validated = $request->validate([
            'nodo_id' => 'required|string|max:120',
        ]);

        $res = $this->cultivoService->vincularNodo($id, $validated['nodo_id']);

        return redirect()->route('cultivos.index')
            ->with($res['ok'] ? 'success' : 'error', $res['mensaje']);
    }

    public function desvincularSensor(Request $request, string $id)
    {
        $nodoId = $request->input('nodo_id');
        $res = $this->cultivoService->desvincularNodo($id, is_string($nodoId) ? $nodoId : null);

        return redirect()->route('cultivos.index')
            ->with($res['ok'] ? 'success' : 'error', $res['mensaje']);
    }

   
    public function lecturasEnVivo()
    {
        $cultivos = $this->cultivoService->all();

        $payload = collect($cultivos)->map(function ($c) {
            return [
                'id'               => $c['id'] ?? null,
                'sensor_activo'    => $c['sensor_activo'] ?? false,
                'lectura_antigua'  => $c['lectura_antigua'] ?? false,
                'ultima_lectura'   => $c['ultima_lectura'] ?? null,
                'temperatura'      => $c['temperatura'] ?? null,
                'humedad'          => $c['humedad'] ?? null,
                'ph'               => $c['ph'] ?? null,
                'conductividad_ec' => $c['conductividad_ec'] ?? null,
                'nitrogeno'        => $c['nitrogeno'] ?? null,
                'fosforo'          => $c['fosforo'] ?? null,
                'potasio'          => $c['potasio'] ?? null,
                'estado'           => $c['estado'] ?? null,
                'requiere_atencion'=> $c['requiere_atencion'] ?? false,
            ];
        })->values();

        return response()->json([
            'ok'        => true,
            'timestamp' => now()->toIso8601String(),
            'cultivos'  => $payload,
        ]);
    }
}