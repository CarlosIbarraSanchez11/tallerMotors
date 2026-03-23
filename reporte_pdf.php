<?php
session_start();
if (!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit(); }
require_once "Poo/Conexion.php";
$db = new Conexion();
$id_taller_sesion = $_SESSION['id_taller'];

include 'master/header.php'; 

// 🚀 URL base de tu bucket en Google Cloud Storage
$url_base_bucket = "https://storage.googleapis.com/taller-dr-motors-storage/uploads/pdf/";
?>

<style>
    /* Estilos para el buscador de DataTables */
    .dataTables_filter input { border-radius: 20px; padding: 5px 15px; border: 1px solid #dee2e6; outline: none; margin-left: 10px; }
    .dataTables_length select { padding: 5px 10px; border-radius: 8px; border: 1px solid #dee2e6; outline: none; }
    
    /* Botón estilo PDF */
    .btn-pdf {
        background: #fff0f0; color: #dc3545; border: 1px solid #ffc1c1;
        transition: all 0.2s ease; font-size: 12px;
    }
    .btn-pdf:hover { background: #dc3545; color: white; transform: translateY(-2px); }
    
    /* Diseño de Placa */
    .placa-badge {
        font-family: 'Monaco', monospace; background: #212529; color: #f8f9fa;
        padding: 4px 10px; border-radius: 6px; border: 1px solid #495057;
        font-size: 13px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0"><i class="fa fa-history me-2 text-primary"></i>Historial de Reportes PDF</h4>
            <small class="text-muted">Busca por Placa, DNI o Propietario los expedientes almacenados en la nube</small>
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
                            <th>Servicio Realizado</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-4">Expediente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Sinceramente, usamos ci.fecha_cita porque ot.fecha_orden no existe
                        $sql = "SELECT ot.id_orden, ot.url_pdf,
                                       v.placa, v.marca, v.modelo,
                                       c.nombre_completo, c.dni_ruc,
                                       ci.fecha_cita,
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
                                    <div class="d-flex flex-column">
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;"><?php echo $u['marca']; ?></small>
                                        <small class="text-muted text-uppercase" style="font-size: 10px;"><?php echo $u['modelo']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary bg-opacity-10 text-primary me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                                        <i class="fa fa-user" style="font-size: 13px;"></i>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="small fw-bold text-dark"><?php echo $u['nombre_completo']; ?></span>
                                        <small class="text-muted" style="font-size: 11px;">DNI/RUC: <?php echo $u['dni_ruc']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 fw-semibold" style="font-size: 11px;">
                                    <i class="fa fa-wrench me-1"></i> <?php echo $u['nombre_servicio'] ?? 'Mantenimiento'; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="text-success" title="Archivo en la nube">
                                    <i class="fa fa-cloud-check fa-lg"></i>
                                </div>
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
    // Sinceramente, si la tabla ya fue inicializada, la destruimos para evitar errores de duplicidad
    if ($.fn.DataTable.isDataTable('#tablaReportes')) {
        $('#tablaReportes').DataTable().destroy();
    }

    $('#tablaReportes').DataTable({
        responsive: true,
        order: [[0, 'desc']], // Ordenar por fecha reciente
        pageLength: 10,
        language: {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
            "search": "Buscar:",
            "searchPlaceholder": "Placa, DNI o Propietario...",
            "lengthMenu": "Ver _MENU_ reportes"
        },
        columnDefs: [
            { orderable: false, targets: [4, 5] } // Desactivar flechas de orden en Estado y Acción
        ]
    });
});
</script>

<?php 
include 'master/footer.php'; 
?>