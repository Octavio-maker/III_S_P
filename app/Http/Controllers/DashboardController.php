<?php

namespace App\Http\Controllers;

use App\Services\CultivoService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected CultivoService $cultivoService)
    {
    }

    public function index(Request $request)
    {
        $cultivos = $this->cultivoService->all();

        $alertas = collect($cultivos)->filter(fn ($c) => $c['requiere_atencion'])->values();

        $proximaCosecha = collect($cultivos)
            ->filter(fn ($c) => !empty($c['cosecha_estimada']))
            ->sortBy('cosecha_estimada')
            ->first();

        return view('dashboard.index', [
            'cultivos'         => $cultivos,
            'alertas'          => $alertas,
            'proximaCosecha'   => $proximaCosecha,
            'totalCultivos'    => count($cultivos),
            'firestoreActivo'  => !$this->cultivoService->usandoDemo(),
        ]);
    }
}