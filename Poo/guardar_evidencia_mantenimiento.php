<?php
// Poo/guardar_evidencia_mantenimiento.php
session_start();

// 📂 LIBRERÍAS DE GOOGLE (ADC - Credenciales automáticas en Cloud Run)
require __DIR__ . '/../vendor/autoload.php'; 
use Google\Cloud\Storage\StorageClient;

require_once "Conexion.php";
$db = new Conexion();

// Diagnóstico de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_FILES['foto_item']) || $_FILES['foto_item']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No se recibió la foto correctamente.");
    }

    $id_cita    = $_POST['id_cita'] ?? null;
    $id_orden   = $_POST['id_orden'] ?? null;
    $descripcion = mysqli_real_escape_string($db->conexion, $_POST['nombre_item_texto'] ?? 'Sin descripción');

    if (!$id_cita || !$id_orden) {
        throw new Exception("Faltan identificadores de orden o cita.");
    }

    // --- ☁️ CONFIGURACIÓN DE CLOUD STORAGE ---
    $nombreBucket = 'taller-dr-motors-storage';
    $storage = new StorageClient(); 
    $bucket = $storage->bucket($nombreBucket);
    $carpetaDestino = "img/mantenimiento/"; // 👈 Tu nueva carpeta organizada

    // 1. GESTIÓN DE ARCHIVO HACIA EL BUCKET
    $ext = pathinfo($_FILES['foto_item']['name'], PATHINFO_EXTENSION);
    $nuevo_nombre = "mante_" . $id_orden . "_" . time() . "." . $ext;

    try {
        $fileStream = fopen($_FILES['foto_item']['tmp_name'], 'r');
        $bucket->upload($fileStream, [
            'name' => $carpetaDestino . $nuevo_nombre
        ]);
        // Ya no necesitamos move_uploaded_file porque ya está en la nube
    } catch (Exception $e) {
        throw new Exception("Error al subir al Bucket: " . $e->getMessage());
    }

    // 2. INSERTAR EN LA BASE DE DATOS
    $sql = "INSERT INTO orden_evidencias (id_orden, id_cita, descripcion, foto) 
            VALUES ('$id_orden', '$id_cita', '$descripcion', '$nuevo_nombre')";
    
    if ($db->ejecutar($sql)) {

        // 🚀 3. LÓGICA DE RETORNO INTELIGENTE
        $sql_tipo = "SELECT s.tipo_raiz 
                     FROM citas ci 
                     INNER JOIN servicios s ON ci.id_servicio = s.id_servicio 
                     WHERE ci.id_cita = '$id_cita' LIMIT 1";
        
        $res_tipo = $db->ejecutar($sql_tipo);
        $dato_serv = $db->recorrer($res_tipo);
        $tipo_raiz = $dato_serv['tipo_raiz'] ?? 'MANTENIMIENTO';

        $url_retorno = ($tipo_raiz === 'DIAGNOSTICO') ? "../ejecucion_diagnostico.php" : "../gestion_taller.php";

        header("Location: $url_retorno?id_cita=$id_cita&res=foto_ok");
        exit();

    } else {
        throw new Exception("Error al guardar en BD.");
    }

} catch (Exception $e) {
    echo "<div style='color:red; font-family:sans-serif; padding:20px;'>";
    echo "<h3>❌ Error Crítico:</h3>" . $e->getMessage();
    echo "<br><br><a href='javascript:history.back()'>Volver a intentar</a></div>";
}