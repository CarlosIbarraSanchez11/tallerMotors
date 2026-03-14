<?php
// 1. Apuntamos al nuevo archivo de YCloud
require_once "libs/classY.php"; 

// 2. Datos de prueba
$telefono = "921872052"; // Tu número (la función en classY le pondrá el +51)
$nombrePadre = "Carlos Ibarra";
$correo = "carlos@ejemplo.com";

echo "<h2>Iniciando prueba con YCloud (Oficial) 📩</h2>";

// 3. Llamamos directamente a la función (No necesitas 'new' porque no es una clase)
$resultado = enviarTemplateBienvenida($telefono, $nombrePadre, $correo);

if ($resultado) {
    echo "<b style='color:green;'>✅ ¡Éxito! El mensaje debería estar en camino.</b>";
} else {
    echo "<b style='color:red;'>❌ Error en el envío.</b><br>";
    echo "Revisa el archivo <b>log_ycloud.txt</b> en tu servidor para ver el motivo real.";
}

echo "<hr>";
echo "Paso siguiente: Verifica tu WhatsApp en el celular 51921872052.";
?>