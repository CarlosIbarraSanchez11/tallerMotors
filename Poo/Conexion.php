<?php
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lineas = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (strpos(trim($linea), '#') === 0) continue; // Ignorar comentarios
        list($nombre, $valor) = explode('=', $linea, 2);
        putenv(trim($nombre) . "=" . trim($valor));
    }
}

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_USER',    getenv('DB_USER')    ?: 'root'); 
define('DB_PASS',    getenv('DB_PASS')    ?: ''); 
define('DB_NAME',    getenv('DB_NAME')    ?: 'jambosys_taller');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

class Conexion {
    public $conexion;

    public function __construct() {
        $this->conectar();
    }

    private function conectar() {
        $host = DB_HOST;
        $socket = getenv('DB_SOCKET'); // Agregaremos esta variable en Cloud Run

        if ($socket) {
            // Sinceramente, esta es la forma "Pro" de conectar en Google Cloud
            // El host se pasa como null y el socket en el último parámetro
            $this->conexion = mysqli_connect(null, DB_USER, DB_PASS, DB_NAME, null, $socket);
        } else {
            // Esta es la conexión normal por IP (la que usas en local)
            $this->conexion = mysqli_connect($host, DB_USER, DB_PASS, DB_NAME);
        }

        if (mysqli_connect_errno()) {
            die("Error de conexión: " . mysqli_connect_error());
        }

        mysqli_set_charset($this->conexion, DB_CHARSET);
    }

    public function ejecutar($sql) {
        $query = mysqli_query($this->conexion, $sql);
        
        if (!$query) {
            echo "<div style='background:#f8d7da; color:#721c24; padding:20px; border-radius:10px; font-family:sans-serif;'>";
            echo "<h4>❌ Error en la Base de Datos</h4>";
            echo "<b>Mensaje:</b> " . mysqli_error($this->conexion) . "<br>";
            echo "<b>Consulta:</b> <pre>" . $sql . "</pre>";
            echo "</div>";
            exit; 
        }
        return $query;
    }

    public function recorrer($resultado) {
        return mysqli_fetch_array($resultado);
    }

    public function contar($resultado) {
        return mysqli_num_rows($resultado);
    }

    public function ultimoId() {
        return mysqli_insert_id($this->conexion);
    }
}
?>