<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$taller_nombre  = $_SESSION['nombre_taller_sede'] ?? 'Sede Central';
$nivel = $_SESSION['nivel_taller'] ?? '';

$total_pedidos_pendientes = 0;
// Sinceramente, verificamos si existe la conexión para no generar errores
if (isset($db)) {
    $sql_count = "SELECT COUNT(*) as total 
                  FROM pedidos_repuestos 
                  WHERE estado_pedido IN ('SOLICITADO', 'SOLICITADO POR CLIENTE')";
    $res_count = $db->ejecutar($sql_count);
    $reg_count = $db->recorrer($res_count);
    $total_pedidos_pendientes = $reg_count['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. Motors</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
    :root { 
        --sidebar-bg: #005082; 
        --sidebar-active: #00a8e8; 
        --bg-main: #f0f5f9; 
        --card-shadow: 0 10px 30px rgba(0,0,0,0.05);
        --sidebar-width: 260px;
        --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    body { 
        background-color: var(--bg-main); 
        font-family: 'Inter', 'Segoe UI', sans-serif; 
        transition: var(--transition); 
        overflow-x: hidden; 
    }
    
    /* 🚀 SIDEBAR MOTOR */
    #sidebar { 
        width: var(--sidebar-width); 
        height: 100vh; 
        position: fixed; 
        background: var(--sidebar-bg); 
        transition: var(--transition); 
        z-index: 2000; 
        border-right: 1px solid rgba(255,255,255,0.05); 
        display: flex; 
        flex-direction: column;
        box-shadow: 4px 0 15px rgba(0,0,0,0.1);
    }

    .sidebar-header { flex-shrink: 0; padding: 20px; }
    
    /* 🛠️ SCROLL PERSONALIZADO */
    .sidebar-menu { 
        flex-grow: 1; 
        overflow-y: auto; 
        overflow-x: hidden; 
        padding-bottom: 20px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.2) transparent;
    }
    
    .sidebar-menu::-webkit-scrollbar { width: 4px; }
    .sidebar-menu::-webkit-scrollbar-track { background: transparent; }
    .sidebar-menu::-webkit-scrollbar-thumb { 
        background: rgba(255,255,255,0.1); 
        border-radius: 10px; 
    }
    .sidebar-menu:hover::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); }

    .sidebar-footer { flex-shrink: 0; border-top: 1px solid rgba(255,255,255,0.05); }
    
    /* ✨ LINKS Y BOTONES */
    #sidebar .nav-link { 
        color: rgba(255,255,255,0.65); 
        padding: 12px 18px; 
        margin: 4px 15px; 
        border-radius: 12px; 
        font-weight: 500; 
        transition: var(--transition); 
        text-decoration: none; 
        display: flex;
        align-items: center;
        border-left: 3px solid transparent;
    }

    #sidebar .nav-link i { font-size: 1.1rem; width: 25px; transition: var(--transition); }

    /* Estado Activo con Resplandor */
    #sidebar .nav-link.active { 
        background: rgba(0, 168, 232, 0.15); 
        color: var(--sidebar-active); 
        border-left: 3px solid var(--sidebar-active);
        box-shadow: inset 0 0 10px rgba(0,168,232,0.1);
    }

    #sidebar .nav-link.active i { color: var(--sidebar-active); }

    /* Efecto Hover Pro */
    #sidebar .nav-link:hover:not(.active) { 
        color: white; 
        background: rgba(255,255,255,0.08); 
        transform: translateX(5px);
    }
    
    /* 📐 LOGICA DE COLAPSO */
    #content { 
        margin-left: var(--sidebar-width); 
        width: calc(100% - var(--sidebar-width)); 
        transition: var(--transition); 
        min-height: 100vh; 
    }
    
    #content.expanded { margin-left: 85px; width: calc(100% - 85px); }
    #sidebar.collapsed { width: 85px; }
    
    #sidebar.collapsed .nav-text, 
    #sidebar.collapsed .logo-principal,
    #sidebar.collapsed .badge { display: none; }
    
    #sidebar.collapsed .nav-link { margin: 4px 12px; justify-content: center; padding: 12px 0; border-left: none; }
    #sidebar.collapsed .nav-link i { width: auto; margin: 0; font-size: 1.3rem; }

    /* 📱 RESPONSIVE */
    @media (max-width: 768px) {
        #sidebar { left: -260px; }
        #sidebar.mobile-show { left: 0; width: 260px; box-shadow: 10px 0 30px rgba(0,0,0,0.3); }
        #content { margin-left: 0 !important; width: 100% !important; }
    }

    /* 🚨 BADGE PULSANTE */
    .pulse-red { 
        animation: pulse-red-animation 2s infinite; 
        font-size: 0.7rem !important;
        font-weight: 700;
    }
    
    @keyframes pulse-red-animation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
</style>
    
</head>
<body>

