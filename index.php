<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jambosys Taller | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body { 
            background: linear-gradient(135deg, #343a40 0%, #212529 100%); 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card { 
            width: 100%; 
            max-width: 400px; 
            padding: 30px; 
            border-radius: 20px; 
            border: none;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
        }
        .brand-icon {
            /* font-size: 3rem; */
            /* color: #0d6efd; */
            /* margin-bottom: 15pxs; */
        }
    </style>
</head>
<body>

    <div class="card login-card shadow-lg">
        <div class="card-body text-center">
            <div class="brand-icon">
                <img src="image/logo_taller.png" alt="Logo Dr. Motors" style="width: 200px; height: 150px; object-fit: contain; border-radius: 5px;">
            </div>
            <!-- <h3 class="fw-bold mb-2">JAMBOSYS</h3> -->
            <p class="text-muted mb-4">Gestión de Taller Automotriz</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4 small" role="alert">
                    <i class="fa fa-triangle-exclamation me-2"></i>
                    <div>
                        <?php 
                            if($_GET['error'] == 'pass_incorrecto') echo "La contraseña no coincide.";
                            if($_GET['error'] == 'usuario_no_existe') echo "El usuario no está registrado.";
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <form action="procesar_login.php" method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label small fw-bold">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa fa-user text-muted"></i></span>
                        <input type="text" name="user" class="form-control bg-light border-start-0" required placeholder="Nombre de usuario">
                    </div>
                </div>
                
                <div class="mb-4 text-start">
                    <label class="form-label small fw-bold">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa fa-lock text-muted"></i></span>
                        <input type="password" name="pass" class="form-control bg-light border-start-0" required placeholder="********">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow">
                    INGRESAR AL SISTEMA <i class="fa fa-arrow-right-to-bracket ms-2"></i>
                </button>
            </form>
        </div>
        <div class="card-footer bg-transparent border-0 text-center pb-4">
            <span class="text-muted x-small">&copy; 2026 Jambosystems</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>