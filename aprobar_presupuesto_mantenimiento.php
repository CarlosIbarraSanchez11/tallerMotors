<?php
require_once "Poo/Conexion.php";
$db = new Conexion();

$id_cita = mysqli_real_escape_string($db->conexion, $_GET['id']);
$token = mysqli_real_escape_string($db->conexion, $_GET['tk']);

$sql_cita = "SELECT ci.*, ve.placa, ve.marca, ve.modelo 
             FROM citas ci 
             JOIN vehiculos ve ON ci.id_vehiculo = ve.id_vehiculo 
             WHERE ci.id_cita = '$id_cita' AND ci.token_aprobacion = '$token'";
$res_cita = $db->ejecutar($sql_cita);

if ($db->contar($res_cita) == 0) { die("Enlace no válido o expirado."); }
$cita = $db->recorrer($res_cita);

$sql_conteo = "SELECT COUNT(*) as total FROM orden_hallazgos 
               WHERE id_cita = '$id_cita' AND estado_aprobacion = 'ESPERANDO_CONFIRMACION'";
$conteo = $db->recorrer($db->ejecutar($sql_conteo));
$link_cerrado = ($conteo['total'] == 0);

$sql_h = "SELECT h.*, p.nombre_producto 
          FROM orden_hallazgos h 
          LEFT JOIN productos p ON h.id_producto = p.id_producto
          WHERE h.id_cita = '$id_cita' AND h.estado_aprobacion = 'ESPERANDO_CONFIRMACION'";
