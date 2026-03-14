<?php
session_start();
include 'master/header.php'; 
require_once "Poo/Conexion.php";
$db = new Conexion();
?>
<style>
    /* Estilo para que la cantidad resalte más ahora que hay menos datos */
    .cantidad-texto {
        font-size: 1.1rem;
        font-weight: 800;
    }
    .select2-container--open { z-index: 9999999 !important; }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <div>
            <h4 class="fw-bold m-0 text-dark">Movimientos de Entrada</h4>
            <small class="text-muted">Control de ingresos de repuestos al inventario</small>
        </div>
        <button class="btn btn-dark rounded-pill px-4 shadow" data-bs-toggle="modal" data-bs-target="#modalNuevoIngreso">
            <i class="fa fa-plus-circle me-2"></i> Registrar Ingreso
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Fecha / Hora</th>
                            <th>Producto / Repuesto</th>
                            <th>Origen del Ingreso</th>
                            <th class="text-end pe-4">Cantidad Ingresada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT i.*, p.nombre_producto, p.marca, prov.razon_social 
                                FROM ingresos i 
                                INNER JOIN productos p ON i.id_producto = p.id_producto 
                                LEFT JOIN proveedores prov ON i.id_proveedor = prov.id_proveedor 
                                ORDER BY i.id_ingreso DESC";
                        
                        $res = $db->ejecutar($sql);
                        while($row = $db->recorrer($res)):
                            $es_formal = !empty($row['id_proveedor']);
                        ?>
                        <tr>
                            <td class="ps-4 small text-muted">
                                <div class="fw-bold text-dark"><?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?></div>
                                <div><?php echo date('H:i', strtotime($row['fecha_registro'])); ?></div>
                            </td>

                            <td>
                                <div class="fw-bold text-dark"><?php echo $row['nombre_producto']; ?></div>
                                <div class="small text-muted text-uppercase"><?php echo $row['marca']; ?></div>
                            </td>

                            <td>
                                <?php if($es_formal): ?>
                                    <div class="small fw-bold text-primary"><i class="fa fa-building me-1"></i> <?php echo $row['razon_social']; ?></div>
                                    <div class="extra-small text-muted">Documento registrado</div>
                                <?php else: ?>
                                    <div class="small text-secondary"><i class="fa fa-boxes me-1"></i> Ingreso Interno</div>
                                    <div class="extra-small text-muted italic">Carga de stock inicial</div>
                                <?php endif; ?>
                            </td>

                            <td class="text-end pe-4">
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-4 py-2 cantidad-texto">
                                    <i class="fa fa-plus-circle small me-2"></i><?php echo $row['cantidad']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'modales/modal_ingreso.php'; ?>
<?php include 'master/footer.php'; ?>

<script>
// Mantengo tu lógica de Select2 para que el buscador siga funcionando igual de bien
$(document).ready(function() {
    function initBuscadores() {
        $('#select_proveedor, #select_producto').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalNuevoIngreso'),
            width: '100%'
        });
    }

    $('#modalNuevoIngreso').on('shown.bs.modal', function () {
        initBuscadores();
        setTimeout(() => {
            const searchField = document.querySelector('.select2-search__field');
            if(searchField) searchField.focus();
        }, 200);
    });

    window.toggleProveedor = function() {
        var tipo = $('#tipo_ingreso').val();
        var seccion = $('#seccion_formal');
        if (tipo === 'formal') {
            seccion.fadeIn(200);
            $('#select_proveedor').select2({ theme: 'bootstrap-5', dropdownParent: $('#modalNuevoIngreso'), width: '100%' });
        } else {
            seccion.hide();
        }
    }
});
</script>