<?php

namespace App\Http\Controllers;

use App\Services\CultivoService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected CultivoService $cultivoService)
    {
    }

    public function generar(Request $request)
    {
        $cultivos = $this->cultivoService->all();

        return view('reportes.index', [
            'cultivos'    => $cultivos,
            'generadoEn'  => now()->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y, H:i'),
            'usuario'     => session('user_name', 'Usuario Pouani'),
        ]);
    }
}
