<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();
include 'master/header.php'; 

// Consultar todos los servicios configurados
$sql = "SELECT * FROM servicios ORDER BY tipo_raiz, especialidad";
$res = $db->ejecutar($sql);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><i class="fa fa-cogs me-2 text-primary"></i>Configuración de Servicios</h4>
        <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalNuevoServicio">
            <i class="fa fa-plus me-2"></i>Nuevo Servicio
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-secondary small fw-bold text-uppercase">Servicio</th>
                            <th class="py-3 text-secondary small fw-bold text-uppercase">Especialidad</th>
                            <th class="py-3 text-secondary small fw-bold text-uppercase">Detalles</th>
                            <th class="py-3 text-secondary small fw-bold text-uppercase text-center">Duración</th>
                            <th class="py-3 text-secondary small fw-bold text-uppercase">Precio Base</th>
                            <th class="py-3 text-secondary small fw-bold text-uppercase text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($s = $db->recorrer($res)):
                            $color_especialidad = 'text-primary'; 
                            
                            if ($s['especialidad'] == 'DIAGNOSTICO') {
                                $color_especialidad = 'text-success'; 
                            } elseif ($s['especialidad'] == 'CORRECTIVO') {
                                $color_especialidad = 'text-danger';  
                            }
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?php echo $s['nombre_servicio']; ?></div>
                                <small class="text-muted"><?php echo $s['tipo_raiz']; ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light <?php echo $color_especialidad; ?> border">
                                    <?php echo $s['especialidad']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="small text-dark"><?php echo $s['tipo_tecnologia']; ?></div>
                                <div class="text-muted small"><?php echo $s['categoria_vehiculo']; ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-soft-dark text-dark border">
                                    <i class="fa fa-clock me-1 text-muted"></i><?php echo $s['duracion_horas']; ?> h
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-success">S/ <?php echo number_format($s['precio_base'], 2); ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm rounded-3">
                                    <button class="btn btn-white btn-sm border" title="Editar" 
                                            onclick='prepararEdicion(<?php echo json_encode($s); ?>)'>
                                        <i class="fa fa-edit text-primary"></i>
                                    </button>
                                    
                                    <button class="btn btn-white btn-sm border" title="Checklist" 
                                            onclick="verChecklist(<?php echo $s['id_servicio']; ?>, '<?php echo $s['nombre_servicio']; ?>', '<?php echo $s['tipo_raiz']; ?>')">
                                        <i class="fa fa-list-check text-dark"></i>
                                    </button>

                                    <?php if($s['tipo_raiz'] == 'MANTENIMIENTO'): ?>
                                    <button class="btn btn-white btn-sm border" title="Insumos" 
                                            onclick="verInsumos(<?php echo $s['id_servicio']; ?>, '<?php echo $s['nombre_servicio']; ?>')">
                                        <i class="fa fa-box-open text-success"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Cabecera estilo "Corporate Slate" */
    .table thead th { 
        background-color: #f8fafc; /* Gris azulado muy tenue */
        color: #64748b; /* Texto gris pizarra profesional */
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e2e8f0; /* Línea divisoria elegante */
        padding-top: 1.25rem;
        padding-bottom: 1.25rem;
        border-top: none;
    }

    /* Quitar bordes internos molestos */
    .table th, .table td {
        border-color: #f1f5f9;
    }

    /* Efecto de fila premium */
    .table-hover tbody tr {
        transition: background-color 0.2s ease;
    }
    
    .table-hover tbody tr:hover { 
        background-color: #fdfdfd !important; /* Cambio de color casi imperceptible */
        box-shadow: inset 3px 0 0 #0d6efd; /* Rayita azul de "foco" a la izquierda */
    }

    /* Badges más profesionales (más pequeños y redondeados) */
    .badge {
        font-weight: 600;
        padding: 0.4em 0.8em;
        font-size: 0.7rem;
    }

    /* Botones de acción estilo "Ghost" */
    .btn-action-outline {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #64748b;
        padding: 5px 10px;
        border-radius: 6px;
        transition: all 0.2s;
    }
    .btn-action-outline:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }
</style>

<div class="modal fade" id="modalEdicionRapida" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Editar Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEdicionRapida">
                <div class="modal-body">
                    <input type="hidden" name="id_servicio" id="edit_id_servicio">
                    <input type="hidden" name="accion" value="actualizar_base">
                    <div class="mb-3">
                        <label class="small fw-bold">Precio Base (S/):</label>
                        <input type="number" name="precio" id="edit_precio" class="form-control rounded-3" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Duración Est. (Horas):</label>
                        <input type="number" name="horas" id="edit_horas" class="form-control rounded-3" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalChecklist" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-primary" id="tituloChecklist">Configurar Pasos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoPaso" class="mb-4 bg-light p-3 rounded-4 border border-dashed">
                    <input type="hidden" name="id_servicio" id="idServicioChecklist">
                    <div class="row g-2">
                        <div class="col-md-3" id="contenedorSector">
                            <select name="seccion_paso" class="form-select border-0 shadow-sm">
                                <option value="MOTOR">MOTOR</option>
                                <option value="FRENOS">FRENOS</option>
                                <option value="SUSPENSION">SUSPENSIÓN</option>
                                <option value="SISTEMA ELECTRICO">SIST. ELÉCTRICO</option>
                                <option value="FLUIDOS">FLUIDOS</option>
                            </select>
                        </div>
                        
                        <div class="col-md-5" id="colDescripcion">
                            <input type="text" name="descripcion" class="form-control border-0 shadow-sm" placeholder="Descripción del paso..." required>
                        </div>

                        <div class="col-md-2">
                            <select name="categoria_paso" class="form-select border-0 shadow-sm">
                                <option value="EJECUCION">EJECUCIÓN</option>
                                <option value="PRE-REQUISITO">PRE-REQUISITO</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">Añadir</button>
                        </div>
                    </div>
                </form>
                <div id="listaPasos" class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Nuevo Servicio -->
<div class="modal fade" id="modalNuevoServicio" tabindex="-1">
    <div class="modal-dialog modal-md border-0">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-primary">Configurar Nuevo Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoServicio">
                <input type="hidden" name="accion" value="nuevo_servicio">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Tipo de Servicio:</label>
                            <select name="tipo_raiz" id="tipo_raiz" class="form-select rounded-3 shadow-sm" onchange="actualizarEspecialidades()">
                                <option value="" selected disabled>Seleccionar tipo...</option>
                                <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                                <option value="DIAGNOSTICO">DIAGNOSTICO</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Especialidad:</label>
                            <select name="especialidad" id="especialidad" class="form-select rounded-3 shadow-sm">
                                <option value="PREVENTIVO">PREVENTIVO</option>
                                <option value="CORRECTIVO">CORRECTIVO</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Nivel:</label>
                            <select name="nivel" id="nivel_servicio" class="form-select rounded-3 shadow-sm">
                                <option value="" selected disabled>Seleccionar Nivel</option>
                                <option value="MENOR">MENOR</option>
                                <option value="REGULAR">REGULAR</option> <option value="MAYOR">MAYOR</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Tecnología:</label>
                            <select name="tecnologia" class="form-select rounded-3 shadow-sm">
                                <option value="" selected disabled>Seleccionar Tecnología</option>
                                <option value="CONVENCIONAL">CONVENCIONAL</option>
                                <option value="DIESEL">DIESEL</option>
                                <option value="GAMA_ALTA">GAMA ALTA</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Categoría Vehículo:</label>
                            <select name="categoria" class="form-select rounded-3 shadow-sm">
                                <option value="" selected disabled>Seleccionar Categoria del Vehiculo</option>
                                <option value="AUTO">AUTO</option>
                                <option value="CAMIONETA">CAMIONETA</option>
                                <option value="FURGON">FURGON</option>
                                <option value="GAMA_ALTA">GAMA ALTA</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Duración (Hrs):</label>
                            <input type="number" name="horas" class="form-control rounded-3" value="2" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Precio Base (S/):</label>
                        <input type="number" name="precio" class="form-control rounded-3 fw-bold text-success" step="0.01" value="180.00" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow">Crear Servicio y Pasos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Productos -->
