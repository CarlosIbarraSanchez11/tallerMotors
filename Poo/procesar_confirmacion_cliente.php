<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once "Conexion.php";
    $db = new Conexion();

    if (!isset($_POST['id_cita'])) {
        throw new Exception("Datos insuficientes.");
    }

    $id_cita = mysqli_real_escape_string($db->conexion, $_POST['id_cita']);
    $aprobados_ids = isset($_POST['hallazgos']) ? $_POST['hallazgos'] : [];

    // 🚀 1. OBTENER EL TÉCNICO DE LA CITA
    // Sinceramente, esto es lo que faltaba para evitar el NULL
    $sql_cita_info = "SELECT id_tecnico FROM citas WHERE id_cita = '$id_cita'";
    $res_cita_info = $db->ejecutar($sql_cita_info);
    $info_c = $db->recorrer($res_cita_info);
    $id_tecnico_asignado = $info_c['id_tecnico'] ?? 'NULL';

    // 2. OBTENEMOS LOS HALLAZGOS ENVIADOS
    $sql_h = "SELECT * FROM orden_hallazgos 
              WHERE id_cita = '$id_cita' 
              AND estado_aprobacion = 'ESPERANDO_CONFIRMACION'";
    $res_h = $db->ejecutar($sql_h);

    while ($h = $db->recorrer($res_h)) {
        $id_h = $h['id_hallazgo'];
        
        if (in_array($id_h, $aprobados_ids)) {
            // --- APROBADO ---
            $db->ejecutar("UPDATE orden_hallazgos SET estado_aprobacion = 'APROBADO' WHERE id_hallazgo = '$id_h'");

            if (!empty($h['id_producto']) && $h['id_producto'] != 'NULL') {
                $id_prod = $h['id_producto'];
                $cant    = $h['cantidad'];
                $precio_real = (float)$h['precio_producto']; 

                // 🚀 3. INSERTAR PEDIDO CON EL ID DEL TÉCNICO
                // Si el técnico es NULL, lo pasamos sin comillas, si no, con comillas.
                $val_tecnico = ($id_tecnico_asignado == 'NULL') ? "NULL" : "'$id_tecnico_asignado'";

                $sql_pedido = "INSERT INTO pedidos_repuestos (
                                    id_cita, 
                                    id_producto, 
                                    tipo_procedencia, 
                                    cantidad, 
                                    precio_unidad, 
                                    estado_pedido, 
                                    id_mecanico_pide, 
                                    fecha_pedido
                                ) VALUES (
                                    '$id_cita', 
                                    '$id_prod', 
                                    'HALLAZGO', 
                                    '$cant', 
                                    '$precio_real', 
                                    'SOLICITADO POR CLIENTE', 
                                    $val_tecnico, 
                                    NOW()
                                )";
                $db->ejecutar($sql_pedido);
            }
        } else {
            // --- RECHAZADO ---
            $db->ejecutar("UPDATE orden_hallazgos SET estado_aprobacion = 'RECHAZADO' WHERE id_hallazgo = '$id_h'");
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => "Tu respuesta ha sido registrada. Muchas gracias."
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}