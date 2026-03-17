<?php
// 1. AJUSTES DE POTENCIA
ini_set('memory_limit', '1024M'); 
set_time_limit(0); 

ob_start();
session_start();
$id_cita = $_GET['id_cita'] ?? die("Error: ID de cita no recibido");
session_write_close(); 

// ✅ 2. EL NUEVO AUTOLOAD (Usa el de la raíz)
require_once __DIR__ . '/vendor/autoload.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

require_once "Poo/Conexion.php";
$db = new Conexion();

// ✅ 3. FUNCIÓN DE IMAGEN MEJORADA PARA LA NUBE
function imagenBase64($ruta_o_url) {
    // Sinceramente, si es una URL de Google Cloud, la leemos igual
    $context = stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
    $datosArchivo = @file_get_contents($ruta_o_url, false, $context);
    
    if ($datosArchivo !== false) {
        $tipoArchivo = pathinfo($ruta_o_url, PATHINFO_EXTENSION);
        return 'data:image/' . $tipoArchivo . ';base64,' . base64_encode($datosArchivo);
    }
    return ""; 
}

function txt($texto) {
    if (!$texto) return "";
    return mb_convert_encoding($texto, 'UTF-8', 'UTF-8');
}

// CONSULTA DE DATOS MAESTRA (Igual que la tuya)
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

// ✅ 4. RUTAS ACTUALIZADAS (Sin /taller/)
$url_bucket = "https://storage.googleapis.com/taller-dr-motors-storage/img/ordenes/";
$root = __DIR__ . "/"; // La raíz es donde está este archivo

// Logo local (Este sí está en el servidor)
$logo_b64 = imagenBase64($root . "image/logo_taller.png");

// Fotos desde la NUBE (Google Cloud Storage)
$foto_frontal_b64 = $d['foto_frontal'] ? imagenBase64($url_bucket . $d['foto_frontal']) : "";
$foto_posterior_b64 = $d['foto_posterior'] ? imagenBase64($url_bucket . $d['foto_posterior']) : "";
$foto_tablero_b64 = $d['foto_tablero'] ? imagenBase64($url_bucket . $d['foto_tablero']) : "";

// Configuramos Dompdf para que no falle con las fuentes
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica'); // Sinceramente, Helvetica evita el error de Arial Black
$dompdf = new Dompdf($options);
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

<div class="footer">DOCUMENTO TÉCNICO OFICIAL DR. MOTORS &nbsp;—&nbsp; PÁGINA {PAGENO}</div>

