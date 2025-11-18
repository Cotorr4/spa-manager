<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test API Tratamientos</h2>";

// Simular sesión
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_nombre'] = 'Test';
$_SESSION['ultimo_acceso'] = time();

// Test incluir archivos
require_once __DIR__ . '/private/database/config.php';
require_once __DIR__ . '/private/helpers/session.php';
require_once __DIR__ . '/private/helpers/sanitizar.php';
require_once __DIR__ . '/private/helpers/utils.php';
require_once __DIR__ . '/private/models/Tratamiento.php';

echo "✅ Archivos cargados correctamente<br><br>";

// Test listar
$modelo = new Tratamiento();
$tratamientos = $modelo->listar();

echo "✅ Tratamientos encontrados: " . count($tratamientos) . "<br><br>";

// Test crear
$datos = [
    'nombre' => 'Test Tratamiento',
    'descripcion' => 'Test',
    'duracion' => 30,
    'precio' => 50.00,
    'activo' => 1
];

$id = $modelo->crear($datos);

if ($id) {
    echo "✅ Tratamiento creado con ID: {$id}<br>";
} else {
    echo "❌ Error al crear tratamiento<br>";
}
