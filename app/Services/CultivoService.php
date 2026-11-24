<?php

namespace App\Services;

use Carbon\Carbon;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CultivoService
{
    /** Minutos sin lecturas a partir de los cuales se considera que el sensor perdió señal. */
    public const MINUTOS_SIN_SENAL = 20;

    protected ?FirestoreClient $firestore = null;
    protected bool $firestoreDisponible = false;
    protected string $ultimoError = '';
    protected bool $usandoDemo = false;
    protected ?array $cacheCultivos = null;
    protected ?array $cacheNodos = null;

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
     * Devuelve todos los cultivos, cruzados con sus alertas sin revisar,
     * el nodo vinculado y la última lectura válida del sensor.
     */
    public function all(): array
    {
        if ($this->cacheCultivos !== null) {
            return $this->cacheCultivos;
        }

        $items = [];

        if ($this->firestore) {
            try {
                $alertasPorCultivo  = $this->alertasNoRevisadas();
                $estadoSensores     = $this->estadoSensoresPorCultivo();

                $docs = $this->firestore->collection('cultivos')->documents();
                foreach ($docs as $doc) {
                    if ($doc->exists()) {
                        $items[] = $this->normalizar(
                            $doc->id(),
                            $doc->data(),
                            $alertasPorCultivo[$doc->id()] ?? null,
                            $estadoSensores[$doc->id()] ?? null
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

        return $this->cacheCultivos = $items;
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $cultivo) {
            if ($cultivo['id'] === $id) {
                return $cultivo;
            }
        }
        return null;
    }

    /**
     * Lista todos los nodos en 'nodos_sensores' junto con a qué cultivo están asignados.
     */
    public function nodosDisponibles(): array
    {
        if ($this->cacheNodos !== null) {
            return $this->cacheNodos;
        }

        if (!$this->firestore) {
            return $this->cacheNodos = [];
        }

        try {
            $nombresCultivos = [];
            foreach ($this->firestore->collection('cultivos')->documents() as $cDoc) {
                if ($cDoc->exists()) {
                    $cData = $cDoc->data();
                    $nombresCultivos[$cDoc->id()] = $cData['nombre'] ?? ('#' . substr($cDoc->id(), 0, 4));
                }
            }

            $nodos = [];
            foreach ($this->firestore->collection('nodos_sensores')->documents() as $doc) {
                if (!$doc->exists()) {
                    continue;
                }
                $d = $doc->data();
                $cultivoId = $d['cultivo_id'] ?? null;
                $asignadoDesde = !empty($d['asignado_desde']) ? $this->aFecha($d['asignado_desde'])->format('c') : null;

                $nodos[] = [
                    'id'              => $doc->id(),
                    'nombre'          => $d['nombre_nodo'] ?? $d['nombre'] ?? ('Nodo ' . substr($doc->id(), 0, 6)),
                    'encendido'       => (bool) ($d['encendido'] ?? true),
                    'nivel_bateria'   => isset($d['nivel_bateria']) ? (float) $d['nivel_bateria'] : null,
                    'cultivo_id'      => $cultivoId ?: null,
                    'cultivo_nombre'  => ($cultivoId && isset($nombresCultivos[$cultivoId])) ? $nombresCultivos[$cultivoId] : null,
                    'asignado_desde'  => $asignadoDesde,
                ];
            }

            return $this->cacheNodos = $nodos;
        } catch (\Throwable $e) {
            Log::error('CultivoService: error listando nodos_sensores -> ' . $e->getMessage());
            return $this->cacheNodos = [];
        }
    }

    /**
     * Mueve un nodo a $cultivoId, guardando asignado_desde y conservando
     * la última lectura como histórica en el cultivo anterior si existía.
     */
    public function vincularNodo(string $cultivoId, string $nodoId): array
    {
        if (!$this->firestore) {
            return ['ok' => false, 'mensaje' => 'Firestore no está disponible.'];
        }

        try {
            $cultivoDoc = $this->firestore->collection('cultivos')->document($cultivoId)->snapshot();
            if (!$cultivoDoc->exists()) {
                return ['ok' => false, 'mensaje' => 'El cultivo destino no existe.'];
            }

            $nodoRef = $this->firestore->collection('nodos_sensores')->document($nodoId);
            $nodoDoc = $nodoRef->snapshot();
            if (!$nodoDoc->exists()) {
                return ['ok' => false, 'mensaje' => 'El nodo de sensor seleccionado no existe.'];
            }

            $nodoData = $nodoDoc->data();
            $cultivoAnteriorId = $nodoData['cultivo_id'] ?? null;

            // Si ya medía otro cultivo distinto, conservar su última lectura como histórica.
            if (!empty($cultivoAnteriorId) && $cultivoAnteriorId !== $cultivoId) {
                $ultimaLecturaAnterior = $this->ultimaLecturaDeNodo($nodoId, $nodoData['asignado_desde'] ?? null);
                if ($ultimaLecturaAnterior !== null) {
                    $this->firestore->collection('cultivos')->document($cultivoAnteriorId)->update([
                        [
                            'path'  => 'ultima_lectura_historica',
                            'value' => [
                                'nodo_id'          => $nodoId,
                                'nombre_nodo'      => $nodoData['nombre_nodo'] ?? $nodoData['nombre'] ?? $nodoId,
                                'temperatura'      => $ultimaLecturaAnterior['temperatura'],
                                'humedad'          => $ultimaLecturaAnterior['humedad'],
                                'ph'               => $ultimaLecturaAnterior['ph'],
                                'conductividad_ec' => $ultimaLecturaAnterior['conductividad_ec'],
                                'nitrogeno'        => $ultimaLecturaAnterior['nitrogeno'],
                                'fosforo'          => $ultimaLecturaAnterior['fosforo'],
                                'potasio'          => $ultimaLecturaAnterior['potasio'],
                                'fecha_lectura'    => $ultimaLecturaAnterior['_fecha'],
                                'archivada_en'     => new \DateTimeImmutable('now'),
                            ],
                        ],
                    ]);
                }
            }

            $ahora = new \DateTimeImmutable('now');
            $nodoRef->update([
                ['path' => 'cultivo_id', 'value' => $cultivoId],
                ['path' => 'asignado_desde', 'value' => $ahora],
            ]);

            $this->cacheCultivos = null;
            $this->cacheNodos = null;

            $nombreDestino = $cultivoDoc->data()['nombre'] ?? 'cultivo';
            return ['ok' => true, 'mensaje' => 'Sensor vinculado a "' . $nombreDestino . '". Esperando nueva lectura desde este momento.'];
        } catch (\Throwable $e) {
            Log::error('CultivoService: error vinculando nodo -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return ['ok' => false, 'mensaje' => 'No se pudo vincular el sensor: ' . $e->getMessage()];
        }
    }

    /**
     * Desvincula el nodo del cultivo (lo deja libre).
     */
    public function desvincularNodo(string $cultivoId, ?string $nodoId = null): array
    {
        if (!$this->firestore) {
            return ['ok' => false, 'mensaje' => 'Firestore no está disponible.'];
        }

        try {
            $desvinculados = 0;

            foreach ($this->firestore->collection('nodos_sensores')->documents() as $doc) {
                if (!$doc->exists()) {
                    continue;
                }
                $d = $doc->data();
                $coincideCultivo = (($d['cultivo_id'] ?? null) === $cultivoId);
                $coincideNodo = ($nodoId === null || $nodoId === '' || $doc->id() === $nodoId);

                if ($coincideCultivo && $coincideNodo) {
                    $ultimaLectura = $this->ultimaLecturaDeNodo($doc->id(), $d['asignado_desde'] ?? null);
                    if ($ultimaLectura !== null) {
                        $this->firestore->collection('cultivos')->document($cultivoId)->update([
                            [
                                'path'  => 'ultima_lectura_historica',
                                'value' => [
                                    'nodo_id'          => $doc->id(),
                                    'nombre_nodo'      => $d['nombre_nodo'] ?? $d['nombre'] ?? $doc->id(),
                                    'temperatura'      => $ultimaLectura['temperatura'],
                                    'humedad'          => $ultimaLectura['humedad'],
                                    'ph'               => $ultimaLectura['ph'],
                                    'conductividad_ec' => $ultimaLectura['conductividad_ec'],
                                    'nitrogeno'        => $ultimaLectura['nitrogeno'],
                                    'fosforo'          => $ultimaLectura['fosforo'],
                                    'potasio'          => $ultimaLectura['potasio'],
                                    'fecha_lectura'    => $ultimaLectura['_fecha'],
                                    'archivada_en'     => new \DateTimeImmutable('now'),
                                ],
                            ],
                        ]);
                    }

                    $doc->reference()->update([
                        ['path' => 'cultivo_id', 'value' => null],
                        ['path' => 'asignado_desde', 'value' => null],
                    ]);
                    $desvinculados++;
                }
            }

            $this->cacheCultivos = null;
            $this->cacheNodos = null;

            if ($desvinculados === 0) {
                return ['ok' => false, 'mensaje' => 'No se encontró un sensor vinculado a este cultivo.'];
            }

            return ['ok' => true, 'mensaje' => 'Sensor desvinculado correctamente.'];
        } catch (\Throwable $e) {
            Log::error('CultivoService: error desvinculando nodo -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return ['ok' => false, 'mensaje' => 'No se pudo desvincular el sensor: ' . $e->getMessage()];
        }
    }

    /**
     * Lee la colección 'alertas' y regresa [cultivo_id => ['id' => ..., 'mensaje' => ...]]
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
     * Obtiene la última lectura de un nodo (opcionalmente filtrando >= desde).
     */
    protected function ultimaLecturaDeNodo(string $nodoId, $desde = null): ?array
    {
        try {
            $docs = $this->firestore->collection('lecturas_sensores')
                ->where('nodo_id', '=', $nodoId)
                ->orderBy('fecha_lectura', 'DESC')
                ->limit(1)
                ->documents();

            $fechaMinima = !empty($desde) ? $this->aFecha($desde) : null;

            foreach ($docs as $lectura) {
                if (!$lectura->exists()) {
                    continue;
                }
                $data  = $lectura->data();
                $fecha = $this->aFecha($data['fecha_lectura'] ?? null);

                if ($fechaMinima !== null && $fecha < $fechaMinima) {
                    return null;
                }

                return [
                    'temperatura'      => $this->numero($data['temperatura'] ?? null),
                    'humedad'          => $this->numero($data['humedad'] ?? null),
                    'ph'               => $this->numero($data['ph'] ?? null),
                    'conductividad_ec' => $this->numero($data['conductividad_ec'] ?? null),
                    'nitrogeno'        => $this->numero($data['nitrogeno'] ?? null),
                    'fosforo'          => $this->numero($data['fosforo'] ?? null),
                    'potasio'          => $this->numero($data['potasio'] ?? null),
                    'fecha_lectura'    => $fecha->format('c'),
                    '_fecha'           => $fecha,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('CultivoService: error leyendo lecturas del nodo ' . $nodoId . ' -> ' . $e->getMessage());
            $this->ultimoError = 'Lecturas del nodo ' . $nodoId . ': ' . $e->getMessage();
        }

        return null;
    }

    /**
     * Devuelve [cultivo_id => ['nodo_id' => ..., 'nombre_nodo' => ..., 'esperando_lectura' => bool, 'lectura' => ?array]].
     */
    protected function estadoSensoresPorCultivo(): array
    {
        $resultado = [];

        try {
            foreach ($this->firestore->collection('nodos_sensores')->documents() as $nodo) {
                if (!$nodo->exists()) {
                    continue;
                }
                $d = $nodo->data();
                $cultivoId = $d['cultivo_id'] ?? null;
                if (empty($cultivoId)) {
                    continue;
                }

                $nodoId = $nodo->id();
                $nombreNodo = $d['nombre_nodo'] ?? $d['nombre'] ?? ('Nodo ' . substr($nodoId, 0, 6));
                $asignadoDesdeRaw = $d['asignado_desde'] ?? null;
                $lectura = $this->ultimaLecturaDeNodo($nodoId, $asignadoDesdeRaw);

                $esperandoLectura = ($lectura === null && !empty($asignadoDesdeRaw));

                if (!isset($resultado[$cultivoId])) {
                    $resultado[$cultivoId] = [
                        'nodo_id'           => $nodoId,
                        'nombre_nodo'       => $nombreNodo,
                        'asignado_desde'    => !empty($asignadoDesdeRaw) ? $this->aFecha($asignadoDesdeRaw)->format('c') : null,
                        'esperando_lectura' => $esperandoLectura,
                        'lectura'           => $lectura,
                    ];
                } else {
                    $prevFecha = $resultado[$cultivoId]['lectura']['_fecha'] ?? null;
                    $currFecha = $lectura['_fecha'] ?? null;
                    if ($currFecha !== null && ($prevFecha === null || $currFecha > $prevFecha)) {
                        $resultado[$cultivoId]['nodo_id'] = $nodoId;
                        $resultado[$cultivoId]['nombre_nodo'] = $nombreNodo;
                        $resultado[$cultivoId]['esperando_lectura'] = false;
                        $resultado[$cultivoId]['lectura'] = $lectura;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('CultivoService: error leyendo sensores -> ' . $e->getMessage());
        }

        return $resultado;
    }

    protected function numero($valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        return (float) $valor;
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
            $this->cacheCultivos = null;
            $this->cacheNodos = null;
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
            $this->cacheCultivos = null;
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
                'nombre'            => $data['nombre'],
                'tipo_cultivo'      => $data['tipo_cultivo'] ?? 'general',
                'sector_ubicacion'  => !empty($data['sector']) ? $data['sector'] : 'Sin asignar',
                'sistema'           => !empty($data['sistema']) ? $data['sistema'] : 'Sistema NFT',
                'plantas'           => (int) ($data['plantas'] ?? 0),
                'fecha_inicio'      => !empty($data['fecha_inicio']) ? new \DateTimeImmutable($data['fecha_inicio']) : now(),
                'dia_actual'        => (int) ($data['dia_actual'] ?? 0),
                'dias_totales'      => (int) ($data['dia_total'] ?? 30),
                'etapa_crecimiento' => $data['etapa_crecimiento'] ?? 'Germinación',
            ]);
            $this->cacheCultivos = null;
            return true;
        } catch (\Throwable $e) {
            Log::error('CultivoService: error creando cultivo -> ' . $e->getMessage());
            $this->ultimoError = $e->getMessage();
            return false;
        }
    }

    /**
     * Normaliza un documento de Firestore al formato que usan las vistas.
     * Si el cultivo no tiene sensor vinculado, las lecturas quedan en null
     * (nunca se inventan valores).
     */
    protected function normalizar(string $id, array $data, ?array $alerta = null, ?array $estadoSensor = null): array
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

        $tieneNodoVinculado = $estadoSensor !== null;
        $lectura = $estadoSensor['lectura'] ?? null;
        $esperandoLectura = (bool) ($estadoSensor['esperando_lectura'] ?? false);

        $fechaLectura   = $lectura['fecha_lectura'] ?? null;
        $lecturaAntigua = false;
        if ($fechaLectura) {
            $minutos        = (time() - Carbon::parse($fechaLectura)->getTimestamp()) / 60;
            $lecturaAntigua = $minutos > self::MINUTOS_SIN_SENAL;
        }

        $historicaRaw = $data['ultima_lectura_historica'] ?? null;
        $historica = null;
        if (is_array($historicaRaw)) {
            $fechaHist = !empty($historicaRaw['fecha_lectura']) ? $this->aFecha($historicaRaw['fecha_lectura'])->format('c') : null;
            $historica = [
                'nodo_id'          => $historicaRaw['nodo_id'] ?? null,
                'nombre_nodo'      => $historicaRaw['nombre_nodo'] ?? 'Sensor anterior',
                'temperatura'      => $this->numero($historicaRaw['temperatura'] ?? null),
                'humedad'          => $this->numero($historicaRaw['humedad'] ?? null),
                'ph'               => $this->numero($historicaRaw['ph'] ?? null),
                'conductividad_ec' => $this->numero($historicaRaw['conductividad_ec'] ?? null),
                'nitrogeno'        => $this->numero($historicaRaw['nitrogeno'] ?? null),
                'fosforo'          => $this->numero($historicaRaw['fosforo'] ?? null),
                'potasio'          => $this->numero($historicaRaw['potasio'] ?? null),
                'fecha_lectura'    => $fechaHist,
            ];
        }

        return [
            'id'                        => $id,
            'nombre'                    => $data['nombre'] ?? 'Cultivo sin nombre',
            'lote'                      => $data['lote'] ?? ('#' . substr($id, 0, 4)),
            'sector'                    => $data['sector_ubicacion'] ?? $data['sector'] ?? 'Sin asignar',
            'sistema'                   => $data['sistema'] ?? 'Sistema NFT',
            'plantas'                   => (int) ($data['plantas'] ?? 0),
            'sembrado'                  => $this->formatearFecha($data['fecha_inicio'] ?? $data['sembrado'] ?? null),
            'dia_actual'                => $diaActual,
            'dia_total'                 => $diaTotal,
            'progreso'                  => $progreso,
            'etapa_crecimiento'         => $etapa,
            'estado'                    => $estadoVisual,
            'requiere_atencion'         => $requiereAtencion,
            'alerta_id'                 => $alerta['id'] ?? null,
            'alerta_mensaje'            => $alerta['mensaje'] ?? null,
            'ph_min'                    => (float) ($data['ph_min'] ?? 5.8),
            'ph_max'                    => (float) ($data['ph_max'] ?? 6.4),
            'ce_min'                    => (float) ($data['ce_min'] ?? 1.2),
            'ce_max'                    => (float) ($data['ce_max'] ?? 1.8),

            // --- Estado del sensor y lecturas reales ---
            'nodo_vinculado_id'         => $estadoSensor['nodo_id'] ?? null,
            'nodo_vinculado_nombre'     => $estadoSensor['nombre_nodo'] ?? null,
            'asignado_desde'            => $estadoSensor['asignado_desde'] ?? null,
            'esperando_lectura'         => $esperandoLectura,
            'sensor_activo'             => $lectura !== null,
            'tiene_nodo'                => $tieneNodoVinculado,
            'temperatura'               => $lectura['temperatura'] ?? null,
            'humedad'                   => $lectura['humedad'] ?? null,
            'ph'                        => $lectura['ph'] ?? null,
            'conductividad_ec'          => $lectura['conductividad_ec'] ?? null,
            'nitrogeno'                 => $lectura['nitrogeno'] ?? null,
            'fosforo'                   => $lectura['fosforo'] ?? null,
            'potasio'                   => $lectura['potasio'] ?? null,
            'ultima_lectura'            => $fechaLectura,
            'lectura_antigua'           => $lecturaAntigua,
            'ultima_lectura_historica'  => $historica,

            // Compatibilidad temporal con la vista de IA (se retiran en la Fase C).
            'nutrientes'                => null,
            'oxigeno'                   => null,

            'cosecha_estimada'          => $this->formatearFecha($data['cosecha_estimada'] ?? null) ?? $this->calcularCosechaEstimada($data),
            'slug'                      => Str::slug(($data['nombre'] ?? 'cultivo') . '-' . $id),
        ];
    }

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
            ],
            'demo-002' => [
                'nombre' => 'Fresa Albión', 'sector_ubicacion' => 'Sector B',
                'sistema' => 'Sistema Gotero - Sustrato Coco', 'plantas' => 300,
                'dia_actual' => 56, 'dias_totales' => 90, 'etapa_crecimiento' => 'Floración',
                'ph_min' => 5.5, 'ph_max' => 6.0, 'ce_min' => 1.8, 'ce_max' => 2.2,
            ],
        ];
    }
}