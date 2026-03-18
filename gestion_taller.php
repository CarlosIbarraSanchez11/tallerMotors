<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();

if(!isset($_GET['id_cita'])) { header("Location: citas.php"); exit(); }
$id_cita = $_GET['id_cita'];

// Consulta Maestra: Traemos cita, vehículo, cliente, orden y servicio
$sql = "SELECT ci.*, v.placa, v.marca, v.modelo, v.color, cl.nombre_completo, 
               ot.id_orden, ot.km_ingreso, ot.observaciones_recepcion, 
               s.id_servicio, s.nombre_servicio, s.precio_base
        FROM citas ci
        INNER JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
        INNER JOIN clientes cl ON ci.id_cliente = cl.id_cliente
        LEFT JOIN ordenes_trabajo ot ON ci.id_cita = ot.id_cita
        LEFT JOIN servicios s ON ci.id_servicio = s.id_servicio
        WHERE ci.id_cita = '$id_cita'";

$res = $db->ejecutar($sql);
$d = $db->recorrer($res);

$id_orden_actual = $d['id_orden'];
$id_serv_actual = $d['id_servicio']; // Para los 55 puntos
$labor_descripcion = !empty($d['nombre_servicio']) ? $d['nombre_servicio'] : "Mantenimiento Preventivo";
$labor_precio = !empty($d['precio_base']) ? $d['precio_base'] : 0;

$n_kit_combo = 1;
$n_hallazgo_combo = 1;
$opciones_repuestos = [];

// Misma consulta que usas en tu tabla
$sql_combo_pedidos = "SELECT pr.tipo_procedencia, p.nombre_producto 
                      FROM pedidos_repuestos pr 
                      JOIN productos p ON pr.id_producto = p.id_producto
                      WHERE pr.id_cita = '$id_cita'
                      ORDER BY pr.id_pedido ASC"; 

$res_c = $db->ejecutar($sql_combo_pedidos);

if ($db->contar($res_c) > 0) {
    while($c = $db->recorrer($res_c)) {
        if ($c['tipo_procedencia'] == 'HALLAZGO') {
            $label = "HALLAZGO #" . $n_hallazgo_combo++ . " - " . $c['nombre_producto'];
        } else {
            $label = "KIT DE SERVICIO #" . $n_kit_combo++ . " - " . $c['nombre_producto'];
        }
        $opciones_repuestos[] = $label;
    }
}

$config = $db->recorrer($db->ejecutar("SELECT * FROM config_taller WHERE id_config = 1"));
$v_hh = $config['valor_hh_soles'] ?? 17.00;
// Sumamos el 34% de gastos (alquiler, gestión, etc.)
$pct_gastos = (($config['pct_alquiler'] ?? 7) + ($config['pct_gestion'] ?? 10) + ($config['pct_marketing'] ?? 10) + ($config['pct_herramientas'] ?? 2) + ($config['pct_transporte'] ?? 5)) / 100;
$pct_utilidad = ($config['pct_utilidad'] ?? 30) / 100;

$q_pasos = $db->recorrer($db->ejecutar("SELECT COUNT(*) as total FROM pasos_servicio WHERE id_servicio = '$id_serv_actual'"));
$total_pasos_requeridos = $q_pasos['total'];

$sql_conteo = "SELECT COUNT(*) as total 
               FROM inspeccion_resultados 
               WHERE id_orden = '$id_orden_actual' 
               AND (
                   (estado = 'NO_TIENE') 
                   OR 
                   (estado != 'NO_TIENE' AND foto_evidencia IS NOT NULL AND foto_evidencia != '')
               )";

$q_llenados = $db->recorrer($db->ejecutar($sql_conteo));
$pasos_completados = $q_llenados['total'];

$inspeccion_terminada = ($pasos_completados >= $total_pasos_requeridos && $total_pasos_requeridos > 0);

$q_evidencias = $db->recorrer($db->ejecutar("SELECT COUNT(*) as total FROM orden_evidencias WHERE id_orden = '$id_orden_actual'"));
$tiene_evidencias = ($q_evidencias['total'] > 0);

$puede_finalizar = ($inspeccion_terminada && $tiene_evidencias);

$porcentaje = ($total_pasos_requeridos > 0) ? round(($pasos_completados / $total_pasos_requeridos) * 100) : 0;

// Definir color según avance
$color_progreso = "bg-primary";
if($porcentaje > 40) $color_progreso = "bg-info";
if($porcentaje > 80) $color_progreso = "bg-success";

