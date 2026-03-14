<?php
// libs/class.php

function enviarNotificacionWhapi($celular, $mensaje, $url_imagen = null) {
    // 1. 🛡️ PROTECCIÓN TOTAL: Saca el token del "aire" (Environment)
    // Sinceramente, si no hay token en el servidor o en tu .env, no hace nada.
    $token = getenv('WHAPI_TOKEN') ?: 'TOKEN_NO_CONFIGURADO'; 
    
    // 2. Limpieza profunda del número
    $celular = preg_replace('/[^0-9]/', '', $celular); 
    
    // Si el número tiene 9 dígitos (Perú), le ponemos el 51. 
    if (strlen($celular) == 9) {
        $celular = "51" . $celular;
    } elseif (strlen($celular) > 11) {
        $celular = substr($celular, -11);
    }

    $id_destino = $celular . "@s.whatsapp.net";

    // 3. Configurar Endpoint
    if ($url_imagen) {
        $url = "https://gate.whapi.cloud/messages/image";
        $data = ["to" => $id_destino, "media" => $url_imagen, "caption" => $mensaje];
    } else {
        $url = "https://gate.whapi.cloud/messages/text";
        $data = ["to" => $id_destino, "body" => $mensaje];
    }

    // 4. Enviar Petición
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $resultado = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code < 200 || $http_code > 299) {
        // Sinceramente, guardamos el error pero sin mostrar el Token en el log
        error_log("Whapi Error ($http_code): " . $resultado);
    }
    
    return $resultado;
}