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
            <h4 class="fw-bold text-dark m-0">Gestión de Usuarios</h4>
            <small class="text-muted">Administra los accesos de todo el personal de Dr. Motors</small>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="nuevoUsuario()">
            <i class="fa fa-user-plus me-2"></i> Nuevo Usuario
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaUsuarios" class="table table-hover align-middle mb-0 w-100">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Colaborador</th>
                            <th>Usuario</th>
                            <th>Rol / Nivel</th>
                            <th>Taller / Sede</th> 
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT u.*, t.nombre_taller 
                                FROM usuarios u 
                                LEFT JOIN talleres t ON u.id_taller = t.id_taller 
                                ORDER BY u.id_taller ASC, u.id_usuario DESC;";

                        $res = $db->ejecutar($sql);
                        while($u = $db->recorrer($res)):

                            // --- 🚀 LÓGICA DE ICONOS Y COLORES AMPLIADA ---
                            $rol_clase = 'bg-secondary text-secondary';
                            $icon_rol = 'fa-user'; 

                            switch (strtoupper($u['nivel'])) {
                                case 'ADMINISTRADOR':
                                    $rol_clase = 'bg-danger text-danger'; $icon_rol = 'fa-user-shield';
                                    break;
                                case 'GERENTE':
                                    $rol_clase = 'bg-dark text-dark'; $icon_rol = 'fa-briefcase';
                                    break;
                                case 'JEFE':
                                    $rol_clase = 'bg-primary text-primary'; $icon_rol = 'fa-user-tie';
                                    break;
                                case 'MECANICO':
                                    $rol_clase = 'bg-warning text-warning'; $icon_rol = 'fa-tools';
                                    break;
                                case 'LOGISTICA':
                                    $rol_clase = 'bg-info text-info'; $icon_rol = 'fa-boxes-stacked';
                                    break;
                                case 'CALL':
                                    $rol_clase = 'bg-success text-success'; $icon_rol = 'fa-headset';
                                    break;
                                case 'FACTURACION':
                                    $rol_clase = 'bg-primary text-primary'; $icon_rol = 'fa-file-invoice-dollar';
                                    break;
                                case 'LIMPIEZA':
                                    $rol_clase = 'bg-secondary text-secondary'; $icon_rol = 'fa-broom';
                                    break;
                            }

                            $color_base = explode('-', $rol_clase)[1];
                            $color_estado = ($u['estado'] == 1) ? 'success' : 'danger';
                            $estado_texto = ($u['estado'] == 1) ? 'Activo' : 'Inactivo';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary bg-opacity-10 text-primary me-3" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                        <i class="fa fa-user-circle"></i>
                                    </div>
                                    <span class="fw-bold text-dark"><?php echo $u['nombre']; ?></span>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border fw-medium"><?php echo $u['usuario']; ?></span></td>
                            <td>
                                <span class="badge <?php echo $rol_clase; ?> bg-opacity-10 px-3 border border-<?php echo $color_base; ?> border-opacity-25 rounded-pill shadow-sm">
                                    <i class="fa <?php echo $icon_rol; ?> me-1 small"></i> 
                                    <?php echo strtoupper($u['nivel']); ?>
                                </span>
                            </td>
                            <td><span class="text-primary small fw-bold"><?php echo $u['nombre_taller'] ?? 'GLOBAL'; ?></span></td>
                            <td>
                                <span class="badge bg-<?php echo $color_estado; ?> bg-opacity-10 text-<?php echo $color_estado; ?> rounded-pill px-3 fw-bold">
                                    <?php echo $estado_texto; ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light text-primary rounded-circle shadow-sm me-2" onclick='editarUsuario(<?php echo json_encode($u); ?>)'>
                                    <i class="fa fa-edit"></i>
                                </button>
                                <a href="Poo/estado_usuario.php?id=<?php echo $u['id_usuario']; ?>&st=<?php echo $u['estado']; ?>" 
                                   class="btn btn-sm btn-light text-<?php echo ($u['estado']==1) ? 'danger' : 'success'; ?> rounded-circle shadow-sm"
                                   onclick="return confirm('¿Cambiar estado del usuario?')">
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
</div>

<style>
    .dataTables_wrapper { padding: 20px !important; }
    .group-header { background-color: #f8f9fa !important; font-weight: bold; color: #005082; }
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#tablaUsuarios')) { $('#tablaUsuarios').DataTable().destroy(); }

    $('#tablaUsuarios').DataTable({
        responsive: true,
        destroy: true,
        pageLength: 25,
        columnDefs: [ { "targets": [3], "visible": false } ],
        order: [[3, 'asc']],
        language: { "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" },
        drawCallback: function (settings) {
            var api = this.api();
            var rows = api.rows({ page: 'current' }).nodes();
            var last = null;
            var colCount = api.columns(':visible').count();

            api.column(3, { page: 'current' }).data().each(function (group, i) {
                if (last !== group) {
                    $(rows).eq(i).before(
                        '<tr class="group-header"><td colspan="' + colCount + '"><i class="fa fa-warehouse me-2"></i> SEDE: ' + (group ? group : "ADMINISTRACIÓN CENTRAL") + '</td></tr>'
                    );
                    last = group;
                }
            });
        }
    });
});

function nuevoUsuario() {
    $('#tituloModal').text('Nuevo Colaborador');
    $('#formUsuario')[0].reset();
    $('#id_usuario').val('');
    $('#passHelp').hide();
    $('#password').attr('required', true);
    $('#modalUsuario').modal('show');
}

function editarUsuario(u) {
    $('#tituloModal').text('Editar Colaborador');
    $('#id_usuario').val(u.id_usuario);
    $('#nombre').val(u.nombre);
    $('#usuario').val(u.usuario);
    $('#nivel').val(u.nivel);
    $('#id_taller').val(u.id_taller);
    $('#passHelp').show();
    $('#password').attr('required', false);
    $('#modalUsuario').modal('show');
}
</script>

<?php 
include 'modales/modal_usuario.php'; 
include 'master/footer.php'; 
?>