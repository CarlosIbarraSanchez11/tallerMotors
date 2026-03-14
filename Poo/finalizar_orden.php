<?php
// Activamos errores para diagnóstico rápido
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "Conexion.php"; 
$db = new Conexion();

if (isset($_GET['id_cita'])) {
    $id_cita = mysqli_real_escape_string($db->conexion, $_GET['id_cita']);

    // 🚀 1. OBTENEMOS LA FECHA DE LA CITA ANTES DE ACTUALIZAR NADA
    $sql_fecha = "SELECT fecha_cita FROM citas WHERE id_cita = '$id_cita' LIMIT 1";
    $res_f = $db->ejecutar($sql_fecha);
    $datos_f = $db->recorrer($res_f);
    $fecha_retorno = $datos_f['fecha_cita'] ?? date('Y-m-d'); // Si falla, usamos hoy por seguridad

    // 2. Actualizamos la CITA a 'LAVADO'
    $sql_cita = "UPDATE citas SET estado = 'LAVADO' WHERE id_cita = '$id_cita'";
    $db->ejecutar($sql_cita);

    // 3. Actualizamos la ORDEN DE TRABAJO a 'FINALIZADO' 
    $sql_orden = "UPDATE ordenes_trabajo SET 
                    estado_orden = 'FINALIZADO' 
                  WHERE id_cita = '$id_cita'";
    
    if ($db->ejecutar($sql_orden)) {
        // 🚀 4. REDIRECCIÓN INTELIGENTE
        // Sinceramente, enviamos la fecha de vuelta para que el filtro de citas.php la reconozca
        header("Location: ../citas.php?fecha=$fecha_retorno&res=mantenimiento_concluido");
        exit();
    } else {
        echo "Error en la DB: " . mysqli_error($db->conexion);
    }
} else {
    echo "ID de cita no recibido";
}