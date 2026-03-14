<?php
// Poo/webhook_whapi.php
ini_set('display_errors', 0); 
error_reporting(E_ALL);

session_start();
require_once "Conexion.php"; 
require_once "../libs/class.php"; 
$db = new Conexion();

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$log_file = 'debug_registro.txt';

if (!empty($json)) {
    $temp_data = json_decode($json, true);
    if (isset($temp_data['messages'][0])) {
        $remitente = $temp_data['messages'][0]['chat_id'];
        $contenido = $temp_data['messages'][0]['text']['body'] ?? 'No es texto';
        file_put_contents($log_file, "[" . date('H:i:s') . "] ENTRADA: De $remitente | Mensaje: $contenido\n", FILE_APPEND);
    }
}

if (isset($data['messages'])) {
    foreach ($data['messages'] as $msg) {
        if ($msg['from_me'] || $msg['type'] !== 'text') continue; 

        $id_whapi_completo = $msg['chat_id']; 
        $texto = trim($msg['text']['body']); 
        $texto_mayus = strtoupper($texto);
        $id_numerico = preg_replace('/[^0-9]/', '', $id_whapi_completo);

        // 🚀 LÓGICA 1: ¿EL CLIENTE ESCRIBIÓ SU CELULAR (9 dígitos)?
        // Si el texto tiene exactamente 9 números, intentamos confirmar de una vez.
        if (preg_match('/^[0-9]{9}$/', $texto)) {
            $sql = "SELECT ci.id_cita, ci.token_confirmacion, c.nombre_completo 
                    FROM citas ci 
                    JOIN clientes c ON ci.id_cliente = c.id_cliente 
                    WHERE REPLACE(REPLACE(c.telefono, ' ', ''), '-', '') LIKE '%$texto' 
                    AND ci.estado = 'EN_CONFIRMACION' 
                    ORDER BY ci.id_cita DESC LIMIT 1";
            
            $res = $db->ejecutar($sql);
            $cita = $db->recorrer($res);

            if ($cita) {
                $id_cita = $cita['id_cita'];
                $db->ejecutar("UPDATE citas SET estado = 'PENDIENTE' WHERE id_cita = '$id_cita'");
                
                $link_qr = "https://www.jambosystems.com/taller/ver_qr.php?t=" . $cita['token_confirmacion'];
                $msg_exito = "¡Perfecto, *{$cita['nombre_completo']}*! Ya vinculé tu cita. ✅ Confirmada.\n\nPresenta este Pase QR al llegar:\n" . $link_qr;
                
                enviarNotificacionWhapi($id_whapi_completo, $msg_exito);
                file_put_contents($log_file, "[" . date('H:i:s') . "] Vinculación Manual Exitosa: $texto\n", FILE_APPEND);
                continue; 
            }
        }

        // 🚀 LÓGICA 2: ¿EL CLIENTE ESCRIBIÓ "SI" o "NO"?
        if ($texto_mayus == 'SI' || $texto_mayus == 'SÍ' || $texto_mayus == 'NO') {
            
            // Buscamos por los últimos 9 dígitos del ID que nos manda WhatsApp
            $numero_busqueda = substr($id_numerico, -9); 

            $sql = "SELECT ci.id_cita, ci.token_confirmacion, ci.estado, c.nombre_completo 
                    FROM citas ci 
                    JOIN clientes c ON ci.id_cliente = c.id_cliente 
                    WHERE REPLACE(REPLACE(c.telefono, ' ', ''), '-', '') LIKE '%$numero_busqueda' 
                    AND ci.estado IN ('EN_CONFIRMACION', 'PENDIENTE') 
                    ORDER BY ci.id_cita DESC LIMIT 1";
            
            $res = $db->ejecutar($sql);
            $cita = $db->recorrer($res);

            if ($cita) {
                $id_cita = $cita['id_cita'];
                if ($texto_mayus == 'NO') {
                    $db->ejecutar("UPDATE citas SET estado = 'CANCELADO' WHERE id_cita = '$id_cita'");
                    enviarNotificacionWhapi($id_whapi_completo, "Entendido. ⚠️ Cita liberada. ¡Saludos de Dr. Motors!");
                } else {
                    if ($cita['estado'] == 'EN_CONFIRMACION') {
                        $db->ejecutar("UPDATE citas SET estado = 'PENDIENTE' WHERE id_cita = '$id_cita'");
                    }
                    $link_qr = "https://www.jambosystems.com/taller/ver_qr.php?t=" . $cita['token_confirmacion'];
                    $msg_qr = "¡Excelente, *{$cita['nombre_completo']}*! ✅ Cita confirmada. Pase QR:\n" . $link_qr;
                    enviarNotificacionWhapi($id_whapi_completo, $msg_qr);
                }
                file_put_contents($log_file, "[" . date('H:i:s') . "] Confirmado por SI: $numero_busqueda\n", FILE_APPEND);
            } else {
                // ❌ FALLBACK: No encontramos al cliente (Probable ID de Meta)
                if (strlen($id_numerico) > 12 && ($texto_mayus == 'SI' || $texto_mayus == 'SÍ')) {
                    $msg_pedir = "¡Hola! 👋 Soy el asistente de *Dr. Motors*.\n\nNo logro ubicar tu cita automáticamente porque vienes de un anuncio.\n\n📌 Por favor, **escribe solo tu número de celular** (9 dígitos) para enviarte tu Pase QR ahora mismo.";
                    enviarNotificacionWhapi($id_whapi_completo, $msg_pedir);
                    file_put_contents($log_file, "[" . date('H:i:s') . "] Auxilio enviado a ID Meta: $id_numerico\n", FILE_APPEND);
                }
            }
        }
    }
}