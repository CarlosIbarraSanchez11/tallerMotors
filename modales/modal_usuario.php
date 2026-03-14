<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold m-0" id="tituloModal">Nuevo Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUsuario" action="Poo/guardar_usuario.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_usuario" id="id_usuario">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nombre Completo</label>
                        <input type="text" name="nombre" id="nombre" class="form-control bg-light border-0 shadow-none" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Sede / Taller Asignado</label>
                        <select name="id_taller" id="id_taller" class="form-select bg-light border-0 shadow-none">
                            <option value="">-- SELECCIONAR TALLER (O GLOBAL) --</option>
                            <?php 
                            $talleres = $db->ejecutar("SELECT * FROM talleres");
                            while($t = $db->recorrer($talleres)){
                                echo "<option value='".$t['id_taller']."'>".$t['nombre_taller']."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Usuario Acceso</label>
                            <input type="text" name="usuario" id="usuario" class="form-control bg-light border-0 shadow-none" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Contraseña</label>
                            <input type="password" name="password" id="password" class="form-control bg-light border-0 shadow-none">
                            <small class="text-primary" id="passHelp" style="display:none; font-size: 10px;">
                                <i class="fa fa-info-circle"></i> Dejar en blanco para mantener actual
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nivel de Acceso</label>
                        <select name="nivel" id="nivel" class="form-select bg-light border-0 shadow-none" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="ADMINISTRADOR">ADMINISTRADOR</option>
                            <option value="GERENTE">GERENTE</option>
                            <option value="CALL CENTER">CALL CENTER</option>
                            <option value="LOGISTICA">LOGÍSTICA</option>
                            <option value="JEFE">JEFE</option>
                            <!-- <option value="JEFE DIAGNOSTICO">JEFE DIAGNOSTICO</option> -->
                            <option value="MECANICO">MECÁNICO</option>
                            <option value="LIMPIEZA">LIMPIEZA</option>
                            <option value="FACTURACION">FACTURACION</option>
                            
                            <!-- <option value="MECANICO DIAGNOSTICO">MECÁNICO DIAGNOSTICO</option> -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-3 shadow-sm fw-bold py-2">Confirmar Datos</button>
                </div>
            </form>
        </div>
    </div>
</div>