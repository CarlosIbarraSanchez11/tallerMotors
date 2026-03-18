<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();

if(!isset($_GET['id_cita'])) { header("Location: citas.php"); exit(); }
$id_cita = $_GET['id_cita'];

$url_bucket_ordenes = "https://storage.googleapis.com/taller-dr-motors-storage/img/ordenes/";
$url_bucket_lavado  = "https://storage.googleapis.com/taller-dr-motors-storage/img/lavado/";

// Traemos datos del vehículo y las fotos
$sql = "SELECT ci.*, v.placa, v.marca, v.modelo, ot.id_orden, ot.foto_frontal, ot.foto_lavado
        FROM citas ci
        JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
        JOIN ordenes_trabajo ot ON ci.id_cita = ot.id_cita
        WHERE ci.id_cita = '$id_cita'";

$res = $db->ejecutar($sql);
$d = $db->recorrer($res);

include 'master/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Control de Lavado y Salida</h4>
            <span class="badge bg-primary px-3 rounded-pill">Orden #<?php echo $d['id_orden']; ?></span>
        </div>
        <a href="citas.php" class="btn btn-outline-secondary rounded-pill bg-white shadow-sm">
            <i class="fa fa-arrow-left me-2"></i>Regresar
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-muted small">DATOS DE LA UNIDAD</h6>
                    <p class="mb-1"><b>Placa:</b> <span class="badge bg-dark"><?php echo $d['placa']; ?></span></p>
                    <p class="mb-3"><b>Vehículo:</b> <?php echo $d['marca'] . " " . $d['modelo']; ?></p>
                    
                    <h6 class="fw-bold border-bottom pb-2 mb-2 text-muted small">FOTO DE RECEPCIÓN (REFERENCIA)</h6>
                    <img src="<?php echo $url_bucket_ordenes . $d['foto_frontal']; ?>" class="img-fluid rounded-4 shadow-sm mb-2" style="width: 100%; height: 200px; object-fit: cover;">
                    <p class="text-muted small italic text-center">Así llegó el vehículo al taller.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-info"><i class="fa fa-magic me-2"></i>Evidencia de Lavado Final</h5>
                </div>
                <div class="card-body">
                    <form action="Poo/guardar_lavado.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">
                        <input type="hidden" name="id_orden" value="<?php echo $d['id_orden']; ?>">

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="fw-bold mb-3 d-block small text-uppercase">Checklist de Calidad</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="qc1" required>
                                    <label class="form-check-label small" for="qc1 text-muted">Carrocería limpia y seca</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="qc2" required>
                                    <label class="form-check-label small" for="qc2 text-muted">Aspirado interior completo</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="qc3" required>
                                    <label class="form-check-label small" for="qc3 text-muted">Vidrios sin manchas</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="qc4" required>
                                    <label class="form-check-label small" for="qc4 text-muted">Niveles verificados</label>
                                </div>
                            </div>

                            <div class="col-md-6 text-center">
                                <label class="fw-bold mb-3 d-block small text-uppercase">Foto del Vehículo Terminado</label>
                                
                                <div id="preview-container" class="mb-3 <?php echo $d['foto_lavado'] ? '' : 'd-none'; ?>">
                                    <img id="img-preview" src="<?php echo $d['foto_lavado'] ? $url_bucket_lavado . $d['foto_lavado'] : '#'; ?>" 
                                        class="img-fluid rounded-4 shadow" style="max-height: 200px; width: 100%; object-fit: cover;">
                                </div>

                                <div class="bg-light p-4 rounded-4 border-dashed border-2" id="drop-area">
                                    <i class="fa fa-car-wash fs-1 text-info mb-2"></i>
                                    <input type="file" name="foto_lavado" class="form-control form-control-sm" accept="image/*" required onchange="previewImage(event)">
                                    <small class="text-muted d-block mt-2">Toma la foto final para el cliente</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 opacity-10">

                        <div class="text-end">
                            <button type="submit" class="btn btn-info text-white btn-lg rounded-pill px-5 fw-bold shadow">
                                FINALIZAR Y LISTO PARA ENTREGA <i class="fa fa-check-circle ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    var input = event.target;
    var preview = document.getElementById('img-preview');
    var container = document.getElementById('preview-container');
    var dropArea = document.getElementById('drop-area');

    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.classList.remove('d-none');
            dropArea.classList.add('bg-success-light');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<style>
.border-dashed { border-style: dashed !important; border-color: #dee2e6 !important; border-width: 2px !important; }
.bg-success-light { background-color: rgba(40, 167, 69, 0.05) !important; }
</style>

<?php include 'master/footer.php'; ?>