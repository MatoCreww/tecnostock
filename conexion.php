<?php
// conexion.php
$host = 'localhost';
$baseDatos = 'tecnostock';
$usuario = 'root';
$clave = ''; // En XAMPP por defecto no tiene contraseña

$dsn = "mysql:host=$host;dbname=$baseDatos;charset=utf8mb4";

$opciones = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $usuario, $clave, $opciones);
} catch (PDOException $error) {
    exit('Error crítico: No fue posible conectar con la base de datos.');
}
?>