<?php
require_once "Conexion.php";
$db = new Conexion();

// Forzamos la búsqueda de la cita #30 que vemos en tu captura
$id_prueba = 30; 
$sql = "SELECT ci.id_cita, ci.estado, c.nombre_completo 
        FROM citas ci 
        JOIN clientes c ON ci.id_cliente = c.id_cliente 
        WHERE ci.id_cita = $id_prueba";

$res = $db->ejecutar($sql);
$cita = $db->recorrer($res);

if ($cita) {
    echo "✅ CONEXIÓN OK: Encontrada cita #" . $cita['id_cita'] . " de " . $cita['nombre_completo'] . "<br>";
    echo "Estado actual: <b>" . $cita['estado'] . "</b>";
} else {
    echo "❌ ERROR: No se encuentra la cita o falla el JOIN. Revisa los nombres de las tablas.";
}