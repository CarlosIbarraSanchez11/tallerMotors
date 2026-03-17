<?php
// 1. Configuración de errores y sesión
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 📂 EL TRUCO MÁGICO: Aquí es donde PHP busca la carpeta 'vendor' que ignoramos en Git
require __DIR__ . '/../vendor/autoload.php'; 
use Google\Cloud\Storage\StorageClient;

require_once "Conexion.php";
$db = new Conexion();

if ($_POST) {
    // Recibimos los datos básicos
    $id_cita     = $_POST['id_cita'];
    $km          = $_POST['km_ingreso'];
    $combustible = $_POST['combustible'];
    $obs         = mysqli_real_escape_string($db->conexion, $_POST['observaciones']);
    $id_tecnico_final = isset($_POST['tecnicos_finales'][0]) ? $_POST['tecnicos_finales'][0] : NULL;

    if (!$id_tecnico_final) {
        die("Error: No se ha asignado un técnico para esta orden.");
    }

    // --- ☁️ CONFIGURACIÓN DE GOOGLE CLOUD STORAGE ---
    $nombreBucket = 'taller-dr-motors-storage';
    // Sinceramente, en Cloud Run no necesitas poner llaves JSON si diste los permisos de IAM que vimos
    $storage = new StorageClient();
    $bucket = $storage->bucket($nombreBucket);
    $carpetaDestino = "img/ordenes/"; 

    $fotos = ['foto_frontal' => NULL, 'foto_posterior' => NULL, 'foto_tablero' => NULL];

    foreach ($fotos as $key => $val) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
            $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
            $nombre_archivo = "OT_" . $id_cita . "_" . $key . "_" . time() . "." . $ext;
            
            // 🚀 SUBIDA DIRECTA AL BUCKET
            try {
                $fileStream = fopen($_FILES[$key]['tmp_name'], 'r');
                $bucket->upload($fileStream, [
                    'name' => $carpetaDestino . $nombre_archivo
                ]);
                $fotos[$key] = $nombre_archivo; // Guardamos el nombre para la DB
            } catch (Exception $e) {
                // Sinceramente, si falla la subida, lo registramos pero seguimos para no frenar el taller
                error_log("Fallo subida a Storage: " . $e->getMessage());
            }
        }
    }

    // --- INICIO DE TRANSACCIÓN ---
    $db->conexion->begin_transaction();

    try {
        // ACTUALIZAMOS LA CITA
        $sql_update_cita = "UPDATE citas SET 
                            id_tecnico = '$id_tecnico_final', 
                            estado = 'EN PROCESO' 
                            WHERE id_cita = '$id_cita'";
        $db->ejecutar($sql_update_cita);

        // INSERTAMOS LA ORDEN MAESTRA
        $sql_orden = "INSERT INTO ordenes_trabajo (
            id_cita, km_ingreso, nivel_combustible, observaciones_recepcion, 
            foto_frontal, foto_posterior, foto_tablero, estado_orden
        ) VALUES (
            '$id_cita', '$km', '$combustible', '$obs', 
            '{$fotos['foto_frontal']}', '{$fotos['foto_posterior']}', '{$fotos['foto_tablero']}', 'EN_PROCESO'
        )";
        $db->ejecutar($sql_orden);
        $id_orden = $db->conexion->insert_id;

        // GUARDAMOS EL CHECKLIST
        if (isset($_POST['check'])) {
            foreach ($_POST['check'] as $item => $estado) {
                $item_esc = mysqli_real_escape_string($db->conexion, $item);
                $estado_esc = mysqli_real_escape_string($db->conexion, $estado);
                $sql_check = "INSERT INTO orden_checklist (id_orden, item_nombre, estado_item) 
                              VALUES ('$id_orden', '$item_esc', '$estado_esc')";
                $db->ejecutar($sql_check);
            }
        }

        // REGISTRAMOS AL TÉCNICO
        $sql_equipo = "INSERT INTO orden_tecnicos (id_orden, id_tecnico) 
                       VALUES ('$id_orden', '$id_tecnico_final')";
        $db->ejecutar($sql_equipo);

        $resFecha = $db->ejecutar("SELECT fecha_cita FROM citas WHERE id_cita = '$id_cita'");
        $datosCita = $db->recorrer($resFecha);
        $fecha_destino = $datosCita['fecha_cita'];

        $db->conexion->commit();
        header("Location: ../citas.php?fecha=" . $fecha_destino);
        exit();

    } catch (Exception $e) {
        $db->conexion->rollback();
        header("Location: ../citas.php?res=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
}
?>