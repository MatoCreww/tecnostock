<?php
session_start();

// Control de acceso seguro
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$nombreUsuario = $_SESSION['usuario_nombre'];

// Consultas dinámicas para los contadores del Dashboard
try {
    // 1. Contar total de productos
    $stmtProd = $pdo->query("SELECT COUNT(*) FROM productos");
    $totalProductos = $stmtProd->fetchColumn();

    // 2. Contar total de categorías activas
    $stmtCat = $pdo->query("SELECT COUNT(*) FROM categorias WHERE estado = 1");
    $totalCategorias = $stmtCat->fetchColumn();

    // 3. Contar productos con stock crítico (menor o igual al mínimo)
    $stmtBajo = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock_actual <= stock_minimo");
    $stockBajo = $stmtBajo->fetchColumn();
} catch (PDOException $e) {
    $totalProductos = 0;
    $totalCategorias = 0;
    $stockBajo = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Panel Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Barra de Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">TecnoStock</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="productos.php">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">Categorías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movimientos.php">Movimientos</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <span class="text-white">Hola, <strong><?= htmlspecialchars($nombreUsuario) ?></strong></span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0 p-4 mb-4">
                    <h2>Bienvenido a TecnoStock</h2>
                    <p class="text-muted">Sistema modular de gestión de inventario.</p>
                </div>
            </div>
        </div>

        <!-- Tarjetas de resumen dinámicas -->
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white p-3">
                    <h5>Total Productos</h5>
                    <h2 class="fw-bold m-0"><?= $totalProductos ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-success text-white p-3">
                    <h5>Categorías</h5>
                    <h2 class="fw-bold m-0"><?= $totalCategorias ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-warning text-dark p-3">
                    <h5>Stock Bajo</h5>
                    <h2 class="fw-bold m-0"><?= $stockBajo ?></h2>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>