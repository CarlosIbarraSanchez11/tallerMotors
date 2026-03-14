<?php
header('Content-Type: application/json');
require_once "Conexion.php";
$db = new Conexion();

// Recibir el ID de la cita y el array de hallazgos seleccionados
$id_cita = isset($_POST['id_cita']) ? $_POST['id_cita'] : null;
$aprobados = isset($_POST['hallazgos']) ? $_POST['hallazgos'] : [];

if (!$id_cita) {
    echo json_encode(['status' => 'error', 'message' => 'ID de cita no proporcionado.']);
    exit;
}

try {
    // 1. Primero marcamos todos los hallazgos PENDIENTES de esta cita como RECHAZADOS
    // Esto asegura que si el cliente no marcó alguno, quede como rechazado.
    $sql_rechazar_todo = "UPDATE hallazgos SET estado_aprobacion = 'RECHAZADO' 
                          WHERE id_cita = '$id_cita' AND estado_aprobacion = 'PENDIENTE'";
    $db->ejecutar($sql_rechazar_todo);

    if (count($aprobados) > 0) {
        // 2. Si hay elementos seleccionados, los marcamos como APROBADOS
        $ids = implode(",", array_map('intval', $aprobados)); // Limpieza básica de IDs
        $sql_aprobar = "UPDATE hallazgos SET estado_aprobacion = 'APROBADO' 
                        WHERE id_hallazgo IN ($ids)";
        $db->ejecutar($sql_aprobar);
        
        // El vehículo entra a reparación
        $nuevo_estado = 'EN REPARACION';
        $mensaje = "Las reparaciones han sido autorizadas. Iniciamos los trabajos de inmediato.";
    } else {
        // 3. Si no seleccionó nada, el vehículo se va directo a lavado
        $nuevo_estado = 'LAVADO';
        $mensaje = "Has declinado las reparaciones. Tu vehículo pasará directamente a lavado.";
    }

    // 4. Actualizar el estado oficial de la cita en la tabla 'citas'
    $sql_update_cita = "UPDATE citas SET estado = '$nuevo_estado' WHERE id_cita = '$id_cita'";
    $db->ejecutar($sql_update_cita);

    // 5. Responder al frontend para que ejecute la redirección a la web principal
    echo json_encode([
        'status' => 'success', 
        'message' => $mensaje,
        'redirect' => 'https://drmotorsperu.com/'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>