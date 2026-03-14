<?php
include 'master/header.php'; 
require_once "Poo/Conexion.php";
$db = new Conexion();

// --- 1. CONSULTAS PARA LOS CONTADORES SUPERIORES ---
// Vehículos actualmente en el taller
$res_taller = $db->ejecutar("SELECT COUNT(*) as total FROM citas WHERE estado = 'EN PROCESO'");
$count_taller = $db->recorrer($res_taller)['total'];

// Citas pendientes
$res_pend = $db->ejecutar("SELECT COUNT(*) as total FROM citas WHERE estado = 'PENDIENTE'");
$count_pend = $db->recorrer($res_pend)['total'];

// Vehículos finalizados/entregados (del mes actual)
$res_fin = $db->ejecutar("SELECT COUNT(*) as total FROM citas WHERE estado IN ('FINALIZADO', 'POR ENTREGAR') AND MONTH(fecha_cita) = MONTH(CURRENT_DATE())");
$count_fin = $db->recorrer($res_fin)['total'];

// --- 2. CONSULTA PARA EL FLUJO DE SERVICIOS (Conteo por estados reales) ---
$res_estados = $db->ejecutar("SELECT estado, COUNT(*) as cant FROM citas GROUP BY estado");
$estados_data = [];
while($row = $db->recorrer($res_estados)) {
    $estados_data[$row['estado']] = $row['cant'];
}

// Helper para evitar errores de "offset undefined"
function getCant($array, $key) { return $array[$key] ?? 0; }
?>

<div class="container-fluid px-4">
    <h4 class="fw-bold mb-4 text-dark">Dashboard General - IPS Global</h4>
    
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 p-3 d-flex align-items-center">
                <div class="icon-box bg-primary bg-opacity-10 text-primary me-3"><i class="fa fa-car-side fs-4"></i></div>
                <div><h4 class="fw-bold mb-0"><?php echo $count_taller; ?></h4><small class="text-muted">En Proceso</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 p-3 d-flex align-items-center">
                <div class="icon-box bg-warning bg-opacity-10 text-warning me-3"><i class="fa fa-clock fs-4"></i></div>
                <div><h4 class="fw-bold mb-0"><?php echo $count_pend; ?></h4><small class="text-muted">Pendientes</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 p-3 d-flex align-items-center">
                <div class="icon-box bg-success bg-opacity-10 text-success me-3"><i class="fa fa-check-circle fs-4"></i></div>
                <div><h4 class="fw-bold mb-0"><?php echo $count_fin; ?></h4><small class="text-muted">Entregados Mes</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 p-3 d-flex align-items-center border-start border-4 border-info">
                <div class="icon-box bg-info bg-opacity-10 text-info me-3"><i class="fa fa-calendar-check fs-4"></i></div>
                <div><h4 class="fw-bold mb-0"><?php echo date('d/m'); ?></h4><small class="text-muted">Hoy</small></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h6 class="fw-bold mb-4"><i class="fa fa-project-diagram me-2 text-primary"></i> Flujo de Servicio - Estados actuales</h6>
        <div class="d-flex justify-content-between text-center overflow-auto py-2">
            <div><div class="icon-box bg-secondary text-white mx-auto mb-2 shadow"><i class="fa fa-pause"></i></div><small class="fw-bold d-block">Pendiente</small><span class="badge bg-light text-dark"><?php echo getCant($estados_data, 'PENDIENTE'); ?> veh.</span></div>
            <div><div class="icon-box bg-primary text-white mx-auto mb-2 shadow"><i class="fa fa-tools"></i></div><small class="fw-bold d-block">En Proceso</small><span class="badge bg-light text-dark"><?php echo getCant($estados_data, 'EN PROCESO'); ?> veh.</span></div>
            <div><div class="icon-box bg-warning text-white mx-auto mb-2 shadow"><i class="fa fa-clipboard-check"></i></div><small class="fw-bold d-block">Por Entregar</small><span class="badge bg-light text-dark"><?php echo getCant($estados_data, 'POR ENTREGAR'); ?> veh.</span></div>
            <div><div class="icon-box bg-success text-white mx-auto mb-2 shadow"><i class="fa fa-flag-checkered"></i></div><small class="fw-bold d-block">Finalizado</small><span class="badge bg-light text-dark"><?php echo getCant($estados_data, 'FINALIZADO'); ?> veh.</span></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0 text-dark">Servicios Recientes (Últimos 10)</h5>
                <a href="calendario.php" class="text-decoration-none small fw-bold">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle border-0">
                    <thead class="text-muted small text-uppercase">
                        <tr>
                            <th class="border-0">PLACA</th>
                            <th class="border-0">VEHÍCULO</th>
                            <th class="border-0">CLIENTE</th>
                            <th class="border-0">ESTADO</th>
                            <th class="border-0 text-end">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Consulta para traer los últimos servicios con datos de cliente y vehículo
                        $sql_recientes = "SELECT c.id_cita, c.estado, cl.nombre_completo, v.placa, v.marca, v.modelo 
                                          FROM citas c
                                          INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
                                          INNER JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
                                          ORDER BY c.fecha_cita DESC LIMIT 10";
                        
                        $res_recientes = $db->ejecutar($sql_recientes);
                        
                        while($ser = $db->recorrer($res_recientes)):
                            // Definimos la clase de Bootstrap y el color de texto según el estado
                            switch($ser['estado']) {
                                case 'PENDIENTE':
                                    // Amarillo/Ámbar - Indica espera
                                    $badge_class = 'bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50';
                                    break;
                                case 'EN PROCESO':
                                    // Azul - Indica acción activa
                                    $badge_class = 'bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50';
                                    break;
                                case 'POR ENTREGAR':
                                    // Morado o Cian - Un estado intermedio distinto
                                    $badge_class = 'bg-info bg-opacity-25 text-info border border-info border-opacity-50';
                                    break;
                                case 'FINALIZADO':
                                    // Verde - Indica éxito/terminado
                                    $badge_class = 'bg-success bg-opacity-25 text-success border border-success border-opacity-50';
                                    break;
                                default:
                                    $badge_class = 'bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-50';
                            }?>
                        <tr class="border-bottom border-light">
                            <td><span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 fw-bold"><?php echo $ser['placa']; ?></span></td>
                            <td class="fw-bold"><?php echo $ser['marca'] . " " . $ser['modelo']; ?></td>
                            <td><?php echo $ser['nombre_completo']; ?></td>
                            <td>
                                <span class="badge rounded-pill <?php echo $badge_class; ?> px-3">
                                    <i class="fa fa-circle me-1" style="font-size: 8px;"></i> <?php echo $ser['estado']; ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="citas.php?id=<?php echo $ser['id_cita']; ?>" class="btn btn-sm btn-light rounded-circle shadow-sm">
                                    <i class="fa fa-eye text-primary"></i>
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
<?php
include 'master/footer.php';
?>
