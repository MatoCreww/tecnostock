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

// 1. REGISTRAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $stock_actual = intval($_POST['stock_actual'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $id_categoria = intval($_POST['id_categoria'] ?? 0);

    if (!empty($codigo) && !empty($nombre) && $id_categoria > 0 && $precio >= 0 && $stock_actual >= 0 && $stock_minimo >= 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) 
                                   VALUES (:codigo, :nombre, :descripcion, :precio, :stock_actual, :stock_minimo, :id_categoria)");
            $stmt->execute([
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':precio' => $precio,
                ':stock_actual' => $stock_actual,
                ':stock_minimo' => $stock_minimo,
                ':id_categoria' => $id_categoria
            ]);
            $mensaje = 'Producto registrado con éxito.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Ya existe un producto con ese código asignado.';
            } else {
                $error = 'Error al registrar el producto: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Por favor complete los campos obligatorios. Los valores numéricos no pueden ser negativos.';
    }
}

// 2. EDITAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto'])) {
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $id_categoria = intval($_POST['id_categoria'] ?? 0);

    if ($id_producto > 0 && !empty($nombre) && $id_categoria > 0 && $precio >= 0 && $stock_minimo >= 0) {
        try {
            $stmt = $pdo->prepare("UPDATE productos 
                                   SET nombre = :nombre, descripcion = :descripcion, precio = :precio, stock_minimo = :stock_minimo, id_categoria = :id_categoria 
                                   WHERE id_producto = :id_producto");
            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':precio' => $precio,
                ':stock_minimo' => $stock_minimo,
                ':id_categoria' => $id_categoria,
                ':id_producto' => $id_producto
            ]);
            $mensaje = 'Producto actualizado correctamente.';
        } catch (PDOException $e) {
            $error = 'Error al actualizar el producto: ' . $e->getMessage();
        }
    } else {
        $error = 'Datos inválidos para la actualización del producto.';
    }
}

// 3. CAMBIAR ESTADO (DESACTIVAR / ACTIVAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $nuevo_estado = intval($_POST['nuevo_estado'] ?? 1);

    if ($id_producto > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE productos SET estado = :estado WHERE id_producto = :id_producto");
            $stmt->execute([
                ':estado' => $nuevo_estado,
                ':id_producto' => $id_producto
            ]);
            $estadoTexto = ($nuevo_estado === 1) ? 'activado' : 'desactivado';
            $mensaje = "El producto ha sido {$estadoTexto} correctamente.";
        } catch (PDOException $e) {
            $error = 'Error al cambiar el estado del producto: ' . $e->getMessage();
        }
    }
}

// 4. CONSULTA Y BÚSQUEDA
$busqueda = trim($_GET['buscar'] ?? '');

try {
    if (!empty($busqueda)) {
        $query = "SELECT p.*, c.nombre AS categoria_nombre 
                  FROM productos p 
                  INNER JOIN categorias c ON p.id_categoria = c.id_categoria 
                  WHERE p.nombre LIKE :b1 OR p.codigo LIKE :b2 OR c.nombre LIKE :b3
                  ORDER BY p.id_producto DESC";
        $stmt = $pdo->prepare($query);
        
        $term = "%{$busqueda}%";
        $stmt->execute([
            ':b1' => $term,
            ':b2' => $term,
            ':b3' => $term
        ]);
    } else {
        $query = "SELECT p.*, c.nombre AS categoria_nombre 
                  FROM productos p 
                  INNER JOIN categorias c ON p.id_categoria = c.id_categoria 
                  ORDER BY p.id_producto DESC";
        $stmt = $pdo->query($query);
    }
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos = [];
    $error = 'Error al consultar la lista de productos: ' . $e->getMessage();
}

