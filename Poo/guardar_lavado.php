<?php
session_start();
require __DIR__ . '/../vendor/autoload.php'; 
use Google\Cloud\Storage\StorageClient;

require_once "Conexion.php"; 
require_once "../libs/classY.php"; 

$db = new Conexion();
$id_usuario_actual = $_SESSION['id_usuario'] ?? null;

if ($_POST) {
    $id_cita = mysqli_real_escape_string($db->conexion, $_POST['id_cita']);
    $id_orden = mysqli_real_escape_string($db->conexion, $_POST['id_orden']);
    
    // 1. SUBIR FOTO DE LAVADO (Tu lógica de siempre)
    $nombreBucket = 'taller-dr-motors-storage';
    $storage = new StorageClient(); 
    $bucket = $storage->bucket($nombreBucket);
    $nombre_foto = "";

    if (!empty($_FILES['foto_lavado']['name'])) {
        $ext = pathinfo($_FILES['foto_lavado']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "final_" . $id_orden . "_" . time() . "." . $ext;
        $bucket->upload(fopen($_FILES['foto_lavado']['tmp_name'], 'r'), ['name' => "img/lavado/" . $nombre_foto]);
    }

    // 🚀 2. "LLAMAR" AL REPORTE DE 300 LÍNEAS
    // Sinceramente, aquí hacemos la magia:
    $_GET['id_cita'] = $id_cita; // Le pasamos el ID como si viniera por URL
    $generar_para_bucket = true; // Activamos el modo "silencioso" que pusimos en el paso 1
    
    include "../generar_reporte_pdf.php"; 
    // Ahora, gracias al include, tenemos disponible la variable $pdf_final_contenido
    
    // 3. SUBIR EL PDF AL BUCKET
    $nombre_pdf = "Expediente_OT_" . $id_orden . ".pdf";
    if (isset($pdf_final_contenido)) {
        $bucket->upload($pdf_final_contenido, [
            'name' => "uploads/pdf/" . $nombre_pdf
        ]);
    }

    // 4. ACTUALIZAR BASE DE DATOS
    $db->ejecutar("UPDATE ordenes_trabajo SET 
                    foto_lavado = '$nombre_foto', 
                    url_pdf = '$nombre_pdf', 
                    id_usuario_limpieza = '$id_usuario_actual',
                    estado_orden = 'FINALIZADO' 
                   WHERE id_orden = '$id_orden'");

    $db->ejecutar("UPDATE citas SET estado = 'POR ENTREGAR' WHERE id_cita = '$id_cita'");

    // 5. REDIRECCIÓN
    header("Location: ../citas.php?res=finalizado");
    exit();
}