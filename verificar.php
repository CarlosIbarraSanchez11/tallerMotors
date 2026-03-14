<?php
include 'master/header.php'; 
require_once "Poo/Conexion.php";
$db = new Conexion();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="fw-bold mb-0"><i class="fa fa-qrcode me-2 text-info"></i>Escáner Inteligente</h5>
                </div>
                
                <div class="card-body p-4">
                    <div id="status-message" class="mb-3 small text-muted">
                        Alinee el código QR dentro del recuadro
                    </div>
                    
                    <div id="reader" class="rounded-4 overflow-hidden border bg-light shadow-inner mb-4" style="width: 100%; min-height: 300px;"></div>

                    <div id="loading-screen" class="d-none">
                        <div class="py-4">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <h6 class="fw-bold">Redirigiendo al expediente...</h6>
                            <p class="text-muted small">Por favor, espere un momento.</p>
                        </div>
                    </div>

                    <div id="controls">
                        <button id="btn-start" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
                            <i class="fa fa-camera me-2"></i> INICIAR ESCANEO
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script src="https://unpkg.com/html5-qrcode"></script>

<script>
    const html5QrCode = new Html5Qrcode("reader");
    const qrConfig = { fps: 20, qrbox: { width: 250, height: 250 } }; // Subimos FPS para más velocidad

    document.getElementById('btn-start').addEventListener('click', function() {
        this.classList.add('d-none');
        
        html5QrCode.start(
            { facingMode: "environment" }, 
            qrConfig,
            (decodedText) => {
                // 🚀 DETECCIÓN INSTANTÁNEA
                procesarRedireccion(decodedText);
            }
        ).catch((err) => {
            alert("Error: No se pudo acceder a la cámara.");
            this.classList.remove('d-none');
        });
    });

    function procesarRedireccion(url) {
        // Sinceramente, filtramos que sea un link nuestro para evitar virus o errores
        if(url.includes("id_cita=") || url.includes("token=") || url.includes("t=")) {
            
            // 1. Detenemos la cámara de inmediato para liberar recursos
            html5QrCode.stop();

            // 2. Cambiamos la interfaz a modo "Cargando"
            document.getElementById('reader').classList.add('d-none');
            document.getElementById('status-message').classList.add('d-none');
            document.getElementById('loading-screen').classList.remove('d-none');

            // 3. ¡SALTO AUTOMÁTICO!
            // Sinceramente, 300ms es lo ideal: lo suficiente para que el ojo vea que "pasó algo"
            setTimeout(() => {
                window.location.href = url;
            }, 300);
        }
    }
</script>

<style>
    #reader video { border-radius: 15px !important; object-fit: cover !important; }
    .shadow-inner { box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); }
    /* Efecto de escaneo (opcional) */
    #reader { position: relative; }
    #reader::after {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 2px;
        background: rgba(13, 110, 253, 0.5);
        animation: scanning 2s infinite linear;
    }
    @keyframes scanning {
        0% { top: 0; }
        100% { top: 100%; }
    }
</style>

<?php include 'master/footer.php'; ?>