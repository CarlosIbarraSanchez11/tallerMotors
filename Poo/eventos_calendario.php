<?php
require_once "Conexion.php";
$db = new Conexion();

// Consulta que une la tabla citas con las demás para obtener nombres y placas
$sql = "SELECT 
            c.id_cita, 
            c.fecha_cita, 
            c.hora_inicio, 
            c.hora_fin, 
            c.estado,
            c.observaciones,
            v.placa, 
            v.marca, 
            v.modelo,
            u.nombre AS tecnico,
            s.nombre_servicio
        FROM citas c
        JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
        JOIN usuarios u ON c.id_tecnico = u.id_usuario
        JOIN servicios s ON c.id_servicio = s.id_servicio
        WHERE c.id_taller = 1"; // Ajustar si el ID de taller es variable

$res = $db->ejecutar($sql);
$eventos = [];

while($row = $db->recorrer($res)) {
    // Definir color según el estado
    $color = ($row['estado'] == 'PENDIENTE') ? '#f39c12' : '#27ae60';

    $eventos[] = [
        'id'    => $row['id_cita'],
        'title' => $row['placa'] . " - " . $row['tecnico'], // Título que se ve en el cuadro
        'start' => $row['fecha_cita'] . 'T' . $row['hora_inicio'],
        'end'   => $row['fecha_cita'] . 'T' . $row['hora_fin'],
        'backgroundColor' => $color,
        'borderColor'     => $color,
        // Datos adicionales para mostrar al hacer clic
        'extendedProps' => [
            'vehiculo' => $row['marca'] . " " . $row['modelo'] . " [" . $row['placa'] . "]",
            'servicio' => $row['nombre_servicio'],
            'tecnico'  => $row['tecnico'],
            'obs'      => $row['observaciones'] ?? 'Sin observaciones',
            'estado'   => $row['estado']
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($eventos);