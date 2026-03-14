<?php
session_start();
require_once "Poo/Conexion.php";
include 'master/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<!-- <style>
    /* 1. Limpieza de bordes y fondos */
    .fc {
        --fc-border-color: #f1f3f5;
        --fc-button-bg-color: #ffffff;
        --fc-button-border-color: #e9ecef;
        --fc-button-text-color: #495057;
        --fc-button-active-bg-color: #0d6efd;
        --fc-button-active-text-color: #ffffff;
        --fc-today-bg-color: #f8f9fa;
    }

    /* 2. Quitar el subrayado azul de los días */
    .fc-col-header-cell-cushion, .fc-daygrid-day-number {
        color: #495057 !important;
        text-decoration: none !important;
        font-weight: 700;
        font-size: 0.9rem;
    }

    /* 3. Eventos estilo "Pastilla Minimalista" */
    .fc-event {
        border: none !important;
        background-color: #fff4e6 !important; /* Fondo naranja muy suave */
        border-left: 4px solid #fd7e14 !important; /* Acento naranja fuerte */
        padding: 5px 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .fc-event-main {
        color: #d9480f !important; /* Texto en tono café/naranja oscuro */
        font-size: 1.5rem;
    }

    /* 4. Suavizar las líneas de tiempo */
    .fc-timegrid-slot {
        height: 3em !important; 
        border-bottom: 1px solid #f8f9fa !important;
    }

    .fc-timegrid-axis-cushion {
        font-size: 0.75rem;
        color: #adb5bd;
    }
</style> -->

<div class="container-fluid px-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark m-0">Calendario de Citas</h4>
        <small class="text-muted">Visualización de horarios por vehículo y técnico</small>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek', // Vista semanal para ver las horas
        locale: 'es',
        slotMinTime: '07:00:00', // Hora de inicio del taller
        slotMaxTime: '19:00:00', // Hora de cierre
        allDaySlot: false,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        events: 'Poo/eventos_calendario.php', // El archivo PHP que creamos arriba
        
        eventClick: function(info) {
            // Mostrar detalles de la cita al hacer clic
            let props = info.event.extendedProps;
            Swal.fire({
                title: 'Detalle de la Cita',
                html: `
                    <div class="text-start small">
                        <p><strong>Vehículo:</strong> ${props.vehiculo}</p>
                        <p><strong>Servicio:</strong> ${props.servicio}</p>
                        <p><strong>Técnico:</strong> ${props.tecnico}</p>
                        <p><strong>Estado:</strong> ${props.estado}</p>
                        <p><strong>Observaciones:</strong> ${props.obs}</p>
                    </div>
                `,
                icon: 'info'
            });
        }
    });
    calendar.render();
});
</script>

<style>
    .fc { font-family: inherit; }
    .fc-event { cursor: pointer; padding: 2px; }
    .fc-v-event { background-color: #0d6efd; border: none; }
</style>

<?php include 'master/footer.php'; ?>