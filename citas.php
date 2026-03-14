<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();
include 'master/header.php'; 

// Capturamos la fecha del filtro (por defecto la de hoy)
$fecha_filtro = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$id_taller = $_SESSION['id_taller'] ?? null;
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Agenda de Servicios</h4>
            <small class="text-muted">Control de ingresos y tiempos de bahía para el taller</small>
        </div>
        <div class="d-flex align-items-center">
            <label class="me-2 small fw-bold text-muted">FECHA:</label>
            <input type="date" id="filtro_fecha" class="form-control shadow-sm" 
                   value="<?php echo $fecha_filtro; ?>" 
                   onchange="window.location.href='citas.php?fecha='+this.value">
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Horario</th>
                            <th>Vehículo / Cliente</th>
                            <th>Servicio Solicitado</th>
                            <th>Técnico</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // CONSULTA MAESTRA CON JOINS
                        $sql = "SELECT ci.*, 
                                    cl.nombre_completo AS cliente, 
                                    ve.placa, ve.marca, ve.modelo,
                                    se.nombre_servicio, se.especialidad, se.tipo_raiz, -- <-- AQUÍ ESTABA EL ERROR
                                    us.nombre AS tecnico_nombre
                                FROM citas ci
                                JOIN clientes cl ON ci.id_cliente = cl.id_cliente
                                JOIN vehiculos ve ON ci.id_vehiculo = ve.id_vehiculo
                                JOIN servicios se ON ci.id_servicio = se.id_servicio
                                JOIN usuarios us ON ci.id_tecnico = us.id_usuario
                                WHERE ci.id_taller = '$id_taller' 
                                AND ci.fecha_cita = '$fecha_filtro'
                                AND ci.estado != 'FINALIZADO'
                                ORDER BY ci.hora_inicio ASC";

                        $res = $db->ejecutar($sql);
                        
                        if ($db->conexion->affected_rows > 0):
                            while($c = $db->recorrer($res)):
                                // Lógica de badges por estado
                                $color = 'warning'; // Amarillo por defecto (Pendiente / En Proceso)

                                if($c['estado'] == 'COMPLETADA' || $c['estado'] == 'FINALIZADO') {
                                    $color = 'success'; // Verde
                                } elseif($c['estado'] == 'CANCELADA') {
                                    $color = 'danger';  // Rojo
                                } elseif($c['estado'] == 'POR ENTREGAR') {
                                    $color = 'info';    // Celeste/Azul claro (¡Este es el nuevo!)
                                } elseif($c['estado'] == 'LAVADO') {
                                    $color = 'primary'; // Azul oscuro
                                }
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="badge bg-primary bg-opacity-10 text-primary p-2 me-2">
                                        <i class="fa fa-clock"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <?php echo date('H:i', strtotime($c['hora_inicio'])); ?> - 
                                            <?php echo date('H:i', strtotime($c['hora_fin'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold mb-0 text-uppercase"><?php echo $c['placa']; ?></div>
                                <div class="small text-muted"><?php echo $c['marca']." ".$c['modelo']; ?> | <?php echo $c['cliente']; ?></div>
                            </td>
                            <td>
                                <?php 
                                    // 🚀 LÓGICA DE COLORES POR TIPO DE SERVICIO
                                    $colorServicio = 'info'; // Color celeste por defecto
                                    $iconoServicio = 'fa-info-circle';

                                    if ($c['especialidad'] == 'DIAGNOSTICO') {
                                        $colorServicio = 'danger'; // Rojo para Diagnóstico
                                        $iconoServicio = 'fa-search';
                                    } elseif ($c['especialidad'] == 'MANTENIMIENTO' || $c['especialidad'] == 'PREVENTIVO') {
                                        $colorServicio = 'success'; // Verde para Mantenimiento
                                        $iconoServicio = 'fa-tools';
                                    }
                                ?>
                                <span class="badge bg-<?php echo $colorServicio; ?> bg-opacity-10 text-<?php echo $colorServicio; ?> border border-<?php echo $colorServicio; ?> border-opacity-25 px-3">
                                    <i class="fa <?php echo $iconoServicio; ?> me-1"></i>
                                    <?php echo $c['nombre_servicio']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center small">
                                    <div class="bg-secondary rounded-circle me-2" style="width:8px; height:8px;"></div>
                                    <span class="fw-semibold text-secondary"><?php echo $c['tecnico_nombre']; ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $color; ?> rounded-pill px-3">
                                    <?php echo $c['estado']; ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
    
                                <?php if($c['estado'] == 'PENDIENTE' && in_array($nivel, ['ADMINISTRADOR', 'CALL', 'JEFE MECÁNICOS', 'GERENTE'])): ?>
                                    <button type="button" 
                                            onclick="abrirModalTecnicos(<?php echo $c['id_cita']; ?>, '<?php echo $c['tipo_raiz']; ?>')" 
                                            class="btn btn-sm btn-light text-success shadow-sm rounded-circle me-1" 
                                            title="Asignar Técnicos e Iniciar">
                                        <i class="fa fa-play"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if($c['estado'] == 'EN PROCESO' && in_array($nivel, ['ADMINISTRADOR', 'MECANICO', 'JEFE MECÁNICOS', 'GERENTE'])): ?>
                                    <?php $destino = ($c['especialidad'] == 'DIAGNOSTICO') ? 'ejecucion_diagnostico.php' : 'gestion_taller.php'; ?>
                                    <a href="<?php echo $destino; ?>?id_cita=<?php echo $c['id_cita']; ?>" 
                                    class="btn btn-sm btn-info text-white shadow-sm rounded-circle me-1" 
                                    title="Gestionar Ejecución">
                                        <i class="fa <?php echo ($c['especialidad'] == 'DIAGNOSTICO') ? 'fa-microchip' : 'fa-tools'; ?>"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if($c['estado'] == 'LAVADO' && in_array($nivel, ['ADMINISTRADOR', 'LIMPIEZA'])): ?>
                                    <a href="control_lavado.php?id_cita=<?php echo $c['id_cita']; ?>" 
                                    class="btn btn-sm text-white shadow-sm rounded-circle me-1" 
                                    style="background-color: #00d2ff; border: none;" title="Control de Lavado">
                                        <i class="fa fa-broom"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if(in_array($c['estado'], ['POR ENTREGAR', 'FINALIZADO']) && in_array($nivel, ['ADMINISTRADOR', 'FACTURACION', 'GERENTE'])): ?>
                                    <a href="checkout_orden.php?id_cita=<?php echo $c['id_cita']; ?>" 
                                    class="btn btn-sm <?php echo ($c['estado'] == 'FINALIZADO') ? 'btn-success' : 'btn-warning'; ?> shadow-sm rounded-circle me-1" 
                                    title="Resumen y Caja">
                                        <i class="fa <?php echo ($c['estado'] == 'FINALIZADO') ? 'fa-file-invoice-dollar' : 'fa-hand-holding-usd'; ?>"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if($c['estado'] == 'EN REPARACION' && in_array($nivel, ['ADMINISTRADOR', 'MECANICO', 'JEFE MECÁNICOS'])): ?>
                                    <a href="ejecucion_reparacion.php?id_cita=<?php echo $c['id_cita']; ?>" 
                                    class="btn btn-sm btn-danger text-white shadow-sm rounded-circle me-1" 
                                    title="Ejecutar Reparaciones">
                                        <i class="fa fa-wrench"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if(in_array($nivel, ['ADMINISTRADOR', 'GERENTE'])): ?>
                                    <button class="btn btn-sm btn-light text-danger shadow-sm rounded-circle" title="Cancelar Cita">
                                        <i class="fa fa-times"></i>
                                    </button>
                                <?php endif; ?>

                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fa fa-calendar-minus fa-4x text-muted" style="opacity: 0.2;"></i>
                                </div>
                                <h6 class="fw-bold text-muted mb-1">¡Día despejado!</h6>
                                <p class="text-muted small">No hay citas programadas para esta fecha en la agenda.</p>
                                
                                <a href="clientes.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 mt-2">
                                    <i class="fa fa-plus me-1"></i> Agendar nueva cita
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAsignarTecnicos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa fa-users text-primary me-2"></i>Asignar Equipo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAsignarEquipo" action="recepcion_vehiculo.php" method="GET">
                <div class="modal-body">
                    <input type="hidden" name="id_cita" id="modal_id_cita">
                    <p class="text-muted small mb-3">Seleccione los técnicos que realizarán este trabajo:</p>
                    
                    <div id="lista_tecnicos_check" class="row g-2">
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">
                        Confirmar Equipo y Recepcionar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function abrirModalTecnicos(idCita, tipoRaiz) {
        $('#modal_id_cita').val(idCita);
        const contenedor = $('#lista_tecnicos_check');
        contenedor.html('<div class="text-center w-100 py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>');

        $.post('Poo/obtener_responsables.php', { id_cita: idCita }, function(tecnicos) {
            let html = '';
            
            if(tecnicos.length > 0) {
                tecnicos.forEach(t => {
                    // 🚀 CAMBIO A RADIO: Ahora solo puedes elegir uno.
                    // Usamos name="id_tecnico" (sin corchetes) para enviar un solo valor.
                    let isChecked = t.seleccionado ? 'checked' : '';
                    let borderClass = t.seleccionado ? 'border-primary bg-primary bg-opacity-10' : 'bg-light';
                    let tag = t.seleccionado ? '<span class="badge bg-primary float-end">MECÁNICO ASIGNADO</span>' : '';

                    html += `
                    <div class="col-12 mb-2">
                        <div class="form-check p-3 border rounded-4 ${borderClass} shadow-sm">
                            <input class="form-check-input ms-0 me-3" type="radio" name="id_tecnico" 
                                value="${t.id_usuario}" id="t_${t.id_usuario}" ${isChecked} required>
                            <label class="form-check-label fw-bold w-100" for="t_${t.id_usuario}">
                                ${t.nombre} ${tag}
                                <div class="text-muted fw-normal small">${t.nivel}</div>
                            </label>
                        </div>
                    </div>`;
                });
            } else {
                html = `<div class="alert alert-warning small w-100">No hay mecánicos disponibles.</div>`;
            }
            contenedor.html(html);
            $('#modalAsignarTecnicos').modal('show');
        }, 'json');
    }
</script>

<?php include 'master/footer.php'; ?>