<?php
session_start();
// 📂 CARGAMOS LIBRERÍAS (Google Storage + Dompdf)
require __DIR__ . '/../vendor/autoload.php'; 
use Google\Cloud\Storage\StorageClient;
use Dompdf\Dompdf;
use Dompdf\Options;

require_once "Conexion.php"; 
require_once "../libs/classY.php"; 

$db = new Conexion();
$id_usuario_actual = $_SESSION['id_usuario'] ?? null;

if ($_POST) {
    $id_cita = mysqli_real_escape_string($db->conexion, $_POST['id_cita']);
    $id_orden = mysqli_real_escape_string($db->conexion, $_POST['id_orden']);
    
    // 1. OBTENER INFORMACIÓN MAESTRA
    $sql_info = "SELECT ci.*, v.placa, v.marca, v.modelo, cl.nombre_completo, cl.telefono,
                        ot.km_ingreso, ot.foto_frontal, ot.foto_posterior, ot.foto_tablero,
                        s.nombre_servicio
                 FROM citas ci 
                 JOIN clientes cl ON ci.id_cliente = cl.id_cliente 
                 JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
                 JOIN ordenes_trabajo ot ON ci.id_cita = ot.id_cita
                 JOIN servicios s ON ci.id_servicio = s.id_servicio
                 WHERE ci.id_cita = '$id_cita' LIMIT 1";
    
    $res_info = $db->ejecutar($sql_info);
    $datos = $db->recorrer($res_info);
    
    if (!$datos) { die("Error: No se encontró la cita."); }

    // --- ☁️ CONFIGURACIÓN DE CLOUD STORAGE ---
    $nombreBucket = 'taller-dr-motors-storage';
    $storage = new StorageClient(); 
    $bucket = $storage->bucket($nombreBucket);

    // 🚀 2. SUBIR FOTO DE LAVADO AL BUCKET
    $nombre_foto = "";
    if (!empty($_FILES['foto_lavado']['name']) && $_FILES['foto_lavado']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_lavado']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "final_" . $id_orden . "_" . time() . "." . $ext;
        
        try {
            $fileStream = fopen($_FILES['foto_lavado']['tmp_name'], 'r');
            $bucket->upload($fileStream, [
                'name' => "img/lavado/" . $nombre_foto
            ]);
        } catch (Exception $e) {
            error_log("Fallo subida lavado: " . $e->getMessage());
        }
    }

    // 🚀 3. GENERAR Y SUBIR EL PDF AL BUCKET (uploads/reportes/)
    $nombre_pdf = "Reporte_OT_" . $id_orden . "_" . $datos['placa'] . ".pdf";
    
    try {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        // Preparamos el diseño (Sinceramente, usa el archivo de diseño que creamos)
        ob_start();
        $d = $datos; 
        $d['foto_lavado'] = $nombre_foto; 
        // Aquí defines las rutas para que el PDF sepa de dónde jalar las fotos del bucket
        $url_bucket_base = "https://storage.googleapis.com/$nombreBucket/img/";
        
        include "../diseno_pdf_limpio.php"; // 👈 Tu HTML/CSS del reporte
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf_content = $dompdf->output();

        // 📤 SUBIDA DIRECTA AL BUCKET
        $bucket->upload($pdf_content, [
            'name' => "uploads/pdf/" . $nombre_pdf // 👈 Se guarda en tu carpeta de reportes en el Bucket
        ]);

    } catch (Exception $e) {
        error_log("Error generando/subiendo PDF: " . $e->getMessage());
        $nombre_pdf = ""; 
    }

    // 🚀 4. ACTUALIZACIÓN FINAL DE BASE DE DATOS
    $sql_ot = "UPDATE ordenes_trabajo SET 
                foto_lavado = '$nombre_foto', 
                url_pdf = '$nombre_pdf', 
                id_usuario_limpieza = '$id_usuario_actual',
                estado_orden = 'FINALIZADO' 
               WHERE id_orden = '$id_orden'";
    $db->ejecutar($sql_ot);

    $db->ejecutar("UPDATE citas SET estado = 'POR ENTREGAR' WHERE id_cita = '$id_cita'");

    // 🚀 5. NOTIFICACIÓN YCLOUD
    $nombre_cliente = explode(' ', $datos['nombre_completo'])[0];
    enviarTemplateEntregaVehiculo($datos['telefono'], $nombre_cliente, $datos['placa'], $id_cita, $datos['token_confirmacion']);

    header("Location: ../citas.php?fecha=".$datos['fecha_cita']."&res=finalizado");
    exit();
}