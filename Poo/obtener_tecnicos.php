<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();

// 1. Obtenemos el taller de la sesión
$id_taller = $_SESSION['id_taller'] ?? 1; 

// 2. SQL: Buscamos usuarios activos que empiecen con "MECANICO" 
// Sin importar si son de preventivo, diagnóstico o si tienen la especialidad en NULL.
$sql = "SELECT id_usuario, nombre, nivel 
        FROM usuarios 
        WHERE id_taller = '$id_taller' 
        AND estado = 1 
        AND nivel LIKE 'MECANICO%' 
        ORDER BY nombre ASC";

$res = $db->ejecutar($sql);

echo '<option value="">Seleccione al mecánico responsable...</option>';

if ($db->contar($res) > 0) {
    while ($u = $db->recorrer($res)) {
        // Mostramos el nombre y su nivel específico para que sepa quién es quién
        echo "<option value='".$u['id_usuario']."'>".$u['nombre']." (".$u['nivel'].")</option>";
    }
} else {
    echo '<option value="">No hay mecánicos registrados en este taller</option>';
}
?>