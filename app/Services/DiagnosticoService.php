<?php

namespace App\Services;

class DiagnosticoService
{
    /**
     * Genera un diagnóstico basado en reglas a partir de las 7 variables reales
     * del sensor y el perfil de umbrales del tipo de cultivo (config/umbrales.php).
     */
    public function generar(array $cultivo): array
    {
        $tips = [];

        // 1. Alerta activa desde la colección 'alertas' (máxima prioridad)
        if (!empty($cultivo['requiere_atencion'])) {
            $tips[] = [
                'nivel'   => 'danger',
                'icono'   => 'fa-triangle-exclamation',
                'titulo'  => 'Alerta activa sin resolver',
                'mensaje' => $cultivo['alerta_mensaje'] ?? 'Hay una alerta pendiente de revisión para este cultivo.',
            ];
        }

        // 2. Sin lecturas activas del sensor
        if (empty($cultivo['sensor_activo'])) {
            if (!empty($cultivo['esperando_lectura'])) {
                $tips[] = [
                    'nivel'   => 'info',
                    'icono'   => 'fa-rotate',
                    'titulo'  => 'Esperando primera lectura del sensor',
                    'mensaje' => 'El nodo ya está vinculado a este lote, pero aún no envía una lectura posterior a la vinculación.',
                ];
            } else {
                $tips[] = [
                    'nivel'   => 'info',
                    'icono'   => 'fa-plug-circle-xmark',
                    'titulo'  => 'Sin datos de sensores',
                    'mensaje' => 'Este cultivo no tiene un nodo vinculado. Usa el botón "Vincular sensor" en Cultivos para recibir temperatura, humedad, pH, CE y NPK en tiempo real.',
                ];
            }
        } else {
            if (!empty($cultivo['lectura_antigua'])) {
                $tips[] = [
                    'nivel'   => 'warning',
                    'icono'   => 'fa-satellite-dish',
                    'titulo'  => 'Sensor sin señal reciente',
                    'mensaje' => 'Han pasado más de 20 minutos desde la última lectura recibida. Verifica alimentación o conexión Wi-Fi del nodo.',
                ];
            }

            $perfil = $this->perfilUmbrales($cultivo);

            $evaluaciones = [
                [
                    'campo'  => 'temperatura',
                    'nombre' => 'Temperatura',
                    'icono'  => 'fa-temperature-half',
                    'bajo'   => 'Está por debajo del rango recomendado; puede ralentizar el desarrollo radicular.',
                    'alto'   => 'Supera el rango recomendado; riesgo de estrés térmico en raíces.',
                ],
                [
                    'campo'  => 'humedad',
                    'nombre' => 'Humedad del sustrato',
                    'icono'  => 'fa-droplet',
                    'bajo'   => 'Humedad baja en zona radicular; revisa frecuencia de riego.',
                    'alto'   => 'Humedad elevada; vigila aireación para prevenir hongos.',
                ],
                [
                    'campo'  => 'ph',
                    'nombre' => 'pH',
                    'icono'  => 'fa-vial',
                    'bajo'   => 'pH ácido por debajo del óptimo; puede bloquear la absorción de calcio y magnesio.',
                    'alto'   => 'pH alcalino por encima del óptimo; dificulta la asimilación de hierro y fósforo.',
                ],
                [
                    'campo'  => 'conductividad_ec',
                    'nombre' => 'Conductividad Eléctrica (CE)',
                    'icono'  => 'fa-bolt',
                    'bajo'   => 'Concentración de sales baja; el cultivo podría requerir mayor aporte nutritivo.',
                    'alto'   => 'Concentración de sales elevada; riesgo de estrés osmótico en raíces.',
                ],
                [
                    'campo'  => 'nitrogeno',
                    'nombre' => 'Nitrógeno (N)',
                    'icono'  => 'fa-seedling',
                    'bajo'   => 'Nivel bajo de nitrógeno; puede reducir el vigor vegetativo y amarillear hojas.',
                    'alto'   => 'Nivel alto de nitrógeno; exceso de follaje blando.',
                ],
                [
                    'campo'  => 'fosforo',
                    'nombre' => 'Fósforo (P)',
                    'icono'  => 'fa-flask',
                    'bajo'   => 'Nivel bajo de fósforo; afecta enraizamiento y transferencia energética.',
                    'alto'   => 'Nivel elevado de fósforo; puede interferir con micronutrientes como zinc o hierro.',
                ],
                [
                    'campo'  => 'potasio',
                    'nombre' => 'Potasio (K)',
                    'icono'  => 'fa-leaf',
                    'bajo'   => 'Nivel bajo de potasio; reduce resistencia hídrica y calidad de cosecha.',
                    'alto'   => 'Nivel alto de potasio; puede competir con la absorción de calcio y magnesio.',
                ],
            ];

            foreach ($evaluaciones as $ev) {
                $campo = $ev['campo'];
                $valor = $cultivo[$campo] ?? null;
                if ($valor === null || !isset($perfil[$campo])) {
                    continue;
                }

                $min    = (float) $perfil[$campo]['min'];
                $max    = (float) $perfil[$campo]['max'];
                $unidad = $perfil[$campo]['unidad'] ?? '';
                $sufijo = $unidad !== '' ? " {$unidad}" : '';

                if ($valor < $min) {
                    $tips[] = [
                        'nivel'   => 'warning',
                        'icono'   => $ev['icono'],
                        'titulo'  => "{$ev['nombre']} baja ({$valor}{$sufijo})",
                        'mensaje' => "Lectura actual: {$valor}{$sufijo} (rango {$perfil['label']}: {$min}–{$max}{$sufijo}). {$ev['bajo']}",
                    ];
                } elseif ($valor > $max) {
                    $tips[] = [
                        'nivel'   => 'warning',
                        'icono'   => $ev['icono'],
                        'titulo'  => "{$ev['nombre']} alta ({$valor}{$sufijo})",
                        'mensaje' => "Lectura actual: {$valor}{$sufijo} (rango {$perfil['label']}: {$min}–{$max}{$sufijo}). {$ev['alto']}",
                    ];
                } else {
                    $tips[] = [
                        'nivel'   => 'ok',
                        'icono'   => $ev['icono'],
                        'titulo'  => "{$ev['nombre']} óptima ({$valor}{$sufijo})",
                        'mensaje' => "Dentro del rango ideal para {$perfil['label']} ({$min}–{$max}{$sufijo}).",
                    ];
                }
            }
        }

        // 3. Progreso del ciclo
        if (($cultivo['progreso'] ?? 0) >= 100) {
            $tips[] = [
                'nivel'   => 'ok',
                'icono'   => 'fa-basket-shopping',
                'titulo'  => 'Listo para cosecha',
                'mensaje' => 'El cultivo alcanzó el 100% de su ciclo estimado. Es buen momento para programar la cosecha.',
            ];
        } elseif (($cultivo['progreso'] ?? 0) >= 85) {
            $tips[] = [
                'nivel'   => 'info',
                'icono'   => 'fa-hourglass-end',
                'titulo'  => 'Cosecha próxima',
                'mensaje' => "Va en {$cultivo['progreso']}% del ciclo. Prepárate para cosechar en los próximos días.",
            ];
        }

        return $tips;
    }

