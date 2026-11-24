<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CultivoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\IaController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas y Autenticación
|--------------------------------------------------------------------------
*/

// Vista de Login
Route::get('/', function () {
    if (session('authenticated')) {
        return redirect()->route('dashboard');
    }
    return view('auth.login');
})->name('login');

// Autenticación Real con Google + Firestore
Route::post('/login-google', [AuthController::class, 'loginGoogle'])->name('login.google');

// Cerrar Sesión (limpia Laravel Y Firebase en el navegador)
Route::get('/logout', function () {
    session()->flush();
    return view('auth.logout');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas Protegidas por Sesión
|--------------------------------------------------------------------------
*/

Route::middleware('auth.session')->group(function () {

    // Dashboard Resumen
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gestión de Cultivos y Lotes
    Route::get('/cultivos', [CultivoController::class, 'index'])->name('cultivos.index');
    
    Route::get('/cultivos/lecturas-en-vivo', [CultivoController::class, 'lecturasEnVivo'])->name('cultivos.lecturas-en-vivo');
    Route::post('/cultivos', [CultivoController::class, 'store'])->name('cultivos.store');
    Route::post('/cultivos/{id}/vincular-sensor', [CultivoController::class, 'vincularSensor'])->name('cultivos.vincular.sensor');
    Route::post('/cultivos/{id}/desvincular-sensor', [CultivoController::class, 'desvincularSensor'])->name('cultivos.desvincular.sensor');
    Route::delete('/cultivos/{id}', [CultivoController::class, 'destroy'])->name('cultivos.destroy');
    Route::post('/cultivos/alertas/{id}/resolver', [CultivoController::class, 'resolverAlerta'])->name('cultivos.alertas.resolver');
    

    // Módulo de IA Agrotech
    Route::get('/inteligencia-ia', [IaController::class, 'index'])->name('ia.index');

    // Generar Informe (imprimible)
    Route::get('/reportes/generar', [ReportController::class, 'generar'])->name('reportes.generar');

    /*
    |----------------------------------------------------------------------
    | Rutas Protegidas por Rol (Administrador)
    |----------------------------------------------------------------------
    */
    Route::middleware('admin.role')->prefix('administracion')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/usuarios', [AdminController::class, 'storeUsuario'])->name('usuarios.store');
        Route::post('/usuarios/{id}/rol', [AdminController::class, 'actualizarRol'])->name('usuarios.rol');
        Route::post('/usuarios/{id}/estado', [AdminController::class, 'alternarEstado'])->name('usuarios.estado');
    });

});