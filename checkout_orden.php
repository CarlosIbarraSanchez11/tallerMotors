<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();

$id_cita = isset($_GET['id_cita']) ? $_GET['id_cita'] : die("Error: ID no recibido");

// 🚀 1. CONFIGURACIÓN DE RUTAS DEL BUCKET
$url_base = "https://storage.googleapis.com/taller-dr-motors-storage/img/";
$url_ordenes       = $url_base . "ordenes/";
$url_evidencias    = $url_base . "evidencias/";    // Inspección/Checklist
$url_mantenimiento = $url_base . "mantenimiento/"; // Trabajo realizado
$url_lavado        = $url_base . "lavado/";        // Foto final

// CONSULTA PRINCIPAL
$sql = "SELECT ci.*, v.placa, v.marca, v.modelo, cl.nombre_completo, cl.telefono,
               ot.id_orden, ot.km_ingreso, ot.nivel_combustible, ot.observaciones_recepcion, 
               ot.foto_frontal, ot.foto_posterior, ot.foto_tablero, ot.foto_lavado, ot.estado_orden,
               s.nombre_servicio, s.precio_base
        FROM citas ci
        LEFT JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
        LEFT JOIN clientes cl ON ci.id_cliente = cl.id_cliente
        LEFT JOIN ordenes_trabajo ot ON ci.id_cita = ot.id_cita
        LEFT JOIN servicios s ON ci.id_servicio = s.id_servicio
        WHERE ci.id_cita = '$id_cita' LIMIT 1";

$res = $db->ejecutar($sql);
$d = $db->recorrer($res);
$id_orden = $d['id_orden'];

include 'master/header.php';
?>

<div class="container-fluid px-4 py-4 bg-light">
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
        <div class="card-body p-4 p-md-5">
            <div class="row g-5">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center mb-4 mt-5">
                        <div class="bg-success text-white rounded-circle p-2 me-3 shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-camera-retro"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark">Inspección Técnica</h5>
                    </div>

                    <div class="accordion accordion-flush shadow-sm rounded-4 overflow-hidden mb-5" id="accordionInformeSistemas">
                        <?php
                        $sql_ir = "SELECT ir.*, ip.descripcion_paso, ip.seccion_paso FROM inspeccion_resultados ir 
                                   INNER JOIN pasos_servicio ip ON ir.id_paso = ip.id_paso 
                                   WHERE ir.id_cita = '$id_cita' ORDER BY ip.seccion_paso DESC";
                        $res_ir = $db->ejecutar($sql_ir);
                        $seccion_actual = ""; $i = 0;
                        
                        while($ir = $db->recorrer($res_ir)):
                            if ($seccion_actual != $ir['seccion_paso']): 
                                if ($seccion_actual != "") echo '</div></div></div></div>';
                                $seccion_actual = $ir['seccion_paso']; $i++; $target_id = "collapseSystem" . $i;
                        ?>
                            <div class="accordion-item border-bottom">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?php echo ($i > 1) ? 'collapsed' : ''; ?> fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $target_id; ?>">
                                        SISTEMA: <?php echo $seccion_actual; ?>
                                    </button>
                                </h2>
                                <div id="<?php echo $target_id; ?>" class="accordion-collapse collapse <?php echo ($i == 1) ? 'show' : ''; ?>">
                                    <div class="accordion-body bg-light-subtle"><div class="row g-3">
                        <?php endif; ?>
                            <div class="col-md-4 col-lg-3">
                                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
                                    <div class="p-3">
                                        <small class="fw-bold d-block mb-2" style="font-size: 11px;"><?php echo $ir['descripcion_paso']; ?></small>
                                        <?php if($ir['foto_evidencia']): ?>
                                            <div class="rounded-3 overflow-hidden shadow-sm" style="height: 100px;">
                                                <img src="<?php echo $url_evidencias . $ir['foto_evidencia']; ?>" class="w-100 h-100 object-fit-cover">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php if ($seccion_actual != "") echo '</div></div></div></div>'; ?>
                    </div>

                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-success text-white rounded-circle p-2 me-3 shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-tools"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Evidencias del Trabajo Realizado</h5>
                        </div>

                        <div class="row g-3">
                            <?php
                            $sql_ev = "SELECT * FROM orden_evidencias WHERE id_cita = '$id_cita' ORDER BY id_evidencia ASC";
                            $res_ev = $db->ejecutar($sql_ev);
                            while($ev = $db->recorrer($res_ev)):
                            ?>
                            <div class="col-md-4 col-lg-3">
                                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
                                    <div class="position-relative" style="height: 160px;">
                                        <img src="<?php echo $url_mantenimiento . $ev['foto']; ?>" class="w-100 h-100 object-fit-cover">
                                    </div>
                                    <div class="card-body p-3">
                                        <p class="mb-0 fw-bold small"><?php echo $ev['descripcion']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <h6 class="text-dark fw-bold text-uppercase mb-3 small">Fotos de Ingreso</h6>
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <img src="<?php echo $url_ordenes . $d['foto_frontal']; ?>" class="w-100 rounded-3 shadow-sm" style="height: 100px; object-fit: cover;">
                            <p class="text-center x-small mt-1 text-muted">Frontal</p>
                        </div>
                        <div class="col-4">
                            <img src="<?php echo $url_ordenes . $d['foto_posterior']; ?>" class="w-100 rounded-3 shadow-sm" style="height: 100px; object-fit: cover;">
                            <p class="text-center x-small mt-1 text-muted">Posterior</p>
                        </div>
                        <div class="col-4">
                            <img src="<?php echo $url_ordenes . $d['foto_tablero']; ?>" class="w-100 rounded-3 shadow-sm" style="height: 100px; object-fit: cover;">
                            <p class="text-center x-small mt-1 text-muted">Tablero</p>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <small class="fw-bold d-block mb-2 text-primary text-uppercase">Evidencia de Entrega (Lavado):</small>
                        <div class="rounded-4 overflow-hidden border border-primary shadow-sm">
                            <?php if($d['foto_lavado']): ?>
                                <img src="<?php echo $url_lavado . $d['foto_lavado']; ?>" class="w-100" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <span class="text-muted small">Pendiente de Lavado</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap');
body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
.fw-black { font-weight: 900; }
.x-small { font-size: 9px; }
.letter-spacing-tight { letter-spacing: -2px; }
@media print {
    .no-print { display: none !important; }
    .container-fluid { padding: 0 !important; }
    body { background: white !important; }
    .card-header { background: #212529 !important; -webkit-print-color-adjust: exact; }
}
</style>

<?php include 'master/footer.php'; ?>