    /**
     * Resuelve el perfil de umbrales según 'tipo_cultivo' o el nombre del cultivo.
     */
    public function perfilUmbrales(array $cultivo): array
    {
        $perfiles = config('umbrales.perfiles', []);
        $default  = config('umbrales.default', 'general');

        $tipo = strtolower(trim((string) ($cultivo['tipo_cultivo'] ?? '')));
        if ($tipo !== '' && isset($perfiles[$tipo])) {
            return $perfiles[$tipo];
        }

        $nombre = mb_strtolower((string) ($cultivo['nombre'] ?? ''));
        foreach ($perfiles as $clave => $perfil) {
            if ($clave !== 'general' && str_contains($nombre, $clave)) {
                return $perfil;
            }
        }

        return $perfiles[$default] ?? [
            'label'            => 'General Hidropónico',
            'temperatura'      => ['min' => 18.0, 'max' => 26.0, 'unidad' => '°C'],
            'humedad'          => ['min' => 55.0, 'max' => 80.0, 'unidad' => '%'],
            'ph'               => ['min' => 5.5,  'max' => 6.5,  'unidad' => ''],
            'conductividad_ec' => ['min' => 300,  'max' => 1600, 'unidad' => 'µS/cm'],
            'nitrogeno'        => ['min' => 15,   'max' => 80,   'unidad' => 'mg/kg'],
            'fosforo'          => ['min' => 15,   'max' => 60,   'unidad' => 'mg/kg'],
            'potasio'          => ['min' => 40,   'max' => 180,  'unidad' => 'mg/kg'],
        ];
    }

    /**
     * Determina el estado general del cultivo según el peor nivel encontrado.
     */
    public function estadoGeneral(array $tips): string
    {
        $prioridad = ['danger' => 3, 'warning' => 2, 'info' => 1, 'ok' => 0];
        $peor = 'ok';
        foreach ($tips as $tip) {
            if (($prioridad[$tip['nivel']] ?? 0) > $prioridad[$peor]) {
                $peor = $tip['nivel'];
            }
        }
        return match ($peor) {
            'danger'  => 'Crítico',
            'warning' => 'Requiere Atención',
            'info'    => 'Estable',
            default   => 'Saludable',
        };
    }
}