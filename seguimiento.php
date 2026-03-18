<?php
require_once "Poo/Conexion.php";
$db = new Conexion();

// 1. Validar el Token de la URL
$token = $_GET['t'] ?? '';

if (empty($token)) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>Acceso no autorizado</h3><p>El enlace es inválido o ha expirado.</p></div>");
}

// 2. Obtener datos de la Cita, Cliente y Servicio
$sql_cita = "SELECT c.*, cl.nombre_completo, s.nombre_servicio 
             FROM citas c
             INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
             INNER JOIN servicios s ON c.id_servicio = s.id_servicio
             WHERE c.token_confirmacion = '$token' LIMIT 1";

$res_cita = $db->ejecutar($sql_cita);
$cita = $db->recorrer($res_cita);

if (!$cita) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>Cita no encontrada</h3><p>Verifique el enlace enviado.</p></div>");
}

$id_cita = $cita['id_cita'];
$id_serv = $cita['id_servicio'];

// --- 3. LÓGICA DE PROGRESO POR ÁREAS (Agrupado por seccion_paso) ---
$sql_areas = "SELECT 
                p.seccion_paso, 
                COUNT(p.id_paso) as total_area,
                COUNT(r.id_paso) as listos_area
              FROM pasos_servicio p
              LEFT JOIN inspeccion_resultados r ON p.id_paso = r.id_paso AND r.id_cita = '$id_cita'
              WHERE p.id_servicio = '$id_serv'
              GROUP BY p.seccion_paso";

$res_areas = $db->ejecutar($sql_areas);
$progreso_por_area = [];
$total_general = 0;
$listos_general = 0;

while($area = $db->recorrer($res_areas)){
    $total_general += $area['total_area'];
    $listos_general += $area['listos_area'];
    
    $porc = ($area['total_area'] > 0) ? round(($area['listos_area'] / $area['total_area']) * 100) : 0;
    $progreso_por_area[$area['seccion_paso']] = $porc;
}

