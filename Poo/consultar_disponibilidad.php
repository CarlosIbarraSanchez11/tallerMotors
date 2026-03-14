<?php
// Poo/consultar_disponibilidad.php
ob_start();
require_once "Conexion.php";
$db = new Conexion();

$ocupados = [];

// Capturamos los datos y aseguramos que sean números
$id_t   = intval($_POST['id_tecnico'] ?? 0);
$fecha  = mysqli_real_escape_string($db->conexion, $_POST['fecha'] ?? '');
$id_tal = intval($_POST['id_taller'] ?? 0);

if ($id_t > 0 && !empty($fecha) && $id_tal > 0) {
    
    // 🚀 SQL Blindado: Filtramos estrictamente por el taller actual
    $sql = "SELECT hora_inicio, hora_fin FROM citas 
            WHERE id_tecnico = '$id_t' 
            AND fecha_cita = '$fecha' 
            AND id_taller = '$id_tal' 
            AND estado NOT IN ('CANCELADA', 'CANCELADO')";
    
    $res = $db->ejecutar($sql);

    if ($res) {
        while ($cita = $db->recorrer($res)) {
            $inicio = strtotime($cita['hora_inicio']);
            $fin    = strtotime($cita['hora_fin']);
            
            // Marcamos el rango (08:00 a 10:00 bloquea 08:00 y 09:00)
            for ($i = $inicio; $i < $fin; $i += 3600) {
                $ocupados[] = date("H:i", $i);
            }
        }
    }
}

ob_clean();
header('Content-Type: application/json');
echo json_encode(array_values(array_unique($ocupados)));
exit();