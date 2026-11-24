<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Umbrales de referencia por tipo de cultivo (7 variables del sensor)
    |--------------------------------------------------------------------------
    | Unidades:
    | - temperatura: °C
    | - humedad: % (humedad del sustrato / suelo)
    | - ph: escala 0 - 14
    | - conductividad_ec: µS/cm
    | - nitrogeno, fosforo, potasio: mg/kg
    */

    'default' => 'general',

    'perfiles' => [
        'general' => [
            'label'            => 'General Hidropónico',
            'temperatura'      => ['min' => 18.0, 'max' => 26.0, 'unidad' => '°C'],
            'humedad'          => ['min' => 55.0, 'max' => 80.0, 'unidad' => '%'],
            'ph'               => ['min' => 5.5,  'max' => 6.5,  'unidad' => ''],
            'conductividad_ec' => ['min' => 300,  'max' => 1600, 'unidad' => 'µS/cm'],
            'nitrogeno'        => ['min' => 15,   'max' => 80,   'unidad' => 'mg/kg'],
            'fosforo'          => ['min' => 15,   'max' => 60,   'unidad' => 'mg/kg'],
            'potasio'          => ['min' => 40,   'max' => 180,  'unidad' => 'mg/kg'],
        ],

        'papa' => [
            'label'            => 'Papa',
            'temperatura'      => ['min' => 16.0, 'max' => 24.0, 'unidad' => '°C'],
            'humedad'          => ['min' => 60.0, 'max' => 80.0, 'unidad' => '%'],
            'ph'               => ['min' => 5.2,  'max' => 6.2,  'unidad' => ''],
            'conductividad_ec' => ['min' => 300,  'max' => 1800, 'unidad' => 'µS/cm'],
            'nitrogeno'        => ['min' => 15,   'max' => 90,   'unidad' => 'mg/kg'],
            'fosforo'          => ['min' => 20,   'max' => 70,   'unidad' => 'mg/kg'],
            'potasio'          => ['min' => 50,   'max' => 220,  'unidad' => 'mg/kg'],
        ],

        'lechuga' => [
            'label'            => 'Lechuga',
            'temperatura'      => ['min' => 15.0, 'max' => 22.0, 'unidad' => '°C'],
            'humedad'          => ['min' => 60.0, 'max' => 80.0, 'unidad' => '%'],
            'ph'               => ['min' => 5.5,  'max' => 6.2,  'unidad' => ''],
            'conductividad_ec' => ['min' => 300,  'max' => 1400, 'unidad' => 'µS/cm'],
            'nitrogeno'        => ['min' => 20,   'max' => 85,   'unidad' => 'mg/kg'],
            'fosforo'          => ['min' => 15,   'max' => 55,   'unidad' => 'mg/kg'],
            'potasio'          => ['min' => 45,   'max' => 170,  'unidad' => 'mg/kg'],
        ],

        'jitomate' => [
            'label'            => 'Jitomate / Tomate',
            'temperatura'      => ['min' => 20.0, 'max' => 28.0, 'unidad' => '°C'],
            'humedad'          => ['min' => 55.0, 'max' => 75.0, 'unidad' => '%'],
            'ph'               => ['min' => 5.5,  'max' => 6.5,  'unidad' => ''],
            'conductividad_ec' => ['min' => 400,  'max' => 2200, 'unidad' => 'µS/cm'],
            'nitrogeno'        => ['min' => 20,   'max' => 100,  'unidad' => 'mg/kg'],
            'fosforo'          => ['min' => 20,   'max' => 75,   'unidad' => 'mg/kg'],
            'potasio'          => ['min' => 60,   'max' => 240,  'unidad' => 'mg/kg'],
        ],

        'fresa' => [
            'label'            => 'Fresa',
            'temperatura'      => ['min' => 16.0, 'max' => 24.0, 'unidad' => '°C'],
            'humedad'          => ['min' => 60.0, 'max' => 80.0, 'unidad' => '%'],
            'ph'               => ['min' => 5.5,  'max' => 6.2,  'unidad' => ''],
            'conductividad_ec' => ['min' => 300,  'max' => 1600, 'unidad' => 'µS/cm'],
            'nitrogeno'        => ['min' => 15,   'max' => 75,   'unidad' => 'mg/kg'],
            'fosforo'          => ['min' => 15,   'max' => 60,   'unidad' => 'mg/kg'],
            'potasio'          => ['min' => 50,   'max' => 200,  'unidad' => 'mg/kg'],
        ],
    ],
];