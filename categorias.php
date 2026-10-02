<?php
session_start();

// Control de acceso seguro
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$mensaje = '';
$error = '';

// Procesar el registro de nueva categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_categoria'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (!empty($nombre)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)");
            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion
            ]);
            $mensaje = 'Categoría registrada con éxito.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Nombre duplicado (UNIQUE)
                $error = 'Ya existe una categoría con ese nombre.';
            } else {
                $error = 'Error al registrar la categoría: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'El nombre de la categoría es obligatorio.';
    }
}

// Consultar categorías ordenadas de la más reciente a la más antigua
try {
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY id_categoria DESC");
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
    $error = 'Error al obtener la lista de categorías.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Categorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">TecnoStock</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="productos.php#">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="categorias.php">Categorías</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white">Hola, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong></span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido -->
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Gestión de Categorías</h2>
                <p class="text-muted">Administra las clasificaciones para los productos del inventario.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                    + Nueva Categoría
                </button>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabla de Categorías -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categorias) > 0): ?>
                                <?php foreach ($categorias as $cat): ?>
                                    <tr>
                                        <td><?= $cat['id_categoria'] ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($cat['nombre']) ?></td>
                                        <td><?= htmlspecialchars($cat['descripcion'] ?? 'Sin descripción') ?></td>
                                        <td>
                                            <?php if ($cat['estado'] == 1): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No hay categorías registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registro -->
    <div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="categorias.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Categoría</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-content-body p-3">
                        <input type="hidden" name="crear_categoria" value="1">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Categoría</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Ej: Hardware">
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Descripción breve..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Categoría</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>