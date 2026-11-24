<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Log;

class AdminService
{
    protected ?FirestoreClient $firestore = null;
    protected bool $firestoreDisponible = false;
    protected string $ultimoError = '';

    public function __construct()
    {
        try {
            $credentialsPath = storage_path('app/firebase-credentials.json');

            if (file_exists($credentialsPath)) {
                $keyFileData = json_decode(file_get_contents($credentialsPath), true);

                if (is_array($keyFileData) && isset($keyFileData['private_key'])) {
                    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
                    $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = $credentialsPath;
                    $_SERVER['GOOGLE_APPLICATION_CREDENTIALS'] = $credentialsPath;

                    $this->firestore = new FirestoreClient([
                        'projectId'   => $keyFileData['project_id'] ?? 'sistema-hidroponico-97751',
                        'keyFile'     => $keyFileData,
                        'keyFilePath' => $credentialsPath,
                        'transport'   => 'rest',
                    ]);
                    $this->firestoreDisponible = true;
                } else {
                    $this->ultimoError = 'El archivo de credenciales no tiene private_key.';
                }
            } else {
                $this->ultimoError = 'No se encontró storage/app/firebase-credentials.json';
            }
        } catch (\Throwable $e) {
            Log::error('AdminService: no se pudo inicializar Firestore -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
        }
    }

    public function conectado(): bool
    {
        return $this->firestoreDisponible;
    }

    public function ultimoError(): string
    {
        return $this->ultimoError;
    }

    public function usuarios(): array
    {
        $lista = [];
        if (!$this->firestore) {
            return $lista;
        }
        try {
            foreach ($this->firestore->collection('usuarios')->documents() as $doc) {
                if ($doc->exists()) {
                    $d = $doc->data();
                    $lista[] = [
                        'id'     => $doc->id(),
                        'nombre' => $d['nombre'] ?? 'Sin nombre',
                        'correo' => $d['correo'] ?? $doc->id(),
                        'rol'    => $d['rol'] ?? 'Encargado',
                        'estado' => $d['estado'] ?? 'Activo',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error('AdminService: error leyendo usuarios -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
        }
        return $lista;
    }

    public function sensores(): array
    {
        $lista = [];
        if (!$this->firestore) {
            return $lista;
        }
        try {
            $nombresCultivos = [];
            foreach ($this->firestore->collection('cultivos')->documents() as $cDoc) {
                if ($cDoc->exists()) {
                    $cData = $cDoc->data();
                    $nombresCultivos[$cDoc->id()] = $cData['nombre'] ?? ('#' . substr($cDoc->id(), 0, 4));
                }
            }

            foreach ($this->firestore->collection('nodos_sensores')->documents() as $doc) {
                if (!$doc->exists()) {
                    continue;
                }
                $d = $doc->data();
                $cultivoId = $d['cultivo_id'] ?? null;
                $cultivoNombre = ($cultivoId && isset($nombresCultivos[$cultivoId]))
                    ? $nombresCultivos[$cultivoId]
                    : null;

                $ultimaLectura = null;
                try {
                    $docsLectura = $this->firestore->collection('lecturas_sensores')
                        ->where('nodo_id', '=', $doc->id())
                        ->orderBy('fecha_lectura', 'DESC')
                        ->limit(1)
                        ->documents();

                    $asignadoDesde = !empty($d['asignado_desde']) ? $this->aFecha($d['asignado_desde']) : null;

                    foreach ($docsLectura as $lDoc) {
                        if (!$lDoc->exists()) {
                            continue;
                        }
                        $lData = $lDoc->data();
                        $fechaL = !empty($lData['fecha_lectura']) ? $this->aFecha($lData['fecha_lectura']) : null;
                        if ($fechaL !== null && ($asignadoDesde === null || $fechaL >= $asignadoDesde)) {
                            $ultimaLectura = $fechaL->format('d M Y, H:i');
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('AdminService: no se pudo leer última lectura del nodo ' . $doc->id() . ' -> ' . $e->getMessage());
                }

                $lista[] = [
                    'id'             => $doc->id(),
                    'nombre_nodo'    => $d['nombre_nodo'] ?? ('Nodo ' . substr($doc->id(), 0, 6)),
                    'cultivo_id'     => $cultivoId,
                    'cultivo_nombre' => $cultivoNombre,
                    'ultima_lectura' => $ultimaLectura,
                    'encendido'      => (bool) ($d['encendido'] ?? false),
                    'nivel_bateria'  => $d['nivel_bateria'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('AdminService: error leyendo sensores -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
        }
        return $lista;
    }

    public function alertas(): array
    {
        $lista = [];
        if (!$this->firestore) {
            return $lista;
        }
        try {
            foreach ($this->firestore->collection('alertas')->documents() as $doc) {
                if ($doc->exists()) {
                    $d = $doc->data();
                    $lista[] = [
                        'id'       => $doc->id(),
                        'mensaje'  => $d['mensaje'] ?? 'Alerta sin descripción',
                        'revisada' => (bool) ($d['revisada'] ?? false),
                        'fecha'    => $this->aTexto($d['fecha_alerta'] ?? null),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error('AdminService: error leyendo alertas -> ' . $e->getMessage());
        }
        return array_reverse($lista);
    }

    protected function aFecha($valor): ?\DateTimeInterface
    {
        if (empty($valor)) {
            return null;
        }
        if (is_object($valor) && method_exists($valor, 'get')) {
            return $valor->get();
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor;
        }
        try {
            return new \DateTimeImmutable((string) $valor);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function aTexto($valor): ?string
    {
        $dt = $this->aFecha($valor);
        if ($dt !== null) {
            return $dt->format('d M Y, H:i');
        }
        return is_scalar($valor) ? (string) $valor : null;
    }

    /**
     * Crea el usuario usando su correo como ID del documento,
     * igual que ya lo tienes hecho a mano en Firestore.
     */
    public function crearUsuario(array $data): bool
    {
        if (!$this->firestore) {
            return false;
        }
        try {
            $this->firestore->collection('usuarios')->document($data['correo'])->set([
                'nombre' => $data['nombre'],
                'correo' => $data['correo'],
                'rol'    => $data['rol'] ?? 'Encargado',
                'estado' => 'Activo',
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('AdminService: error creando usuario -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    public function actualizarRol(string $id, string $rol): bool
    {
        if (!$this->firestore) {
            return false;
        }
        try {
            $this->firestore->collection('usuarios')->document($id)->update([
                ['path' => 'rol', 'value' => $rol],
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('AdminService: error actualizando rol -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    public function alternarEstado(string $id): bool
    {
        if (!$this->firestore) {
            return false;
        }
        try {
            $snapshot = $this->firestore->collection('usuarios')->document($id)->snapshot();
            $actual   = $snapshot->exists() ? ($snapshot->data()['estado'] ?? 'Activo') : 'Activo';
            $nuevo    = $actual === 'Activo' ? 'Inactivo' : 'Activo';

            $this->firestore->collection('usuarios')->document($id)->update([
                ['path' => 'estado', 'value' => $nuevo],
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('AdminService: error cambiando estado -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }
}