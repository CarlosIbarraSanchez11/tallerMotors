<?php
// 1. AJUSTES DE RENDIMIENTO
ini_set('memory_limit', '1024M'); 
set_time_limit(0); 

ob_start();
session_start();

// ✅ CARGA DE LIBRERÍAS
require_once __DIR__ . '/vendor/autoload.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

require_once "Poo/Conexion.php";
$db = new Conexion();

$id_cita = $_GET['id_cita'] ?? die("Error: ID de cita no recibido");

// ✅ 2. RUTAS MAESTRAS DEL BUCKET DE GOOGLE CLOUD
$url_base = "https://storage.googleapis.com/taller-dr-motors-storage/img/";
$url_ordenes       = $url_base . "ordenes/";
$url_evidencias    = $url_base . "evidencias/";    // Inspección Técnica
$url_mantenimiento = $url_base . "mantenimiento/"; // Trabajo Realizado
$url_lavado        = $url_base . "lavado/";        // Foto final

// 3. FUNCIÓN MAESTRA BASE64 (Indispensable para Dompdf en Cloud Run)
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

// 4. CONSULTA DE DATOS PRINCIPAL
$sql = "SELECT ci.*, v.placa, v.marca, v.modelo, cl.nombre_completo,
               ot.id_orden, ot.km_ingreso, ot.nivel_combustible,
               ot.foto_frontal, ot.foto_posterior, ot.foto_tablero, ot.foto_lavado,
               s.nombre_servicio
        FROM citas ci
        LEFT JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
        LEFT JOIN clientes cl ON ci.id_cliente = cl.id_cliente
        LEFT JOIN ordenes_trabajo ot ON ci.id_cita = ot.id_cita
        LEFT JOIN servicios s ON ci.id_servicio = s.id_servicio
        WHERE ci.id_cita = '$id_cita' LIMIT 1";

$res = $db->ejecutar($sql);
$d = $db->recorrer($res);
$id_orden = $d['id_orden'];