<nav id="sidebar">
    <div class="sidebar-header p-4 text-center border-bottom border-white border-opacity-10">
        <div class="mb-2 logo-principal">
            <img src="image/logo_taller.png" alt="Logo" class="img-fluid rounded-3" style="max-height: 60px;">
        </div>
        <div class="text-white-50 text-uppercase small" style="letter-spacing: 1px;">
            <?php echo $nivel ?: 'Taller'; ?>
        </div>
    </div>

    <div class="sidebar-menu py-3">
        <div class="nav flex-column">
            
            <?php if($nivel == 'ADMINISTRADOR'): ?>
                <a href="dashboard.php" class="nav-link"><i class="fa fa-columns me-2"></i> <span class="nav-text">Dashboard</span></a>
            <?php endif; ?>

            <?php if(in_array($nivel, ['ADMINISTRADOR', 'CALL CENTER'])): ?>
                <a href="verificar.php" class="nav-link"><i class="fa fa-qrcode me-2"></i> <span class="nav-text">Escaner QR</span></a>
                <a href="clientes.php" class="nav-link"><i class="fa fa-user-group me-2"></i> <span class="nav-text">Clientes</span></a>
            <?php endif; ?>

            <?php if(in_array($nivel, ['ADMINISTRADOR', 'JEFE'])): ?>
                <a href="citas.php" class="nav-link"><i class="fa fa-calendar-check me-2"></i> <span class="nav-text">Citas</span></a>
            <?php endif; ?>

            <?php if(in_array($nivel, ['ADMINISTRADOR', 'LOGISTICA'])): ?>
                <a href="almacen_pedidos.php" class="nav-link d-flex align-items-center justify-content-between">
                    <span><i class="fa fa-list-check me-2"></i> <span class="nav-text">Pedidos</span></span>
                    <?php if ($total_pedidos_pendientes > 0): ?>
                        <span class="badge bg-danger rounded-pill pulse-red" style="font-size: 10px; padding: 4px 8px;">
                            <?php echo $total_pedidos_pendientes; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="productos.php" class="nav-link"><i class="fa fa-boxes-stacked me-2"></i> <span class="nav-text">Productos</span></a>
                <a href="proveedores.php" class="nav-link"><i class="fa fa-truck-field me-2"></i> <span class="nav-text">Proveedores</span></a>
                <a href="ingresos.php" class="nav-link"><i class="fa fa-truck-ramp-box me-2"></i> <span class="nav-text">Ingresos</span></a>
            <?php endif; ?>

            <?php if(in_array($nivel, ['ADMINISTRADOR', 'GERENTE'])): ?>
                <a href="maestro_servicio.php" class="nav-link"><i class="fa fa-wrench me-2"></i> <span class="nav-text">Servicios</span></a>
                <a href="configuracion_taller.php" class="nav-link"><i class="fa fa-sliders me-2"></i> <span class="nav-text">Gastos Administrativos</span></a>
                <a href="gestor_costos.php" class="nav-link"><i class="fa fa-calculator me-2"></i> <span class="nav-text">Gestor de Costos</span></a>
                <a href="usuarios.php" class="nav-link"><i class="fa fa-users-cog me-2"></i> <span class="nav-text">Usuarios</span></a>
            <?php endif; ?>

        </div>
    </div>

    <div class="sidebar-footer p-3">
        <hr class="mx-2 border-white border-opacity-10 mb-3">
        <a href="logout.php" class="btn btn-danger w-100 d-flex align-items-center justify-content-center py-2 rounded-3 fw-bold shadow-sm border-0">
            <i class="fa fa-power-off me-2"></i> 
            <span class="nav-text">Cerrar Sesión</span>
        </a>
    </div>
</nav>

<div id="content">
    <nav class="navbar navbar-expand-lg py-3 px-4">
        <div class="container-fluid">
            <button type="button" id="btnToggle" class="btn btn-white shadow-sm rounded-3 border">
                <i class="fa fa-bars text-primary"></i>
            </button>
            
            <div class="ms-auto d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block">
                    <p class="mb-0 fw-bold small text-dark">
                        <i class="fa fa-shop text-primary me-1"></i>
                        <?php echo $taller_nombre; ?>
                    </p>
                    <small class="text-muted" style="font-size: 11px;">
                        Usuario: <?php echo $usuario_nombre; ?>
                    </small>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($usuario_nombre); ?>&background=00a8e8&color=fff" 
                     class="rounded-circle border" width="45">
            </div>
        </div>
    </nav>

    <script>
        $(document).ready(function() {
            // 🚀 1. GESTOR DE ALERTAS (CORREGIDO)
            var urlParams = new URLSearchParams(window.location.search);
            var r = urlParams.get('res'); // Usamos 'r' para no chocar con nada
            var m = urlParams.get('msg');

            if (r) {
                // Limpia la URL inmediatamente
                window.history.replaceState({}, document.title, window.location.pathname);

                if (r === 'ok') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Ingreso Exitoso!',
                        text: 'Los datos se guardaron correctamente.',
                        confirmButtonColor: '#005082',
                        timer: 3000
                    });
                } 
                else if (r === 'edit') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualización Exitosa!',
                        text: 'Los datos se editaron correctamente.',
                        confirmButtonColor: '#005082',
                        timer: 3000
                    });
                }
                else if (r === 'cita_enviada') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Cita Agendada!', 
                        html: `
                            <div class="text-center">
                                <p>Cita registrada e invitación de WhatsApp enviada.</p>
                                <div class="p-2 mb-2 bg-warning bg-opacity-10 text-warning rounded border border-warning border-opacity-25" style="font-size: 0.85rem;">
                                    <i class="fa fa-clock me-1"></i> Estado: <b>Pendiente</b>
                                </div>
                                <i class="fab fa-whatsapp text-success fa-2x mt-2"></i>
                            </div>
                        `,
                        confirmButtonColor: '#005082'
                    });
                } 
                else if (r === 'error' || r === 'cita_error') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atención',
                        text: m ? decodeURIComponent(m) : 'No se pudo completar la operación.',
                        confirmButtonColor: '#d33'
                    });
                }
            }

            // 🚀 2. MARCAR MENÚ ACTIVO
            var activePage = window.location.pathname.split("/").pop();
            $('.nav-link').each(function() {
                if ($(this).attr('href') === activePage) {
                    $(this).addClass('active');
                }
            });
        });
    </script>