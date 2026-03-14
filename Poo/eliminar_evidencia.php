<?php
session_start();
// Activamos errores a tope para ver el culpable
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "Conexion.php";
$db = new Conexion();

if (isset($_GET['id']) && isset($_GET['id_cita'])) {
    $id_evidencia = mysqli_real_escape_string($db->conexion, $_GET['id']);
    $id_cita = mysqli_real_escape_string($db->conexion, $_GET['id_cita']);

    // 1. Buscamos el nombre del archivo
    $sql_foto = "SELECT foto FROM orden_evidencias WHERE id_evidencia = '$id_evidencia' LIMIT 1";
    $res_foto = $db->ejecutar($sql_foto);
    $datos_foto = $db->recorrer($res_foto);

    if ($datos_foto) {
        $nombre_archivo = $datos_foto['foto'];
        $ruta_completa = "../img/evidencias/" . $nombre_archivo;

        // 2. Borramos el archivo físico
        if (!empty($nombre_archivo) && file_exists($ruta_completa)) {
            if (!unlink($ruta_completa)) {
                // Si no puede borrar el archivo, te avisará (posibles permisos)
                echo "Aviso: No se pudo borrar el archivo físico, pero intentaremos borrar el registro.<br>";
            }
        }

        // 3. Borramos el registro de la tabla
        $sql_delete = "DELETE FROM orden_evidencias WHERE id_evidencia = '$id_evidencia'";
        
        if ($db->ejecutar($sql_delete)) {
            header("Location: ../gestion_taller.php?id_cita=$id_cita&res=eliminado_ok");
            exit();
        } else {
            // Si el DELETE falla, aquí verás por qué (ej: error de sintaxis o FK)
            die("Error SQL al eliminar: " . mysqli_error($db->conexion));
        }
    } else {
        // Si entra aquí, es que el ID que pasaste no existe en la tabla orden_evidencias
        die("Error: No se encontró ninguna evidencia con el ID: " . $id_evidencia);
    }
} else {
    die("Error: Faltan parámetros en la URL (id o id_cita).");
}
?>