include 'master/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Gestión de Orden #<?php echo $id_orden_actual; ?></h4>
            <span class="badge bg-info text-white px-3 rounded-pill">Estado: <?php echo $d['estado']; ?></span>
        </div>
        <a href="citas.php" class="btn btn-outline-secondary rounded-pill shadow-sm">
            <i class="fa fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-muted small">INFORMACIÓN GENERAL</h6>
                    <p class="mb-1 small"><b>Placa:</b> <span class="badge bg-dark"><?php echo $d['placa']; ?></span></p>
                    <p class="mb-1 small"><b>Vehículo:</b> <?php echo $d['marca'] . " " . $d['modelo']; ?></p>
                    <p class="mb-3 small"><b>Cliente:</b> <?php echo $d['nombre_completo']; ?></p>
                    
                    <h6 class="fw-bold border-bottom pb-2 mb-2 text-muted small">REPORTE INGRESO</h6>
                    <div class="alert alert-warning small py-2 mb-0 border-0">
                        <?php echo $d['observaciones_recepcion'] ?: 'Sin observaciones'; ?>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-primary small mb-2 text-uppercase">Mano de Obra</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold small text-dark"><?php echo $labor_descripcion; ?></span>
                        <span class="fw-bold text-primary fs-5">S/ <?php echo number_format($labor_precio, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Acordion de las ordenes de Inspección -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-clipboard-check me-2 text-primary"></i>Inspección Técnica</h5>
                            <small class="text-muted">Progreso de revisión de los 55 puntos clave.</small>
                        </div>
                        <div class="text-end">
                            <span class="h4 fw-bold mb-0 <?php echo ($porcentaje == 100) ? 'text-success' : 'text-primary'; ?>">
                                <?php echo $porcentaje; ?>%
                            </span>
                            <small class="d-block text-muted small" style="font-size: 10px;">COMPLETADO</small>
                        </div>
                    </div>
                    
                    <div class="progress rounded-pill shadow-sm" style="height: 12px; background-color: #f0f2f5;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated <?php echo $color_progreso; ?>" 
                            role="progressbar" 
                            style="width: <?php echo $porcentaje; ?>%; transition: width 1s ease-in-out;" 
                            aria-valuenow="<?php echo $porcentaje; ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted" style="font-size: 11px;">
                            <i class="fa fa-check-double me-1"></i> <?php echo $pasos_completados; ?> de <?php echo $total_pasos_requeridos; ?> puntos revisados
                        </small>
                        <?php if($porcentaje == 100): ?>
                            <small class="badge bg-success bg-opacity-10 text-white border border-success border-opacity-25 rounded-pill px-2" style="font-size: 9px;">
                                <i class="fa fa-star me-1"></i>REVISIÓN TOTAL
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
    <form action="Poo/guardar_progreso_inspeccion.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">
        <input type="hidden" name="id_orden" value="<?php echo $id_orden_actual; ?>">

        <div class="accordion accordion-flush border rounded-4 overflow-hidden" id="accInspeccion">
            <?php
            // 🚀 1. DEFINIMOS LA RUTA DEL BUCKET PARA LAS EVIDENCIAS
            $url_bucket_evidencias = "https://storage.googleapis.com/taller-dr-motors-storage/img/evidencias/";

            // CONSULTA CLAVE: Une los pasos con los resultados ya guardados
            $sql_p = "SELECT p.*, r.estado as estado_guardado, r.foto_evidencia 
                        FROM pasos_servicio p
                        LEFT JOIN inspeccion_resultados r ON p.id_paso = r.id_paso AND r.id_orden = '$id_orden_actual'
                        WHERE p.id_servicio = '$id_serv_actual' 
                        ORDER BY p.seccion_paso, p.orden_paso";
            
            $res_p = $db->ejecutar($sql_p);
            $seccion_actual = "";
            $index = 0;

            while($p = $db->recorrer($res_p)):
                if($p['seccion_paso'] != $seccion_actual):
                    if($seccion_actual != "") echo '</table></div></div></div>';
                    $seccion_actual = $p['seccion_paso'];
                    $index++;
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold text-uppercase small" type="button" data-bs-toggle="collapse" data-bs-target="#sec_<?php echo $index; ?>">
                        <i class="fa fa-folder me-2 text-primary"></i> <?php echo $seccion_actual; ?>
                    </button>
                </h2>
                <div id="sec_<?php echo $index; ?>" class="accordion-collapse collapse" data-bs-parent="#accInspeccion">
                    <div class="accordion-body p-0">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <?php endif; 
                            
                            $esta_bloqueado = !empty($p['foto_evidencia']);
                            $disabled = $esta_bloqueado ? 'disabled' : '';
                            $estado = $p['estado_guardado'] ?? null;
                            ?>
                            <tr>
                                <td width="35%" class="small ps-3 fw-semibold text-wrap"><?php echo $p['descripcion_paso']; ?></td>
                                <td width="55%">
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <input type="radio" class="btn-check" name="estado[<?php echo $p['id_paso']; ?>]" id="p<?php echo $p['id_paso']; ?>_ok" value="OK" <?php echo ($estado == 'OK') ? 'checked' : ''; ?> <?php echo $disabled; ?>>
                                        <label class="btn btn-outline-success border-1" for="p<?php echo $p['id_paso']; ?>_ok"><small class="fw-bold">OK</small></label>

                                        <input type="radio" class="btn-check" name="estado[<?php echo $p['id_paso']; ?>]" id="p<?php echo $p['id_paso']; ?>_reg" value="REGULAR" <?php echo ($estado == 'REGULAR') ? 'checked' : ''; ?> <?php echo $disabled; ?>>
                                        <label class="btn btn-outline-warning border-1" for="p<?php echo $p['id_paso']; ?>_reg"><small class="fw-bold">REG.</small></label>
                                        
                                        <input type="radio" class="btn-check" name="estado[<?php echo $p['id_paso']; ?>]" id="p<?php echo $p['id_paso']; ?>_mal" value="MAL" <?php echo ($estado == 'MAL') ? 'checked' : ''; ?> <?php echo $disabled; ?>>
                                        <label class="btn btn-outline-danger border-1" for="p<?php echo $p['id_paso']; ?>_mal"><small class="fw-bold">MAL</small></label>

                                        <input type="radio" class="btn-check" name="estado[<?php echo $p['id_paso']; ?>]" id="p<?php echo $p['id_paso']; ?>_na" value="NO_TIENE" <?php echo ($estado == 'NO_TIENE') ? 'checked' : ''; ?> <?php echo $disabled; ?>>
                                        <label class="btn btn-outline-secondary border-1" for="p<?php echo $p['id_paso']; ?>_na"><small class="fw-bold">N/A</small></label>
                                    </div>
                                    <?php if($esta_bloqueado): ?>
                                        <input type="hidden" name="estado[<?php echo $p['id_paso']; ?>]" value="<?php echo $estado; ?>">
                                    <?php endif; ?>
                                </td>
                                <td width="10%" class="text-end pe-3">
                                    <?php if($esta_bloqueado): ?>
                                        <div class="position-relative d-inline-block">
                                            <img src="<?php echo $url_bucket_evidencias . $p['foto_evidencia']; ?>" 
                                                 class="rounded border border-success shadow-sm" 
                                                 style="width: 38px; height: 38px; object-fit: cover;"
                                                 alt="Evidencia Guardada">
                                            
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-dark p-1 border border-white">
                                                <i class="fa fa-lock text-white" style="font-size: 8px;"></i>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <label class="btn btn-sm btn-light text-muted mb-0 border shadow-sm btn-camera-handler">
                                            <i class="fa fa-camera"></i>
                                            <input type="file" name="foto_punto[<?php echo $p['id_paso']; ?>]" class="d-none input-foto-checklist" accept="image/*" capture="camera">
                                        </label>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($seccion_actual != "") echo '</table></div></div></div>'; ?>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm py-2">
                                <i class="fa fa-save me-2"></i>GUARDAR PROGRESO TÉCNICO
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <style>
                .progress-bar {
                    background-image: linear-gradient(45deg, rgba(255,255,255,.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.15) 50%, rgba(255,255,255,.15) 75%, transparent 75%, transparent) !important;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .bg-info { background-color: #0dcaf0 !important; } /* Un azul más vibrante */
                .bg-success { background-color: #198754 !important; } /* Verde Dr. Motors */
            </style>

            <!-- Parte del Hallazgo -->
            <div class="p-3 bg-light rounded-4 border border-dashed text-center my-3">
                <p class="small text-muted mb-2">¿Encontraste algo que no está en la lista?</p>
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-4 shadow-sm" 
                        onclick="abrirModalHallazgo('INSPECCIÓN GENERAL / ADICIONAL')">
                    <i class="fa fa-plus-circle me-1"></i> NUEVO HALLAZGO GENERAL
                </button>
            </div>

            <!-- Tabla de Hallazgos Adicionales -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-danger border-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0 text-danger">
                            <i class="fa fa-file-invoice-dollar me-2"></i>Presupuestos Adicionales Detectados
                        </h5>
                        <small class="text-muted">Precios calculados según Matriz de Rentabilidad</small>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm px-4 fw-bold" onclick="notificarCliente()">
                        <i class="fab fa-whatsapp me-2"></i> ENVIAR APROBACIÓN
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Detalle del Hallazgo</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-center">Total Presupuesto</th>
                                    <th class="text-center">Estado</th>
                                    <!-- <th class="text-center pe-4">Acción</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Sinceramente, incluimos la cantidad en el SELECT
                                $sql_h = "SELECT h.*, p.nombre_producto, p.marca 
                                        FROM orden_hallazgos h 
                                        LEFT JOIN productos p ON h.id_producto = p.id_producto 
                                        WHERE h.id_orden = '$id_orden_actual'";
                                $res_h = $db->ejecutar($sql_h);
                                
                                if($db->conexion->affected_rows > 0):
                                    while($h = $db->recorrer($res_h)):
                                        // Ahora el total es directo de la columna consolidada
                                        $total_final = $h['precio_producto']; 
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger bg-opacity-10 p-2 rounded-3 me-3">
                                                <i class="fa fa-wrench text-danger"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark text-uppercase" style="font-size: 0.85rem;"><?php echo $h['punto_falla']; ?></div>
                                                <div class="small text-muted">
                                                    <?php echo $h['nombre_producto'] ? $h['nombre_producto'] . " (" . $h['marca'] . ")" : 'Servicio Técnico'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border fw-bold"><?php echo $h['cantidad'] ?? 1; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        // Sinceramente, como consolidamos todo, el total es solo la columna de producto
                                        $total_mostrar = $h['precio_producto']; 
                                        ?>
                                        <div class="fw-bold text-danger fs-6">S/ <?php echo number_format($total_mostrar, 2); ?></div>
                                        <small class="text-muted italic" style="font-size: 0.7rem;">Inc. Instalación y Gastos</small>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        // Sinceramente, definimos los colores exactos en Hexadecimal para que no dependan de Bootstrap
                                        $status_data = [
                                            'PENDIENTE'              => ['bg' => '#fff3cd', 'text' => '#856404', 'border' => '#ffeeba', 'label' => 'POR ENVIAR'],
                                            'ESPERANDO_CONFIRMACION' => ['bg' => '#d1ecf1', 'text' => '#0c5460', 'border' => '#bee5eb', 'label' => 'ENVIADO'],
                                            'APROBADO'               => ['bg' => '#d4edda', 'text' => '#155724', 'border' => '#c3e6cb', 'label' => 'APROBADO'],
                                            'RECHAZADO'              => ['bg' => '#f8d7da', 'text' => '#721c24', 'border' => '#f5c6cb', 'label' => 'RECHAZADO']
                                        ];

                                        $est = $h['estado_aprobacion'] ?? 'PENDIENTE';
                                        $cfg = $status_data[$est] ?? $status_data['PENDIENTE'];
                                        ?>
                                        
                                        <span class="badge rounded-pill" 
                                            style="background-color: <?php echo $cfg['bg']; ?> !important; 
                                                    color: <?php echo $cfg['text']; ?> !important; 
                                                    border: 1px solid <?php echo $cfg['border']; ?> !important;
                                                    padding: 8px 15px;
                                                    font-size: 0.7rem;
                                                    font-weight: 800;
                                                    display: inline-block;">
                                            <i class="fa <?php echo ($est == 'PENDIENTE') ? 'fa-clock' : 'fa-check-circle'; ?> me-1"></i>
                                            <?php echo $cfg['label']; ?>
                                        </span>
                                    </td>
                                    <!-- <td class="text-center pe-4">
                                        <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                            <button class="btn btn-sm btn-white border-end" title="Ver Evidencia" onclick="verEvidencia('<?php echo $h['foto_evidencia']; ?>')">
                                                <i class="fa fa-eye text-muted"></i>
                                            </button>
                                            <button class="btn btn-sm btn-white" onclick="solicitarAprobacion(<?php echo $h['id_hallazgo']; ?>)" title="Notificar">
                                                <i class="fab fa-whatsapp text-success"></i>
                                            </button>
                                        </div>
                                    </td> -->
                                </tr>
                                <?php 
                                    endwhile; 
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="fas fa-box-open fa-3x text-muted mb-2 d-block mx-auto" style="opacity: 0.3;"></i>
                                        
                                        <span class="text-muted small">Sin hallazgos</span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <style>
                .x-small { font-size: 11px; }
                .italic { font-style: italic; }
                /* Animación sutil al pasar el mouse por el botón de notificar */
                .btn-danger:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
                    transition: all 0.2s;
                }
            </style>
           
            <!-- TABLA DEL KIT DE Repuestos Utilizados + Carga de Kit por Servicio -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <h5 class="fw-bold mb-0 text-dark me-3">Repuestos Utilizados</h5>
                        
                        <a href="Poo/cargar_repuestos_default.php?id_cita=<?php echo $id_cita; ?>&id_servicio=<?php echo $id_serv_actual; ?>" 
                        class="btn btn-primary btn-sm rounded-pill shadow-sm px-3">
                            <i class="fa fa-magic me-1"></i> Cargar Kit Base
                        </a>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-dark border me-2" style="font-size: 10px;">
                            <i class="fa fa-sync-alt me-1"></i> Actualizaciones: <span id="total-actualizaciones">0</span>
                        </span>
                        <span class="badge bg-success bg-opacity-10 text-white border-success border-opacity-25 text-white" style="font-size: 10px; color: white">
                            Siguiente en: <span id="countdown">60</span>s
                        </span>
                    </div>
                </div>
                
                <div id="contenedorTablaRepuestos" class="card-body p-0">
                    <?php include 'componentes/tabla_repuestos_contenido.php'; ?>
                </div>
            </div>

            <!-- Fotos de la evidancia del cambio del MANTENIMIENTO -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-success border-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-success">
                        <i class="fa fa-tools me-2"></i>Repuestos Instalados
                    </h5>
                    <button class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSubirEvidencia">
                        <i class="fa fa-plus-circle me-1"></i> Registrar Cambio
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php
                        // 1. Definimos la URL base de tu bucket
                        $url_bucket = "https://storage.googleapis.com/taller-dr-motors-storage/img/evidencias/";

                        // Filtramos por id_orden
                        $sql_ev = "SELECT * FROM orden_evidencias WHERE id_orden = '$id_orden_actual' ORDER BY id_evidencia DESC";
                        $res_ev = $db->ejecutar($sql_ev);
                        
                        if($db->contar($res_ev) > 0):
                            while($ev = $db->recorrer($res_ev)):
                        ?>
                            <div class="col-6 col-md-4">
                                <div class="card h-100 border-0 shadow-sm bg-light rounded-4 overflow-hidden position-relative">
                                    <span class="position-absolute top-0 start-0 m-2 badge rounded-pill bg-success shadow-sm" style="z-index: 10;">
                                        Nuevo
                                    </span>
                                    
                                    <img src="<?php echo $url_bucket . $ev['foto']; ?>" 
                                        class="img-fluid" 
                                        style="height: 140px; width: 100%; object-fit: cover;"
                                        alt="Evidencia">
                                    
                                    <div class="card-body p-2 text-center">
                                        <small class="fw-bold text-uppercase d-block text-truncate text-dark" style="font-size: 0.75rem;">
                                            <?php echo $ev['descripcion']; ?>
                                        </small>
                                        
                                        <hr class="my-1 opacity-25">
                                        
                                        <a href="Poo/eliminar_evidencia.php?id=<?php echo $ev['id_evidencia']; ?>&id_cita=<?php echo $id_cita; ?>" 
                                        class="text-danger small text-decoration-none fw-bold" 
                                        onclick="return confirm('¿Eliminar este repuesto?')">
                                            <i class="fa fa-trash-alt me-1"></i>Borrar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; else: ?>
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="fa fa-box-open fa-3x mb-3 opacity-25"></i>
                                <p class="mb-0">No hay repuestos registrados en esta orden.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Parte del precio  -->
            <div class="card border-0 shadow-sm rounded-4 bg-dark text-white p-4">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="d-flex gap-4">
                            <div><small class="d-block text-white-50 small">Repuestos</small><span class="fw-bold">S/ <?php echo number_format($total_repuestos, 2); ?></span></div>
                            <div class="border-start border-secondary ps-4"><small class="d-block text-white-50 small">Mano Obra</small><span class="fw-bold">S/ <?php echo number_format($labor_precio, 2); ?></span></div>
                        </div>
                    </div>
                    <div class="col-md-5 text-end">
                        <h2 class="fw-bold text-info mb-0">S/ <?php echo number_format($total_repuestos + $labor_precio, 2); ?></h2>
                        <small class="text-white-50 d-block">Total de Orden</small>
                        <span class="badge bg-warning text-dark mt-2" style="font-size: 10px; letter-spacing: 0.5px;">
                            <i class="fa fa-exclamation-circle me-1"></i> PRECIOS NO INCLUYEN IGV
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-end">
                <?php if ($puede_finalizar): ?>
                    <a href="Poo/finalizar_orden.php?id_cita=<?php echo $id_cita; ?>" 
                    class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow-lg"
                    onclick="return confirm('¿Finalizar mantenimiento? Se pasará a Lavado.')">
                        TERMINAR TRABAJO <i class="fa fa-flag-checkered ms-2"></i>
                    </a>
                <?php else: ?>
                    <div class="d-inline-block text-end">
                        <div class="alert alert-light border-0 shadow-sm rounded-4 py-2 px-3 mb-2 small text-start">
                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.75rem;">REQUISITOS PARA TERMINAR:</h6>
                            <ul class="list-unstyled mb-0" style="font-size: 0.7rem;">
                                <li class="<?php echo $inspeccion_terminada ? 'text-success' : 'text-danger'; ?>">
                                    <i class="fa <?php echo $inspeccion_terminada ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                    Inspección completa: <?php echo $pasos_completados; ?>/<?php echo $total_pasos_requeridos; ?> puntos.
                                </li>
                                <li class="<?php echo $tiene_evidencias ? 'text-success' : 'text-danger'; ?>">
                                    <i class="fa <?php echo $tiene_evidencias ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                    Fotos de repuestos instalados (Mínimo 1).
                                </li>
                            </ul>
                        </div>
                        <button class="btn btn-secondary btn-lg rounded-pill px-5 fw-bold opacity-50" disabled>
                            TERMINAR TRABAJO <i class="fa fa-lock ms-2"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- <div class="mt-4 text-end">

                <a href="Poo/finalizar_orden.php?id_cita=<?php echo $id_cita; ?>"

                   class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow-lg"

                   onclick="return confirm('¿Finalizar mantenimiento? Se pasará a Lavado.')">

                    TERMINAR TRABAJO <i class="fa fa-flag-checkered ms-2"></i>

                </a>

            </div> -->
        </div>
    </div>
</div>

<!-- Modal para subir la Evidencia -->
<div class="modal fade" id="modalSubirEvidencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="Poo/guardar_evidencia_mantenimiento.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-success bg-opacity-10 text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 15px;">
                            <i class="fa fa-camera fa-2x"></i>
                        </div>
                        <h5 class="fw-bold">Captura de Evidencia</h5>
                        <p class="text-muted small">Registra la instalación de repuestos <b>ENTREGADOS</b>.</p>
                    </div>

                    <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">
                    <input type="hidden" name="id_orden" value="<?php echo $id_orden_actual; ?>">
                    <input type="hidden" name="nombre_item_texto" id="input_descripcion_texto">

                    <div class="mb-3">
                        <label class="small fw-bold text-secondary mb-2">Repuesto Instalado</label>
                        <select name="id_producto" id="select_evidencia_mante" class="form-select shadow-sm border-0 bg-light py-2" required onchange="capturarTextoCombo()">
                            <option value="">-- Seleccionar --</option>
                            <?php
                            // 🚀 FILTRO MAESTRO: Solo estado 'ENTREGADO'
                            $sql_m = "SELECT pr.*, p.nombre_producto 
                                      FROM pedidos_repuestos pr 
                                      JOIN productos p ON pr.id_producto = p.id_producto 
                                      WHERE pr.id_cita = '$id_cita' 
                                      AND pr.estado_pedido = 'RECIBIDO'
                                      ORDER BY pr.tipo_procedencia DESC, pr.id_pedido ASC";
                            
                            $res_m = $db->ejecutar($sql_m);
                            $hay_entregados = false;
                            $nk = 1; $nh = 1;

                            if($db->contar($res_m) > 0):
                                while($rm = $db->recorrer($res_m)):
                                    $hay_entregados = true;
                                    $label = ($rm['tipo_procedencia'] == 'HALLAZGO') ? "HALLAZGO #".$nh++ : "KIT DE SERVICIO #".$nk++;
                                    $texto_completo = $label . " - " . $rm['nombre_producto'];
                            ?>
                                <option value="<?php echo $rm['id_producto']; ?>">
                                    <?php echo $texto_completo; ?>
                                </option>
                            <?php 
                                endwhile; 
                            endif; 

                            if(!$hay_entregados):
                            ?>
                                <option value="" disabled>No hay repuestos entregados por almacén aún</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="btn btn-outline-success w-100 py-4 border-dashed rounded-4 bg-light bg-opacity-10" style="border-style: dashed !important; border-width: 2px; cursor: pointer;" id="label-foto-evidencia">
                            <i class="fa fa-camera fa-2x d-block mb-2"></i>
                            <span class="fw-bold" id="texto-boton-foto">Tomar Foto del Repuesto</span>
                            <input type="file" name="foto_item" class="d-none" accept="image/*" capture="camera" required onchange="previewImagenEvidencia(event)">
                        </label>
                    </div>

                    <div id="container-preview-evidencia" class="d-none mb-4 text-center">
                        <img id="img-preview-evidencia" src="#" class="img-fluid rounded-4 shadow" style="max-height: 200px; border: 3px solid #198754;">
                        <p class="small text-success mt-2 fw-bold"><i class="fa fa-check-circle"></i> Imagen capturada</p>
                    </div>

                    <div class="row g-2">
                        <div class="col-8">
                            <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold py-2 shadow">
                                <i class="fa fa-save me-2"></i>GUARDAR EVIDENCIA
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-light w-100 rounded-pill fw-bold py-2" data-bs-dismiss="modal">CERRAR</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// 🚀 Función para atrapar el texto del combo y meterlo al hidden
function capturarTextoCombo() {
    var combo = document.getElementById("select_evidencia_mante");
    var texto = combo.options[combo.selectedIndex].text;
    document.getElementById("input_descripcion_texto").value = texto;
}

function previewImagen(event) {
    const input = event.target;
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('img-preview-hallazgo');
        const container = document.getElementById('container-preview');
        output.src = reader.result;
        container.classList.remove('d-none');
    };
    if(input.files[0]) reader.readAsDataURL(input.files[0]);
}
</script>


<div class="modal fade" id="modalHallazgo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="Poo/guardar_orden_hallazgo.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <h5 class="fw-bold mb-3 text-primary"><i class="fa fa-search me-2"></i>Registrar Hallazgo</h5>
                    
                    <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">
                    <input type="hidden" name="id_orden" value="<?php echo $id_orden_actual; ?>">

                    <input type="hidden" name="precio_producto" id="h_pventa_hidden"> 
                    <input type="hidden" name="precio_mano_obra" id="h_pobra_hidden">

                    <div class="mb-3">
                        <label class="small fw-bold">Punto de Falla / Componente</label>
                        <input type="text" name="punto_falla" id="h_paso" class="form-control bg-light" value="INSPECCIÓN GENERAL / ADICIONAL">
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Descripción del Hallazgo</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="Describa el problema detectado..."></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4">
                            <label class="small fw-bold">Cant.</label>
                            <input type="number" name="cantidad" id="h_cant" class="form-control" value="1" min="1" oninput="actualizarPreciosHallazgo()">
                        </div>
                        <div class="col-8">
                            <label class="small fw-bold">Producto / Repuesto</label>
                            <select name="id_producto" id="select_producto" class="form-select shadow-sm" onchange="actualizarPreciosHallazgo()">
                                <option value="">-- Seleccionar --</option>
                                <?php
                                $sql_prod = "SELECT p.id_producto, p.nombre_producto, p.marca, c.precio_compra, c.tiempo_hh_obra 
                                            FROM productos p 
                                            INNER JOIN costos_productos c ON p.id_producto = c.id_producto 
                                            WHERE p.stock_actual > 0 
                                            ORDER BY p.nombre_producto ASC";
                                $res_prod = $db->ejecutar($sql_prod);
                                while($pr = $db->recorrer($res_prod)):
                                ?>
                                    <option value="<?php echo $pr['id_producto']; ?>" 
                                            data-compra="<?php echo $pr['precio_compra']; ?>" 
                                            data-tiempo="<?php echo $pr['tiempo_hh_obra']; ?>">
                                        <?php echo $pr['nombre_producto'] . " (" . $pr['marca'] . ")"; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="bg-primary bg-opacity-10 p-3 rounded-4 text-center border border-primary border-opacity-25 mb-3">
                        <small class="text-primary d-block fw-bold mb-1" style="font-size: 11px; letter-spacing: 1px;">PRESUPUESTO ESTIMADO (INC. MATRIZ)</small>
                        <h3 class="fw-bold mb-0 text-primary">S/ <span id="h_total_label">0.00</span></h3>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted">Evidencia Fotográfica de la Falla</label>
                        <input type="file" name="foto_falla" class="form-control form-control-sm" accept="image/*" capture="camera" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-3 shadow-lg">
                        <i class="fa fa-save me-2"></i>CONFIRMAR HALLAZGO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Arreglo para el hover del capturador de fotos */
.btn-outline-success.border-dashed:hover {
    background-color: rgba(25, 135, 84, 0.1) !important; /* Un verde muy suave al pasar el mouse */
    color: #198754 !important; /* Mantiene el texto verde fuerte */
    border-color: #198754 !important;
}

.btn-outline-success.border-dashed:hover i,
.btn-outline-success.border-dashed:hover span {
    color: #198754 !important; /* Asegura que el icono y el texto no se vuelvan blancos */
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/compressorjs/1.2.1/compressor.min.js"></script>

<script>
    // Pasamos la matriz a JS para que el cálculo sea instantáneo
    const MATRIZ_CONFIG = {
        valor_hh: <?php echo $v_hh; ?>,
        pct_gastos: <?php echo $pct_gastos; ?>,
        pct_utilidad: <?php echo $pct_utilidad; ?>
    };
                                    
    /* 1. GESTIÓN DE MODALES */
    function abrirModalHallazgo(paso) {
        const inputPaso = document.getElementById('h_paso');
        if(inputPaso) inputPaso.value = paso;
        
        const modalElement = document.getElementById('modalHallazgo');
        if(modalElement) {
            const myModal = bootstrap.Modal.getOrCreateInstance(modalElement);
            myModal.show();
        }
    }

    function abrirResolucionHallazgo(id, descripcion) {
        const inputId = document.getElementById('sol_id_hallazgo');
        const textDesc = document.getElementById('sol_desc_hallazgo');
        if(inputId) inputId.value = id;
        if(textDesc) textDesc.innerText = descripcion;
        
        const modalElement = document.getElementById('modalResolverHallazgo');
        if(modalElement) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.show();
        }
    }

    function nuevoHallazgoGeneral() {
        abrirModalHallazgo("INSPECCIÓN GENERAL / ADICIONAL");
    }

    /**
     * 2. COMPRESIÓN DE IMÁGENES (EL MOTOR DE PESO LIGERO)
     * Esta función reduce la imagen a 800px de ancho y calidad 0.6*/
    function procesarYComprimirImagen(file, callback) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function(event) {
            const img = new Image();
            img.src = event.target.result;
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const maxWidth = 800; // Ancho ideal para reportes técnicos
                const scaleSize = maxWidth / img.width;
                
                canvas.width = maxWidth;
                canvas.height = img.height * scaleSize;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                // Exportamos como JPEG con calidad 0.6 (60%)
                // Esto suele dejar la imagen entre 60kb y 90kb
                const dataUrl = canvas.toDataURL('image/jpeg', 0.6);
                
                // Convertimos el DataURL a un archivo real para que el PHP lo reciba normal
                fetch(dataUrl)
                    .then(res => res.blob())
                    .then(blob => {
                        const compressedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        callback(compressedFile, dataUrl);
                    });
            };
        };
    }

    /* 3. LISTENERS PARA EVENTOS */
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('input-foto-checklist')) {
            const file = e.target.files[0];
            if (!file) return;

            const label = e.target.closest('.btn-camera-handler');
            
            // Función de compresión (asegúrate de tenerla definida)
            procesarYComprimirImagen(file, function(compressedFile, dataUrl) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                e.target.files = dataTransfer.files;

                // Cambio visual: De cámara a Check Azul
                label.classList.replace('btn-light', 'btn-primary');
                label.classList.replace('text-muted', 'text-white');
                label.querySelector('i').className = 'fa fa-check';
            });
        }
    });

    /* 4. VISTA PREVIA (Para modales individuales) */
    function previewImage(event) {
        const input = event.target;
        const file = input.files[0];
        if (!file) return;

        const preview = document.getElementById('img-preview');
        const container = document.getElementById('preview-container');
        const dropArea = document.getElementById('drop-area');

        procesarYComprimirImagen(file, function(compressedFile, dataUrl) {
            // Reemplazamos el archivo en el input por el comprimido
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(compressedFile);
            input.files = dataTransfer.files;

            // Mostramos la vista previa
            if(preview) preview.src = dataUrl;
            if(container) container.classList.remove('d-none');
            if(dropArea) dropArea.classList.add('d-none');
        });
    }

    function actualizarPreciosHallazgo() {
        const select = document.getElementById('select_producto');
        const option = select.options[select.selectedIndex];
        const cant = parseInt(document.getElementById('h_cant').value) || 1;
        
        // 🚀 OJO AQUÍ: Los IDs deben ser idénticos a los del HTML
        const labelTotal = document.getElementById('h_total_label');
        const inputVentaHidden = document.getElementById('h_pventa_hidden'); // Antes decía h_pventa
        const inputObraHidden = document.getElementById('h_pobra_hidden');   // Antes decía h_pobra

        if (!option || option.value === "") {
            if(labelTotal) labelTotal.innerText = "0.00";
            return;
        }

        // 1. Extraer datos base
        const costoCompra = parseFloat(option.getAttribute('data-compra')) || 0;
        const tiempoHH = parseFloat(option.getAttribute('data-tiempo')) || 0;

        // 2. Aplicar Matriz (HH: 17.00 | Gastos: 34% | Utilidad: 30%)
        const moBase = tiempoHH * MATRIZ_CONFIG.valor_hh;
        const factorMatriz = (1 + MATRIZ_CONFIG.pct_gastos) * (1 + MATRIZ_CONFIG.pct_utilidad);

        const totalConsolidado = (costoCompra + moBase) * factorMatriz * cant;

        // 3. ASIGNACIÓN CORRECTA: Ahora sí encontrará el elemento
        if(inputVentaHidden) inputVentaHidden.value = totalConsolidado.toFixed(2);
        if(inputObraHidden) inputObraHidden.value = "0.00";

        // 4. Mostrar en pantalla
        if(labelTotal) {
            labelTotal.innerText = totalConsolidado.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }

    function abrirModalHallazgo(paso) {
        const inputPaso = document.getElementById('h_paso');
        if(inputPaso) {
            inputPaso.value = paso;
        }
        
        // Inicialización segura del modal de Bootstrap 5
        const modalElement = document.getElementById('modalHallazgo');
        if(modalElement) {
            const myModal = bootstrap.Modal.getOrCreateInstance(modalElement);
            myModal.show();
        } else {
            console.error("No se encontró el modal con ID: modalHallazgo");
        }
    }

    function notificarCliente() {
        if(confirm("¿Enviar el presupuesto actual al cliente por WhatsApp?")) {
            const id_cita = "<?php echo $id_cita; ?>";
            fetch('Poo/notificar_cliente_aprobacion.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id_cita=' + id_cita
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                location.reload();
            });
        }
    }
