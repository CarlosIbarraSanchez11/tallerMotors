<?php
// Poo/webhook_ycloud.php
require_once "Conexion.php"; 
require_once "../libs/classY.php"; 
$db = new Conexion();

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!empty($json)) {
    file_put_contents("debug_ycloud.txt", "[" . date('H:i:s') . "] --- PROCESANDO ---" . PHP_EOL, FILE_APPEND);
}

// 🚀 AJUSTE SEGÚN TU LOG: Usamos 'type' y el valor exacto del JSON
if (isset($data['type']) && $data['type'] === 'whatsapp.inbound_message.received') {
    
    // Los datos viven dentro de 'whatsappInboundMessage'
    $msg = $data['whatsappInboundMessage'];
    
    $remitente = $msg['from']; // +51921872052
    
    // Capturamos el texto del botón
    $texto_recibido = $msg['button']['text'] ?? '';

    if (isset($msg['button']['text'])) {
        // Si hizo clic en el botón
        $texto_recibido = $msg['button']['text'];
    } elseif (isset($msg['text']['body'])) {
        // Si el usuario escribió el mensaje manualmente
        $texto_recibido = $msg['text']['body'];
    }
    
    // Normalizamos para evitar errores de tildes
    $texto_limpio = strtoupper(str_replace(['á', 'é', 'í', 'ó', 'ú', 'SÍ'], ['A', 'E', 'I', 'O', 'U', 'SI'], $texto_recibido));

    $num_solo = preg_replace('/[^0-9]/', '', $remitente);
    $num_9 = substr($num_solo, -9); 

    file_put_contents("debug_ycloud.txt", "Paso: Cliente $num_9 envió: '$texto_limpio'" . PHP_EOL, FILE_APPEND);

    // 🔍 BUSQUEDA EN DB
    $sql = "SELECT ci.id_cita, ci.token_confirmacion, c.nombre_completo, v.placa 
            FROM citas ci 
            JOIN clientes c ON ci.id_cliente = c.id_cliente 
            JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo 
            WHERE REPLACE(c.telefono, ' ', '') LIKE '%$num_9' 
            AND ci.estado = 'EN_CONFIRMACION' 
            ORDER BY ci.id_cita DESC LIMIT 1";
    
    $res = $db->ejecutar($sql);
    $cita = $db->recorrer($res);

    if ($cita) {
        $id_cita = $cita['id_cita'];

        // --- CASO 1: SI (Confirmar) ---
        if (strpos($texto_limpio, 'SI') !== false) {
            file_put_contents("debug_ycloud.txt", "Paso: Confirmando cita $id_cita" . PHP_EOL, FILE_APPEND);
            
            $db->ejecutar("UPDATE citas SET estado = 'PENDIENTE' WHERE id_cita = '$id_cita'");
            
            // Disparamos el Pase QR
            $envio = enviarPaseQRTaller($remitente, $cita['nombre_completo'], $cita['placa'], $cita['token_confirmacion']);
            
            if($envio) {
                file_put_contents("debug_ycloud.txt", "✅ FINAL: QR enviado." . PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents("debug_ycloud.txt", "❌ FINAL: Error en enviarPaseQRTaller. Revisa error_api_ycloud.txt" . PHP_EOL, FILE_APPEND);
            }
        } 
        // --- CASO 2: NO (Cancelar) ---
        elseif (strpos($texto_limpio, 'NO') !== false) {
            file_put_contents("debug_ycloud.txt", "Paso: Cancelando cita $id_cita" . PHP_EOL, FILE_APPEND);
            
            $db->ejecutar("UPDATE citas SET estado = 'CANCELADO' WHERE id_cita = '$id_cita'");
            
            // Disparamos el Template de Cancelación
            enviarTemplateCancelacion($remitente, $cita['nombre_completo']);
            
            file_put_contents("debug_ycloud.txt", "🚫 FINAL: Cancelación enviada." . PHP_EOL, FILE_APPEND);
        }
    } else {
        file_put_contents("debug_ycloud.txt", "⚠️ No se halló cita EN_CONFIRMACION para $num_9." . PHP_EOL, FILE_APPEND);
    }
} else {
    file_put_contents("debug_ycloud.txt", "❌ Estructura de JSON no reconocida o no es un mensaje entrante." . PHP_EOL, FILE_APPEND);
}

http_response_code(200);