<?php

namespace App\Services;

class DiagnosticoService
{
    /**
     * Genera un diagnóstico basado en reglas a partir de los datos
     * normalizados de un cultivo (sensores + metas + progreso).
     */
    public function generar(array $cultivo): array
    {
        $tips = [];

        // 1. Alerta activa desde la colección 'alertas' (máxima prioridad)
        if ($cultivo['requiere_atencion']) {
            $tips[] = [
                'nivel'   => 'danger',
                'icono'   => 'fa-triangle-exclamation',
                'titulo'  => 'Alerta activa sin resolver',
                'mensaje' => $cultivo['alerta_mensaje'] ?? 'Hay una alerta pendiente de revisión para este cultivo.',
            ];
        }

        // 2. Sin sensor conectado -> no se puede evaluar el ambiente
        if (!$cultivo['sensor_activo']) {
            $tips[] = [
                'nivel'   => 'info',
                'icono'   => 'fa-plug-circle-xmark',
                'titulo'  => 'Sin datos de sensores',
                'mensaje' => 'Este cultivo no tiene un nodo de sensores vinculado, así que no podemos evaluar temperatura, humedad u oxígeno en tiempo real. Vincula un nodo en la colección nodos_sensores para un diagnóstico completo.',
            ];
        } else {
            // Temperatura (rango general hidropónico: 18-26°C)
            $temp = $cultivo['temperatura'];
            if ($temp < 18) {
                $tips[] = ['nivel' => 'warning', 'icono' => 'fa-temperature-low', 'titulo' => 'Temperatura baja', 'mensaje' => "La temperatura actual ({$temp}°C) está por debajo del rango recomendado (18-26°C). Puede ralentizar el crecimiento."];
            } elseif ($temp > 26) {
                $tips[] = ['nivel' => 'warning', 'icono' => 'fa-temperature-high', 'titulo' => 'Temperatura alta', 'mensaje' => "La temperatura actual ({$temp}°C) supera el rango recomendado (18-26°C). Riesgo de estrés térmico en la raíz."];
            } else {
                $tips[] = ['nivel' => 'ok', 'icono' => 'fa-temperature-half', 'titulo' => 'Temperatura estable', 'mensaje' => "La temperatura ({$temp}°C) está dentro del rango saludable."];
            }

            // Humedad relativa (rango general: 50-75%)
            $hum = $cultivo['humedad'];
            if ($hum < 50) {
                $tips[] = ['nivel' => 'warning', 'icono' => 'fa-droplet-slash', 'titulo' => 'Humedad baja', 'mensaje' => "La humedad relativa ({$hum}%) está baja. Vigila signos de deshidratación o plagas como ácaros."];
            } elseif ($hum > 75) {
                $tips[] = ['nivel' => 'warning', 'icono' => 'fa-droplet', 'titulo' => 'Humedad alta', 'mensaje' => "La humedad relativa ({$hum}%) está alta. Riesgo de hongos o moho, considera ventilar."];
            } else {
                $tips[] = ['nivel' => 'ok', 'icono' => 'fa-droplet', 'titulo' => 'Humedad adecuada', 'mensaje' => "La humedad relativa ({$hum}%) está en un rango saludable."];
            }

            // Oxígeno disuelto (ideal >= 5 mg/L)
            if ($cultivo['oxigeno'] !== null) {
                $ox = (float) $cultivo['oxigeno'];
                if ($ox < 5) {
                    $tips[] = ['nivel' => 'danger', 'icono' => 'fa-wind', 'titulo' => 'Oxígeno disuelto bajo', 'mensaje' => "El oxígeno disuelto ({$ox} mg/L) está por debajo de 5 mg/L. Riesgo de asfixia radicular, revisa el sistema de aireación."];
                } else {
                    $tips[] = ['nivel' => 'ok', 'icono' => 'fa-wind', 'titulo' => 'Oxigenación correcta', 'mensaje' => "El oxígeno disuelto ({$ox} mg/L) es adecuado para la zona radicular."];
                }
            }

            // Nutrientes (solo referencia; tu esquema aún no define un rango objetivo)
            if ($cultivo['nutrientes'] !== null) {
                $tips[] = ['nivel' => 'info', 'icono' => 'fa-flask', 'titulo' => 'Nivel de nutrientes', 'mensaje' => "Lectura actual de nutrientes: {$cultivo['nutrientes']}. Compárala con mediciones anteriores para detectar tendencias."];
            }
        }

        // 3. Progreso del ciclo
        if ($cultivo['progreso'] >= 100) {
            $tips[] = ['nivel' => 'ok', 'icono' => 'fa-basket-shopping', 'titulo' => 'Listo para cosecha', 'mensaje' => 'El cultivo alcanzó el 100% de su ciclo estimado. Es buen momento para programar la cosecha.'];
        } elseif ($cultivo['progreso'] >= 85) {
            $tips[] = ['nivel' => 'info', 'icono' => 'fa-hourglass-end', 'titulo' => 'Cosecha próxima', 'mensaje' => "Va en {$cultivo['progreso']}% del ciclo. Prepárate para cosechar en los próximos días."];
        }

        return $tips;
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