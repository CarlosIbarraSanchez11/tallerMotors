<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();
include 'master/header.php'; 

$id_cita = $_GET['id_cita'];

// 1. DATOS DE LA CITA Y VEHÍCULO
$sql = "SELECT ci.*, ve.placa, ve.marca, ve.modelo, cl.nombre_completo
        FROM citas ci
        JOIN vehiculos ve ON ci.id_vehiculo = ve.id_vehiculo
        JOIN clientes cl ON ci.id_cliente = cl.id_cliente
        WHERE ci.id_cita = '$id_cita'";
$c = $db->recorrer($db->ejecutar($sql));

// 2. OBTENER SOLO HALLAZGOS APROBADOS POR EL CLIENTE
$sql_h = "SELECT * FROM hallazgos WHERE id_cita = '$id_cita' AND estado_aprobacion = 'APROBADO'";
$res_h = $db->ejecutar($sql_h);

// --- 3. NUEVO: OBTENER LOS REPUESTOS YA REGISTRADOS EN ESTA CITA ---
$sql_insumos = "SELECT im.*, p.nombre_producto 
                FROM inventario_movimientos im 
                JOIN productos p ON im.id_producto = p.id_producto 
                WHERE im.id_cita = '$id_cita' AND im.tipo_movimiento = 'SALIDA'
                ORDER BY im.fecha_registro DESC";
$res_insumos = $db->ejecutar($sql_insumos);
$total_insumos = $db->conexion->affected_rows;
?>

<div class="container-fluid px-4 pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <div>
            <h4 class="fw-bold text-dark m-0">Centro de Reparaciones</h4>
            <span class="badge bg-danger px-3">ORDEN DE TRABAJO EN MARCHA</span>
        </div>
        <div class="text-end">
            <h5 class="m-0 text-uppercase fw-bold text-primary"><?php echo $c['placa']; ?></h5>
            <small class="text-muted"><?php echo $c['nombre_completo']; ?></small>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="fa fa-tasks me-2 text-primary"></i>Trabajos Autorizados</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small">
                                <tr>
                                    <th class="ps-4">Descripción del Trabajo</th>
                                    <th class="text-center">Prioridad</th>
                                    <th class="text-end pe-4">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($h = $db->recorrer($res_h)): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo $h['descripcion_falla']; ?></div>
                                        <small class="text-success">Presupuesto: S/ <?php echo number_format($h['monto_estimado'], 2); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo ($h['prioridad'] == 'ALTA') ? 'danger' : 'warning'; ?> rounded-pill">
                                            <?php echo $h['prioridad']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-outline-success btn-sm rounded-circle btn-completar" 
                                                onclick="marcarTareaCompletada(<?php echo $h['id_hallazgo']; ?>, this)">
                                            <i class="fa fa-check"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="fa fa-box-open me-2 text-primary"></i>Insumos e Inventario</h6>
                </div>
                <div class="card-body pt-0">
                    <p class="small text-muted">Busca repuestos para descontar del stock:</p>
                    <div class="position-relative">
                        <div class="input-group mb-0 shadow-sm rounded-3 overflow-hidden"> 
                            <span class="input-group-text bg-white border-0"><i class="fa fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-0" placeholder="Ej: Aceite, Filtro..." id="buscarProducto" autocomplete="off">
                        </div>
                        <div id="resultadosBusqueda" class="list-group shadow position-absolute w-100" style="z-index: 1050;"></div>
                    </div>
                    
                    <div id="listaInsumosUsados" class="mt-4">
                        <h6 class="small fw-bold text-muted mb-3 text-uppercase">Repuestos registrados:</h6>
                        <div id="contenedorInsumos">
                            <?php if($total_insumos > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while($ins = $db->recorrer($res_insumos)): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 border-bottom">
                                            <div>
                                                <div class="small fw-bold text-dark"><?php echo $ins['nombre_producto']; ?></div>
                                                <small class="text-muted">Cantidad: <?php echo $ins['cantidad']; ?> | S/ <?php echo number_format($ins['precio_aplicado'], 2); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <button class="btn btn-sm btn-outline-danger border-0 rounded-circle" 
                                                        onclick="eliminarInsumo(<?php echo $ins['id_movimiento']; ?>, '<?php echo $ins['nombre_producto']; ?>', <?php echo $ins['id_producto']; ?>, <?php echo $ins['cantidad']; ?>)">
                                                    <i class="fa fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted border rounded-4 border-dashed bg-light">
                                    <i class="fa fa-box-open fa-2x mb-2 opacity-25"></i><br>
                                    <small>No se han registrado repuestos aún.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold">¿Reparaciones terminadas?</h5>
                    <p class="small opacity-75">El vehículo será derivado al área de lavado automáticamente.</p>
                    <button type="button" onclick="finalizarReparacion(<?php echo $id_cita; ?>)" class="btn btn-light btn-lg w-100 rounded-pill fw-bold text-primary shadow">
                        Finalizar y Lavado <i class="fa fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Buscador de productos
    document.getElementById('buscarProducto').addEventListener('input', function() {
        let q = this.value;
        let contenedor = document.getElementById('resultadosBusqueda');
        
        if(q.length > 2) {
            fetch('Poo/buscar_productos.php?q=' + q)
            .then(r => r.text())
            .then(html => {
                contenedor.innerHTML = html;
            });
        } else {
            contenedor.innerHTML = '';
        }
    });

    function agregarInsumo(idProd, nombre, precio) {
        const idCita = <?php echo $id_cita; ?>;
        
        fetch('Poo/registrar_consumo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'id_producto': idProd,
                'id_cita': idCita,
                'precio': precio
            })
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire('¡Registrado!', nombre + ' descontado del stock.', 'success').then(() => {
                    location.reload(); 
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }

    function marcarTareaCompletada(id, btn) {
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-success');
        btn.innerHTML = '<i class="fa fa-check-double"></i>';
        btn.disabled = true;
    }

    function finalizarReparacion(idCita) {
        Swal.fire({
            title: '¿Terminaste todo?',
            text: "Se notificará que el auto ya está listo para lavado.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, listo'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('Poo/procesar_fin_reparacion.php', {
                    method: 'POST',
                    body: new URLSearchParams({'id_cita': idCita})
                })
                .then(r => r.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire('¡Excelente!', data.message, 'success').then(() => {
                            window.location.href = 'citas.php';
                        });
                    }
                });
            }
        });
    }

    function eliminarInsumo(idMov, nombre, idProd, cant) {
        Swal.fire({
            title: '¿Anular este repuesto?',
            text: "Se devolverán " + cant + " unidad(es) de " + nombre + " al inventario.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, anular'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('Poo/eliminar_consumo_reparacion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'id_movimiento': idMov,
                        'id_producto': idProd,
                        'cantidad': cant
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire('Anulado', 'Stock actualizado.', 'success').then(() => location.reload());
                    }
                });
            }
        });
    }
</script>

<?php include 'master/footer.php'; ?>