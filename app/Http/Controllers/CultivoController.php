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
            'cultivos'        => $cultivos,
            'totalPlantas'    => array_sum(array_column($cultivos, 'plantas')),
            'firestoreActivo' => !$this->cultivoService->usandoDemo(),
            'errorConexion'   => $this->cultivoService->ultimoError(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'     => 'required|string|max:100',
            'fecha_inicio' => 'nullable|date',
            'sector'     => 'nullable|string|max:50',
            'sistema'    => 'nullable|string|max:100',
            'plantas'    => 'nullable|integer|min:0',
            'dia_actual' => 'nullable|integer|min:0',
            'dia_total'  => 'nullable|integer|min:1|max:365',
            'ph_min'     => 'nullable|numeric',
            'ph_max'     => 'nullable|numeric',
            'ce_min'     => 'nullable|numeric',
            'ce_max'     => 'nullable|numeric',
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
}