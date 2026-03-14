<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    session_start();
    require_once "Conexion.php";
    require_once "../libs/classY.php"; 
    $db = new Conexion();

    if (!isset($_POST['id_cita'])) { throw new Exception("ID de cita no recibido."); }
    $id_cita = mysqli_real_escape_string($db->conexion, $_POST['id_cita']);

    // 🚀 1. OBTENER DATOS Y EL TIPO DE RAÍZ (MANTENIMIENTO O DIAGNOSTICO)
    $sql_c = "SELECT cl.telefono, cl.nombre_completo, ve.placa, s.tipo_raiz 
              FROM citas ci 
              JOIN clientes cl ON ci.id_cliente = cl.id_cliente 
              JOIN vehiculos ve ON ci.id_vehiculo = ve.id_vehiculo
              JOIN servicios s ON ci.id_servicio = s.id_servicio
              WHERE ci.id_cita = '$id_cita' LIMIT 1";
    
    $res_c = $db->ejecutar($sql_c);
    if ($db->contar($res_c) == 0) { throw new Exception("No se encontró la cita."); }
    $data_c = $db->recorrer($res_c);

    // 🚀 2. DEFINIR URL DE RETORNO SEGÚN TIPO_RAIZ
    $tipo_raiz = $data_c['tipo_raiz'] ?? 'MANTENIMIENTO';
    $url_retorno = ($tipo_raiz === 'DIAGNOSTICO') ? "ejecucion_diagnostico.php" : "gestion_taller.php";

    // 3. CONTAR HALLAZGOS PENDIENTES
    $sql_h = "SELECT COUNT(*) as total FROM orden_hallazgos 
              WHERE id_cita = '$id_cita' AND estado_aprobacion = 'PENDIENTE'";
    $res_h = $db->ejecutar($sql_h);
    $data_h = $db->recorrer($res_h);
    $total_items = $data_h['total'];

    if ($total_items == 0) { throw new Exception("No hay hallazgos nuevos para notificar."); }

    // 4. Generar token y actualizar estados
    $token = bin2hex(random_bytes(8));
    $db->ejecutar("UPDATE citas SET token_aprobacion = '$token' WHERE id_cita = '$id_cita'");
    $db->ejecutar("UPDATE orden_hallazgos SET estado_aprobacion = 'ESPERANDO_CONFIRMACION' 
                   WHERE id_cita = '$id_cita' AND estado_aprobacion = 'PENDIENTE'");

    // 🚀 5. ENVIAR WHATSAPP (YCloud)
    $envio = enviarPresupuestoCorrectivoTaller(
        $data_c['telefono'], 
        $data_c['nombre_completo'], 
        $data_c['placa'], 
        $total_items, 
        $id_cita, 
        $token
    );

    if ($envio) {
        // Sinceramente, enviamos la URL para que el JS sepa a dónde redirigir
        echo json_encode([
            'status' => 'success', 
            'message' => 'Presupuesto enviado al cliente.',
            'url' => $url_retorno // 👈 Esto es clave
        ]);
    } else {
        throw new Exception("Error al conectar con la API de YCloud.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}