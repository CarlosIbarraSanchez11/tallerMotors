<?php

$yc_apiKey = getenv('YCLOUD_API_KEY')     ?: 'adb9c481cd5760ae056be156d3393f1b';
$yc_from   = getenv('YCLOUD_FROM_NUMBER') ?: '+51943527168';
$yc_url    = 'https://api.ycloud.com/v2/whatsapp/messages';

// --- FUNCIÓN 1: PARA PADRES (BIENVENIDA) ---

function enviarTemplateBienvenida($celular, $nombrePadre, $usuarioCorreo) {
    global $yc_apiKey, $yc_url, $yc_from;

    $data = [
        "from" => $yc_from,
        "to" => prepararCelularY($celular),
        "type" => "template",
        "template" => [
            "name" => "bienvenida_sir_usil",
            "language" => ["code" => "es"],
            "components" => [
                ["type" => "body", "parameters" => [
                    ["type" => "text", "text" => $nombrePadre],
                    ["type" => "text", "text" => $usuarioCorreo]
                ]]
            ]
        ]
    ];
    return ejecutarCurlYCloud($yc_url, $yc_apiKey, $data);
}

function enviarTemplatePaseQR($celular, $nombreFamiliar, $tokenUnico) {
    global $yc_apiKey, $yc_url, $yc_from;

    $data = [
        "from" => $yc_from,
        "to" => prepararCelularY($celular),
        "type" => "template",
        "template" => [
            "name" => "pase_qr",
            "language" => ["code" => "es"],
            "components" => [
                ["type" => "body", "parameters" => [
                    ["type" => "text", "text" => $nombreFamiliar]
                ]],
                ["type" => "button", "sub_type" => "url", "index" => 0, "parameters" => [
                    ["type" => "text", "text" => $tokenUnico]
                ]]
            ]
        ]
    ];
    return ejecutarCurlYCloud($yc_url, $yc_apiKey, $data);
}

// --- SECCIÓN 2: TALLER (DR. MOTORS) ---

function enviarConfirmacionCitaTaller($celular, $nombre, $servicio, $fecha, $hora, $precio) {
    global $yc_apiKey, $yc_url, $yc_from;

    $data = [
        "from" => $yc_from,
        "to" => prepararCelularY($celular),
        "type" => "template",
        "template" => [
            "name" => "confirmar_cita_taller_v3",
            "language" => ["code" => "es"],
            "components" => [
                ["type" => "body", "parameters" => [
                    ["type" => "text", "text" => $nombre],
                    ["type" => "text", "text" => $servicio],
                    ["type" => "text", "text" => $fecha],
                    ["type" => "text", "text" => $hora],
                    ["type" => "text", "text" => $precio]
                ]]
            ]
        ]
    ];
    return ejecutarCurlYCloud($yc_url, $yc_apiKey, $data);
}

function enviarPaseQRTaller($celular, $nombreCliente, $placa, $token) {
    global $yc_apiKey, $yc_url, $yc_from;

    $data = [
        "from" => $yc_from,
        "to" => prepararCelularY($celular),
        "type" => "template",
        "template" => [
            "name" => "confirmacion_exitosa_taller_v2",
            "language" => ["code" => "es"],
            "components" => [
                ["type" => "body", "parameters" => [
                    ["type" => "text", "text" => $nombreCliente]
                ]],
                ["type" => "button", "sub_type" => "url", "index" => 0, "parameters" => [
                    ["type" => "text", "text" => $token]
                ]]
            ]
        ]
    ];
    return ejecutarCurlYCloud($yc_url, $yc_apiKey, $data);
}

function enviarTemplateSeguimientoTaller($celular, $nombre, $token) {
    global $yc_apiKey, $yc_url, $yc_from;
    $variableBoton = "?t=" . $token;

    $data = [
        "from" => $yc_from,
        "to" => prepararCelularY($celular),
        "type" => "template",
        "template" => [
            "name" => "seguimiento_inspeccion_taller_v2", 
            "language" => ["code" => "es"],
            "components" => [
                ["type" => "body", "parameters" => [
                    ["type" => "text", "text" => $nombre]
                ]],
                ["type" => "button", "sub_type" => "url", "index" => 0, "parameters" => [
                    ["type" => "text", "text" => $variableBoton]
                ]]
            ]
        ]
    ];
    return ejecutarCurlYCloud($yc_url, $yc_apiKey, $data);
}

function enviarPresupuestoCorrectivoTaller($celular, $nombre, $placa, $cantidad, $id_cita, $token) {
    global $yc_apiKey, $yc_url, $yc_from;
    $variableBoton = "?id=" . $id_cita . "&tk=" . $token;

    $data = [
        "from" => $yc_from,
        "to" => prepararCelularY($celular),
        "type" => "template",
        "template" => [
            "name" => "presupuesto_correctivo_taller_v3", 
            "language" => ["code" => "es"],
            "components" => [
                ["type" => "body", "parameters" => [
                    ["type" => "text", "text" => $nombre],
                    ["type" => "text", "text" => $placa],
                    ["type" => "text", "text" => $cantidad]
                ]],
                ["type" => "button", "sub_type" => "url", "index" => 0, "parameters" => [
                    ["type" => "text", "text" => $variableBoton]
                ]]
            ]
        ]
    ];
    return ejecutarCurlYCloud($yc_url, $yc_apiKey, $data);
}

function enviarTemplateCancelacion($celular, $nombre) {
    global $yc_apiKey, $yc_url, $yc_from;

    $data = [
        "from" => $yc_from,
        "to" => prepararCelularY($celular),
        "type" => "template",
        "template" => [
            "name" => "cancelacion_cita_taller_v2",
            "language" => ["code" => "es"],
            "components" => [
                ["type" => "body", "parameters" => [
                    ["type" => "text", "text" => $nombre]
                ]]
            ]
        ]
    ];
    return ejecutarCurlYCloud($yc_url, $yc_apiKey, $data);
}

function enviarTemplateEntregaVehiculo($celular, $nombre, $placa, $id_cita, $token) {
    global $yc_apiKey, $yc_url, $yc_from;
    $variableBoton = "?id_cita=" . $id_cita . "&t=" . $token;

    $data = [
        "from" => $yc_from,
        "to" => prepararCelularY($celular),
        "type" => "template",
        "template" => [
            "name" => "entrega_vehiculo_taller_v2", 
            "language" => ["code" => "es"],
            "components" => [
                ["type" => "body", "parameters" => [
                    ["type" => "text", "text" => $nombre],
                    ["type" => "text", "text" => $placa]
                ]],
                ["type" => "button", "sub_type" => "url", "index" => 0, "parameters" => [
                    ["type" => "text", "text" => $variableBoton]
                ]]
            ]
        ]
    ];
    return ejecutarCurlYCloud($yc_url, $yc_apiKey, $data);
}

// Utilidades

function prepararCelularY($celular) {
    $celular = preg_replace('/[^0-9]/', '', $celular);
    if (strlen($celular) == 9) $celular = "51" . $celular;
    if (strpos($celular, '+') !== 0) $celular = "+" . $celular;
    return $celular;
}

function ejecutarCurlYCloud($url, $apiKey, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-Key: $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $resultado = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200 && $httpCode != 201) {
        $error_log = "[" . date('Y-m-d H:i:s') . "] Código: $httpCode | Respuesta: $resultado" . PHP_EOL;
        file_put_contents("error_api_ycloud.txt", $error_log, FILE_APPEND);
    }

    return ($httpCode == 200 || $httpCode == 201); 
}