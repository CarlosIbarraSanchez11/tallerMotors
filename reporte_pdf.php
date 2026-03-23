<?php
session_start();
if (!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit(); }
require_once "Poo/Conexion.php";
$db = new Conexion();
$id_taller_sesion = $_SESSION['id_taller'];

include 'master/header.php'; 

// URL base de tu bucket en Google Cloud Storage
$url_base_bucket = "https://storage.googleapis.com/taller-dr-motors-storage/uploads/pdf/";
?>

<style>
    .dataTables_filter input { border-radius: 20px; padding: 5px 15px; border: 1px solid #dee2e6; outline: none; margin-left: 10px; }
    .dataTables_length select { padding: 5px 10px; border-radius: 8px; border: 1px solid #dee2e6; outline: none; }
    .btn-pdf { background: #fff0f0; color: #dc3545; border: 1px solid #ffc1c1; transition: all 0.2s ease; font-size: 12px; }
    .btn-pdf:hover { background: #dc3545; color: white; transform: translateY(-2px); }
    .placa-badge { font-family: 'Monaco', monospace; background: #212529; color: #f8f9fa; padding: 4px 10px; border-radius: 6px; border: 1px solid #495057; font-size: 13px; font-weight: bold; }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0"><i class="fa fa-history me-2 text-primary"></i>Historial de Reportes PDF</h4>
            <small class="text-muted">Busca por Placa, DNI o Propietario los expedientes en la nube</small>
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
                            <th>Propietario / DNI</th>
                            <th>Servicio</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 🚀 SQL CORREGIDO: Seguimos la cadena lógica de IDs
                        $sql = "SELECT ot.id_orden, ot.url_pdf,
                                       ci.fecha_cita,
                                       v.placa, v.marca, v.modelo,
                                       c.nombre_completo, c.dni_ruc,
                                       s.nombre_servicio
                                FROM ordenes_trabajo ot
                                JOIN citas ci ON ot.id_cita = ci.id_cita
                                JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
                                JOIN clientes c ON v.id_cliente = c.id_cliente
                                LEFT JOIN servicios s ON ci.id_servicio = s.id_servicio
                                WHERE ot.url_pdf IS NOT NULL AND ot.url_pdf != ''
                                AND ci.id_taller = '$id_taller_sesion'
                                ORDER BY ot.id_orden DESC";
                        
                        $res = $db->ejecutar($sql);
                        while($u = $db->recorrer($res)):
                            $fecha_f = !empty($u['fecha_cita']) ? date("d/m/Y", strtotime($u['fecha_cita'])) : "S/F";
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark"><?php echo $fecha_f; ?></span>
                                    <small class="text-primary fw-bold" style="font-size: 10px;">OT-<?php echo str_pad($u['id_orden'], 5, "0", STR_PAD_LEFT); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="placa-badge me-2"><?php echo strtoupper($u['placa']); ?></span>
                                    <small class="text-muted text-uppercase" style="font-size: 10px;"><?php echo $u['marca'] . " " . $u['modelo']; ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="small fw-bold text-dark"><?php echo $u['nombre_completo']; ?></span>
                                    <small class="text-muted" style="font-size: 11px;">DNI: <?php echo $u['dni_ruc']; ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3" style="font-size: 10px;">
                                    <?php echo $u['nombre_servicio'] ?? 'Mantenimiento'; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <i class="fa fa-cloud-check text-success fa-lg" title="Sincronizado"></i>
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
    if ($.fn.DataTable.isDataTable('#tablaReportes')) {
        $('#tablaReportes').DataTable().destroy();
    }
    $('#tablaReportes').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
            "search": "Buscar:",
            "searchPlaceholder": "Placa, DNI o Propietario..."
        }
    });
});
</script>

<?php include 'master/footer.php'; ?>