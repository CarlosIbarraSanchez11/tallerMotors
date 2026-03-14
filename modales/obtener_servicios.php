<?php
// Activamos reporte de errores para ver qué pasa si vuelve a fallar
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "Conexion.php";
$db = new Conexion();

// if (isset($_POST['tipo_vehiculo'])) {
//     $tipo = mysqli_real_escape_string($db->conexion, $_POST['tipo_vehiculo']);

//     // Ajustamos la consulta según tu imagen: eliminamos "estado" que no existe
//     $sql = "SELECT id_servicio, nombre_servicio, duracion_horas, especialidad 
//             FROM servicios 
//             WHERE categoria_vehiculo = '$tipo' OR categoria_vehiculo = 'TODOS' 
//             ORDER BY nombre_servicio ASC";
    
//     $res = $db->ejecutar($sql);
    
//     // Verificación de error en la consulta
//     if (!$res) {
//         die("Error en la consulta: " . mysqli_error($db->conexion));
//     }

//     if ($db->conexion->affected_rows > 0) {
//         echo '<option value="" disabled selected>Seleccione servicio para '.$tipo.'...</option>';
//         while ($s = $db->recorrer($res)) {
//             echo "<option value='".$s['id_servicio']."' 
//                           data-duracion='".$s['duracion_horas']."' 
//                           data-especialidad='".$s['especialidad']."'>
//                     ".$s['nombre_servicio']." (".$s['duracion_horas']."h)
//                   </option>";
//         }
//     } else {
//         echo '<option value="">Sin servicios para la categoría: '.$tipo.'</option>';
//     }
// } else {
//     echo '<option value="">Error: Falta el tipo de vehículo</option>';
// }
if (isset($_POST['tipo_vehiculo'])) {
    $tipo = mysqli_real_escape_string($db->conexion, $_POST['tipo_vehiculo']);
    $especialidad = mysqli_real_escape_string($db->conexion, $_POST['especialidad']); // Recibimos el flujo

    $sql = "SELECT id_servicio, nombre_servicio, duracion_horas, especialidad 
            FROM servicios 
            WHERE (categoria_vehiculo = '$tipo' OR categoria_vehiculo = 'MULTIMARCA' OR categoria_vehiculo = 'TODOS') 
            AND especialidad = '$especialidad' 
            ORDER BY nombre_servicio ASC";
    
    $res = $db->ejecutar($sql);
    
    if ($db->conexion->affected_rows > 0) {
        echo '<option value="" disabled selected>Seleccione '.strtolower($especialidad).' para '.$tipo.'...</option>';
        while ($s = $db->recorrer($res)) {
            echo "<option value='".$s['id_servicio']."' 
                          data-duracion='".$s['duracion_horas']."' 
                          data-especialidad='".$s['especialidad']."'>
                    ".$s['nombre_servicio']." (".$s['duracion_horas']."h)
                  </option>";
        }
    } else {
        echo '<option value="">Sin servicios de '.$especialidad.' para '.$tipo.'</option>';
    }
}

?>