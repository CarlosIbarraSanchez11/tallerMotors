<?php
session_start();
if (!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit(); }
require_once "Poo/Conexion.php";
$db = new Conexion();
include 'master/header.php'; 
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">Catálogo de Productos</h4>
            <small class="text-muted">Gestión de inventario en tiempo real</small>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow" data-bs-toggle="modal" data-bs-target="#modalProducto" onclick="nuevoProducto()">
            <i class="fa fa-plus-circle me-2"></i> Crear Nuevo Producto
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Cód / Producto</th>
                            <th>Marca</th>
                            <th>Categoría</th>
                            <th class="text-center">Stock Actual</th>
                            <th class="text-center">Stock Mín.</th>
                            <th>Medida</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Sinceramente, ahora usamos directamente el campo stock_actual de la tabla
                        $sql = "SELECT *, stock_actual as stock_calculado 
                                FROM productos 
                                WHERE id_taller = '1' 
                                ORDER BY nombre_producto ASC";

                        $res = $db->ejecutar($sql);
                        while($row = $db->recorrer($res)):
                            $stock = $row['stock_actual']; // 👈 Usamos el valor real de la tabla
                            $minimo = $row['stock_minimo'];
                            
                            // Lógica de alertas: Rojo si es igual o menor al mínimo
                            $clase_stock = ($stock <= $minimo) ? 'badge bg-danger' : 'badge bg-success';
                            $alerta = ($stock <= $minimo) ? '<i class="fa fa-exclamation-triangle me-1"></i>' : '';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?php echo $row['nombre_producto']; ?></div>
                                <small class="text-muted"><?php echo $row['codigo_barras'] ?: 'Sin Código'; ?></small>
                            </td>
                            <td><?php echo $row['marca']; ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info"><?php echo $row['categoria']; ?></span></td>
                            
                            <td class="text-center">
                                <span class="<?php echo $clase_stock; ?> px-3 py-2 rounded-pill shadow-sm">
                                    <?php echo $alerta . $stock; ?>
                                </span>
                            </td>

                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-3"><?php echo $minimo; ?></span>
                            </td>

                            <td><span class="text-secondary small fw-bold"><?php echo $row['unidad_medida']; ?></span></td>

                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" 
                                        onclick='editarProducto(<?php echo json_encode($row); ?>)'>
                                    <i class="fa fa-edit"></i>
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

<?php include 'modales/modal_producto.php'; ?>
<?php include 'master/footer.php'; ?>

<script>
    function nuevoProducto() {
        $('#tituloModalProd').text('Nuevo Producto en Catálogo');
        $('#formProducto')[0].reset();
        $('#id_producto').val('');
    }

    function editarProducto(p) {
        $('#tituloModalProd').text('Editar Datos de Catálogo');
        $('#id_producto').val(p.id_producto);
        $('#codigo_barras').val(p.codigo_barras);
        $('#nombre_producto').val(p.nombre_producto);
        $('#marca').val(p.marca);
        $('#categoria').val(p.categoria);
        $('#unidad_medida').val(p.unidad_medida); 
        $('#stock_minimo').val(p.stock_minimo);
        $('#modalProducto').modal('show');
    }
</script>