$res_h = $db->ejecutar($sql_h);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto Dr. Motors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #0d6efd; --success-color: #198754; --dark-bg: #1e293b; }
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #334155; }
        .app-header { background: var(--dark-bg); padding: 30px 20px; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px; color: white; margin-bottom: 25px; }
        .card-hallazgo { border: none; border-radius: 24px; overflow: hidden; margin-bottom: 20px; transition: all 0.3s; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.05); cursor: pointer; }
        .check-servicio:checked + .card-hallazgo { transform: translateY(-5px); border: 2px solid var(--primary-color); }
        .img-container { position: relative; height: 180px; }
        .img-evidencia { width: 100%; height: 100%; object-fit: cover; }
        .price-badge { position: absolute; bottom: 15px; right: 15px; background: white; padding: 8px 15px; border-radius: 15px; font-weight: 800; color: var(--success-color); }
        .selection-indicator { position: absolute; top: 15px; left: 15px; width: 30px; height: 30px; border-radius: 50%; background: white; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; z-index: 10; }
        .check-servicio:checked + .card-hallazgo .selection-indicator { background: var(--primary-color); border-color: var(--primary-color); color: white; }
        .summary-bar { position: sticky; bottom: 20px; background: white; padding: 20px; border-radius: 25px; margin: 0 15px 20px; box-shadow: 0 15px 50px rgba(0,0,0,0.2); z-index: 1000; }
        .overlay-bloqueo { background: #f8fafc; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; display: flex; align-items: center; justify-content: center; }
        .header-logo { max-height: 50px; margin-bottom: 15px; }
    </style>
</head>
<body>

<?php if ($link_cerrado): ?>
    <div class="overlay-bloqueo">
        <div class="text-center p-4">
            <div class="mb-4 text-success"><i class="fa-solid fa-circle-check fa-5x"></i></div>
            <h2 class="fw-bold">¡Todo en Orden!</h2>
            <p class="text-muted fs-5">No tienes hallazgos pendientes.<br>Seguimos trabajando en tu unidad.</p>
        </div>
    </div>
<?php endif; ?>

<header class="app-header text-center">
    <img src="image/logo_taller.png" alt="Logo" class="header-logo">
    <h3 class="fw-bold mb-3"><?php echo $cita['placa']; ?></h3>
    <div class="d-flex justify-content-center gap-2">
        <span class="badge bg-white bg-opacity-10 rounded-pill px-3 py-2"><?php echo $cita['marca']; ?></span>
        <span class="badge bg-white bg-opacity-10 rounded-pill px-3 py-2 text-info"><?php echo $cita['estado']; ?></span>
    </div>
</header>

<div class="container mb-5">
    <div class="mb-4 ps-2 text-center text-md-start">
        <h5 class="fw-bold mb-1">Presupuesto de Hallazgos</h5>
        <p class="text-muted small">Selecciona los servicios que deseas autorizar.</p>
    </div>

    <form id="formAprobacion">
        <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">

        <?php 
        // 🚀 1. DEFINIMOS LA RUTA DEL BUCKET PARA HALLAZGOS
        $url_bucket_hallazgos = "https://storage.googleapis.com/taller-dr-motors-storage/img/hallazgos/";

        while($h = $db->recorrer($res_h)): 
            $total_item = $h['precio_producto']; 
            $nombre_item = !empty($h['nombre_producto']) ? $h['nombre_producto'] : $h['punto_falla'];
        ?>
        <div class="position-relative">
            <input type="checkbox" class="btn-check check-servicio" name="hallazgos[]" 
                   id="h_<?php echo $h['id_hallazgo']; ?>" value="<?php echo $h['id_hallazgo']; ?>" 
                   data-precio="<?php echo $total_item; ?>" autocomplete="off">
            
            <label class="card card-hallazgo" for="h_<?php echo $h['id_hallazgo']; ?>">
                <div class="selection-indicator"><i class="fa fa-check small"></i></div>
                <div class="img-container">
                    <?php if($h['foto_evidencia']): ?>
                        <img src="<?php echo $url_bucket_hallazgos . $h['foto_evidencia']; ?>" class="img-evidencia">
                    <?php else: ?>
                        <div class="img-evidencia d-flex align-items-center justify-content-center bg-light">
                            <i class="fa fa-wrench fa-3x text-muted opacity-25"></i>
                        </div>
                    <?php endif; ?>
                    <div class="price-badge">S/ <?php echo number_format($total_item, 2); ?></div>
                </div>
                <div class="card-body p-4">
                    <span class="text-primary fw-bold small text-uppercase mb-1 d-block"><?php echo $h['punto_falla']; ?></span>
                    <h5 class="fw-bold text-dark mb-2"><?php echo $nombre_item; ?></h5>
                    <p class="text-muted small mb-0 lh-sm"><?php echo $h['descripcion']; ?></p>
                </div>
            </label>
        </div>
        <?php endwhile; ?>

        <div class="summary-bar animate__animated animate__fadeInUp">
            <div class="row align-items-center g-2">
                <div class="col-12 col-md-4 mb-2 mb-md-0 text-center text-md-start">
                    <small class="text-muted d-block">Total Autorizado:</small>
                    <span class="h4 fw-bold text-dark mb-0" id="montoTotal">S/ 0.00</span>
                </div>
                <div class="col-6 col-md-4">
                    <button type="button" onclick="confirmarAccion('RECHAZAR')" class="btn btn-outline-danger w-100 rounded-pill fw-bold py-2">
                        <i class="fa fa-times me-1"></i> Rechazar Todo
                    </button>
                </div>
                <div class="col-6 col-md-4">
                    <button type="button" onclick="confirmarAccion('APROBAR')" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow">
                        Autorizar <i class="fa fa-check ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmarAccion(tipo) {
        const form = document.getElementById('formAprobacion');
        const seleccionados = document.querySelectorAll('.check-servicio:checked').length;
        const totalText = document.getElementById('montoTotal').innerText;

        let config = {};

        if (tipo === 'RECHAZAR') {
            config = {
                title: '¿Rechazar presupuesto?',
                text: 'Se notificará al taller que no autorizas estos trabajos adicionales.',
                icon: 'warning',
                confirmButtonText: 'Sí, rechazar todo',
                confirmButtonColor: '#dc3545'
            };
            // Desmarcamos todo para enviar el array de hallazgos vacío
            document.querySelectorAll('.check-servicio').forEach(c => c.checked = false);
        } else {
            if (seleccionados === 0) {
                Swal.fire('Atención', 'Selecciona al menos un hallazgo o pulsa "Rechazar Todo".', 'info');
                return;
            }
            config = {
                title: '¿Confirmar Autorización?',
                text: `Autorizarás trabajos por un total de ${totalText}.`,
                icon: 'question',
                confirmButtonText: 'Sí, autorizar',
                confirmButtonColor: '#0d6efd'
            };
        }

        Swal.fire({
            title: config.title,
            text: config.text,
            icon: config.icon,
            showCancelButton: true,
            confirmButtonColor: config.confirmButtonColor,
            confirmButtonText: config.confirmButtonText,
            cancelButtonText: 'Volver',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                const formData = new FormData(form);
                fetch('Poo/procesar_confirmacion_cliente.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({ title: '¡Perfecto!', text: data.message, icon: 'success' }).then(() => { window.location.reload(); });
                    } else { Swal.fire('Error', data.message, 'error'); }
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const contenedor = document.getElementById('formAprobacion');
        const labelTotal = document.getElementById('montoTotal');

        function calcularTotal() {
            let total = 0;
            document.querySelectorAll('.check-servicio:checked').forEach(item => {
                total += parseFloat(item.dataset.precio) || 0;
            });
            labelTotal.innerText = 'S/ ' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
        }

        contenedor.addEventListener('change', (e) => {
            if (e.target.classList.contains('check-servicio')) calcularTotal();
        });
    });
</script>
</body>
</html>