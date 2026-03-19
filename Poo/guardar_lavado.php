<?php
session_start();
// 1. AJUSTES DE POTENCIA PARA GENERACIÓN PESADA
ini_set('memory_limit', '1024M'); 
set_time_limit(0); 

// 📂 LIBRERÍAS (GCS + Dompdf)
require __DIR__ . '/../vendor/autoload.php'; 
use Google\Cloud\Storage\StorageClient;
use Dompdf\Dompdf;
use Dompdf\Options;

require_once "Conexion.php"; 
require_once "../libs/classY.php"; 

$db = new Conexion();
$id_usuario_actual = $_SESSION['id_usuario'] ?? null;

// ✅ FUNCIÓN BASE64 (Vital para que el PDF procese las fotos del Bucket)
function imagenBase64($url) {
    if (!$url || empty($url)) return "";
    $context = stream_context_create([
        "ssl" => ["verify_peer" => false, "verify_peer_name" => false],
        "http" => ["header" => "User-Agent: PHP\r\n"]
    ]);
    $datosArchivo = @file_get_contents($url, false, $context);
    if ($datosArchivo !== false) {
        $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        return 'data:image/' . ($ext ?: 'jpeg') . ';base64,' . base64_encode($datosArchivo);
    }
    return ""; 
}

function txt($texto) {
    return mb_convert_encoding($texto ?? '', 'UTF-8', 'UTF-8');
}

