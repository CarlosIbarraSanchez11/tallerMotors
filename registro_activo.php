<?php
session_start();
if(!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit; }
include 'master/header.php'; // Asumiendo que crearás un header con el diseño
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-primary text-white p-3 rounded-top-4">
                    <h5 class="mb-0"><i class="fa fa-car me-2"></i> Nuevo Ingreso al Taller</h5>
                </div>
                <div class="card-body p-4">
                    <form action="Poo/procesar_ingreso.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6 class="text-primary fw-bold mb-3 text-uppercase">Datos del Cliente</h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">DNI / RUC</label>
                                    <input type="text" name="documento" class="form-control" required placeholder="Buscar o registrar...">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Nombre Completo</label>
                                    <input type="text" name="nombre_cliente" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Teléfono / WhatsApp</label>
                                    <input type="text" name="telefono" class="form-control" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-primary fw-bold mb-3 text-uppercase">Datos del Vehículo</h6>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label small fw-bold">Placa</label>
                                        <input type="text" name="placa" class="form-control" required placeholder="ABC-123">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label small fw-bold">Marca</label>
                                        <input type="text" name="marca" class="form-control" placeholder="Ej: Toyota">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label small fw-bold">Modelo</label>
                                        <input type="text" name="modelo" class="form-control" placeholder="Ej: Hilux">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label small fw-bold">Año</label>
                                        <input type="number" name="anio" class="form-control">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label small fw-bold">Kilometraje Actual</label>
                                        <input type="number" name="km" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="text-end">
                            <button type="reset" class="btn btn-light px-4">Limpiar</button>
                            <button type="submit" class="btn btn-primary px-5 fw-bold">REGISTRAR ENTRADA</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>