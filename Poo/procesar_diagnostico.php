<?php
header('Content-Type: application/json');
ini_set('display_errors', 0); // No mostrar errores HTML que rompen el JSON
error_reporting(E_ALL);

try {
    session_start();
    require_once "Conexion.php";
    require_once "../libs/class.php"; // Verifica que esta ruta sea correcta para Whapi
    $db = new Conexion();

    if (!isset($_POST['id_cita'])) {
        throw new Exception("ID de cita no recibido.");
    }

    $id_cita = $_POST['id_cita'];

    // 1. GUARDAR RESULTADOS DEL CHECKLIST (Bucle de los pasos realizados)
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'paso_') === 0) {
            $id_paso = str_replace('paso_', '', $key);
            $obs = isset($_POST['obs_'.$id_paso]) ? $db->conexion->real_escape_string($_POST['obs_'.$id_paso]) : '';
            
            $sql_check = "INSERT INTO checklist_ejecucion (id_cita, id_paso, estado, observacion) 
                          VALUES ('$id_cita', '$id_paso', '$value', '$obs')";
            $db->ejecutar($sql_check);
        }
    }

    // 2. CONTAR SI HAY FALLAS (Para decidir el mensaje)
    $sql_h = "SELECT COUNT(*) as total FROM hallazgos WHERE id_cita = '$id_cita'";
    $conteo = $db->recorrer($db->ejecutar($sql_h));

    // 3. DATOS PARA WHATSAPP
    $sql_c = "SELECT cl.telefono, cl.nombre_completo, ve.placa 
              FROM citas ci 
              JOIN clientes cl ON ci.id_cliente = cl.id_cliente 
              JOIN vehiculos ve ON ci.id_vehiculo = ve.id_vehiculo
              WHERE ci.id_cita = '$id_cita'";
    $data_c = $db->recorrer($db->ejecutar($sql_c));

    // 4. GENERAR TOKEN Y DEFINIR ESTADO
    $token = bin2hex(random_bytes(8));
    
    if ($conteo['total'] == 0) {
        $nuevo_estado = 'LAVADO';
        $mensaje = "✅ *Dr. Motors:* Hola {$data_c['nombre_completo']}, diagnóstico terminado de la unidad *{$data_c['placa']}*. ¡Todo está perfecto! Pasamos a lavado.";
    } else {
        $nuevo_estado = 'ESPERANDO_APROBACION';
        $link = "https://www.jambosystems.com/taller/aprobar_presupuesto.php?id=$id_cita&tk=$token";
        $mensaje = "⚠️ *Dr. Motors:* Hola {$data_c['nombre_completo']}, diagnóstico terminado de la unidad *{$data_c['placa']}*. Detectamos {$conteo['total']} hallazgos. Revisa y aprueba aquí: $link";
    }

    // 5. ACTUALIZAR CITA
    $sql_update = "UPDATE citas SET estado = '$nuevo_estado', token_aprobacion = '$token' WHERE id_cita = '$id_cita'";
    $db->ejecutar($sql_update);

    // 6. ENVIAR WHATSAPP (Solo si tienes conexión)
    @enviarNotificacionWhapi($data_c['telefono'], $mensaje);

    echo json_encode([
        'status' => 'success', 
        'message' => ($conteo['total'] == 0) ? 'Vehículo enviado a Lavado.' : 'Reporte enviado al cliente.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}