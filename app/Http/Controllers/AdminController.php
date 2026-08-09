<?php

namespace App\Http\Controllers;

use App\Services\AdminService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(protected AdminService $adminService)
    {
    }

    public function index(Request $request)
    {
        $usuarios = $this->adminService->usuarios();
        $sensores = $this->adminService->sensores();
        $alertas  = $this->adminService->alertas();

        return view('admin.index', [
            'usuarios'        => $usuarios,
            'sensores'        => $sensores,
            'alertas'         => $alertas,
            'totalUsuarios'   => count($usuarios),
            'usuariosActivos' => count(array_filter($usuarios, fn ($u) => $u['estado'] === 'Activo')),
            'totalSensores'   => count($sensores),
            'sensoresActivos' => count(array_filter($sensores, fn ($s) => $s['encendido'])),
            'alertasActivas'  => count(array_filter($alertas, fn ($a) => !$a['revisada'])),
            'firestoreActivo' => $this->adminService->conectado(),
            'errorConexion'   => $this->adminService->ultimoError(),
        ]);
    }

    public function storeUsuario(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'correo' => 'required|email|max:150',
            'rol'    => 'required|in:Administrador,Encargado',
        ]);

        $ok = $this->adminService->crearUsuario($validated);

        return redirect()->route('admin.index')
            ->with($ok ? 'success' : 'error', $ok ? 'Usuario agregado correctamente.' : 'No se pudo guardar el usuario en Firestore.');
    }

    public function actualizarRol(Request $request, string $id)
    {
        if ($id === session('user_email')) {
            return redirect()->route('admin.index')
                ->with('error', 'No puedes cambiar tu propio rol. Pídele a otro Administrador que lo haga.');
        }

        $request->validate([
            'rol' => 'required|in:Administrador,Encargado',
        ]);

        $ok = $this->adminService->actualizarRol($id, $request->input('rol'));

        return redirect()->route('admin.index')
            ->with($ok ? 'success' : 'error', $ok ? 'Rol actualizado.' : 'No se pudo actualizar el rol.');
    }

   public function alternarEstado(string $id)
    {
        if ($id === session('user_email')) {
            return redirect()->route('admin.index')
                ->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $ok = $this->adminService->alternarEstado($id);

        return redirect()->route('admin.index')
            ->with($ok ? 'success' : 'error', $ok ? 'Estado del usuario actualizado.' : 'No se pudo actualizar el estado.');
    }
}