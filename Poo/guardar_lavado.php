<?php
session_start();
// 📂 CARGAMOS LIBRERÍAS DE GOOGLE
require __DIR__ . '/../vendor/autoload.php'; 
use Google\Cloud\Storage\StorageClient;

require_once "Conexion.php"; 
require_once "../libs/classY.php"; 

$db = new Conexion();
$id_usuario_actual = $_SESSION['id_usuario'] ?? null;

if ($_POST) {
    $id_cita = mysqli_real_escape_string($db->conexion, $_POST['id_cita']);
    $id_orden = mysqli_real_escape_string($db->conexion, $_POST['id_orden']);
    
    // OBTENER INFORMACIÓN PARA WHATSAPP
    $sql_info = "SELECT ci.fecha_cita, ci.token_confirmacion, cl.nombre_completo, cl.telefono, v.placa 
                 FROM citas ci 
                 JOIN clientes cl ON ci.id_cliente = cl.id_cliente 
                 JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
                 WHERE ci.id_cita = '$id_cita' LIMIT 1";
    
    $res_info = $db->ejecutar($sql_info);
    $datos = $db->recorrer($res_info);
    
    if (!$datos) { die("Error: No se encontró la cita."); }

    // --- ☁️ CONFIGURACIÓN DE CLOUD STORAGE ---
    $nombreBucket = 'taller-dr-motors-storage';
    $storage = new StorageClient(); 
    $bucket = $storage->bucket($nombreBucket);
    $carpetaDestino = "img/lavado/"; // 👈 Carpeta organizada en el Bucket

    // 🚀 PROCESAR FOTO FINAL AL BUCKET
    $nombre_foto = "";
    if (!empty($_FILES['foto_lavado']['name']) && $_FILES['foto_lavado']['error'] === UPLOAD_ERR_OK) {
        
        $ext = pathinfo($_FILES['foto_lavado']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "final_" . $id_orden . "_" . time() . "." . $ext;
        
        try {
            $fileStream = fopen($_FILES['foto_lavado']['tmp_name'], 'r');
            $bucket->upload($fileStream, [
                'name' => $carpetaDestino . $nombre_foto
            ]);
        } catch (Exception $e) {
            error_log("Fallo subida lavado a Storage: " . $e->getMessage());
        }
    }

    // ACTUALIZACIÓN DE BASE DE DATOS
    $sql_ot = "UPDATE ordenes_trabajo SET 
                foto_lavado = '$nombre_foto', 
                id_usuario_limpieza = '$id_usuario_actual',
                estado_orden = 'FINALIZADO' 
               WHERE id_orden = '$id_orden'";
    $db->ejecutar($sql_ot);

    $db->ejecutar("UPDATE citas SET estado = 'POR ENTREGAR' WHERE id_cita = '$id_cita'");

    // NOTIFICACIÓN YCLOUD
    $nombre_cliente = explode(' ', $datos['nombre_completo'])[0];
    enviarTemplateEntregaVehiculo($datos['telefono'], $nombre_cliente, $datos['placa'], $id_cita, $datos['token_confirmacion']);

    header("Location: ../citas.php?fecha=".$datos['fecha_cita']."&res=finalizado");
    exit();
}