// Porcentaje general del carro
$porcentaje_general = ($total_general > 0) ? round(($listos_general / $total_general) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Inspección - iCURA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; color: #1c1e21; }
        .card-main { border-radius: 20px; border: none; overflow: hidden; }
        .progress-main { height: 12px; border-radius: 10px; background-color: #e4e6eb; }
        .progress-area { height: 6px; border-radius: 10px; background-color: #e4e6eb; }
        .status-badge { font-size: 0.7rem; padding: 5px 12px; border-radius: 50px; font-weight: 700; text-transform: uppercase; }
        .img-evidencia { width: 60px; height: 60px; object-fit: cover; border-radius: 12px; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .section-header { background-color: #ffffff; padding: 15px; border-bottom: 1px solid #f0f2f5; margin-top: 10px; }
        .inspection-row { transition: background 0.2s; }
        .inspection-row:active { background-color: #f8f9fa; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="text-center mb-4">
        <img src="image/logo_taller.png" alt="iCURA Logo" style="height: 45px;">
        <!-- <p class="text-muted small mt-2">Sistema de Gestión IPS Global</p> -->
    </div>

    <div class="card card-main shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="text-muted small mb-0">Cliente</h6>
                    <h5 class="fw-bold mb-0"><?php echo $cita['nombre_completo']; ?></h5>
                </div>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?php echo $cita['estado']; ?></span>
            </div>
            
            <div class="mb-2 d-flex justify-content-between align-items-end">
                <span class="small fw-bold text-secondary">Progreso General</span>
                <span class="h4 fw-bold text-primary mb-0"><?php echo $porcentaje_general; ?>%</span>
            </div>
            <div class="progress progress-main">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: <?php echo $porcentaje_general; ?>%"></div>
            </div>
            <div class="mt-3 small text-muted">
                <i class="fa fa-car me-1"></i> Servicio: <strong><?php echo $cita['nombre_servicio']; ?></strong>
            </div>
        </div>
    </div>

    <h6 class="fw-bold px-2 mb-3 text-secondary"><i class="fa fa-tasks me-2"></i>DETALLE DE INSPECCIÓN</h6>

    <div class="accordion accordion-flush shadow-sm rounded-4 overflow-hidden" id="accordionInspeccion">
        <?php
        // 🚀 1. DEFINIMOS LA RUTA BASE DEL BUCKET
        $url_bucket_evidencias = "https://storage.googleapis.com/taller-dr-motors-storage/img/evidencias/";

        $sql_res = "SELECT p.seccion_paso, p.descripcion_paso, r.estado, r.foto_evidencia
                    FROM pasos_servicio p
                    LEFT JOIN inspeccion_resultados r ON p.id_paso = r.id_paso AND r.id_cita = '$id_cita'
                    WHERE p.id_servicio = '$id_serv'
                    ORDER BY p.seccion_paso, p.orden_paso";
        
        $res_puntos = $db->ejecutar($sql_res);
        $seccion_actual = "";
        $contador = 0;

        while($r = $db->recorrer($res_puntos)):
            if($r['seccion_paso'] != $seccion_actual):
                if($seccion_actual != "") echo '</div></div></div>'; 
                
                $seccion_actual = $r['seccion_paso'];
                $porc_area = $progreso_por_area[$seccion_actual] ?? 0;
                $id_collapse = "collapse_" . $contador; 
        ?>
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $id_collapse; ?>">
                            <div class="w-100 me-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark small text-uppercase"><?php echo $seccion_actual; ?></span>
                                    <span class="fw-bold text-primary small"><?php echo $porc_area; ?>%</span>
                                </div>
                                <div class="progress progress-area" style="height: 4px;">
                                    <div class="progress-bar bg-primary" style="width: <?php echo $porc_area; ?>%"></div>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="<?php echo $id_collapse; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionInspeccion">
                        <div class="accordion-body p-0 bg-light">
        <?php 
                $contador++;
            endif; 

            // Lógica de iconos y colores
            $color = 'secondary'; $icon = 'fa-circle-question';
            if($r['estado'] == 'OK') { $color = 'success'; $icon = 'fa-check-circle'; }
            elseif($r['estado'] == 'REGULAR') { $color = 'warning'; $icon = 'fa-exclamation-circle'; }
            elseif($r['estado'] == 'MAL') { $color = 'danger'; $icon = 'fa-times-circle'; }
            
            // Preparamos la URL completa de la imagen si existe
            $url_foto_final = !empty($r['foto_evidencia']) ? $url_bucket_evidencias . $r['foto_evidencia'] : null;
        ?>
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom bg-white mx-2 my-1 rounded-3 shadow-sm">
                <div style="flex: 1;" class="pe-3">
                    <div class="small fw-medium text-dark mb-1"><?php echo $r['descripcion_paso']; ?></div>
                    <span class="badge bg-<?php echo $color; ?>-subtle text-<?php echo $color; ?> status-badge" style="font-size: 0.65rem;">
                        <i class="fa <?php echo $icon; ?> me-1"></i> <?php echo $r['estado'] ?? 'Pendiente'; ?>
                    </span>
                </div>
                <div>
                    <?php if($url_foto_final): ?>
                        <img src="<?php echo $url_foto_final; ?>" 
                            class="img-evidencia rounded-3 shadow-sm" 
                            style="width:50px; height:50px; object-fit: cover; cursor: pointer;"
                            onclick="abrirImagen('<?php echo $url_foto_final; ?>', '<?php echo addslashes($r['descripcion_paso']); ?>')">
                    <?php else: ?>
                        <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width:50px; height:50px; border: 1px dashed #ccc;">
                            <i class="fa fa-camera text-muted opacity-25"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
        <?php if($seccion_actual != "") echo '</div></div></div>'; ?>
    </div>

    <div class="text-center mt-4 pb-5">
        <p class="small text-muted mb-1">© 2026 IPS Global - Ingeniería de Software</p>
        <div class="badge bg-light text-muted border" style="font-size: 0.6rem;">ID-CITA: <?php echo $id_cita; ?> | TOKEN: <?php echo substr($token, 0, 8); ?></div>
    </div>
</div>

<div class="modal fade" id="modalGaleria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg p-3">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 shadow" data-bs-dismiss="modal" aria-label="Close"></button>
                <img src="" id="imgCargaModal" class="img-fluid rounded-4 shadow-lg border border-white border-2">
                <div id="captionModal" class="text-white mt-3 fw-bold small bg-dark bg-opacity-50 py-2 rounded-pill d-inline-block px-4"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalBoostrap = new bootstrap.Modal(document.getElementById('modalGaleria'));
    
    function abrirImagen(url, descripcion) {
        document.getElementById('imgCargaModal').src = url;
        document.getElementById('captionModal').innerText = descripcion;
        modalBoostrap.show();
    }
</script>
</body>
</html>