<?php
session_start();
if (!isset($_SESSION['id_taller'])) { header("Location: index.php"); exit(); }
require_once "Poo/Conexion.php";
$db = new Conexion();
$id_taller_sesion = $_SESSION['id_taller'];

include 'master/header.php'; 
?>

<style>
    /* 🎨 Estilos originales para los slots de tiempo */
    .time-slot { 
        cursor: pointer; border-radius: 12px; padding: 10px; border: 1px solid #e9ecef; 
        background: #f8f9fa; transition: all 0.2s ease; text-align: center; font-weight: 600; color: #495057;
    }
    .time-slot:hover:not(.disabled) { background: #0d6efd; color: white; border-color: #0d6efd; transform: translateY(-2px); }
    .time-slot.disabled { background-color: #e9ecef !important; color: #adb5bd !important; border: 1px dashed #ced4da !important; cursor: not-allowed !important; pointer-events: none; opacity: 0.6; }
    .time-slot.selected { background-color: #00a8e8 !important; color: white !important; border-color: #005082 !important; }

    /* Forzamos que el buscador de DataTables se vea bien con Bootstrap 5 */
    .dataTables_filter input { border-radius: 20px; padding: 5px 15px; border: 1px solid #dee2e6; outline: none; margin-left: 10px; }
    .dataTables_length select {
    padding-right: 25px !important; /* Da espacio para que el número no choque con la flecha */
    padding-left: 10px !important;
    border-radius: 8px !important;
    border: 1px solid #dee2e6 !important;
    outline: none !important;
    background-position: right 5px center !important; /* Ajusta la posición de la flechita */
    min-width: 60px; /* Evita que se encoja demasiado */
}

/* Ajuste opcional para que el texto "Mostrar" y "registros" no esté tan pegado */
.dataTables_length {
    margin-bottom: 15px;
    font-size: 0.9rem;
    color: #6c757d;
}
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Directorio de Clientes</h4>
            <small class="text-muted">Gestiona los propietarios y reserva citas según disponibilidad técnica</small>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="nuevoCliente()">
            <i class="fa fa-user-plus me-2"></i> Nuevo Cliente
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4"> 
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="tablaClientes">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Cliente / Propietario</th>
                            <th>DNI / RUC</th>
                            <th>Teléfono</th>
                            <th>Vehículo</th> 
                            <th>Cita / Correo</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT c.*, 
                                v.id_vehiculo, v.placa, v.marca, v.modelo, v.tipo_vehiculo, v.anio, v.color, v.combustible, v.vin_chasis,
                                ci.id_cita, ci.fecha_cita, ci.hora_inicio, ci.hora_fin, ci.estado AS estado_cita,
                                s.nombre_servicio,
                                u.nombre AS tecnico_nombre
                            FROM clientes c 
                            LEFT JOIN vehiculos v ON c.id_cliente = v.id_cliente 
                            LEFT JOIN citas ci ON (v.id_vehiculo = ci.id_vehiculo AND ci.estado NOT IN ('FINALIZADO', 'CANCELADO'))
                            LEFT JOIN servicios s ON ci.id_servicio = s.id_servicio
                            LEFT JOIN usuarios u ON ci.id_tecnico = u.id_usuario
                            WHERE ci.id_taller = '$id_taller_sesion' OR ci.id_taller IS NULL
                            ORDER BY c.id_cliente DESC";
                        
                        $res = $db->ejecutar($sql);
                        while($u = $db->recorrer($res)):
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary bg-opacity-10 text-primary me-3" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                                        <i class="fa fa-user"></i>
                                    </div>
                                    <span class="fw-bold"><?php echo $u['nombre_completo']; ?></span>
                                </div>
                            </td>
                            <td><?php echo $u['dni_ruc']; ?></td>
                            <td><?php echo $u['telefono']; ?></td>
                            <td>
                                <?php if (!empty($u['placa'])): ?>
                                    <div class="d-flex flex-column">
                                        <span class="badge bg-dark text-white mb-1 shadow-sm" style="width: fit-content; font-family: 'Monaco', monospace; border: 1px solid #fff;">
                                            <?php echo strtoupper($u['placa']); ?>
                                        </span>
                                        <small class="text-primary fw-bold text-uppercase" style="font-size: 9px;"><?php echo $u['tipo_vehiculo']; ?></small>
                                        <small class="text-muted text-uppercase" style="font-size: 10px;"><?php echo $u['marca'] . " " . $u['modelo']; ?></small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small italic">Sin vehículo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($u['id_cita'])): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill" onclick='verInfoCita(<?php echo json_encode($u); ?>)' style="cursor: pointer;">
                                        <i class="fa fa-calendar-check me-1"></i> Cita Activa
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted rounded-pill border"><i class="fa fa-clock me-1"></i> Sin Cita</span>
                                <?php endif; ?>
                                <div class="small text-muted mt-1"><?php echo $u['correo']; ?></div>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm <?php echo !empty($u['id_cita']) ? 'btn-light text-muted disabled' : 'btn-light text-info'; ?> rounded-circle shadow-sm me-1" 
                                        <?php echo !empty($u['id_cita']) ? '' : "onclick='abrirAgendarCita(".json_encode($u).")'"; ?>>
                                    <i class="fa fa-calendar-plus"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" onclick='editarCliente(<?php echo json_encode($u); ?>)'>
                                    <i class="fa fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 🚀 INICIALIZACIÓN ÚNICA
    // Sinceramente, como ya cargamos las librerías en el header, aquí solo damos la orden
    if ($.fn.DataTable.isDataTable('#tablaClientes')) {
        $('#tablaClientes').DataTable().destroy();
    }

    $('#tablaClientes').DataTable({
        responsive: true,
        destroy: true,
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        columnDefs: [
            { orderable: false, targets: [5] }
        ]
    });
});

// --- TUS FUNCIONES DE LÓGICA (No se tocan) ---
function nuevoCliente() { $('#tituloModalCliente').text('Nuevo Cliente'); $('#formCliente')[0].reset(); $('#id_cliente').val(''); }

function editarCliente(u) {
    $('#tituloModalCliente').text('Editar Cliente y Vehículo');
    $('#id_cliente').val(u.id_cliente);
    $('#nombre_completo').val(u.nombre_completo);
    $('#dni_ruc').val(u.dni_ruc);
    $('#telefono').val(u.telefono);
    $('#correo').val(u.correo);
    $('#placa').val(u.placa);
    $('#marca').val(u.marca);
    $('#modelo').val(u.modelo);
    $('#anio').val(u.anio);
    $('#color').val(u.color);
    $('#vin').val(u.vin_chasis);
    $('#combustible').val(u.combustible);
    $('#tipo_vehiculo').val(u.tipo_vehiculo);
    $('#modalCliente').modal('show');
}

function verInfoCita(u) {
    $('#info_vehiculo').text(u.marca + ' ' + u.modelo);
    $('#info_placa').text(u.placa);
    $('#info_fecha').text(u.fecha_cita);
    $('#info_hora').text(u.hora_inicio + ' a ' + u.hora_fin);
    $('#info_tecnico').text(u.tecnico_nombre);
    $('#info_servicio').text(u.nombre_servicio);
    $('#modalInfoCita').modal('show');
}

let categoriaVehiculoActual = ""; 
function abrirAgendarCita(u) {
    $('#formReserva')[0].reset();
    $('#btnConfirmar').prop('disabled', true);
    $('#label_hora').text('');
    $('#contenedor_horarios').html('<div class="text-muted small py-5">Seleccione servicio...</div>');
    $('#cita_id_cliente').val(u.id_cliente);
    $('#cita_id_vehiculo').val(u.id_vehiculo);
    $('#txt_placa').text(u.placa);
    $('#txt_vehiculo').text(u.marca + ' ' + u.modelo);
    $('#label_reserva_texto').text('Cliente: ' + u.nombre_completo);
    categoriaVehiculoActual = u.tipo_vehiculo; 
    $('#f_preventivo').prop('checked', true);
    actualizarListaServicios();
    $('#modalAgendarCita').modal('show');
}

window.actualizarListaServicios = function() {
    if (!categoriaVehiculoActual) return;
    let flujo = $('input[name="flujo_atencion"]:checked').val();
    $.post('Poo/obtener_servicios.php', { tipo_vehiculo: categoriaVehiculoActual, especialidad: flujo }, function(data) {
        $('#sel_servicio').html(data).prop('disabled', false);
    });
};

window.cargarEspecialistasYDuracion = function(select) {
    let option = $(select).find('option:selected');
    let especialidad = option.data('especialidad');
    if(!especialidad) return;
    $.post('Poo/obtener_tecnicos.php', { especialidad: especialidad }, function(data) {
        $('#sel_tecnico').html(data);
        if($('#fecha_cita').val()) { consultarHorarios(); }
    });
};

window.seleccionarHora = function(h) {
    let duracion = $('#sel_servicio option:selected').data('duracion') || 1;
    let horas = ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
    let idxI = horas.indexOf(h);
    let idxF = idxI + (duracion - 1);
    if (idxF >= horas.length) { Swal.fire('Atención', 'No hay tiempo suficiente.', 'warning'); return; }
    $('.time-slot').removeClass('selected');
    for (let i = idxI; i <= idxF; i++) { $(`#slot_${horas[i].replace(':','')}`).addClass('selected'); }
    $('#hora_seleccionada').val(h); 
    $('#label_hora').text(`- Reservado: ${h} a ${horas[idxF].split(':')[0]}:59`);
    $('#btnConfirmar').prop('disabled', false);
};

window.consultarHorarios = function() {
    let tecnico = $('#sel_tecnico').val();
    let fecha_raw = $('#fecha_cita').val(); 
    let taller = '<?php echo $id_taller_sesion; ?>';
    if (!tecnico || !fecha_raw) return;
    let fecha_sql = fecha_raw;
    if (fecha_raw.includes('/')) { let p = fecha_raw.split('/'); fecha_sql = p[2] + '-' + p[1] + '-' + p[0]; }
    $.ajax({
        url: 'Poo/consultar_disponibilidad.php',
        method: 'POST',
        data: { id_tecnico: tecnico, fecha: fecha_sql, id_taller: taller },
        success: function(res) {
            try {
                let ocupados = (typeof res === 'object') ? res : JSON.parse(res);
                let horas = ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
                let html = '';
                horas.forEach(h => {
                    let esta_ocupado = ocupados.some(o => o.substring(0,5) === h);
                    let clase = esta_ocupado ? 'disabled' : '';
                    let evento = esta_ocupado ? '' : `onclick="seleccionarHora('${h}')"`;
                    html += `<div class="col-4 mb-2"><div class="time-slot ${clase}" id="slot_${h.replace(':','')}" ${evento}>${h}</div></div>`;
                });
                $('#contenedor_horarios').html(html);
            } catch(e) { console.error("Error horarios", e); }
        }
    });
};
</script>

<?php 
include 'modales/modal_cliente.php'; 
include 'modales/modal_agendar_cita.php'; 
include 'master/footer.php'; 
?>