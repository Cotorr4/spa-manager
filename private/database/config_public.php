<?php
// Configuración de Conexión (Sitio Público)
$host = 'mysql-apps';
$user = 'root';
$password = 'bitnergia2025prod';
$database = 'spa_manager';

$conn = new mysqli($host, $user, $password, $database);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    // En producción, idealmente no mostrar errores detallados al público
    die("Error de conexión: Servicio no disponible momentáneamente.");
}
?>