</script>

<script>
    let segundosRestantes = 60;
    let totalRefrescos = 0;

    function iniciarTemporizador() {
        const displayCountdown = document.getElementById('countdown');
        const displayTotal = document.getElementById('total-actualizaciones');
        
        setInterval(() => {
            segundosRestantes--;
            
            if (segundosRestantes < 0) {
                recargarTablaRepuestos();
                segundosRestantes = 60; 
                
                totalRefrescos++;
                displayTotal.innerText = totalRefrescos;
            }
            
            displayCountdown.innerText = segundosRestantes;
        }, 1000);
    }

    function recargarTablaRepuestos() {
        const contenedor = document.getElementById('contenedorTablaRepuestos');
        const status = document.getElementById('status-actualizacion');
        const laborPrecio = <?php echo $labor_precio; ?>; 

        if(status) status.innerHTML = '<i class="fa fa-sync fa-spin"></i> Actualizando...';

        // Una sola URL, una sola petición
        const url = 'componentes/tabla_repuestos_contenido.php?id_cita=<?php echo $id_cita; ?>&ajax=1';

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Error en el servidor');
                return response.json();
            })
            .then(data => {
                // 1. Actualizamos la tabla
                if(contenedor) contenedor.innerHTML = data.html;
                
                // 2. Actualizamos el monto de Repuestos
                const elMontoRep = document.getElementById('monto-repuestos');
                if(elMontoRep) elMontoRep.innerText = 'S/ ' + data.total_formateado;

                // 3. Calculamos y actualizamos el Gran Total
                const elMontoTotal = document.getElementById('monto-total-orden');
                if(elMontoTotal) {
                    let granTotal = parseFloat(data.total_repuestos) + parseFloat(laborPrecio);
                    elMontoTotal.innerText = 'S/ ' + granTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
                }

                if(status) status.innerHTML = 'Última actualización: ' + new Date().toLocaleTimeString();
            })
            .catch(error => {
                console.error('Error:', error);
                if(status) status.innerHTML = '<span class="text-danger">Error de conexión</span>';
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        iniciarTemporizador();
    });
</script>

<style>
.border-dashed { border-style: dashed !important; border-color: #dee2e6 !important; }
.accordion-button:not(.collapsed) { background-color: #f8f9fa; color: #0d6efd; box-shadow: none; }
.accordion-button:focus { box-shadow: none; border-color: rgba(0,0,0,.125); }
.x-small { font-size: 0.75rem; }
</style>

<?php 
include 'modales/modal_consumo_repuesto.php';
include 'master/footer.php'; 
?>