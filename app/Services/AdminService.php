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
            foreach ($this->firestore->collection('nodos_sensores')->documents() as $doc) {
                if ($doc->exists()) {
                    $d = $doc->data();
                    $lista[] = [
                        'id'            => $doc->id(),
                        'nombre_nodo'   => $d['nombre_nodo'] ?? 'Nodo sin nombre',
                        'cultivo_id'    => $d['cultivo_id'] ?? null,
                        'encendido'     => (bool) ($d['encendido'] ?? false),
                        'nivel_bateria' => $d['nivel_bateria'] ?? null,
                    ];
                }
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

    protected function aTexto($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }
        if (is_object($valor) && method_exists($valor, 'get')) {
            return $valor->get()->format('d M Y, H:i');
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('d M Y, H:i');
        }
        return (string) $valor;
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