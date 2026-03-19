<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require __DIR__ . '/../vendor/autoload.php'; 
use Google\Cloud\Storage\StorageClient;

require_once "Conexion.php"; 
require_once "../libs/classY.php"; 

$db = new Conexion();
$id_usuario_actual = $_SESSION['id_usuario'] ?? null;

if ($_POST) {
    $id_cita = mysqli_real_escape_string($db->conexion, $_POST['id_cita']);
    $id_orden = mysqli_real_escape_string($db->conexion, $_POST['id_orden']);
    
    // 1. CONFIGURACIÓN BUCKET
    $nombreBucket = 'taller-dr-motors-storage';
    $storage = new StorageClient(); 
    $bucket = $storage->bucket($nombreBucket);
    $nombre_foto = "";

    // Subir foto de lavado
    if (!empty($_FILES['foto_lavado']['name'])) {
        $ext = pathinfo($_FILES['foto_lavado']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "final_" . $id_orden . "_" . time() . "." . $ext;
        $bucket->upload(fopen($_FILES['foto_lavado']['tmp_name'], 'r'), ['name' => "img/lavado/" . $nombre_foto]);
    }

    // 🚀 2. GENERAR PDF (Usando tu archivo de 300 líneas)
    $_GET['id_cita'] = $id_cita; 
    $generar_para_bucket = true; 
    
    // Al incluirlo, heredamos la variable $d con los datos del cliente y el PDF en $pdf_final_contenido
    include "../generar_reporte_pdf.php"; 
    
    // 3. SUBIR PDF AL BUCKET
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

    // 🚀 5. ENVIAR NOTIFICACIÓN WHATSAPP (¡Aquí está lo que faltaba!)
    // Sinceramente, usamos los datos de $d que vienen del include del reporte
    if (isset($d)) {
        $nombre_cliente = explode(' ', $d['nombre_completo'])[0];
        
        // Enviamos el template de entrega
        enviarTemplateEntregaVehiculo(
            $d['telefono'], 
            $nombre_cliente, 
            $d['placa'], 
            $id_cita, 
            $d['token_confirmacion']
        );
    }

    // 6. REDIRECCIÓN
    header("Location: ../citas.php?res=finalizado");
    exit();
}