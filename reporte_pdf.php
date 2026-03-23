<?php
session_start();
if (!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit(); }
require_once "Poo/Conexion.php";
$db = new Conexion();
$id_taller_sesion = $_SESSION['id_taller'];

include 'master/header.php'; 

// URL base de tu bucket (ajusta si usas un proxy o dominio personalizado)
$url_base_bucket = "https://storage.googleapis.com/taller-dr-motors-storage/uploads/pdf/";
?>

<style>
    /* Estilo para que el buscador de DataTables se vea pro */
    .dataTables_filter input { border-radius: 20px; padding: 5px 15px; border: 1px solid #dee2e6; outline: none; margin-left: 10px; }
    .dataTables_length select { padding: 5px 10px; border-radius: 8px; border: 1px solid #dee2e6; }
    
    .btn-pdf {
        background: #fff0f0; color: #dc3545; border: 1px solid #ffc1c1;
        transition: all 0.2s ease;
    }
    .btn-pdf:hover { background: #dc3545; color: white; transform: scale(1.05); }
    
    .placa-badge {
        font-family: 'Monaco', monospace; background: #212529; color: #f8f9fa;
        padding: 4px 10px; border-radius: 6px; border: 1px solid #495057;
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0"><i class="fa fa-history me-2 text-primary"></i>Historial de Reportes PDF</h4>
            <small class="text-muted">Consulta y descarga los expedientes técnicos almacenados en la nube</small>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4"> 
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="tablaReportes">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Fecha / Orden</th>
                            <th>Vehículo (Placa)</th>
                            <th>Propietario</th>
                            <th>Servicio Realizado</th>
                            <th class="text-center">Estado PDF</th>
                            <th class="text-end pe-4">Expediente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Sinceramente, filtramos solo los que tengan url_pdf para no mostrar filas vacías
                        $sql = "SELECT ot.id_orden, ot.fecha_orden, ot.url_pdf, ot.estado_orden,
                                       v.placa, v.marca, v.modelo,
                                       c.nombre_completo,
                                       s.nombre_servicio
                                FROM ordenes_trabajo ot
                                JOIN vehiculos v ON ot.id_vehiculo = v.id_vehiculo
                                JOIN clientes c ON v.id_cliente = c.id_cliente
                                LEFT JOIN citas ci ON ot.id_cita = ci.id_cita
                                LEFT JOIN servicios s ON ci.id_servicio = s.id_servicio
                                WHERE ot.url_pdf IS NOT NULL AND ot.url_pdf != ''
                                ORDER BY ot.id_orden DESC";
                        
                        $res = $db->ejecutar($sql);
                        while($u = $db->recorrer($res)):
                            $fecha_f = date("d/m/Y", strtotime($u['fecha_orden']));
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark"><?php echo $fecha_f; ?></span>
                                    <small class="text-primary fw-bold">OT-<?php echo str_pad($u['id_orden'], 5, "0", STR_PAD_LEFT); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="placa-badge me-2"><?php echo strtoupper($u['placa']); ?></span>
                                    <small class="text-muted text-uppercase" style="font-size: 11px;">
                                        <?php echo $u['marca'] . " " . $u['modelo']; ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-secondary bg-opacity-10 text-secondary me-2" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                        <i class="fa fa-user" style="font-size: 12px;"></i>
                                    </div>
                                    <span class="small fw-semibold"><?php echo $u['nombre_completo']; ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3">
                                    <?php echo $u['nombre_servicio'] ?? 'Mantenimiento'; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="text-success small fw-bold">
                                    <i class="fa fa-cloud-check me-1"></i> Sincronizado
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?php echo $url_base_bucket . $u['url_pdf']; ?>" target="_blank" 
                                   class="btn btn-sm btn-pdf rounded-pill px-3 fw-bold shadow-sm">
                                    <i class="fa fa-file-pdf me-1"></i> VER PDF
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

<script>
$(document).ready(function() {
    // Inicialización de DataTables para el buscador inteligente
    $('#tablaReportes').DataTable({
        responsive: true,
        order: [[0, 'desc']], // Ordenar por fecha reciente
        pageLength: 10,
        language: {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
            "search": "Buscar por Placa o Cliente:",
            "lengthMenu": "Ver _MENU_ reportes"
        }
    });
});
</script>

<?php 
include 'master/footer.php'; 
?>