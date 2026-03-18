<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();

$id_cita = isset($_GET['id_cita']) ? $_GET['id_cita'] : die("Error: ID no recibido");

// 1. CONSULTA PRINCIPAL (Cita, Vehículo, Cliente y Orden)
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

$url_bucket_evidencias = "https://storage.googleapis.com/taller-dr-motors-storage/img/evidencias/";

include 'master/header.php';
?>

<div class="container-fluid px-4 py-4 bg-light">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="citas.php" class="btn btn-white shadow-sm rounded-pill px-4 border-0 text-dark"><i class="fa fa-arrow-left me-2"></i>Volver</a>
        <div>
            <button onclick="window.print();" class="btn btn-white shadow-sm rounded-pill px-4 me-2 border-0 text-dark">
                <i class="fa fa-print me-2 text-secondary"></i>Imprimir
            </button>
            
            <a href="generar_reporte_pdf.php?id_cita=<?php echo $id_cita; ?>" 
            target="_blank" class="btn btn-primary rounded-pill px-4 me-2 shadow-sm border-0">
                <i class="fa fa-file-pdf me-2"></i>Descargar Reporte
            </a>

            <a href="generar_factura.php?id_cita=<?php echo $id_cita; ?>" 
            target="_blank" class="btn btn-danger rounded-pill px-4 me-2 shadow-sm border-0">
                <i class="fa fa-file-invoice me-2"></i>Descargar Factura
            </a>

            <?php if($d['estado'] !== 'FINALIZADO'): // Solo mostrar si no está ya finalizado ?>
                <a href="finalizar_servicio.php?id_cita=<?php echo $id_cita; ?>" 
                class="btn btn-success rounded-pill px-4 shadow-sm border-0"
                onclick="return confirm('¿Confirmas que el vehículo ha sido ENTREGADO al cliente? Esto cerrará el proceso.')">
                    <i class="fa fa-check-double me-2"></i>Entregar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-dark p-4 p-md-5 border-0 text-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="fw-black mb-0 letter-spacing-tight">DR. <span class="text-primary">MOTORS</span></h1>
                    <p class="text-white-50 small fw-bold mb-0">ORDEN DE SERVICIO #<?php echo str_pad($id_orden, 5, "0", STR_PAD_LEFT); ?></p>
                </div>
                <div class="col-md-6 text-md-end mt-3">
                    <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo $d['estado_orden'] ?? 'EN PROCESO'; ?></span>
                </div>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <div class="row mb-5 g-3 border-bottom pb-4">
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 10px;">Cliente</small>
                    <p class="h6 fw-bold mb-0"><?php echo $d['nombre_completo']; ?></p>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 10px;">Vehículo</small>
                    <p class="h6 fw-bold mb-0"><?php echo $d['marca']." ".$d['modelo']; ?> <span class="badge bg-dark ms-1"><?php echo $d['placa']; ?></span></p>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 10px;">KM / Gas</small>
                    <p class="h6 fw-bold mb-0"><?php echo number_format($d['km_ingreso']); ?> KM | <?php echo $d['nivel_combustible']; ?></p>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 10px;">Servicio</small>
                    <p class="h6 fw-bold mb-0 text-primary fw-black"><?php echo $d['nombre_servicio']; ?></p>
                </div>
            </div>

            <div class="row g-5">
                <div class="col-lg-7">
                    
                    <div class="mb-5">
                        <h6 class="text-dark fw-bold text-uppercase mb-3 small"><i class="fa fa-clipboard-list me-2 text-primary"></i>Inventario de Recepción</h6>
                        <div class="row g-2">
                            <?php
                            $sql_check = "SELECT * FROM orden_checklist WHERE id_orden = '$id_orden' AND tipo_item = 'RECEPCION'";
                            $res_check = $db->ejecutar($sql_check);
                            $iconos = ['docs' => 'fa-file-invoice', 'herram' => 'fa-wrench', 'repuesto' => 'fa-tire', 'espejos' => 'fa-eye', 'radio' => 'fa-music', 'vasos' => 'fa-glass-whiskey', 'extintor' => 'fa-fire-extinguisher', 'encendedor' => 'fa-smoking'];
                            
                            while($c = $db->recorrer($res_check)):
                                $ico = $iconos[$c['item_nombre']] ?? 'fa-check';
                            ?>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 border rounded-3 bg-light-subtle">
                                    <i class="fa <?php echo $ico; ?> <?php echo ($c['estado_item']=='OK') ? 'text-success' : 'text-danger'; ?> me-2"></i>
                                    <span class="small text-muted flex-grow-1 text-uppercase" style="font-size: 10px;"><?php echo $c['item_nombre']; ?></span>
                                    <span class="fw-bold small"><?php echo $c['estado_item']; ?></span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-success text-white rounded-circle p-2 me-3 shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-camera-retro"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark">Inspección Técnica</h5>
                    </div>
                    <div class="accordion accordion-flush shadow-sm rounded-4 overflow-hidden mb-5" id="accordionInformeSistemas">
                        <?php
                        // 🚀 1. URL EXACTA DEL BUCKET (Basada en tu imagen de carpetas)
                        $url_bucket_evidencias = "https://storage.googleapis.com/taller-dr-motors-storage/img/evidencias/";

                        // 2. CONSULTA SQL
                        $sql_ir = "SELECT ir.*, ip.descripcion_paso, ip.seccion_paso 
                                    FROM inspeccion_resultados ir 
                                    INNER JOIN pasos_servicio ip ON ir.id_paso = ip.id_paso 
                                    WHERE ir.id_cita = '$id_cita' 
                                    ORDER BY ip.seccion_paso DESC, ip.orden_paso ASC";

                        $res_ir = $db->ejecutar($sql_ir);
                        $seccion_actual = ""; 
                        $i = 0; 
                        
                        while($ir = $db->recorrer($res_ir)):
                            if ($seccion_actual != $ir['seccion_paso']): 
                                if ($seccion_actual != "") echo '</div></div></div></div>'; 
                                $seccion_actual = $ir['seccion_paso'];
                                $i++;
                                $target_id = "collapseSystem" . $i;
                        ?>
                            <div class="accordion-item border-bottom">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?php echo ($i > 1) ? 'collapsed' : ''; ?> fw-bold text-dark bg-white" 
                                            type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $target_id; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary-subtle text-primary rounded-circle p-1 me-3 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                <i class="fa fa-microscope" style="font-size: 14px;"></i>
                                            </div>
                                            <span class="text-uppercase" style="letter-spacing: 0.5px;">SISTEMA: <?php echo $seccion_actual; ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="<?php echo $target_id; ?>" class="accordion-collapse collapse <?php echo ($i == 1) ? 'show' : ''; ?>" data-bs-parent="#accordionInformeSistemas">
                                    <div class="accordion-body bg-light-subtle">
                                        <div class="row g-3">
                        <?php endif; ?>

                            <div class="col-md-4 col-lg-3">
                                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border border-light-subtle overflow-hidden">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="fw-bold text-dark" style="font-size: 11px; line-height: 1.2;">
                                                <?php echo $ir['descripcion_paso']; ?>
                                            </span>
                                            <span class="badge bg-primary rounded-pill px-2 py-1" style="font-size: 8px;">
                                                <?php echo $ir['estado']; ?>
                                            </span>
                                        </div>

                                        <?php 
                                        // 🚀 3. VERIFICACIÓN ULTRA-SEGURA DEL NOMBRE DEL ARCHIVO
                                        $nombre_foto = trim($ir['foto_evidencia']); // Quitamos espacios accidentales
                                        if(!empty($nombre_foto) && $ir['estado'] != 'NO_TIENE'): 
                                        ?>
                                            <div class="rounded-3 overflow-hidden mt-2 position-relative shadow-sm" style="height: 100px;">
                                                <img src="<?php echo $url_bucket_evidencias . $nombre_foto; ?>" 
                                                    class="w-100 h-100 object-fit-cover"
                                                    onerror="this.parentElement.innerHTML='<div class=\"bg-warning-subtle text-warning small p-2 text-center\" style=\"height:100px\">Error al cargar de Google</div>'">
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-light rounded-3 d-flex flex-column align-items-center justify-content-center text-muted mt-2" style="height: 100px; border: 1px dashed #dee2e6;">
                                                <i class="fa fa-camera-slash opacity-25 mb-1"></i>
                                                <span style="font-size: 8px;">Sin registro</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        <?php endwhile; ?>
                        <?php if ($seccion_actual != "") echo '</div></div></div></div>'; ?>
                    </div>

                    <style>
                        #accordionInformeSistemas .collapsing {
                        transition: height 0.8s cubic-bezier(0.4, 0, 0.2, 1);
                        }

                        #accordionInformeSistemas .accordion-button::after {
                        transition: transform 0.6s ease-in-out;
                        }
                    </style>

                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-success text-white rounded-circle p-2 me-3 shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-tools"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Evidencias del Trabajo Realizado</h5>
                        </div>

                        <div class="row g-3">
                            <?php
                            // Consulta para las evidencias de ejecución
                            $sql_ev = "SELECT * FROM orden_evidencias WHERE id_cita = '$id_cita' ORDER BY id_evidencia ASC";
                            $res_ev = $db->ejecutar($sql_ev);
                            $hay_evidencias = false;

                            while($ev = $db->recorrer($res_ev)):
                                $hay_evidencias = true;
                            ?>
                            <div class="col-md-4 col-lg-3">
                                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white border border-light-subtle">
                                    <div class="position-relative" style="height: 160px;">
                                        <img src="img/evidencias/<?php echo $ev['foto']; ?>" class="w-100 h-100 object-fit-cover">
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="badge bg-success shadow-sm rounded-pill" style="font-size: 8px;">
                                                <?php echo $ev['tipo_evidencia']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        <p class="mb-0 fw-bold text-dark small" style="line-height: 1.2;">
                                            <?php echo $ev['descripcion']; ?>
                                        </p>
                                        <small class="text-muted d-block mt-2" style="font-size: 9px;">
                                            <i class="fa fa-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($ev['fecha_subida'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>

                            <?php if(!$hay_evidencias): ?>
                            <div class="col-12">
                                <div class="p-4 border border-dashed rounded-4 text-center bg-light">
                                    <i class="fa fa-camera text-muted opacity-25 fa-2x mb-2"></i>
                                    <p class="text-muted small mb-0">No se registraron evidencias de ejecución para esta orden.</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    
                    <h6 class="text-dark fw-bold text-uppercase mb-3 small"><i class="fa fa-camera me-2 text-primary"></i>Registro Fotográfico de Ingreso</h6>
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <img src="img/ordenes/<?php echo $d['foto_frontal']; ?>" class="w-100 rounded-3 border shadow-sm" style="height: 100px; object-fit: cover;">
                            <p class="text-center x-small mt-1 text-muted">Frontal</p>
                        </div>
                        <div class="col-4">
                            <img src="img/ordenes/<?php echo $d['foto_posterior']; ?>" class="w-100 rounded-3 border shadow-sm" style="height: 100px; object-fit: cover;">
                            <p class="text-center x-small mt-1 text-muted">Posterior</p>
                        </div>
                        <div class="col-4">
                            <img src="img/ordenes/<?php echo $d['foto_tablero']; ?>" class="w-100 rounded-3 border shadow-sm" style="height: 100px; object-fit: cover;">
                            <p class="text-center x-small mt-1 text-muted">KM/Tablero</p>
                        </div>
                    </div>

                    <div class="p-4 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 mb-4">
                        <h6 class="text-primary fw-bold text-uppercase mb-3 small"><i class="fa fa-sparkles me-2"></i>Control de Entrega y Lavado</h6>
                        <ul class="list-unstyled mb-0">
                            <?php
                            $sql_qc = "SELECT * FROM orden_checklist WHERE id_orden = '$id_orden' AND tipo_item = 'CALIDAD'";
                            $res_qc = $db->ejecutar($sql_qc);
                            while($qc = $db->recorrer($res_qc)):
                            ?>
                            <li class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-primary border-opacity-10">
                                <span class="small fw-bold text-dark"><i class="fa fa-check-circle text-primary me-2"></i><?php echo $qc['item_nombre']; ?></span>
                                <span class="badge bg-primary rounded-pill small"><?php echo $qc['estado_item']; ?></span>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>

                    <div class="text-center">
                        <small class="fw-bold d-block mb-2 text-primary text-uppercase" style="font-size: 10px;">Evidencia de Entrega (Lavado):</small>
                        <div class="rounded-4 overflow-hidden border border-primary shadow-sm">
                            <?php if($d['foto_lavado']): ?>
                                <img src="uploads/lavado/<?php echo $d['foto_lavado']; ?>" class="w-100" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <span class="text-muted small">Pendiente de Lavado</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- <div class="row justify-content-end mt-5">
                <div class="col-md-6 col-lg-5">
                    <div class="p-4 rounded-4 bg-white border border-2 border-primary-subtle shadow-sm">
                        <h6 class="fw-bold text-uppercase small mb-4 border-bottom pb-2 text-primary">Detalle de Liquidación</h6>
                        
                        <?php
                        // 1. MANTO. BASE (Viene de la consulta maestra $d)
                        $monto_servicio_base = $d['precio_base']; 
                        
                        // 2. CÁLCULO DE ADICIONALES (Repuestos + su propia Mano de Obra)
                        $sql_extras = "SELECT pr.cantidad, pr.precio_unidad, p.precio_mano_obra, p.nombre_producto
                                    FROM pedidos_repuestos pr
                                    INNER JOIN productos p ON pr.id_producto = p.id_producto
                                    WHERE pr.id_cita = '$id_cita' 
                                    AND pr.estado_pedido IN ('RECIBIDO', 'SOLICITADO POR CLIENTE')
                                    AND pr.precio_unidad > 0"; // Solo los que tienen precio (los adicionales)

                        $res_extras = $db->ejecutar($sql_extras);
                        $total_repuestos_extra = 0;
                        $total_mano_obra_extra = 0;
                        $detalles_html = "";

                        while($extra = $db->recorrer($res_extras)){
                            $sub_repuesto = $extra['cantidad'] * $extra['precio_unidad'];
                            $sub_mo_extra = $extra['cantidad'] * $extra['precio_mano_obra'];
                            
                            $total_repuestos_extra += $sub_repuesto;
                            $total_mano_obra_extra += $sub_mo_extra;

                            // Guardamos el detalle para mostrarlo en el reporte
                            $detalles_html .= '
                            <div class="d-flex justify-content-between mb-1 x-small text-muted ps-2">
                                <span>+ '.$extra['nombre_producto'].' (Cant: '.$extra['cantidad'].')</span>
                                <span>S/ '.number_format($sub_repuesto + $sub_mo_extra, 2).'</span>
                            </div>';
                        }

                        $gran_total = $monto_servicio_base + $total_repuestos_extra + $total_mano_obra_extra;
                        ?>

                        <div class="d-flex justify-content-between mb-3 small fw-bold">
                            <span>Servicio: <?php echo $d['nombre_servicio']; ?></span>
                            <span>S/ <?php echo number_format($monto_servicio_base, 2); ?></span>
                        </div>

                        <?php if($total_repuestos_extra > 0): ?>
                            <small class="text-uppercase fw-bold text-muted d-block mb-2" style="font-size: 9px;">Adicionales (Repuesto + Instalación):</small>
                            <?php echo $detalles_html; ?>
                            <div class="border-top my-2"></div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <span class="h5 fw-black mb-0">TOTAL NETO:</span>
                            <span class="h2 fw-black text-primary mb-0">S/ <?php echo number_format($gran_total, 2); ?></span>
                        </div>
                        
                        <div class="mt-3 p-2 bg-light rounded-3 border text-center" style="font-size: 10px;">
                            <i class="fa fa-info-circle text-primary me-1"></i> El costo incluye repuestos adicionales y mano de obra de instalación.
                        </div>
                    </div>
                </div>
            </div> -->
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