<div class="modal fade" id="modalInsumos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-success" id="tituloInsumos">Repuestos del Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoInsumo" class="mb-4 bg-light p-3 rounded-4 border border-dashed">
                    <input type="hidden" name="id_servicio" id="idServicioInsumos">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="small fw-bold">Producto / Repuesto:</label>
                             <select name="id_producto" id="select_productos_db" class="form-select border-0 shadow-sm" required>
                                <option value="">Cargando...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold">Cant. Sugerida:</label>
                            <input type="number" name="cantidad" class="form-control border-0 shadow-sm" step="0.1" value="1" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100 fw-bold">Vincular</button>
                        </div>
                    </div>
                </form>

                <div id="listaInsumos" class="list-group list-group-flush">
                    </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Lógica para cambiar las especialidades según la raíz
    function actualizarEspecialidades() {
        const raiz = document.getElementById('tipo_raiz').value;
        const comboEsp = document.getElementById('especialidad');
        const comboNivel = document.getElementById('nivel_servicio'); // Obtenemos el select de nivel
        
        comboEsp.innerHTML = ''; // Limpiar
        
        if (raiz === 'MANTENIMIENTO') {
            comboEsp.innerHTML = `
                <option value="PREVENTIVO">PREVENTIVO</option>
                <option value="AIRE">PREVENTIVO AIRE ACONDICIONADO</option>
                <option value="CORRECTIVO">CORRECTIVO</option>
            `;
            // Para mantenimiento podrías preferir solo Menor/Mayor
            comboNivel.innerHTML = `
                <option value="MENOR">MENOR</option>
                <option value="MAYOR">MAYOR</option>
            `;
        } else {
            comboEsp.innerHTML = `<option value="DIAGNOSTICO">DIAGNOSTICO</option>`;
            // Para diagnóstico habilitamos las tres opciones
            comboNivel.innerHTML = `
                <option value="MENOR">MENOR</option>
                <option value="REGULAR">REGULAR</option>
                <option value="MAYOR">MAYOR</option>
            `;
        }
    }

    document.getElementById('formNuevoServicio').addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch('Poo/gestion_servicios.php', { 
            method: 'POST', 
            body: new FormData(this) 
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                // AQUÍ ESTÁ EL SWEET ALERT QUE PEDISTE
                Swal.fire({
                    title: '¡Servicio Creado!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonText: 'Excelente'
                }).then(() => {
                    location.reload(); // Recarga para ver la nueva tarjeta
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        });
    });

    // --- LÓGICA DE EDICIÓN DE PRECIO/HORAS ---
    function prepararEdicion(servicio) {
        document.getElementById('edit_id_servicio').value = servicio.id_servicio;
        document.getElementById('edit_precio').value = servicio.precio_base;
        document.getElementById('edit_horas').value = servicio.duracion_horas;
        new bootstrap.Modal(document.getElementById('modalEdicionRapida')).show();
    }

    document.getElementById('formEdicionRapida').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('Poo/gestion_servicios.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire('¡Éxito!', data.message, 'success').then(() => location.reload());
            }
        });
    });

    // --- LÓGICA DE CHECKLIST (PASOS) ---
    function verChecklist(idServicio, nombre, tipoRaiz) {
        document.getElementById('idServicioChecklist').value = idServicio;
        document.getElementById('tituloChecklist').innerText = "Pasos: " + nombre;
        
        const contenedorSector = document.getElementById('contenedorSector');
        const colDescripcion = document.getElementById('colDescripcion');

        if (tipoRaiz === 'DIAGNOSTICO') {
            contenedorSector.style.display = 'none'; // Ocultar sector
            colDescripcion.className = 'col-md-8';  // Expandir descripción (3 + 5 = 8)
        } else {
            contenedorSector.style.display = 'block'; // Mostrar sector
            colDescripcion.className = 'col-md-5';   // Tamaño normal
        }

        cargarPasos(idServicio);
        new bootstrap.Modal(document.getElementById('modalChecklist')).show();
    }

    function cargarPasos(idServicio) {
        fetch('Poo/obtener_pasos.php?id=' + idServicio)
        .then(r => r.text())
        .then(html => { document.getElementById('listaPasos').innerHTML = html; });
    }

    document.getElementById('formNuevoPaso').addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.append('accion', 'agregar_paso');
        fetch('Poo/gestion_servicios.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                this.reset();
                cargarPasos(document.getElementById('idServicioChecklist').value);
            }
        });
    });

    function eliminarPaso(idPaso) {
        Swal.fire({
            title: '¿Eliminar paso?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, borrar'
        }).then((result) => {
            if (result.isConfirmed) {
                let fd = new FormData();
                fd.append('accion', 'eliminar_paso');
                fd.append('id_paso', idPaso);
                fetch('Poo/gestion_servicios.php', { method: 'POST', body: fd })
                .then(() => cargarPasos(document.getElementById('idServicioChecklist').value));
            }
        });
    }

    let modalInsumos;

    function verInsumos(idServicio, nombre) {
        // 1. Asignamos los valores básicos
        document.getElementById('idServicioInsumos').value = idServicio;
        document.getElementById('tituloInsumos').innerText = "Insumos: " + nombre;

        // 2. Cargamos los datos
        cargarSelectProductos();
        cargarListaInsumos(idServicio);

        // 3. Mostramos el modal correctamente (evita duplicar fondos oscuros)
        const modalElement = document.getElementById('modalInsumos');
        if (!modalInsumos) {
            modalInsumos = new bootstrap.Modal(modalElement);
        }
        modalInsumos.show();
    }

    function cargarSelectProductos() {
        const contenedor = document.getElementById('select_productos_db');
        contenedor.innerHTML = '<option value="">Cargando bodega...</option>';

        // Agregamos Date.now() para que siempre pida datos frescos al servidor
        fetch('Poo/obtener_catalogo_productos.php?v=' + Date.now())
            .then(response => {
                if (!response.ok) throw new Error('Error en red');
                return response.text();
            })
            .then(html => {
                // Aquí es donde el PHP debe enviar SOLO los <option>
                if(html.trim() == "") {
                    contenedor.innerHTML = '<option value="">Sin productos encontrados</option>';
                } else {
                    contenedor.innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contenedor.innerHTML = '<option value="">Error al conectar</option>';
            });
    }

    function cargarListaInsumos(idServicio) {
        fetch('Poo/obtener_insumos_servicio.php?id=' + idServicio + '&v=' + Date.now())
            .then(r => r.text())
            .then(html => { 
                document.getElementById('listaInsumos').innerHTML = html; 
            });
    }

    // Guardar vínculo
    document.getElementById('formNuevoInsumo').addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.append('accion', 'vincular_insumo');
        
        fetch('Poo/gestion_servicios.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                this.reset();
                // Recargamos la lista usando el ID que ya tenemos en el hidden
                cargarListaInsumos(document.getElementById('idServicioInsumos').value);
                Swal.fire({ icon: 'success', title: 'Producto Vinculado', timer: 1000, showConfirmButton: false });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });

    function eliminarInsumo(idSP) {
        Swal.fire({
            title: '¿Retirar producto?',
            text: "Se quitará de este mantenimiento",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, quitar'
        }).then((result) => {
            if (result.isConfirmed) {
                let fd = new FormData();
                fd.append('accion', 'eliminar_insumo');
                fd.append('id_sp', idSP);
                fetch('Poo/gestion_servicios.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    cargarListaInsumos(document.getElementById('idServicioInsumos').value);
                });
            }
        });
    }
</script>

<?php include 'master/footer.php'; ?>