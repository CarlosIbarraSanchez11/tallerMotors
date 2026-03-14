<?php
header('Content-Type: application/json');
require_once "Conexion.php";
$db = new Conexion();

// Verificamos que venga una acción para evitar errores de "Undefined index"
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

try {
    // 1. ACTUALIZAR PRECIO Y HORAS (Lo que ya tenías)
    if ($accion == 'actualizar_base') {
        $id = $_POST['id_servicio'];
        $precio = $_POST['precio'];
        $horas = $_POST['horas'];
        $sql = "UPDATE servicios SET precio_base = '$precio', duracion_horas = '$horas' WHERE id_servicio = '$id'";
        $db->ejecutar($sql);
        echo json_encode(['status' => 'success', 'message' => 'Servicio actualizado correctamente.']);
    }

    // 2. CREAR NUEVO SERVICIO (La pieza que faltaba)
    // 2. CREAR NUEVO SERVICIO
    if ($accion == 'nuevo_servicio') {
        $tipo_raiz    = $_POST['tipo_raiz'];
        $especialidad = $_POST['especialidad'];
        $nivel        = $_POST['nivel'];
        $tecnologia   = $_POST['tecnologia'];
        $categoria    = $_POST['categoria'];
        $precio       = $_POST['precio'];
        $horas        = $_POST['horas'];

        // --- GENERACIÓN DEL NOMBRE SEGÚN TIPO ---
        // Ejemplo Mantenimiento: "Mantenimiento Menor Convencional - AUTO"
        // Ejemplo Diagnóstico: "Diagnóstico Mayor Diesel - CAMIONETA"
        $prefijo = ($tipo_raiz == 'DIAGNOSTICO') ? "Diagnóstico" : "Mantenimiento";
        $nombre_servicio = $prefijo . " " . ucfirst(strtolower($nivel)) . " " . ucfirst(strtolower($tecnologia)) . " - " . $categoria;

        $sql = "INSERT INTO servicios (
                    nombre_servicio, tipo_raiz, nivel, tipo_tecnologia, 
                    categoria_vehiculo, especialidad, duracion_horas, precio_base
                ) VALUES (
                    '$nombre_servicio', '$tipo_raiz', '$nivel', '$tecnologia', 
                    '$categoria', '$especialidad', '$horas', '$precio'
                )";
        
        if($db->ejecutar($sql)) {
            echo json_encode(['status' => 'success', 'message' => "Servicio '$nombre_servicio' creado con éxito."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos.']);
        }
        exit;
    }

    // 3. AGREGAR PASO AL CHECKLIST (Actualizado con Sección y Categoría)
    if ($accion == 'agregar_paso') {
    $id_serv = $_POST['id_servicio'];
    $desc    = $db->conexion->real_escape_string($_POST['descripcion']);
    
    // Si no viene seccion_paso (porque estaba oculto), le ponemos 'DIAGNOSTICO' o 'GENERAL'
    $seccion   = isset($_POST['seccion_paso']) ? $_POST['seccion_paso'] : 'DIAGNOSTICO';
    $categoria = $_POST['categoria_paso'];

        // 1. Calculamos el siguiente orden dentro de ese servicio específico
        // La fórmula es: nuevo_orden = MAX(orden_paso) + 1
        $sql_o = "SELECT IFNULL(MAX(orden_paso), 0) + 1 as nuevo_orden 
                FROM pasos_servicio 
                WHERE id_servicio = '$id_serv'";
        $ord = $db->recorrer($db->ejecutar($sql_o));
        $nuevo_orden = $ord['nuevo_orden'];

        // 2. Insertamos con toda la información técnica
        $sql = "INSERT INTO pasos_servicio 
                (id_servicio, descripcion_paso, orden_paso, seccion_paso, categoria_paso) 
                VALUES 
                ('$id_serv', '$desc', '$nuevo_orden', '$seccion', '$categoria')";
        
        if($db->ejecutar($sql)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al insertar paso']);
        }
        exit;
    }

    // 4. ELIMINAR PASO
    if ($accion == 'eliminar_paso') {
        $id_paso = $_POST['id_paso'];
        $db->ejecutar("DELETE FROM pasos_servicio WHERE id_paso = '$id_paso'");
        echo json_encode(['status' => 'success']);
    }

    // 5. VINCULAR INSUMO AL SERVICIO
    if ($accion == 'vincular_insumo') {
        $id_serv = $_POST['id_servicio'];
        $id_prod = $_POST['id_producto'];
        $cant    = $_POST['cantidad'];

        $sql = "INSERT INTO servicio_productos (id_servicio, id_producto, cantidad_sugerida) 
                VALUES ('$id_serv', '$id_prod', '$cant')";
        
        if($db->ejecutar($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Producto vinculado']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al vincular']);
        }
        exit;
    }

    // 6. ELIMINAR VÍNCULO DE INSUMO
    if ($accion == 'eliminar_insumo') {
        $id_sp = $_POST['id_sp']; // ID de la tabla intermedia
        $db->ejecutar("DELETE FROM servicio_productos WHERE id_servicio_producto = '$id_sp'");
        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}