if ($_POST) {
    $id_cita = mysqli_real_escape_string($db->conexion, $_POST['id_cita']);
    $id_orden = mysqli_real_escape_string($db->conexion, $_POST['id_orden']);
    
    // 2. CONSULTA MAESTRA DE DATOS (Copia exacta de tu reporte)
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
    $d = $db->recorrer($res_info); // Usamos $d para que coincida con tu diseño HTML
    
    if (!$d) { die("Error: No se encontró la información."); }

    // --- ☁️ CONFIGURACIÓN DE STORAGE ---
    $nombreBucket = 'taller-dr-motors-storage';
    $storage = new StorageClient(); 
    $bucket = $storage->bucket($nombreBucket);

    // 🚀 3. SUBIR FOTO DE LAVADO
    $nombre_foto_lavado = "";
    if (!empty($_FILES['foto_lavado']['name']) && $_FILES['foto_lavado']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_lavado']['name'], PATHINFO_EXTENSION);
        $nombre_foto_lavado = "final_" . $id_orden . "_" . time() . "." . $ext;
        
        try {
            $fileStream = fopen($_FILES['foto_lavado']['tmp_name'], 'r');
            $bucket->upload($fileStream, ['name' => "img/lavado/" . $nombre_foto_lavado]);
        } catch (Exception $e) {
            error_log("Fallo subida lavado: " . $e->getMessage());
        }
    }

    // 🚀 4. GENERACIÓN DEL PDF (FUSIÓN DE TU DISEÑO)
    $url_base = "https://storage.googleapis.com/$nombreBucket/img/";
    $url_ordenes       = $url_base . "ordenes/";
    $url_evidencias    = $url_base . "evidencias/";
    $url_mantenimiento = $url_base . "mantenimiento/";
    $url_lavado        = $url_base . "lavado/";

    ob_start(); // Iniciamos captura de HTML
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <style>
            @page { margin: 1cm; }
            body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 11px; line-height: 1.4; }
            .header { width: 100%; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
            .logo-img { width: 150px; height: auto; }
            .doc-title { font-size: 18px; font-weight: bold; color: #0d1b3e; text-align: right; }
            .order-badge { background: #0d1b3e; color: #fff; padding: 5px 12px; border-radius: 4px; font-weight: bold; margin-top: 5px; }
            .section-bar { background: #0d1b3e; color: #fff; padding: 8px 15px; font-weight: bold; border-radius: 30px; margin: 15px 0; text-transform: uppercase; font-size: 11px; }
            .summary-grid { width: 100%; margin-bottom: 20px; border: 1px solid #eee; border-radius: 10px; }
            .summary-item { padding: 10px; border-right: 1px solid #eee; }
            .s-label { font-size: 9px; text-transform: uppercase; color: #999; font-weight: bold; }
            .s-value { font-size: 12px; font-weight: bold; color: #333; }
            .photo-table { width: 100%; margin-bottom: 15px; }
            .photo-cell { width: 33.33%; padding: 5px; vertical-align: top; }
            .card { border: 1px solid #eee; border-radius: 8px; overflow: hidden; background: #fff; }
            .card img { width: 100%; height: 130px; object-fit: cover; display: block; }
            .card-footer { padding: 8px; background: #f9f9f9; text-align: center; border-top: 1px solid #eee; }
            .pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8px; font-weight: bold; margin-top: 4px; }
            .pill-ok { background: #e8f5e9; color: #2e7d32; }
            .pill-fail { background: #ffebee; color: #c62828; }
            .quality-box { background: #f0f7ff; border: 1px solid #d0e3ff; border-radius: 12px; padding: 15px; margin-top: 20px; }
            .sub-system-bar { background: #f8f9fa; border-left: 4px solid #2563eb; padding: 5px 10px; font-weight: bold; font-size: 11px; margin: 15px 0; color: #0d1b3e; }
            .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #999; }
        </style>
    </head>
    <body>
        <div class="footer">DOCUMENTO TÉCNICO OFICIAL DR. MOTORS — PLACA: <?php echo $d['placa']; ?></div>

        <table class="header">
            <tr>
                <td width="50%">
                    <img src="<?php echo imagenBase64(__DIR__ . "/../image/logo_taller.png"); ?>" class="logo-img">
                </td>
                <td align="right">
                    <div class="doc-title">EXPEDIENTE TÉCNICO DIGITAL</div>
                    <div style="font-size:10px;">FECHA: <?php echo date('d/m/Y'); ?></div>
                    <div class="order-badge">ORDEN #<?php echo str_pad($id_orden, 5, "0", STR_PAD_LEFT); ?></div>
                </td>
            </tr>
        </table>

        <table class="summary-grid" cellspacing="0">
            <tr>
                <td class="summary-item" width="33%"><div class="s-label">Propietario</div><div class="s-value"><?php echo txt($d['nombre_completo']); ?></div></td>
                <td class="summary-item" width="33%"><div class="s-label">Vehículo</div><div class="s-value"><?php echo txt($d['marca']." ".$d['modelo']); ?></div></td>
                <td class="summary-item" width="33%" style="border-right:0;"><div class="s-label">Placa</div><div class="s-value" style="color:#2563eb;"><?php echo $d['placa']; ?></div></td>
            </tr>
        </table>

        <div class="section-bar">I. Recepción y Estado de Ingreso</div>
        <table class="photo-table">
            <tr>
                <td class="photo-cell"><div class="card"><img src="<?php echo imagenBase64($url_ordenes . $d['foto_frontal']); ?>"><div class="card-footer">Frontal</div></div></td>
                <td class="photo-cell"><div class="card"><img src="<?php echo imagenBase64($url_ordenes . $d['foto_posterior']); ?>"><div class="card-footer">Posterior</div></div></td>
                <td class="photo-cell"><div class="card"><img src="<?php echo imagenBase64($url_ordenes . $d['foto_tablero']); ?>"><div class="card-footer"><?php echo number_format($d['km_ingreso']); ?> KM</div></div></td>
            </tr>
        </table>

        <div class="quality-box">
            <div style="font-weight:bold; color:#0d1b3e;">Certificación de Entrega y Lavado</div>
            <?php if($nombre_foto_lavado): ?>
                <img src="<?php echo imagenBase64($url_lavado . $nombre_foto_lavado); ?>" style="width:100%; height:200px; object-fit:cover; border-radius:8px; margin-top:10px;">
            <?php endif; ?>
        </div>

        <div style="page-break-after: always;"></div>
        <div class="section-bar">II. Informe Técnico por Sistemas</div>
        <?php
        $sql_ir = "SELECT ir.*, ps.descripcion_paso, ps.seccion_paso FROM inspeccion_resultados ir 
                   INNER JOIN pasos_servicio ps ON ir.id_paso = ps.id_paso 
                   WHERE ir.id_cita = '$id_cita' ORDER BY ps.seccion_paso ASC, ps.orden_paso ASC";
        $res_ir = $db->ejecutar($sql_ir);
        $seccion_actual = ""; $buffer = [];

        while ($ir = $db->recorrer($res_ir)):
            if ($ir['seccion_paso'] !== $seccion_actual):
                if (!empty($buffer)) render_row($buffer, $url_evidencias);
                $buffer = []; $seccion_actual = $ir['seccion_paso'];
                echo '<div class="sub-system-bar">SISTEMA: '.$seccion_actual.'</div>';
            endif;
            $buffer[] = ['foto' => $ir['foto_evidencia'], 'desc' => $ir['descripcion_paso'], 'estado' => $ir['estado']];
            if (count($buffer) == 3) { render_row($buffer, $url_evidencias); $buffer = []; }
        endwhile;
        if (!empty($buffer)) render_row($buffer, $url_evidencias);

        function render_row($items, $url) {
            echo '<table class="photo-table"><tr>';
            foreach ($items as $it) {
                $img = imagenBase64($url . $it['foto']);
                $st = ($it['estado'] == 'OK') ? 'pill-ok' : 'pill-fail';
                echo '<td class="photo-cell"><div class="card">
                        '.($img ? '<img src="'.$img.'">' : '<div style="height:130px; background:#eee;"></div>').'
                        <div class="card-footer"><div style="font-size:8px;">'.$it['desc'].'</div><span class="pill '.$st.'">'.$it['estado'].'</span></div>
                      </div></td>';
            }
            for($i=count($items);$i<3;$i++) echo '<td class="photo-cell"></td>';
            echo '</tr></table>';
        }
        ?>

        <div style="page-break-after: always;"></div>
        <div class="section-bar">III. Evidencias de Mantenimiento</div>
        <?php
        $sql_m = "SELECT * FROM orden_evidencias WHERE id_cita = '$id_cita' ORDER BY id_evidencia ASC";
        $res_m = $db->ejecutar($sql_m);
        $buffer_m = [];
        while($ev = $db->recorrer($res_m)):
            $buffer_m[] = ['foto' => $ev['foto'], 'desc' => $ev['descripcion']];
            if(count($buffer_m) == 3) { render_mante($buffer_m, $url_mantenimiento); $buffer_m = []; }
        endwhile;
        if(!empty($buffer_m)) render_mante($buffer_m, $url_mantenimiento);

        function render_mante($items, $url) {
            echo '<table class="photo-table"><tr>';
            foreach ($items as $it) {
                $img = imagenBase64($url . $it['foto']);
                echo '<td class="photo-cell"><div class="card" style="border-color:#2563eb;">
                        <img src="'.$img.'"><div class="card-footer" style="color:#1e3a8a; font-size:8px;">'.$it['desc'].'</div>
                      </div></td>';
            }
            for($i=count($items);$i<3;$i++) echo '<td class="photo-cell"></td>';
            echo '</tr></table>';
        }
        ?>
    </body>
    </html>
    <?php
    $html_final = ob_get_clean();

    // 🚀 5. CONVERSIÓN Y SUBIDA AL BUCKET
    $nombre_pdf = "Reporte_OT_" . $id_orden . "_" . $d['placa'] . ".pdf";
    try {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html_final);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf_output = $dompdf->output();

        // SUBIDA AL BUCKET (Carpeta uploads/pdf/)
        $bucket->upload($pdf_output, ['name' => "uploads/pdf/" . $nombre_pdf]);
    } catch (Exception $e) {
        error_log("Error PDF: " . $e->getMessage());
        $nombre_pdf = "";
    }

    // 🚀 6. CIERRE DE BASE DE DATOS
    $sql_ot = "UPDATE ordenes_trabajo SET 
                foto_lavado = '$nombre_foto_lavado', 
                url_pdf = '$nombre_pdf', 
                id_usuario_limpieza = '$id_usuario_actual',
                estado_orden = 'FINALIZADO' 
               WHERE id_orden = '$id_orden'";
    $db->ejecutar($sql_ot);
    $db->ejecutar("UPDATE citas SET estado = 'POR ENTREGAR' WHERE id_cita = '$id_cita'");

    // 🚀 7. NOTIFICACIÓN Y REDIRECCIÓN
    $nombre_cliente = explode(' ', $d['nombre_completo'])[0];
    enviarTemplateEntregaVehiculo($d['telefono'], $nombre_cliente, $d['placa'], $id_cita, $d['token_confirmacion']);

    header("Location: ../citas.php?fecha=".$d['fecha_cita']."&res=finalizado");
    exit();
}