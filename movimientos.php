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

// Procesar el registro de un movimiento (Entrada / Salida)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_movimiento'])) {
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');
    $id_usuario = $_SESSION['usuario_id'];

    if ($id_producto > 0 && in_array($tipo, ['entrada', 'salida']) && $cantidad > 0) {
        try {
            // 1. Obtener el stock actual del producto
            $stmtProd = $pdo->prepare("SELECT stock_actual, nombre FROM productos WHERE id_producto = :id LIMIT 1");
            $stmtProd->execute([':id' => $id_producto]);
            $producto = $stmtProd->fetch();

            if ($producto) {
                // Validación para no permitir stock negativo en salidas
                if ($tipo === 'salida' && $cantidad > $producto['stock_actual']) {
                    $error = "Stock insuficiente para realizar la salida. Stock disponible: {$producto['stock_actual']}";
                } else {
                    // USO DE TRANSACCIÓN: Garantizar integridad de datos
                    $pdo->beginTransaction();

                    // Insertar el registro en la tabla de movimientos
                    $stmtMov = $pdo->prepare("INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario) 
                                             VALUES (:tipo, :cantidad, :observacion, :id_producto, :id_usuario)");
                    $stmtMov->execute([
                        ':tipo' => $tipo,
                        ':cantidad' => $cantidad,
                        ':observacion' => $observacion,
                        ':id_producto' => $id_producto,
                        ':id_usuario' => $id_usuario
                    ]);

                    // Actualizar el stock actual en la tabla productos
                    if ($tipo === 'entrada') {
                        $stmtUpdate = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual + :cantidad WHERE id_producto = :id_producto");
                    } else {
                        $stmtUpdate = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id_producto = :id_producto");
                    }
                    $stmtUpdate->execute([
                        ':cantidad' => $cantidad,
                        ':id_producto' => $id_producto
                    ]);

                    // Confirmar la transacción
                    $pdo->commit();
                    $mensaje = 'Movimiento registrado y stock actualizado correctamente.';
                }
            } else {
                $error = 'El producto seleccionado no existe.';
            }
        } catch (PDOException $e) {
            // Revertir cambios en caso de error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Error al procesar el movimiento: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor complete todos los campos obligatorios con valores válidos.';
    }
}

// Consultar el historial de movimientos ordenados del más reciente al más antiguo
try {
    $query = "SELECT m.*, p.nombre AS producto_nombre, p.codigo AS producto_codigo, u.nombre AS usuario_nombre 
              FROM movimientos m 
              INNER JOIN productos p ON m.id_producto = p.id_producto 
              INNER JOIN usuarios u ON m.id_usuario = u.id_usuario 
              ORDER BY m.id_movimiento DESC";
    $stmt = $pdo->query($query);
    $movimientos = $stmt->fetchAll();
} catch (PDOException $e) {
    $movimientos = [];
    $error = 'Error al consultar la lista de movimientos.';
}

// Consultar lista de productos activos para el selector
try {
    $stmtProds = $pdo->query("SELECT id_producto, codigo, nombre, stock_actual FROM productos WHERE estado = 1 ORDER BY nombre ASC");
    $productosDisponibles = $stmtProds->fetchAll();
} catch (PDOException $e) {
    $productosDisponibles = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Movimientos</title>
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
                        <a class="nav-link" href="productos.php">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">Categorías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="movimientos.php">Movimientos</a>
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
    <div class="container mb-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Historial de Movimientos</h2>
                <p class="text-muted">Registro de entradas y salidas de stock en el sistema.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMovimiento">
                    + Registrar Movimiento
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

        <!-- Tabla de Movimientos -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha / Hora</th>
                                <th>Tipo</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Usuario</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($movimientos) > 0): ?>
                                <?php foreach ($movimientos as $mov): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($mov['fecha_hora'])) ?></td>
                                        <td>
                                            <?php if ($mov['tipo'] === 'entrada'): ?>
                                                <span class="badge bg-success">Entrada</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Salida</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($mov['producto_nombre']) ?></strong><br>
                                            <small class="text-muted"><code><?= htmlspecialchars($mov['producto_codigo']) ?></code></small>
                                        </td>
                                        <td class="fw-bold"><?= $mov['cantidad'] ?></td>
                                        <td><?= htmlspecialchars($mov['usuario_nombre']) ?></td>
                                        <td><?= htmlspecialchars($mov['observacion'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No se han registrado movimientos de inventario.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Movimiento -->
    <div class="modal fade" id="modalMovimiento" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="movimientos.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Movimiento de Inventario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="registrar_movimiento" value="1">

                        <div class="mb-3">
                            <label for="id_producto" class="form-label">Producto</label>
                            <select class="form-select" id="id_producto" name="id_producto" required>
                                <option value="" selected disabled>Seleccione un producto...</option>
                                <?php foreach ($productosDisponibles as $p): ?>
                                    <option value="<?= $p['id_producto'] ?>">
                                        <?= htmlspecialchars($p['nombre']) ?> (Código: <?= $p['codigo'] ?> | Stock: <?= $p['stock_actual'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Movimiento</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="entrada">Entrada (+ Reabastecimiento)</option>
                                <option value="salida">Salida (- Retiro / Venta)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad</label>
                            <input type="number" min="1" class="form-control" id="cantidad" name="cantidad" value="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación / Nota</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="2" placeholder="Ej: Compra a proveedor / Salida por venta"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Movimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>