<?php
session_start();
if (!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit(); }
require_once "Poo/Conexion.php";
$db = new Conexion();
include 'master/header.php'; 
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Inventario de Repuestos</h4>
            <small class="text-muted">Control de existencias y precios para el taller</small>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProducto" onclick="nuevoProducto()">
            <i class="fa fa-plus-circle me-2"></i> Nuevo Producto
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Producto / Marca</th>
                            <th>Categoría</th>
                            <th>Costo (S/)</th>
                            <th>Venta (S/)</th>
                            <th>Stock</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM productos WHERE id_taller = '".$_SESSION['id_taller']."' ORDER BY nombre_producto ASC";
                        $res = $db->ejecutar($sql);
                        while($p = $db->recorrer($res)):
                            // Alerta de stock bajo
                            $claseStock = ($p['stock_actual'] <= $p['stock_minimo']) ? 'bg-danger text-white' : 'bg-light text-dark';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?php echo $p['nombre_producto']; ?></div>
                                <div class="small text-muted"><?php echo $p['marca']; ?></div>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo $p['categoria']; ?></span></td>
                            <td><?php echo number_format($p['precio_compra'], 2); ?></td>
                            <td class="fw-bold text-primary"><?php echo number_format($p['precio_venta'], 2); ?></td>
                            <td>
                                <span class="badge <?php echo $claseStock; ?> rounded-pill px-3">
                                    <?php echo $p['stock_actual']; ?> und.
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" onclick='editarProducto(<?php echo json_encode($p); ?>)'>
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger rounded-circle shadow-sm" onclick="eliminarProducto(<?php echo $p['id_producto']; ?>)">
                                    <i class="fa fa-trash"></i>
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

<?php 
include 'modales/modal_producto.php'; 
include 'master/footer.php'; 
?>

<script>
    function nuevoProducto() {
        $('#tituloModal').text('Agregar Nuevo Producto');
        $('#formProducto')[0].reset();
        $('#id_producto').val('');
    }

    function editarProducto(p) {
        $('#tituloModal').text('Editar Producto');
        $('#id_producto').val(p.id_producto);
        $('#nombre_producto').val(p.nombre_producto);
        $('#marca').val(p.marca);
        $('#precio_compra').val(p.precio_compra);
        $('#precio_venta').val(p.precio_venta);
        $('#stock_actual').val(p.stock_actual);
        $('#stock_minimo').val(p.stock_minimo);
        $('#categoria').val(p.categoria);
        $('#modalProducto').modal('show');
    }
</script>