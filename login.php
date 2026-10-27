<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave = trim($_POST['clave'] ?? '');

    if (!empty($correo) && !empty($clave)) {
        try {
            $stmt = $pdo->prepare("SELECT id_usuario, nombre, clave, estado FROM usuarios WHERE correo = :correo LIMIT 1");
            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch();

            if ($usuario && $usuario['estado'] == 1) {
                if ($clave === $usuario['clave'] || password_verify($clave, $usuario['clave'])) {
                    $_SESSION['usuario_id'] = $usuario['id_usuario'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];

                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'La contraseña ingresada es incorrecta.';
                }
            } else {
                $error = 'El correo no existe o el usuario se encuentra inactivo.';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión con la base de datos: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor ingresa tanto el correo como la contraseña.';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Iniciar Sesión</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4 fw-bold text-primary">TecnoStock</h3>
                        <p class="text-center text-muted mb-4">Ingresa tus credenciales para acceder</p>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show text-center py-2" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-link form-control" id="correo" name="correo" placeholder="admin@tecnostock.com" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="clave" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="clave" name="clave" placeholder="••••••••" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-2 py-2">Ingresar al Sistema</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>