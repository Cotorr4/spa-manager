<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Conexión</h2>";

// Test 1: Archivos existen
echo "<h3>1. Verificar archivos:</h3>";
$config = __DIR__ . '/private/database/config.php';
$conexion = __DIR__ . '/private/database/conexion.php';

echo "Config existe: " . (file_exists($config) ? '✅' : '❌') . "<br>";
echo "Conexion existe: " . (file_exists($conexion) ? '✅' : '❌') . "<br>";

// Test 2: Incluir archivos
echo "<h3>2. Incluir archivos:</h3>";
try {
    require_once $config;
    echo "Config cargado: ✅<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
} catch (Exception $e) {
    echo "Error config: ❌ " . $e->getMessage() . "<br>";
}

// Test 3: Conexión PDO
echo "<h3>3. Test de conexión PDO:</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "Conexión PDO: ✅<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "Usuarios en BD: " . $result['total'] . "<br>";
    
} catch (PDOException $e) {
    echo "Error PDO: ❌ " . $e->getMessage() . "<br>";
}

// Test 4: Clase Database
echo "<h3>4. Test clase Database:</h3>";
try {
    require_once $conexion;
    $db = getDB();
    echo "Clase Database: ✅<br>";
    
    $stmt = $db->query("SELECT usuario FROM usuarios LIMIT 1");
    $user = $stmt->fetch();
    echo "Usuario de prueba: " . $user['usuario'] . "<br>";
    
} catch (Exception $e) {
    echo "Error clase: ❌ " . $e->getMessage() . "<br>";
}
