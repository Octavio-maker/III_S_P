<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CultivoService
{
    protected ?FirestoreClient $firestore = null;
    protected bool $firestoreDisponible = false;
    protected string $ultimoError = '';
    protected bool $usandoDemo = false;

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
            Log::error('CultivoService: no se pudo inicializar Firestore -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            $this->firestore = null;
            $this->firestoreDisponible = false;
        }
    }

    public function conectadoAFirestore(): bool
    {
        return $this->firestoreDisponible;
    }

    public function ultimoError(): string
    {
        return $this->ultimoError;
    }

    public function usandoDemo(): bool
    {
        return $this->usandoDemo;
    }

    /**
     * Devuelve todos los cultivos reales de Firestore, cruzados con las
     * alertas no revisadas ('alertas') y la última lectura de sensores
     * ('nodos_sensores' + 'lecturas_sensores').
     */
    public function all(): array
    {
        $items = [];

        if ($this->firestore) {
            try {
                $alertasPorCultivo  = $this->alertasNoRevisadas();
                $lecturasPorCultivo = $this->lecturasPorCultivo();

                $docs = $this->firestore->collection('cultivos')->documents();
                foreach ($docs as $doc) {
                    if ($doc->exists()) {
                        $items[] = $this->normalizar(
                            $doc->id(),
                            $doc->data(),
                            $alertasPorCultivo[$doc->id()] ?? null,
                            $lecturasPorCultivo[$doc->id()] ?? null
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::error('CultivoService: error leyendo Firestore -> ' . $e->getMessage());
                $this->ultimoError = $e->getMessage();
                $items = [];
            }
        }

        if (empty($items)) {
            $this->usandoDemo = true;
            if (empty($this->ultimoError)) {
                $this->ultimoError = 'La colección "cultivos" está vacía o no se pudo leer.';
            }
            foreach ($this->demoData() as $id => $data) {
                $items[] = $this->normalizar($id, $data, null, null);
            }
        }

        return $items;
    }

    /**
     * Lee la colección 'alertas' y regresa [cultivo_id => mensaje]
     * solo con las que tienen revisada = false.
     */
    protected function alertasNoRevisadas(): array
    {
        $mapa = [];
        try {
            $docs = $this->firestore->collection('alertas')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists()) {
                    $data = $doc->data();
                    if (($data['revisada'] ?? true) === false && !empty($data['cultivo_id'])) {
                        $mapa[$data['cultivo_id']] = [
                            'id'      => $doc->id(),
                            'mensaje' => $data['mensaje'] ?? 'Alerta sin descripción',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('CultivoService: error leyendo alertas -> ' . $e->getMessage());
        }
        return $mapa;
    }

    /**
     * Cruza 'nodos_sensores' (nodo_id -> cultivo_id) con 'lecturas_sensores'
     * (nodo_id -> temperatura/humedad/nutrientes/oxigeno) y regresa,
     * por cada cultivo, SOLO la lectura más reciente.
     */
    protected function lecturasPorCultivo(): array
    {
        $resultado = [];
        try {
            $nodoACultivo = [];
            foreach ($this->firestore->collection('nodos_sensores')->documents() as $nodo) {
                if ($nodo->exists()) {
                    $d = $nodo->data();
                    if (!empty($d['cultivo_id'])) {
                        $nodoACultivo[$nodo->id()] = $d['cultivo_id'];
                    }
                }
            }

            $masReciente = [];
            foreach ($this->firestore->collection('lecturas_sensores')->documents() as $lectura) {
                if (!$lectura->exists()) {
                    continue;
                }
                $data = $lectura->data();
                $nodoId = $data['nodo_id'] ?? null;
                if (!$nodoId || !isset($nodoACultivo[$nodoId])) {
                    continue;
                }

                $cultivoId = $nodoACultivo[$nodoId];
                $fecha = $this->aFecha($data['fecha_lectura'] ?? null);

                if (!isset($masReciente[$cultivoId]) || $fecha > $masReciente[$cultivoId]['fecha']) {
                    $masReciente[$cultivoId] = ['fecha' => $fecha, 'data' => $data];
                }
            }

            foreach ($masReciente as $cultivoId => $info) {
                $resultado[$cultivoId] = [
                    'temperatura'   => $info['data']['temperatura'] ?? null,
                    'humedad'       => $info['data']['humedad'] ?? null,
                    'nutrientes'    => $info['data']['nutrientes'] ?? null,
                    'oxigeno'       => $info['data']['oxigeno'] ?? null,
                    'fecha_lectura' => $this->formatearFecha($info['data']['fecha_lectura'] ?? null),
                ];
            }
        } catch (\Throwable $e) {
            Log::error('CultivoService: error leyendo sensores -> ' . $e->getMessage());
        }
        return $resultado;
    }

    protected function aFecha($valor): \DateTimeInterface
    {
        if (empty($valor)) {
            return new \DateTimeImmutable('@0');
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
            return new \DateTimeImmutable('@0');
        }
    }

    public function delete(string $id): bool
    {
        if (!$this->firestore || str_starts_with($id, 'demo-')) {
            return false;
        }
        try {
            $this->firestore->collection('cultivos')->document($id)->delete();
            return true;
        } catch (\Throwable $e) {
            Log::error('CultivoService: error eliminando cultivo -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    public function resolverAlerta(string $alertaId): bool
    {
        if (!$this->firestore) {
            return false;
        }
        try {
            $this->firestore->collection('alertas')->document($alertaId)->update([
                ['path' => 'revisada', 'value' => true],
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('CultivoService: error resolviendo alerta -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    public function create(array $data): bool
    {
        if (!$this->firestore) {
            return false;
        }

        try {
            $this->firestore->collection('cultivos')->add([
                'nombre'              => $data['nombre'],
                'sector_ubicacion'    => $data['sector'] ?? 'Sin asignar',
                'sistema'             => $data['sistema'] ?? 'Sistema NFT',
                'plantas'             => (int) ($data['plantas'] ?? 0),
                'fecha_inicio'        => !empty($data['fecha_inicio']) ? new \DateTimeImmutable($data['fecha_inicio']) : now(),
                'dia_actual'          => (int) ($data['dia_actual'] ?? 0),
                'dias_totales'        => (int) ($data['dia_total'] ?? 30),
                'etapa_crecimiento'   => $data['etapa_crecimiento'] ?? 'Germinación',
                'ph_min'              => (float) ($data['ph_min'] ?? 5.8),
                'ph_max'              => (float) ($data['ph_max'] ?? 6.4),
                'ce_min'              => (float) ($data['ce_min'] ?? 1.2),
                'ce_max'              => (float) ($data['ce_max'] ?? 1.8),
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('CultivoService: error creando cultivo -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    /**
     * Normaliza un documento de Firestore (con tus nombres de campo reales)
     * al formato que usan las vistas.
     */
    protected function normalizar(string $id, array $data, ?array $alerta = null, ?array $lectura = null): array
    {
        $diaActual = (int) ($data['dia_actual'] ?? 0);
        $diaTotal  = max(1, (int) ($data['dias_totales'] ?? $data['dia_total'] ?? 1));
        $progreso  = min(100, round(($diaActual / $diaTotal) * 100));

        $etapa = $data['etapa_crecimiento'] ?? $data['estado'] ?? 'Vegetativa';

        $mapaEtapaAEstado = [
            'Germinación' => 'Germinación',
            'Vegetativa'  => 'Crecimiento',
            'Floración'   => 'Floración',
            'Cosecha'     => 'Cosechable',
        ];
        $estadoVisual = $mapaEtapaAEstado[$etapa] ?? $etapa;

        $requiereAtencion = !empty($alerta);

        return [
            'id'                => $id,
            'nombre'            => $data['nombre'] ?? 'Cultivo sin nombre',
            'lote'              => $data['lote'] ?? ('#' . substr($id, 0, 4)),
            'sector'            => $data['sector_ubicacion'] ?? $data['sector'] ?? 'Sin asignar',
            'sistema'           => $data['sistema'] ?? 'Sistema NFT',
            'plantas'           => (int) ($data['plantas'] ?? 0),
            'sembrado'          => $this->formatearFecha($data['fecha_inicio'] ?? $data['sembrado'] ?? null),
            'dia_actual'        => $diaActual,
            'dia_total'         => $diaTotal,
            'progreso'          => $progreso,
            'etapa_crecimiento' => $etapa,
            'estado'            => $estadoVisual,
            'requiere_atencion' => $requiereAtencion,
            'alerta_id'         => $alerta['id'] ?? null,
            'alerta_mensaje'    => $alerta['mensaje'] ?? null,
            'ph_min'            => (float) ($data['ph_min'] ?? 5.8),
            'ph_max'            => (float) ($data['ph_max'] ?? 6.4),
            'ce_min'            => (float) ($data['ce_min'] ?? 1.2),
            'ce_max'            => (float) ($data['ce_max'] ?? 1.8),
            'temperatura'       => isset($lectura['temperatura']) ? (float) $lectura['temperatura'] : (float) ($data['temperatura'] ?? 22.0),
            'humedad'           => isset($lectura['humedad']) ? (int) $lectura['humedad'] : (int) ($data['humedad'] ?? 65),
            'nutrientes'        => $lectura['nutrientes'] ?? null,
            'oxigeno'           => $lectura['oxigeno'] ?? null,
            'sensor_activo'     => $lectura !== null,
            'ultima_lectura'    => $lectura['fecha_lectura'] ?? null,
            'cosecha_estimada'  => $this->formatearFecha($data['cosecha_estimada'] ?? null) ?? $this->calcularCosechaEstimada($data),
            'slug'              => Str::slug(($data['nombre'] ?? 'cultivo') . '-' . $id),
        ];
    }

    /**
     * Si el cultivo no tiene 'cosecha_estimada' guardada explícitamente,
     * la calculamos como fecha_inicio + dias_totales.
     */
    protected function calcularCosechaEstimada(array $data): ?string
    {
        $inicio = $data['fecha_inicio'] ?? $data['sembrado'] ?? null;
        $dias   = (int) ($data['dias_totales'] ?? $data['dia_total'] ?? 0);

        if (!$inicio || $dias <= 0) {
            return null;
        }

        $fechaInicio = $this->aFecha($inicio);
        if ($fechaInicio->format('Y') === '1970') {
            return null;
        }

        return $fechaInicio->modify("+{$dias} days")->format('Y-m-d H:i:s');
    }

    /**
     * Convierte un Timestamp de Firestore (o cualquier formato de fecha)
     * a un string 'Y-m-d H:i:s' que Carbon pueda leer sin explotar.
     */
    protected function formatearFecha($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }
        if (is_object($valor) && method_exists($valor, 'get')) {
            return $valor->get()->format('Y-m-d H:i:s');
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }
        return (string) $valor;
    }

    protected function demoData(): array
    {
        return [
            'demo-001' => [
                'nombre' => 'Lechuga Italiana', 'sector_ubicacion' => 'Sector A',
                'sistema' => 'Sistema NFT - Módulo A', 'plantas' => 450,
                'dia_actual' => 18, 'dias_totales' => 35, 'etapa_crecimiento' => 'Vegetativa',
                'ph_min' => 5.8, 'ph_max' => 6.2, 'ce_min' => 1.4, 'ce_max' => 1.8,
                'temperatura' => 22.4, 'humedad' => 68,
            ],
            'demo-002' => [
                'nombre' => 'Fresa Albión', 'sector_ubicacion' => 'Sector B',
                'sistema' => 'Sistema Gotero - Sustrato Coco', 'plantas' => 300,
                'dia_actual' => 56, 'dias_totales' => 90, 'etapa_crecimiento' => 'Floración',
                'ph_min' => 5.5, 'ph_max' => 6.0, 'ce_min' => 1.8, 'ce_max' => 2.2,
                'temperatura' => 26.1, 'humedad' => 55,
            ],
        ];
    }
}