<div class="wrapper">

    <!-- HEADER -->
    <table class="header" cellspacing="0" cellpadding="0">
        <tr>
            <td width="50%">
                <table cellspacing="0" cellpadding="0">
                    <tr>
                        <?php if ($logo_b64): ?>
                        <td style="padding-right: 14px; vertical-align: middle;">
                            <img src="<?php echo $logo_b64; ?>" class="logo-img">
                        </td>
                        <?php endif; ?>
                        <!-- <td style="vertical-align: middle;">
                            <div class="brand">DR. <span>MOTORS</span></div>
                            <div class="brand-sub">Expertise Automotriz</div>
                        </td> -->
                    </tr>
                </table>
            </td>
            <td width="50%" class="doc-meta">
                <div class="doc-title">EXPEDIENTE TÉCNICO DIGITAL</div>
                <div class="doc-date">
                    GENERADO EL: <?php echo date('d/m/Y'); ?><br>
                    <?php echo htmlspecialchars($d['marca'].' '.$d['modelo']); ?> &nbsp;|&nbsp; <?php echo htmlspecialchars($d['placa']); ?>
                </div>
                <div class="order-badge">ORDEN #<?php echo str_pad($id_orden, 5, "0", STR_PAD_LEFT); ?></div>
            </td>
        </tr>
    </table>

    <!-- FICHA VEHÍCULO -->
    <table class="summary-grid" cellspacing="0" cellpadding="0">
        <tr>
            <td class="summary-item" width="33%">
                <div class="s-label">Propietario</div>
                <div class="s-value"><?php echo txt($d['nombre_completo']); ?></div>
            </td>
            <td class="summary-item" width="33%">
                <div class="s-label">Vehículo / Modelo</div>
                <div class="s-value"><?php echo txt($d['marca']." ".$d['modelo']); ?></div>
            </td>
            <td class="summary-item" width="33%">
                <div class="s-label">Placa de Rodaje</div>
                <div class="s-value" style="color:#2563eb; font-size:16px;"><?php echo htmlspecialchars($d['placa']); ?></div>
            </td>
        </tr>
    </table>

    <!-- SECCIÓN I -->
    <div class="section-bar">I. &nbsp;Recepción y Estado de Ingreso</div>

    <table class="photo-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="photo-cell">
                <div class="card">
                    <img src="<?php echo imagenBase64($ruta_ordenes . $d['foto_frontal']); ?>">
                    <div class="card-footer"><div class="card-desc">Frontal / Placa</div></div>
                </div>
            </td>
            <td class="photo-cell">
                <div class="card">
                    <img src="<?php echo imagenBase64($ruta_ordenes . $d['foto_posterior']); ?>">
                    <div class="card-footer"><div class="card-desc">Vista Posterior</div></div>
                </div>
            </td>
            <td class="photo-cell">
                <div class="card">
                    <img src="<?php echo imagenBase64($ruta_ordenes . $d['foto_tablero']); ?>">
                    <div class="card-footer">
                        <div class="card-desc">Kilometraje Registrado</div>
                        <div style="font-size:12px; font-weight:700; color:#0d1b3e; margin-top:3px;"><?php echo number_format($d['km_ingreso']); ?> KM</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- CONTROL DE CALIDAD -->
    <div class="quality-box" style="margin-bottom: 10px !important">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="65%" style="vertical-align: top;">
                    <div class="quality-title">Control de Calidad Final</div>
                    <div class="quality-items">
                        &#10003; &nbsp;Limpieza exterior y detallado de carrocería<br>
                        &#10003; &nbsp;Aspirado y desinfección total de cabina<br>
                        &#10003; &nbsp;Verificación técnica de fluidos y niveles de seguridad
                    </div>
                </td>
                <td align="right" style="vertical-align: middle;">
                    <div class="cert-badge">&#10003; &nbsp;CERTIFICADO OK</div>
                </td>
            </tr>
        </table>
        <?php $img_lav = imagenBase64($ruta_lavado . $d['foto_lavado']); ?>
        <img src="<?php echo $img_lav ?: imagenBase64($ruta_ordenes . $d['foto_lavado']); ?>" class="wash-img">
    </div>

    <!-- SECCIÓN II: INSPECCIÓN -->
    <div class="page-break"></div>
    <div class="section-bar">II. &nbsp;Informe Técnico por Sistemas</div>

    <?php
    $sql_ir = "SELECT ir.*, ps.descripcion_paso, ps.seccion_paso 
               FROM inspeccion_resultados ir 
               INNER JOIN pasos_servicio ps ON ir.id_paso = ps.id_paso 
               WHERE ir.id_cita = '$id_cita' 
               ORDER BY ps.seccion_paso ASC, ps.orden_paso ASC";
    $res_ir = $db->ejecutar($sql_ir);

    $seccion_actual = "";
    $buffer = [];

    function render_row($items) {
        echo '<table class="photo-table" cellspacing="0" cellpadding="0"><tr>';
        foreach ($items as $it) {
            $st_cls = ($it['estado'] == 'OK') ? 'pill-ok' : 'pill-fail';
            echo '<td class="photo-cell">
                    <div class="card">
                        <img src="'.$it['img'].'">
                        <div class="card-footer">
                            <div class="card-desc">'.$it['desc'].'</div>
                            <span class="pill '.$st_cls.'">&#9679; '.$it['estado'].'</span>
                        </div>
                    </div>
                  </td>';
        }
        for ($i = count($items); $i < 3; $i++) echo '<td class="photo-cell"></td>';
        echo '</tr></table>';
    }

    while ($ir = $db->recorrer($res_ir)):
        if ($ir['seccion_paso'] !== $seccion_actual):
            if (!empty($buffer)) render_row($buffer);
            $buffer = [];
            $seccion_actual = $ir['seccion_paso'];
            echo '<div class="sub-system-bar">Sistema: '.txt($seccion_actual).'</div>';
        endif;

        $buffer[] = [
            'img'    => imagenBase64($ruta_evidencias . $ir['foto_evidencia']),
            'desc'   => txt($ir['descripcion_paso']),
            'estado' => $ir['estado']
        ];

        if (count($buffer) == 3) { render_row($buffer); $buffer = []; }
    endwhile;
    if (!empty($buffer)) render_row($buffer);
    ?>

    <div class="page-break"></div>
    <div class="section-bar">III. &nbsp;Evidencias de Instalación de Kit de Servicio y Hallazgos</div>
    
    <div style="margin-bottom: 20px; color: #666; font-size: 11px; font-style: italic;">
        Registro fotográfico de los componentes sustituidos y trabajos correctivos realizados en la unidad.
    </div>

    <?php
    // 🚀 CONSULTA ACTUALIZADA: Ahora buscamos en orden_evidencias
    $sql_ev = "SELECT * FROM orden_evidencias 
            WHERE id_cita = '$id_cita' 
            AND foto IS NOT NULL 
            AND foto != ''
            ORDER BY id_evidencia ASC";

    $res_ev = $db->ejecutar($sql_ev);
    $buffer_ev = [];

    // Función de renderizado optimizada para la nueva estructura
    function render_evidencias_row($items) {
        echo '<table class="photo-table" cellspacing="0" cellpadding="0" style="margin-bottom:20px; width: 100%;"><tr>';
        foreach ($items as $it) {
            echo '<td class="photo-cell" style="width: 33.3%; padding: 5px;">
                    <div class="card" style="border: 1px solid #1a73e8; border-radius: 8px; overflow: hidden;">
                        <img src="'.$it['img'].'" style="height: 150px; width: 100%; object-fit: cover;">
                        <div class="card-footer" style="background-color: #f0f7ff; padding: 8px; min-height: 45px;">
                            <div style="color: #0f2057; font-weight: bold; font-size: 9px; line-height: 1.2;">
                                '.txt($it['desc']).'
                            </div>
                        </div>
                    </div>
                </td>';
        }
        // Rellenar celdas vacías si hay menos de 3 para mantener el diseño
        for ($i = count($items); $i < 3; $i++) echo '<td class="photo-cell" style="width: 33.3%;"></td>';
        echo '</tr></table>';
    }

    while ($ev = $db->recorrer($res_ev)):
        // Agregamos al buffer usando los nombres de columna de tu nueva tabla
        $buffer_ev[] = [
            'img'  => imagenBase64($ruta_evidencias . $ev['foto']), // 'foto' en lugar de 'foto_evidencia'
            'desc' => $ev['descripcion'] 
        ];

        if (count($buffer_ev) == 3) { 
            render_evidencias_row($buffer_ev); 
            $buffer_ev = []; 
        }
    endwhile;

    // Renderizar el último renglón si sobraron fotos
    if (!empty($buffer_ev)) render_evidencias_row($buffer_ev);

    if ($db->contar($res_ev) == 0) {
        echo '<div style="text-align:center; padding: 40px; color: #999; border: 2px dashed #eee; border-radius: 15px;">
                <i class="fa fa-info-circle"></i> No se registraron evidencias fotográficas en esta orden de servicio.
            </div>';
    }
    ?>
    

    <!-- <div style="margin-top: 50px; padding: 20px; background-color: #f8f9fa; border-radius: 10px; border: 1px solid #e1e8ed;">
        <table width="100%">
            <tr>
                <td width="70%">
                    <div style="font-size: 11px; font-weight: bold; color: #0f2057; text-transform: uppercase;">Certificación Dr. Motors</div>
                    <div style="font-size: 9px; color: #666; margin-top: 5px;">
                        Todos los repuestos instalados cuentan con garantía de fábrica. El trabajo realizado ha sido verificado bajo estándares técnicos de alta precisión.
                    </div>
                </td>
                <td width="30%" align="right">
                    <div style="border-top: 1px solid #000; width: 150px; margin-top: 40px;"></div>
                    <div style="font-size: 9px; text-align: center; width: 150px;">Firma del Jefe de Taller</div>
                </td>
            </tr>
        </table>
    </div> -->

</div><!-- /.wrapper -->
</body>
</html>
<?php
$html = ob_get_clean();
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Expediente_DrMotors_".$d['placa'].".pdf", array("Attachment" => false));
?>
