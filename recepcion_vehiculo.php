<?php
// 1. Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();

// 2. Validación de entrada (Cita y Técnico Único)
if (!isset($_GET['id_cita']) || empty($_GET['id_cita'])) {
    die("<div class='alert alert-danger'>Error: ID de cita no proporcionado.</div>");
}

// 🚀 CAMBIO CLAVE: Ahora buscamos 'id_tecnico' que viene del Radio Button
$id_tecnico_recibido = $_GET['id_tecnico'] ?? null;

if (!$id_tecnico_recibido) {
    header("Location: citas.php?res=falta_tecnicos");
    exit();
}

$id_cita = $_GET['id_cita'];

// 3. Consulta de datos del vehículo y cliente
$sql = "SELECT ci.*, v.placa, v.marca, v.modelo, v.color, v.id_vehiculo,
               cl.nombre_completo, s.nombre_servicio, s.precio_base
        FROM citas ci
        INNER JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
        INNER JOIN clientes cl ON ci.id_cliente = cl.id_cliente
        LEFT JOIN servicios s ON ci.id_servicio = s.id_servicio
        WHERE ci.id_cita = '$id_cita'";

$res = $db->ejecutar($sql);
$d = $db->recorrer($res);

if (!$d) {
    header("Location: citas.php"); 
    exit();
}

// 4. 🚀 Obtener datos del técnico único asignado
$resT = $db->ejecutar("SELECT nombre, nivel FROM usuarios WHERE id_usuario = '$id_tecnico_recibido'");
$regT = $db->recorrer($resT);
$nombre_tecnico = $regT['nombre'] ?? 'Técnico no encontrado';
$nivel_tecnico = $regT['nivel'] ?? '';

include 'master/header.php'; 
?>

<div class="container-fluid px-4 py-4">
    <form action="Poo/crear_orden.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">
        <input type="hidden" name="id_vehiculo" value="<?php echo $d['id_vehiculo']; ?>">
        
        <input type="hidden" name="tecnicos_finales[]" value="<?php echo $id_tecnico_recibido; ?>">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary bg-opacity-10 border-start border-primary border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1 text-primary">
                                    <i class="fa fa-wrench me-2"></i>MECÁNICO RESPONSABLE
                                </h6>
                                <div class="mt-2">
                                    <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm small">
                                        <i class="fa fa-user-check text-success me-1"></i> 
                                        <b><?php echo $nombre_tecnico; ?></b> 
                                        <small class="text-muted ms-1">(<?php echo $nivel_tecnico; ?>)</small>
                                    </span>
                                </div>
                            </div>
                            <div class="text-end">
                                <i class="fa fa-lock text-primary opacity-25 fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0"><i class="fa fa-file-signature text-primary me-2"></i>Hoja de Recepción Técnica</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-primary bg-opacity-10 border-0 rounded-4 d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h6 class="fw-bold mb-0 text-primary"><?php echo $d['marca'] . " " . $d['modelo']; ?></h6>
                                <span class="badge bg-primary"><?php echo $d['placa']; ?></span>
                                <small class="ms-2 text-muted">Color: <?php echo $d['color']; ?></small>
                            </div>
                            <div class="text-end">
                                <small class="d-block text-muted">Cliente:</small>
                                <span class="fw-bold"><?php echo $d['nombre_completo']; ?></span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">KILOMETRAJE ACTUAL</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fa fa-tachometer-alt"></i></span>
                                    <input type="number" name="km_ingreso" class="form-control border-0 bg-light" required placeholder="00000">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">NIVEL DE COMBUSTIBLE</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fa fa-gas-pump"></i></span>
                                    <select name="combustible" class="form-select border-0 bg-light">
                                        <option value="E">Vacío (E)</option>
                                        <option value="1/4">1/4</option>
                                        <option value="1/2">1/2</option>
                                        <option value="3/4">3/4</option>
                                        <option value="F">Lleno (F)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 opacity-25">

                        <h6 class="fw-bold mb-3">Inventario de Ingreso</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small">
                                <thead class="table-light">
                                    <tr>
                                        <th>ELEMENTO</th>
                                        <th class="text-center" style="width: 200px;">ESTADO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $items_inventario = [
                                        'docs'     => 'Tarjeta de Propiedad y SOAT',
                                        'herram'   => 'Gata y Llave de Ruedas',
                                        'repuesto' => 'Llave de Repuesto',
                                        'espejos'  => 'Espejos y Antenas',
                                        'radio'    => 'Radio / Pantalla Táctil',
                                        'vasos'    => 'Vasos / Tapas de Ruedas',
                                        'extintor' => 'Extintor y Triángulos',
                                        'encendedor'=> 'Encendedor / Cenicero'
                                    ];

                                    foreach ($items_inventario as $key => $label): 
                                    ?>
                                    <tr>
                                        <td class="fw-semibold text-dark">
                                            <i class="fa fa-check-circle text-primary opacity-50 me-2"></i>
                                            <?php echo $label; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group w-100 shadow-sm" role="group">
                                                <input type="radio" class="btn-check" name="check[<?php echo $key; ?>]" 
                                                    id="<?php echo $key; ?>_si" value="OK" checked>
                                                <label class="btn btn-outline-success btn-sm border-2" for="<?php echo $key; ?>_si">
                                                    <i class="fa fa-check"></i>
                                                </label>

                                                <input type="radio" class="btn-check" name="check[<?php echo $key; ?>]" 
                                                    id="<?php echo $key; ?>_no" value="NO">
                                                <label class="btn btn-outline-danger btn-sm border-2" for="<?php echo $key; ?>_no">
                                                    <i class="fa fa-times"></i>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <label class="fw-bold mb-2">Observaciones, Daños o Pertenencias</label>
                        <textarea name="observaciones" class="form-control border-0 bg-light" rows="4" placeholder="Escriba aquí daños o pertenencias..."></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa fa-camera text-primary me-2"></i>Fotos del Ingreso</h6>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="mb-3 p-3 border rounded-4 bg-light text-center preview-box">
                            <label class="d-block small fw-bold mb-2">VISTA FRONTAL / PLACA</label>
                            <div class="mb-2 d-none container-prev">
                                <img src="" class="img-fluid rounded-3 shadow-sm img-prev" style="max-height: 150px;">
                            </div>
                            <input type="file" name="foto_frontal" class="form-control form-control-sm input-comprimir" accept="image/*" capture="camera">
                        </div>

                        <div class="mb-3 p-3 border rounded-4 bg-light text-center preview-box">
                            <label class="d-block small fw-bold mb-2">VISTA POSTERIOR</label>
                            <div class="mb-2 d-none container-prev">
                                <img src="" class="img-fluid rounded-3 shadow-sm img-prev" style="max-height: 150px;">
                            </div>
                            <input type="file" name="foto_posterior" class="form-control form-control-sm input-comprimir" accept="image/*" capture="camera">
                        </div>

                        <div class="mb-3 p-3 border rounded-4 bg-light text-center preview-box">
                            <label class="d-block small fw-bold mb-2">TABLERO (KM / LUCES)</label>
                            <div class="mb-2 d-none container-prev">
                                <img src="" class="img-fluid rounded-3 shadow-sm img-prev" style="max-height: 150px;">
                            </div>
                            <input type="file" name="foto_tablero" class="form-control form-control-sm input-comprimir" accept="image/*" capture="camera">
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow">
                            ABRIR ORDEN DE TRABAJO <i class="fa fa-arrow-right ms-2"></i>
                        </button>
                        <a href="citas.php" class="btn btn-link w-100 text-muted mt-2 text-decoration-none small">Cancelar y volver</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .img-prev {
        object-fit: cover;
        border: 2px solid #fff;
        transition: all 0.3s ease;
    }
    .preview-box {
        transition: all 0.4s ease;
    }
