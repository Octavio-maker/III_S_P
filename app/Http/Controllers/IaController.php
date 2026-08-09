<?php

namespace App\Http\Controllers;

use App\Services\CultivoService;
use App\Services\DiagnosticoService;
use Illuminate\Http\Request;

class IaController extends Controller
{
    public function __construct(
        protected CultivoService $cultivoService,
        protected DiagnosticoService $diagnosticoService
    ) {
    }

    public function index(Request $request)
    {
        $cultivos = $this->cultivoService->all();

        $cultivoId = $request->query('cultivo');
        $seleccionado = $cultivoId
            ? collect($cultivos)->firstWhere('id', $cultivoId)
            : ($cultivos[0] ?? null);

        $diagnostico   = $seleccionado ? $this->diagnosticoService->generar($seleccionado) : [];
        $estadoGeneral = $seleccionado ? $this->diagnosticoService->estadoGeneral($diagnostico) : null;

        return view('ia.index', [
            'cultivos'      => $cultivos,
            'seleccionado'  => $seleccionado,
            'diagnostico'   => $diagnostico,
            'estadoGeneral' => $estadoGeneral,
        ]);
    }
}