// 5. OBTENER CATEGORÍAS ACTIVAS
try {
    $stmtCat = $pdo->query("SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre ASC");
    $categoriasDisponibles = $stmtCat->fetchAll();
} catch (PDOException $e) {
    $categoriasDisponibles = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Productos</title>
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="productos.php">Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="categorias.php">Categorías</a></li>
                    <li class="nav-item"><a class="nav-link" href="movimientos.php">Movimientos</a></li>
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
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h2>Catálogo de Productos</h2>
                <p class="text-muted mb-0">Gestión completa de existencias e información de productos.</p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto">
                    + Nuevo Producto
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

        <!-- Formulario de Búsqueda -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form action="productos.php" method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar por código, nombre o categoría..." value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-secondary w-100">Buscar</button>
                        <?php if (!empty($busqueda)): ?>
                            <a href="productos.php" class="btn btn-outline-secondary">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Productos -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock Actual</th>
                                <th>Stock Mín.</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($productos) > 0): ?>
                                <?php foreach ($productos as $prod): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($prod['codigo']) ?></code></td>
                                        <td class="fw-bold"><?= htmlspecialchars($prod['nombre']) ?></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($prod['categoria_nombre']) ?></span></td>
                                        <td>$<?= number_format($prod['precio'], 2) ?></td>
                                        <td>
                                            <?php if ($prod['stock_actual'] <= $prod['stock_minimo']): ?>
                                                <span class="badge bg-danger"><?= $prod['stock_actual'] ?> (Bajo)</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?= $prod['stock_actual'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $prod['stock_minimo'] ?></td>
                                        <td>
                                            <?php if ($prod['estado'] == 1): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- Botón Editar -->
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-editar" 
                                                    data-id="<?= $prod['id_producto'] ?>"
                                                    data-nombre="<?= htmlspecialchars($prod['nombre']) ?>"
                                                    data-descripcion="<?= htmlspecialchars($prod['descripcion'] ?? '') ?>"
                                                    data-precio="<?= $prod['precio'] ?>"
                                                    data-stock_minimo="<?= $prod['stock_minimo'] ?>"
                                                    data-categoria="<?= $prod['id_categoria'] ?>"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditarProducto">
                                                Editar
                                            </button>

                                            <!-- Botón Desactivar / Activar -->
                                            <form action="productos.php" method="POST" class="d-inline">
                                                <input type="hidden" name="cambiar_estado" value="1">
                                                <input type="hidden" name="id_producto" value="<?= $prod['id_producto'] ?>">
                                                <input type="hidden" name="nuevo_estado" value="<?= $prod['estado'] == 1 ? 0 : 1 ?>">
                                                <?php if ($prod['estado'] == 1): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Desea desactivar este producto?');">Desactivar</button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Activar</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No se encontraron productos en el sistema.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Producto -->
    <div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="productos.php" method="POST" onsubmit="return validarFormulario(this);">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Nuevo Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="crear_producto" value="1">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Código Único</label>
                                <input type="text" class="form-control" name="codigo" required placeholder="Ej: PROD-001">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nombre del Producto</label>
                                <input type="text" class="form-control" name="nombre" required placeholder="Ej: Teclado Mecánico RGB">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" name="id_categoria" required>
                                    <option value="" selected disabled>Seleccione una categoría...</option>
                                    <?php foreach ($categoriasDisponibles as $cat): ?>
                                        <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Precio ($)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="precio" required placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stock Inicial</label>
                                <input type="number" min="0" class="form-control" name="stock_actual" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stock Mínimo Alerta</label>
                                <input type="number" min="0" class="form-control" name="stock_minimo" value="5" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="productos.php" method="POST" onsubmit="return validarFormulario(this);">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="editar_producto" value="1">
                        <input type="hidden" name="id_producto" id="edit_id">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nombre del Producto</label>
                                <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" id="edit_categoria" name="id_categoria" required>
                                    <?php foreach ($categoriasDisponibles as $cat): ?>
                                        <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Precio ($)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="edit_precio" name="precio" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stock Mínimo Alerta</label>
                                <input type="number" min="0" class="form-control" id="edit_stock_minimo" name="stock_minimo" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cargar datos en el Modal de Edición dinámicamente
        document.querySelectorAll('.btn-editar').forEach(boton => {
            boton.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_nombre').value = this.dataset.nombre;
                document.getElementById('edit_descripcion').value = this.dataset.descripcion;
                document.getElementById('edit_precio').value = this.dataset.precio;
                document.getElementById('edit_stock_minimo').value = this.dataset.stock_minimo;
                document.getElementById('edit_categoria').value = this.dataset.categoria;
            });
        });

        // Validaciones JavaScript del lado del cliente
        function validarFormulario(form) {
            const precio = parseFloat(form.precio.value);
            const stockMin = parseInt(form.stock_minimo ? form.stock_minimo.value : 0);

            if (precio < 0 || stockMin < 0) {
                alert('Los valores de precio y stock no pueden ser negativos.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>