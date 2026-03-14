<?php
require_once "Poo/Conexion.php";
$db = new Conexion();

$id_cita = $_GET['id'];
$token = $_GET['tk'];

// 1. VALIDAR SEGURIDAD Y ESTADO
$sql_cita = "SELECT ci.*, ve.placa, ve.marca, ve.modelo 
             FROM citas ci 
             JOIN vehiculos ve ON ci.id_vehiculo = ve.id_vehiculo 
             WHERE ci.id_cita = '$id_cita' AND ci.token_aprobacion = '$token'";
$res_cita = $db->ejecutar($sql_cita);

if ($db->conexion->affected_rows == 0) {
    die("Enlace no válido.");
}
$cita = $db->recorrer($res_cita);

// --- BLOQUEO: Si ya no está esperando aprobación, el link se cierra ---
$link_cerrado = ($cita['estado'] !== 'ESPERANDO_APROBACION');

// 2. OBTENER HALLAZGOS (Solo si está pendiente)
$sql_h = "SELECT * FROM hallazgos WHERE id_cita = '$id_cita'";
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
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .card-hallazgo { border-radius: 20px; border: 2px solid transparent; overflow: hidden; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all 0.3s ease; }
        .check-servicio:checked + .card-hallazgo { border-color: #198754; background-color: #f8fff9; transform: scale(1.02); opacity: 1; box-shadow: 0 8px 25px rgba(25, 135, 84, 0.2); }
        .img-evidencia { height: 180px; object-fit: cover; width: 100%; }
        .sticky-bottom-total { position: sticky; bottom: 0; background: white; padding: 20px; border-top: 1px solid #eee; box-shadow: 0 -10px 30px rgba(0,0,0,0.08); z-index: 1000; }
        .overlay-bloqueo { background: rgba(255,255,255,0.9); backdrop-filter: blur(5px); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; display: flex; align-items: center; justify-content: center; text-align: center; padding: 20px; }
    </style>
</head>
<body>

<?php if ($link_cerrado): ?>
    <div class="overlay-bloqueo">
        <div>
            <div class="mb-4">
                <i class="fa fa-check-circle text-success fa-5x mb-3"></i>
                <h2 class="fw-bold">¡Presupuesto Procesado!</h2>
                <p class="text-muted">El estado actual de tu vehículo es: <br>
                <span class="badge bg-primary fs-5 mt-2"><?php echo $cita['estado']; ?></span></p>
                <p>Ya no es posible realizar modificaciones desde este enlace.</p>
            </div>
            <a href="https://drmotorsperu.com/" class="btn btn-dark btn-lg rounded-pill px-5">
                Ir a la Web Principal
            </a>
        </div>
    </div>
<?php endif; ?>

<div class="container py-4">
    <div class="text-center mb-4">
        <h4 class="fw-bold mb-0">DR. MOTORS</h4>
        <div class="badge bg-dark px-3 py-2 rounded-pill mt-2">
            <i class="fa fa-car me-2"></i> <?php echo $cita['placa']; ?>
        </div>
        <h5 class="mt-4 fw-bold text-primary">Seleccione los trabajos que se realizarán:</h5>
    </div>

    <form id="formAprobacion">
        <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">

        <?php while($h = $db->recorrer($res_h)): ?>
        <div class="item-presupuesto">
            <input type="checkbox" class="btn-check check-servicio" name="hallazgos[]" 
                   id="h_<?php echo $h['id_hallazgo']; ?>" value="<?php echo $h['id_hallazgo']; ?>" 
                   data-precio="<?php echo $h['monto_estimado']; ?>" autocomplete="off">
            
            <label class="card card-hallazgo w-100" for="h_<?php echo $h['id_hallazgo']; ?>">
                <?php if($h['imagen_evidencia']): ?>
                    <img src="uploads/<?php echo $h['imagen_evidencia']; ?>" class="img-evidencia" alt="Falla">
                <?php endif; ?>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-secondary rounded-pill small">ID #<?php echo $h['id_hallazgo']; ?></span>
                        <h5 class="fw-bold mb-0 text-dark">S/ <?php echo number_format($h['monto_estimado'], 2); ?></h5>
                    </div>
                    <p class="card-text text-dark fw-medium mb-0"><?php echo $h['descripcion_falla']; ?></p>
                </div>
            </label>
        </div>
        <?php endwhile; ?>

        <div class="sticky-bottom-total rounded-top-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="h6 text-muted mb-0">Total Seleccionado:</span>
                <span class="h3 fw-bold text-success mb-0" id="montoTotal">S/ 0.00</span>
            </div>
            <button type="button" onclick="confirmarAprobacion()" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-lg">
                CONFIRMAR TRABAJOS
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function calcularTotal() {
        let total = 0;
        document.querySelectorAll('.check-servicio:checked').forEach(item => {
            total += parseFloat(item.getAttribute('data-precio'));
        });
        document.getElementById('montoTotal').innerText = 'S/ ' + total.toFixed(2);
    }

    document.querySelectorAll('.check-servicio').forEach(item => {
        item.addEventListener('change', calcularTotal);
    });

    function confirmarAprobacion() {
        const total = document.getElementById('montoTotal').innerText;
        const seleccionados = document.querySelectorAll('.check-servicio:checked').length;

        let titulo = seleccionados === 0 ? '¿Pasar directo a lavado?' : '¿Confirmar estos trabajos?';
        let texto = seleccionados === 0 ? 'No realizarás reparaciones adicionales.' : "Total de la inversión: " + total;

        Swal.fire({
            title: titulo,
            text: texto,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Revisar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(document.getElementById('formAprobacion'));
                fetch('Poo/procesar_aprobacion_cliente.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire({
                            title: '¡Recibido!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Ir a Inicio'
                        }).then(() => {
                            // REDIRECCIÓN SOLICITADA
                            window.location.href = 'https://drmotorsperu.com/';
                        });
                    }
                });
            }
        });
    }
</script>
</body>
</html>