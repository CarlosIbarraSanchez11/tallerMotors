<?php
session_start();
if (!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit(); }
require_once "Poo/Conexion.php";
$db = new Conexion();
include 'master/header.php'; 

$id_taller = $_SESSION['id_taller'];
$hoy = date('Y-m-d');

// Consulta para el contador de hoy
$countQuery = $db->ejecutar("SELECT COUNT(*) as total FROM citas WHERE fecha_cita = '$hoy' AND id_taller = '$id_taller'");
$countData = $db->recorrer($countQuery);
?>

<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white border-0 shadow-sm rounded-4 p-3">
                <h6 class="small fw-bold mb-0">CITAS PARA HOY</h6>
                <h3 class="fw-bold m-0"><?php echo $countData['total']; ?></h3>
            </div>
        </div>
        <div class="col-md-9 text-end align-self-center">
             <button class="btn btn-dark rounded-pill px-4 shadow-sm" onclick="verCalendario()">
                <i class="fa fa-calendar-alt me-2"></i> Ver en Calendario
             </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Fecha / Hora</th>
                            <th>Vehículo (Placa)</th>
                            <th>Cliente</th>
                            <th>Servicio / Motivo</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Consulta Maestra con JOINs
                        $sql = "SELECT c.*, v.placa, v.modelo, cl.nombre_completo 
                                FROM citas c
                                INNER JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
                                INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
                                WHERE c.id_taller = '$id_taller'
                                ORDER BY c.fecha_cita ASC, c.hora_cita ASC";

                        $res = $db->ejecutar($sql);
                        if($db->numero_filas($res) > 0):
                            while($c = $db->recorrer($res)):
                                // Lógica de colores por estado
                                $badge = 'secondary';
                                if($c['estado_cita'] == 'PENDIENTE') $badge = 'warning';
                                if($c['estado_cita'] == 'CONFIRMADA') $badge = 'success';
                                if($c['estado_cita'] == 'CANCELADA') $badge = 'danger';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?php echo date('d/m/Y', strtotime($c['fecha_cita'])); ?></div>
                                <div class="small text-muted"><i class="fa fa-clock me-1"></i><?php echo date('H:i', strtotime($c['hora_cita'])); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">
                                    <?php echo $c['placa']; ?>
                                </span>
                                <div class="small text-muted mt-1"><?php echo $c['modelo']; ?></div>
                            </td>
                            <td><div class="fw-bold"><?php echo $c['nombre_completo']; ?></div></td>
                            <td>
                                <div class="text-truncate" style="max-width: 250px;" title="<?php echo $c['servicio_motivo']; ?>">
                                    <?php echo $c['servicio_motivo']; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $badge; ?> bg-opacity-10 text-<?php echo $badge; ?> rounded-pill px-3">
                                    <?php echo $c['estado_cita']; ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" title="Editar Cita">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-success rounded-circle shadow-sm" title="Confirmar Llegada">
                                    <i class="fa fa-check"></i>
                                </button>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa fa-calendar-times fa-3x mb-3 opacity-25"></i><br>
                                No hay citas programadas en este momento.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'master/footer.php'; ?>