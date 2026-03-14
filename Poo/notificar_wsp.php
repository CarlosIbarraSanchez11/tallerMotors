<?php
require_once "Conexion.php";
require_once "../libs/class.php"; // Aquí vive tu función enviarNotificacionWhapi
$db = new Conexion();

try {
    // ESTA ES LA CONSULTA QUE BUSCABAS:
    // Filtra citas EN_CONFIRMACION que faltan entre 11 y 12 horas para iniciar
    $sql = "SELECT ci.id_cita, ci.fecha_cita, ci.hora_inicio, 
               c.nombre_completo, c.telefono, s.nombre_servicio 
        FROM citas ci
        JOIN clientes c ON ci.id_cliente = c.id_cliente
        JOIN servicios s ON ci.id_servicio = s.id_servicio
        WHERE ci.estado = 'EN_CONFIRMACION' 
        AND ci.wsp_notificado = 0
        AND CONCAT(ci.fecha_cita, ' ', ci.hora_inicio) >= NOW()
        AND CONCAT(ci.fecha_cita, ' ', ci.hora_inicio) <= DATE_ADD(NOW(), INTERVAL 1 HOUR)";

    $res = $db->ejecutar($sql);

    while ($u = $db->recorrer($res)) {
        $celular = $u['telefono']; 
        $nombre = $u['nombre_completo'];
        $servicio = $u['nombre_servicio'];
        $hora = date('H:i', strtotime($u['hora_inicio']));

        // Mensaje que pide confirmación
        $mensaje = "¡Hola *{$nombre}*! 👋 Soy el asistente de *Dr. Motors*.\n\n";
        $mensaje .= "Tienes una reserva para el servicio de *{$servicio}* en 12 horas (a las *{$hora} hrs*).\n\n";
        $mensaje .= "¿Confirmas tu asistencia para separar tu box de atención?\n\n";
        $mensaje .= "✅ Responde *SI* para confirmar y generar tu Pase QR.\n";
        $mensaje .= "❌ Responde *NO* para liberar el horario.";

        // Usamos tu función real de libs/class.php
        $envio = enviarNotificacionWhapi($celular, $mensaje);

        if ($envio) {
            // Marcamos como notificado para que el Robot no le vuelva a escribir en la siguiente hora
            $db->ejecutar("UPDATE citas SET wsp_notificado = 1 WHERE id_cita = '{$u['id_cita']}'");
            echo "Notificación enviada a {$nombre}\n";
        }
    }

} catch (Exception $e) {
    error_log("Error en cron de notificaciones: " . $e->getMessage());
}