</style>

<?php include 'master/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/compressorjs/1.2.1/compressor.min.js"></script>

<script>
    /**
     * 1. TU FUNCIÓN DE COMPRESIÓN (VERSION MEJORADA)
     * Ahora renombra automáticamente a .jpg para ahorrar espacio.
     */
    function procesarYComprimirImagen(file, callback) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function(event) {
            const img = new Image();
            img.src = event.target.result;
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const maxWidth = 800; // Resolución ideal para reportes
                const scaleSize = maxWidth / img.width;
                
                canvas.width = maxWidth;
                canvas.height = img.height * scaleSize;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                // Exportamos como JPEG al 60% de calidad
                const dataUrl = canvas.toDataURL('image/jpeg', 0.6);
                
                // Lógica para forzar la extensión .jpg en el nombre
                let nombreOriginal = file.name;
                let nombreSinExt = nombreOriginal.substring(0, nombreOriginal.lastIndexOf('.')) || nombreOriginal;
                let nuevoNombre = nombreSinExt + ".jpg";

                fetch(dataUrl)
                    .then(res => res.blob())
                    .then(blob => {
                        const compressedFile = new File([blob], nuevoNombre, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        callback(compressedFile, dataUrl);
                    });
            };
        };
    }

    /**
     * 2. ESCUCHADOR DE EVENTOS
     * Detecta cuando el mecánico elige una foto y activa la compresión.
     */
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('input-comprimir')) {
            const file = e.target.files[0];
            if (!file) return;

            const input = e.target;
            const container = input.closest('.border'); // Feedback visual
            
            // Indicador: azul mientras procesa
            container.style.backgroundColor = "#e8f0fe";

            procesarYComprimirImagen(file, function(compressedFile, dataUrl) {
                // Usamos DataTransfer para inyectar el archivo "flaquito" al input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                input.files = dataTransfer.files;

                // Feedback: verde cuando termina
                container.style.backgroundColor = "#e6fffa";
                console.log(`Foto ${input.name} optimizada: ${(compressedFile.size / 1024).toFixed(2)} KB`);
            });
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('input-comprimir')) {
            const file = e.target.files[0];
            if (!file) return;

            const input = e.target;
            const box = input.closest('.preview-box'); // Contenedor principal
            const containerPrev = box.querySelector('.container-prev'); // Div de la imagen
            const imgPrev = box.querySelector('.img-prev'); // La etiqueta <img>
            
            // Indicador visual: procesando
            box.style.backgroundColor = "#e8f0fe";
            box.style.borderColor = "#00a8e8";

            procesarYComprimirImagen(file, function(compressedFile, dataUrl) {
                // 1. Inyectamos el archivo optimizado al input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                input.files = dataTransfer.files;

                // 🚀 2. MOSTRAR PREVIEW: Usamos el dataUrl que ya generó el Canvas
                imgPrev.src = dataUrl;
                containerPrev.classList.remove('d-none'); // Mostramos el contenedor

                // Feedback visual final
                box.style.backgroundColor = "#e6fffa"; 
                box.style.borderColor = "#38b2ac";
                console.log(`Preview cargada para ${input.name}`);
            });
        }
    });
</script>