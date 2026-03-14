<?php
session_start(); // Paso 1: Abrir sesión
session_unset(); // Paso 2: Vaciar todas las variables
session_destroy(); // Paso 3: Eliminar la sesión del servidor

// Paso 4: Redirigir al login
header("Location: index.php");
exit();
?>