// Logo local y fotos principales convertidas a Base64 para estabilidad
$logo_b64 = imagenBase64(__DIR__ . "/image/logo_taller.png");
$foto_f = imagenBase64($url_ordenes . $d['foto_frontal']);
$foto_p = imagenBase64($url_ordenes . $d['foto_posterior']);
$foto_t = imagenBase64($url_ordenes . $d['foto_tablero']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    
    /* Marino oscuro:   #0d1b3e
       Azul acento:     #2563eb
       Gris pizarra:    #475569
       Fondo suave:     #f1f5f9
       Borde delicado:  #cbd5e1
       Verde OK:        #16a34a
       Rojo alerta:     #dc2626 */

    @page { margin: 2.8cm 2.6cm 2.5cm 2.6cm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Arial Black', 'Archivo Black', sans-serif;
        font-size: 11px;
        color: #1e293b;
        background: #f4f6f7de;
        line-height: 1.65;
    }

    .wrapper {
        background: #fff;
        max-width: 860px;
        margin: 50px auto 60px;
        padding: 50px 54px;
        box-shadow: 0 8px 36px rgba(13,27,62,0.13);
        border-radius: 8px;
    }

    .page-break { page-break-before: always; }

    /* ══════════════════════════════════════════
       HEADER
    ══════════════════════════════════════════ */
    .header {
        width: 100%;
        border-bottom: 3px solid #0d1b3e;
        padding-bottom: 14px;
        margin-bottom: 28px;
    }
    .header td { vertical-align: middle; }

    .brand-wrap { display: flex; align-items: center; gap: 12px; }

    .logo-img {
        height: 54px;
        width: auto;
        object-fit: contain;
        display: block;
    }

    .brand-text { display: inline-block; }
    .brand {
        font-family: 'Monaco', 'Lucida Console', monospace;
        font-size: 34px;
        font-weight: 900;
        color: #0d1b3e;
        letter-spacing: -1px;
        line-height: 1;
    }
    .brand span { color: #2563eb; font-weight: 400; }
    .brand-sub {
        font-size: 8px;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: #2563eb;
        margin-top: 4px;
    }

    .doc-meta { text-align: right; }
    .doc-title {
        font-size: 20px;
        font-weight: 800;
        color: #0d1b3e;
        letter-spacing: 0.5px;
    }
    .doc-date {
        font-size: 12px;
        font-weight: 800;
        color: #64748b;
        margin-top: 3px;
        line-height: 1.7;
    }
    .order-badge {
        display: inline-block;
        background: #0d1b3e;
        font-weight: 800;
        color: #fff;
        padding: 4px 14px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 700;
        margin-top: 6px;
        letter-spacing: 0.5px;
    }

    /* ══════════════════════════════════════════
       FICHA VEHÍCULO
    ══════════════════════════════════════════ */
    .summary-grid { width: 100%; border-collapse: separate; border-spacing: 8px; margin-bottom: 22px; }
    .summary-item {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        padding: 13px 15px;
        border-radius: 10px;
    }
    .s-label {
        font-size: 8px;
        color: #94a3b8;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }
    .s-value { font-size: 12px; font-weight: 700; color: #1e293b; }

    /* ══════════════════════════════════════════
       SECTION BAR
    ══════════════════════════════════════════ */
    .section-bar {
        background: #0d1b3e;
        color: #fff;
        padding: 10px 22px 10px 54px;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-radius: 0 28px 28px 0;
        margin: 40px 0 10px -54px;
        width: calc(62% + 54px);
    }

    .sub-system-bar {
        background: #f1f5f9;
        border-left: 5px solid #2563eb;
        padding: 7px 14px;
        font-weight: 700;
        color: #0d1b3e;
        text-transform: uppercase;
        margin: 20px 0 10px;
        font-size: 10px;
        letter-spacing: 0.8px;
        border-radius: 0 5px 5px 0;
    }

    /* ══════════════════════════════════════════
       CARDS DE FOTOS
    ══════════════════════════════════════════ */
    .photo-table { width: 100%; border-collapse: separate; border-spacing: 14px; }
    .photo-cell  { width: 33.33%; vertical-align: top; }
    .card {
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        overflow: hidden;
        page-break-inside: avoid;
        box-shadow: 0 2px 10px rgba(13,27,62,0.06);
    }
    .card img { width: 100%; height: 140px; object-fit: cover; display: block; }
    .card-footer {
        padding: 9px 10px;
        text-align: center;
        border-top: 1px solid #e2e8f0;
        background: #fafbfc;
    }
    .card-desc {
        font-weight: 700;
        color: #334155;
        font-size: 9px;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
        text-transform: uppercase;
    }

    /* ══════════════════════════════════════════
       STATUS PILLS
    ══════════════════════════════════════════ */
    .pill {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .pill-ok   { background: #dcfce7; color: #16a34a; border: 1px solid #86efac; }
    .pill-fail { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }

    /* ══════════════════════════════════════════
       CERTIFICADO CALIDAD
    ══════════════════════════════════════════ */
    .quality-box {
        background: #f0f6ff;
        border: 1px solid #bfdbfe;
        border-radius: 16px;
        padding: 22px 24px;
        margin-top: 28px;
    }
    .quality-title {
        font-weight: 700;
        color: #0d1b3e;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 2px solid #bfdbfe;
        padding-bottom: 6px;
        display: inline-block;
        margin-bottom: 10px;
    }
    .quality-items {
        font-size: 10.5px;
        color: #334155;
        line-height: 1.9;
    }
    .cert-badge {
        display: inline-block;
        background: #dcfce7;
        color: #16a34a;
        border: 1px solid #86efac;
        font-size: 11px;
        font-weight: 700;
        padding: 9px 22px;
        border-radius: 10px;
        letter-spacing: 0.5px;
    }
    .wash-img {
        width: 40%;
        height: 100px;
        object-fit: cover;
        border-radius: 12px;
        border: 4px solid #fff;
        box-shadow: 0 4px 14px rgba(13,27,62,0.10);
        margin-top: 16px;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    /* ══════════════════════════════════════════
       FOOTER PDF
    ══════════════════════════════════════════ */
    .footer {
        position: fixed;
        bottom: -15px;
        width: 100%;
        text-align: center;
        font-size: 8px;
        color: #94a3b8;
        border-top: 1px solid #e2e8f0;
        padding-top: 8px;
        letter-spacing: 1px;
    }
</style>
</head>
<body>

<div class="footer">DOCUMENTO TÉCNICO OFICIAL DR. MOTORS &nbsp;—&nbsp; PLACA: <?php echo $d['placa']; ?></div>

<div class="wrapper">
    <table class="header">
        <tr>
            <td width="50%">
                <?php if ($logo_b64): ?>
                    <img src="<?php echo $logo_b64; ?>" class="logo-img">
                <?php endif; ?>
            </td>
            <td width="50%" class="doc-meta">
                <div class="doc-title">EXPEDIENTE TÉCNICO DIGITAL</div>
                <div>GENERADO EL: <?php echo date('d/m/Y'); ?></div>
                <div><?php echo htmlspecialchars($d['marca'].' '.$d['modelo']); ?> | <?php echo htmlspecialchars($d['placa']); ?></div>
                <div class="order-badge">ORDEN #<?php echo str_pad($id_orden, 5, "0", STR_PAD_LEFT); ?></div>
            </td>
        </tr>
    </table>

    <table class="summary-grid" cellspacing="0">
        <tr>
            <td class="summary-item" width="33%">
                <div class="s-label">Propietario</div>
                <div class="s-value"><?php echo txt($d['nombre_completo']); ?></div>
            </td>
            <td class="summary-item" width="33%">
                <div class="s-label">Vehículo</div>
                <div class="s-value"><?php echo txt($d['marca']." ".$d['modelo']); ?></div>
            </td>
            <td class="summary-item" width="33%" style="border-right:0;">
                <div class="s-label">Placa</div>
                <div class="s-value" style="color:#2563eb;"><?php echo htmlspecialchars($d['placa']); ?></div>
            </td>
        </tr>
    </table>

    <div class="section-bar">I. Recepción y Estado de Ingreso</div>
    <table class="photo-table">
        <tr>
            <td class="photo-cell">
                <div class="card">
                    <img src="<?php echo $foto_f; ?>">
                    <div class="card-footer"><div class="card-desc">Frontal / Placa</div></div>
                </div>
            </td>
            <td class="photo-cell">
                <div class="card">
                    <img src="<?php echo $foto_p; ?>">
                    <div class="card-footer"><div class="card-desc">Vista Posterior</div></div>
                </div>
            </td>
            <td class="photo-cell">
                <div class="card">
                    <img src="<?php echo $foto_t; ?>">
                    <div class="card-footer">
                        <div class="card-desc">Kilometraje</div>
                        <div style="font-size:11px; font-weight:bold;"><?php echo number_format($d['km_ingreso']); ?> KM</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="quality-box">
        <table width="100%">
            <tr>
                <td width="70%">
                    <div class="quality-title">Control de Calidad Final</div>
                    <div style="font-size:10px; color:#555;">
                        • Limpieza exterior y detallado de carrocería<br>
                        • Aspirado y desinfección de cabina<br>
                        • Verificación técnica de fluidos y niveles
                    </div>
                </td>
                <td align="right">
                    <div class="cert-badge">CERTIFICADO OK</div>
                </td>
            </tr>
        </table>
        <?php 
        $img_l = $d['foto_lavado'] ? imagenBase64($url_lavado . $d['foto_lavado']) : "";
        if($img_l): ?>
            <img src="<?php echo $img_l; ?>" class="wash-img">
        <?php endif; ?>
    </div>

    <div class="page-break"></div>
    <div class="section-bar">II. Informe Técnico por Sistemas</div>

    <?php
    $sql_ir = "SELECT ir.*, ps.descripcion_paso, ps.seccion_paso 
               FROM inspeccion_resultados ir 
               INNER JOIN pasos_servicio ps ON ir.id_paso = ps.id_paso 
               WHERE ir.id_cita = '$id_cita' 
               ORDER BY ps.seccion_paso ASC, ps.orden_paso ASC";
    $res_ir = $db->ejecutar($sql_ir);
    $seccion_actual = ""; $buffer = [];

    function render_pdf_row($items, $url_ev) {
        echo '<table class="photo-table"><tr>';
        foreach ($items as $it) {
            $st_cls = ($it['estado'] == 'OK') ? 'pill-ok' : 'pill-fail';
            $img_b64 = imagenBase64($url_ev . $it['foto']);
            echo '<td class="photo-cell">
                    <div class="card">
                        '.($img_b64 ? '<img src="'.$img_b64.'">' : '<div style="height:140px; background:#f0f0f0; text-align:center; padding-top:60px; color:#999; font-size:8px;">SIN FOTO</div>').'
                        <div class="card-footer">
                            <div class="card-desc">'.$it['desc'].'</div>
                            <span class="pill '.$st_cls.'">'.$it['estado'].'</span>
                        </div>
                    </div>
                  </td>';
        }
        for ($i = count($items); $i < 3; $i++) echo '<td class="photo-cell"></td>';
        echo '</tr></table>';
    }

    while ($ir = $db->recorrer($res_ir)):
        if ($ir['seccion_paso'] !== $seccion_actual):
            if (!empty($buffer)) render_pdf_row($buffer, $url_evidencias);
            $buffer = []; $seccion_actual = $ir['seccion_paso'];
            echo '<div class="sub-system-bar">Sistema: '.txt($seccion_actual).'</div>';
        endif;
        $buffer[] = ['foto' => $ir['foto_evidencia'], 'desc' => txt($ir['descripcion_paso']), 'estado' => $ir['estado']];
        if (count($buffer) == 3) { render_pdf_row($buffer, $url_evidencias); $buffer = []; }
    endwhile;
    if (!empty($buffer)) render_pdf_row($buffer, $url_evidencias);
    ?>

    <div class="page-break"></div>
    <div class="section-bar">III. Evidencias de Mantenimiento Realizado</div>
    <div style="margin-bottom: 15px; color: #666; font-size: 10px; font-style: italic;">
        Registro de componentes sustituidos y trabajos correctivos finalizados.
    </div>
    <?php
    $sql_mante = "SELECT * FROM orden_evidencias WHERE id_cita = '$id_cita' ORDER BY id_evidencia ASC";
    $res_mante = $db->ejecutar($sql_mante);
    $buffer_m = [];

    function render_mante_row($items, $url_m) {
        echo '<table class="photo-table"><tr>';
        foreach ($items as $it) {
            $img_b64 = imagenBase64($url_m . $it['foto']);
            echo '<td class="photo-cell">
                    <div class="card" style="border-color:#2563eb;">
                        <img src="'.$img_b64.'">
                        <div class="card-footer" style="background:#eef2ff;">
                            <div class="card-desc" style="color:#1e3a8a;">'.$it['desc'].'</div>
                        </div>
                    </div>
                  </td>';
        }
        for ($i = count($items); $i < 3; $i++) echo '<td class="photo-cell"></td>';
        echo '</tr></table>';
    }

    while ($ev = $db->recorrer($res_mante)):
        $buffer_m[] = ['foto' => $ev['foto'], 'desc' => txt($ev['descripcion'])];
        if (count($buffer_m) == 3) { render_mante_row($buffer_m, $url_mantenimiento); $buffer_m = []; }
    endwhile;
    if (!empty($buffer_m)) render_mante_row($buffer_m, $url_mantenimiento);
    
    if ($db->contar($res_mante) == 0) {
        echo '<div style="text-align:center; padding: 40px; color: #ccc;">No hay registros de mantenimiento.</div>';
    }
    ?>
</div>

</body>
</html>

<?php
// FINALIZACIÓN DEL PDF
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("Expediente_DrMotors_".$d['placa'].".pdf", ["Attachment" => false]);
?>