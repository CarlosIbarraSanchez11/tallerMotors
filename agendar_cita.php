<?php
session_start();
if (!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit(); }
// Aquí incluirías tu header/menu que ya maqueteamos
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-primary text-white p-3">
                    <h5 class="mb-0"><i class="fa fa-calendar-plus me-2"></i> Agendar Nueva Cita</h5>
                </div>
                <div class="card-body p-4">
                    <form action="Poo/guardar_cita.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">DNI/RUC Cliente</label>
                                <input type="text" name="dni" class="form-control" placeholder="Buscar..." required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nombre Cliente</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha de Cita</label>
                                <input type="date" name="fecha" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Hora</label>
                                <input type="time" name="hora" class="form-control" required>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Motivo / Falla reportada</label>
                                <textarea name="motivo" class="form-control" rows="3" placeholder="Ej: Mantenimiento de 5000km, ruido en frenos..."></textarea>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-5">Agendar Cita</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>