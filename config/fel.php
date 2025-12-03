<?php

return [

    'environment' => env('FEL_ENVIRONMENT'),
    'verify_ssl' => env('FEL_VERIFY_SSL'),

    'username' => env('FEL_USERNAME'),
    'password' => env('FEL_PASSWORD'),

    'urls' => [
        'get_token' => env('FEL_ENVIRONMENT') === 'production'
            ? 'https://ws.ccgfel.gt/Api/GetToken'
            : 'https://testws.ccgfel.gt/Api/GetToken',

        'certificar_dte' => env('FEL_ENVIRONMENT') === 'production'
            ? 'https://ws.ccgfel.gt/Api/CertificarDte'
            : 'https://testws.ccgfel.gt/Api/CertificarDte',

        'anular_dte' => env('FEL_ENVIRONMENT') === 'production'
            ? 'https://ws.ccgfel.gt/Api/AnularDte'
            : 'https://testws.ccgfel.gt/Api/AnularDte',

        'consultar_nit' => env('FEL_ENVIRONMENT') === 'production'
            ? 'https://ws.ccgfel.gt/Api/ConsultarNit'
            : 'https://testws.ccgfel.gt/Api/ConsultarNit',

        'consultar_dte' => env('FEL_ENVIRONMENT') === 'production'
            ? 'https://ws.ccgfel.gt/Api/ConsultarDte'
            : 'https://testws.ccgfel.gt/Api/ConsultarDte',

        'consultar_cui' => env('FEL_ENVIRONMENT') === 'production'
            ? 'https://ws.ccgfel.gt/Api/ConsultarCui'
            : 'https://testws.ccgfel.gt/Api/ConsultarCui',
    ],

    'emisor' => [
        'nit' => env('FEL_NIT_EMISOR'),
        'nombre' => env('FEL_NOMBRE_EMISOR'),
        'nombre_comercial' => env('FEL_NOMBRE_COMERCIAL', env('FEL_NOMBRE_EMISOR')),
        'direccion' => env('FEL_DIRECCION_EMISOR', 'Ciudad de Guatemala'),
        'codigo_postal' => env('FEL_CODIGO_POSTAL', '01001'),
        'municipio' => env('FEL_MUNICIPIO', 'Guatemala'),
        'departamento' => env('FEL_DEPARTAMENTO', 'Guatemala'),
        'pais' => 'GT',
        'pais' => 'GT',
        'afiliacion_iva' => env('FEL_AFILIACION_IVA', 'GEN'),
        'codigo_establecimiento' => env('FEL_CODIGO_ESTABLECIMIENTO', '1'),
    ],

    'storage_path' => env('FEL_STORAGE_PATH', 'fel/xmls'),
];
