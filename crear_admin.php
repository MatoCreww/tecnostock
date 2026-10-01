<?php
require_once 'conexion.php';

$nombre = 'Administrador TecnoStock';
$correo = 'admin@tecnostock.com';
$clavePlana = 'admin123'; // Contraseña de prueba para ingresar
$claveHash = password_hash($clavePlana, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, clave) VALUES (:nombre, :correo, :clave)");
    $stmt->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':clave'  => $claveHash
    ]);
    echo "<h3>¡Usuario Administrador creado exitosamente!</h3>";
    echo "<p><strong>Correo:</strong> admin@tecnostock.com</p>";
    echo "<p><strong>Contraseña:</strong> admin123</p>";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Error de correo duplicado (UNIQUE)
        echo "<p>El usuario administrador ya existe en la base de datos.</p>";
    } else {
        echo "<p>Error al crear usuario: " . $e->getMessage() . "</p>";
    }
}
?>