<?php
// Poo/check_rutas.php

$ruta_a_probar = "../libs/class.php";

echo "<h3>🔍 Inspeccionando Rutas</h3>";
echo "Carpeta actual: " . __DIR__ . "<br>";
echo "Buscando en: " . realpath($ruta_a_probar) . "<br><br>";

if (file_exists($ruta_a_probar)) {
    echo "<b style='color:green;'>✅ ¡ARCHIVO ENCONTRADO!</b> La ruta es correcta.";
} else {
    echo "<b style='color:red;'>❌ ARCHIVO NO ENCONTRADO.</b> Revisa los niveles de carpeta.";
}
?>