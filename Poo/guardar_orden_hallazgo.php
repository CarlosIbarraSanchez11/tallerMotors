<?php
// Poo/guardar_orden_hallazgo.php
session_start();

// 📂 CARGAMOS LIBRERÍAS DE GOOGLE
require __DIR__ . '/../vendor/autoload.php'; 
use Google\Cloud\Storage\StorageClient;

require_once "Conexion.php";
$db = new Conexion();

// Reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_POST['id_orden']) || !isset($_POST['id_cita'])) {
        throw new Exception("Faltan datos de identificación de la orden.");
    }

    $id_orden = $_POST['id_orden'];
    $id_cita  = $_POST['id_cita'];
    $punto    = mysqli_real_escape_string($db->conexion, $_POST['punto_falla']);
    $desc     = mysqli_real_escape_string($db->conexion, $_POST['descripcion']);
    $id_prod  = !empty($_POST['id_producto']) ? $_POST['id_producto'] : "NULL";
    
    $p_venta  = !empty($_POST['precio_producto']) ? $_POST['precio_producto'] : 0;
    $p_obra   = !empty($_POST['precio_mano_obra']) ? $_POST['precio_mano_obra'] : 0;
    $cantidad = !empty($_POST['cantidad']) ? $_POST['cantidad'] : 1;

    // --- ☁️ CONFIGURACIÓN DE CLOUD STORAGE ---
    $nombreBucket = 'taller-dr-motors-storage';
    $storage = new StorageClient(); // Credenciales automáticas en Cloud Run
    $bucket = $storage->bucket($nombreBucket);
    $carpetaDestino = "img/hallazgos/"; // 👈 Tu nueva carpeta organizada

    // 1. GESTIÓN DE FOTO EN EL BUCKET
    $nombre_foto = null;
    if (isset($_FILES['foto_falla']) && $_FILES['foto_falla']['error'] === UPLOAD_ERR_OK) {
        
        $ext = pathinfo($_FILES['foto_falla']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "falla_" . $id_orden . "_" . time() . "." . $ext;
        
        // 🚀 SUBIDA DIRECTA AL BUCKET
        try {
            $fileStream = fopen($_FILES['foto_falla']['tmp_name'], 'r');
            $bucket->upload($fileStream, [
                'name' => $carpetaDestino . $nombre_foto
            ]);
        } catch (Exception $e) {
            throw new Exception("Error al subir al Bucket: " . $e->getMessage());
        }
    }

    // 2. INSERTAR EL HALLAZGO EN DB
    $sql = "INSERT INTO orden_hallazgos (
                id_orden, id_cita, punto_falla, descripcion, 
                id_producto, cantidad, precio_producto, precio_mano_obra, foto_evidencia
            ) VALUES (
                '$id_orden', '$id_cita', '$punto', '$desc', 
                $id_prod, '$cantidad', '$p_venta', '$p_obra', " . ($nombre_foto ? "'$nombre_foto'" : "NULL") . "
            )";

    if ($db->ejecutar($sql)) {
        
        // 3. LÓGICA DE REDIRECCIÓN
        $sql_tipo = "SELECT s.tipo_raiz 
                     FROM citas ci 
                     INNER JOIN servicios s ON ci.id_servicio = s.id_servicio 
                     WHERE ci.id_cita = '$id_cita' LIMIT 1";
        
        $res_tipo = $db->ejecutar($sql_tipo);
        $dato_serv = $db->recorrer($res_tipo);
        $tipo_raiz = $dato_serv['tipo_raiz'] ?? 'MANTENIMIENTO';

        $url_retorno = ($tipo_raiz === 'DIAGNOSTICO') ? "../ejecucion_diagnostico.php" : "../gestion_taller.php";

        header("Location: $url_retorno?id_cita=$id_cita&res=hallazgo_guardado");
        exit();

    } else {
        throw new Exception("Error al insertar el hallazgo en la base de datos.");
    }

} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage();
}