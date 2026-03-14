<div class="modal fade" id="modalAgendarCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold m-0"><i class="fa fa-calendar-alt text-primary me-2"></i> Reservar Espacio en Taller</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formReserva" action="Poo/guardar_cita.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-5 border-end">
                            <input type="hidden" name="id_cliente" id="cita_id_cliente">
                            <input type="hidden" name="id_vehiculo" id="cita_id_vehiculo">
                            
                            <label class="small fw-bold text-muted">Vehículo para el Servicio</label>
                            <div class="d-flex align-items-center bg-light p-3 rounded-3 mb-3 border border-dashed">
                                <div class="bg-primary text-white rounded-2 px-2 py-1 me-2 small fw-bold" id="txt_placa">---</div>
                                <div class="small text-dark fw-bold" id="txt_vehiculo">Cargando...</div>
                            </div>

                            <label class="small fw-bold text-muted">Tipo de Atención</label>
                            <div class="d-flex gap-2 mb-3">
                                <input type="radio" class="btn-check" name="flujo_atencion" id="f_preventivo" value="PREVENTIVO" checked onchange="actualizarListaServicios()">
                                <label class="btn btn-outline-primary btn-sm rounded-pill px-3" for="f_preventivo">
                                    <i class="fa fa-tools"></i> Mantenimiento
                                </label>

                                <input type="radio" class="btn-check" name="flujo_atencion" id="f_diagnostico" value="DIAGNOSTICO" onchange="actualizarListaServicios()">
                                <label class="btn btn-outline-danger btn-sm rounded-pill px-3" for="f_diagnostico">
                                    <i class="fa fa-search"></i> Diagnóstico
                                </label>
                            </div>

                            <label class="small fw-bold text-muted">¿Qué servicio requiere?</label>
                            <select name="id_servicio" id="sel_servicio" class="form-select bg-light border-0 mb-3" onchange="cargarEspecialistasYDuracion(this)" required disabled>
                                <option value="">Cargando servicios compatibles...</option>
                            </select>

                            <label class="small fw-bold text-muted">Especialista Técnico Responsable</label>
                            <select name="id_tecnico" id="sel_tecnico" class="form-select bg-light border-0 mb-3" onchange="consultarHorarios()" required>
                                <option value="">Primero elija un servicio...</option>
                            </select>

                            <label class="small fw-bold text-muted">Fecha de Cita</label>
                            <input type="date" name="fecha" id="fecha_cita" class="form-control bg-light border-0 mb-3" min="<?php echo date('Y-m-d'); ?>" onchange="consultarHorarios()" required>
                            
                            <input type="hidden" name="hora_seleccionada" id="hora_seleccionada">
                        </div>

                        <div class="col-md-7 px-4 text-center">
                            <h6 class="fw-bold text-dark mb-1">Horarios Disponibles</h6>
                            <p class="small text-muted mb-3">Bloques según la duración del servicio.</p>
                            
                            <div id="contenedor_horarios" class="row g-2">
                                <div class="text-muted small py-5">
                                    <i class="fa fa-info-circle me-1"></i><br>
                                    Seleccione servicio, técnico y fecha...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <div class="w-100">
                        <div id="info_reserva_detalle" class="alert alert-info py-2 mb-3 d-none">
                            <small class="fw-bold" id="label_reserva_texto"></small>
                        </div>
                        <button type="submit" id="btnConfirmar" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow" disabled>
                            CONFIRMAR RESERVA <span id="label_hora"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>