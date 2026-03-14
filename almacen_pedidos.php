<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();
include 'master/header.php'; 

// 🚀 Filtro de Taller
$id_taller_sesion = $_SESSION['id_taller'] ?? 1;

// 1. CONSULTA DE PENDIENTES (Ahora con nombre de técnico)
$sql_p = "SELECT pr.*, p.nombre_producto, p.stock_actual, v.placa, v.marca, v.modelo, p.codigo_barras, u.nombre as nombre_tecnico
          FROM pedidos_repuestos pr
          JOIN productos p ON pr.id_producto = p.id_producto
          JOIN citas ci ON pr.id_cita = ci.id_cita
          JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
          LEFT JOIN usuarios u ON pr.id_mecanico_pide = u.id_usuario
          WHERE ci.id_taller = '$id_taller_sesion' 
          AND pr.estado_pedido IN ('SOLICITADO', 'SOLICITADO POR CLIENTE', 'EN COMPRA')
          ORDER BY v.placa ASC, pr.fecha_pedido ASC";

$res_p = $db->ejecutar($sql_p);

// 2. CONSULTA DE ENTREGADOS HOY (Ahora con nombre de técnico)
$sql_e = "SELECT pr.*, p.nombre_producto, v.placa, p.marca, p.codigo_barras, u.nombre as nombre_tecnico
          FROM pedidos_repuestos pr
          JOIN productos p ON pr.id_producto = p.id_producto
          JOIN citas ci ON pr.id_cita = ci.id_cita
          JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
          LEFT JOIN usuarios u ON pr.id_mecanico_pide = u.id_usuario
          WHERE ci.id_taller = '$id_taller_sesion' 
          AND pr.estado_pedido IN ('RECIBIDO', 'ENTREGADO')
          AND DATE(pr.fecha_entrega) = CURDATE() 
          ORDER BY pr.fecha_entrega DESC";

$res_e = $db->ejecutar($sql_e);
?>

