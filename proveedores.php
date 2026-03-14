<?php
session_start();
if (!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit(); }
require_once "Poo/Conexion.php";
$db = new Conexion();
include 'master/header.php'; 
?>
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">Gestión de Proveedores</h4>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalProveedor" onclick="nuevoProveedor()">
            <i class="fa fa-plus me-2"></i> Nuevo Proveedor
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">RUC / Razón Social</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $db->ejecutar("SELECT * FROM proveedores ORDER BY razon_social ASC");
                    while($p = $db->recorrer($res)):
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?php echo $p['razon_social']; ?></div>
                            <small class="text-muted"><i class="fa fa-fingerprint me-1"></i>RUC: <?php echo $p['ruc']; ?></small>
                        </td>
                        <td>
                            <div class="small"><i class="fa fa-phone me-1 text-muted"></i><?php echo $p['telefono']; ?></div>
                            <div class="small"><i class="fa fa-envelope me-1 text-muted"></i><?php echo $p['correo']; ?></div>
                        </td>
                        <td>
                            <?php if($p['estado'] == 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light text-primary rounded-circle shadow-sm me-1" 
                                    onclick='editarProveedor(<?php echo json_encode($p); ?>)' title="Editar">
                                <i class="fa fa-edit"></i>
                            </button>
                            <a href="Poo/estado_proveedor.php?id=<?php echo $p['id_proveedor']; ?>&st=<?php echo $p['estado']; ?>" 
                            class="btn btn-sm btn-light text-danger rounded-circle shadow-sm" title="Cambiar Estado">
                                <i class="fa fa-power-off"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// IMPORTANTE: Aquí llamas al archivo del modal bonito
include 'modales/modal_proveedor.php'; 
include 'master/footer.php'; 
?>

<script>
    function nuevoProveedor() {
        $('#tituloModalProv').text('Nuevo Proveedor');
        $('#formProveedor')[0].reset(); 
        $('#id_proveedor').val(''); // Cambiado de id_provider     
    }

    function editarProveedor(p) {
        $('#tituloModalProv').text('Editar Proveedor');
        $('#id_proveedor').val(p.id_proveedor); // Cambiado
        $('#prov_ruc').val(p.ruc);
        $('#prov_social').val(p.razon_social);
        $('#prov_tel').val(p.telefono);
        $('#prov_mail').val(p.correo);
        $('#prov_dir').val(p.direccion);
        $('#modalProveedor').modal('show');
    }
</script>