<div class="container-fluid py-4" style="background-color: #f8f9fa; min-height: 100vh;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Gestión de Almacén</h3>
            <p class="text-muted small">Control de despachos con trazabilidad de mecánicos</p>
        </div>
        <div class="input-group shadow-sm rounded-pill overflow-hidden" style="width: 300px;">
            <span class="input-group-text bg-white border-0"><i class="fa fa-search text-muted"></i></span>
            <input type="text" id="buscarPedido" class="form-control border-0" placeholder="Buscar placa o repuesto...">
        </div>
    </div>

    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-4 shadow-sm d-inline-flex" id="pills-tab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active rounded-pill fw-bold" data-bs-toggle="pill" data-bs-target="#pills-pendientes" type="button">
                <i class="fa fa-clock me-2"></i>PENDIENTES (<?php echo $db->contar($res_p); ?>)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill fw-bold" data-bs-toggle="pill" data-bs-target="#pills-historial" type="button">
                <i class="fa fa-check-circle me-2"></i>ENTREGADOS HOY
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        
        <div class="tab-pane fade show active" id="pills-pendientes" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaPendientes">
                        <thead class="bg-light text-muted small uppercase">
                            <tr>
                                <th class="ps-4 py-3">Referencia</th>
                                <th>Repuesto / Solicitante</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-center">Stock</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $ultima_placa = ""; 
                            if($db->contar($res_p) > 0):
                                while($p = $db->recorrer($res_p)): 
                                    if ($p['placa'] !== $ultima_placa): 
                                        $ultima_placa = $p['placa'];
                            ?>
                                <tr class="group-header">
                                    <td colspan="6" class="ps-4 py-2">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-dark rounded-pill px-3 py-2 me-3">
                                                <i class="fa fa-car me-2"></i><?php echo $p['placa']; ?>
                                            </span>
                                            <span class="fw-bold text-secondary small text-uppercase"><?php echo $p['marca'] . " " . $p['modelo']; ?></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php
                                $insuficiente = ($p['stock_actual'] < $p['cantidad']);
                                $tecnico = !empty($p['nombre_tecnico']) ? $p['nombre_tecnico'] : 'No asignado';
                                $st_color = ($p['estado_pedido'] == 'EN COMPRA') ? "bg-info text-white" : "bg-warning text-dark";
                                if($p['estado_pedido'] == 'SOLICITADO POR CLIENTE') $st_color = "bg-primary text-white";
                            ?>
                            <tr>
                                <td class="ps-5 text-muted small">#<?php echo $p['id_pedido']; ?></td>
                                <td>
                                    <div class="fw-bold text-dark mb-0"><?php echo $p['nombre_producto']; ?></div>
                                    <div class="text-primary x-small fw-bold">
                                        <i class="fa fa-user-nut me-1"></i>Pide: <?php echo strtoupper($tecnico); ?>
                                    </div>
                                </td>
                                <td class="text-center fw-bold fs-5"><?php echo number_format($p['cantidad'], 0); ?></td>
                                <td class="text-center">
                                    <span class="badge <?php echo $insuficiente ? 'bg-danger-soft text-danger' : 'bg-success-soft text-success'; ?> fs-6 rounded-3">
                                        <?php echo number_format($p['stock_actual'], 0); ?>
                                    </span>
                                </td>
                                <td><span class="badge <?php echo $st_color; ?> rounded-pill small px-3"><?php echo $p['estado_pedido']; ?></span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                        <?php if(!$insuficiente): ?>
                                            <button onclick="procesar(<?php echo $p['id_pedido']; ?>, 'RECIBIDO')" class="btn btn-success btn-sm px-3">
                                                <i class="fa fa-handshake me-1"></i> Entregar
                                            </button>
                                        <?php else: ?>
                                            <button onclick="procesar(<?php echo $p['id_pedido']; ?>, 'EN COMPRA')" class="btn btn-info btn-sm text-white px-3">
                                                <i class="fa fa-shopping-cart me-1"></i> Comprar
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="procesar(<?php echo $p['id_pedido']; ?>, 'RECHAZADO')" class="btn btn-light btn-sm text-danger border-start"><i class="fa fa-times"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No hay pedidos pendientes.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pills-historial" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="ps-4 py-3">Vehículo</th>
                                <th>Repuesto / Entregado a</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Hora Despacho</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($db->contar($res_e) > 0): while($e = $db->recorrer($res_e)): ?>
                            <tr>
                                <td class="ps-4"><span class="badge bg-dark rounded-pill px-3 py-2"><?php echo $e['placa']; ?></span></td>
                                <td>
                                    <div class="fw-bold text-muted"><?php echo $e['nombre_producto']; ?></div>
                                    <small class="text-success fw-bold"><i class="fa fa-check-double me-1"></i>Recibió: <?php echo $e['nombre_tecnico'] ?: 'Técnico'; ?></small>
                                </td>
                                <td class="text-center fw-bold text-muted"><?php echo number_format($e['cantidad'], 0); ?></td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3">ENTREGADO</span></td>
                                <td class="text-end pe-4 text-muted small"><i class="fa fa-clock me-1"></i><?php echo date('H:i', strtotime($e['fecha_entrega'])); ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No hay despachos hoy.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.getElementById('buscarPedido').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let filas = document.querySelectorAll('#tablaPendientes tbody tr:not(.group-header)');
    filas.forEach(f => { f.style.display = f.innerText.toLowerCase().includes(filtro) ? '' : 'none'; });
});

function procesar(id, estado) {
    Swal.fire({
        title: '¿Confirmar acción?',
        text: "El repuesto pasará a: " + estado,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#005082',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Volver'
    }).then((result) => {
        if (result.isConfirmed) {
            const params = new URLSearchParams();
            params.append('id_pedido', id);
            params.append('estado', estado);
            fetch('Poo/procesar_pedido_almacen.php', { method: 'POST', body: params })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire({ title: '¡Listo!', text: data.message, icon: 'success', timer: 1000, showConfirmButton: false }).then(() => location.reload());
                } else { Swal.fire('Error', data.message, 'error'); }
            });
        }
    });
}
</script>

<style>
    .nav-pills .nav-link { color: #6c757d; padding: 10px 25px; }
    .nav-pills .nav-link.active { background-color: #005082; box-shadow: 0 4px 12px rgba(0,80,130,0.2); }
    .group-header { background-color: #f1f3f5 !important; border-top: 2px solid #dee2e6; }
    .bg-success-soft { background-color: #e8f5e9; color: #2e7d32; }
    .bg-danger-soft { background-color: #ffebee; color: #c62828; }
    .x-small { font-size: 0.72rem; }
    .ps-5 { padding-left: 3.5rem !important; }
</style>

<?php include 'master/footer.php'; ?>