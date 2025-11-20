<?php
// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🕵️ Diagnóstico de Imágenes</h1>";

// 1. Conectar a BD
require_once __DIR__ . '/../private/database/config_public.php';
echo "<p>✅ Conexión a BD exitosa.</p>";

// 2. Definir rutas
// RUTA FISICA: Donde están los archivos en el disco del servidor
$ruta_fisica_base = realpath(__DIR__ . '/../storage/tratamientos'); 
// RUTA WEB: Como el navegador intenta llegar a ellas
$ruta_web_base = 'storage/tratamientos'; 

echo "<h3>1. Verificación de Rutas</h3>";
echo "<ul>";
echo "<li><strong>Carpeta Pública (Aquí):</strong> " . __DIR__ . "</li>";
echo "<li><strong>Carpeta Storage (Física):</strong> " . ($ruta_fisica_base ? $ruta_fisica_base : "<span style='color:red'>NO ENCONTRADA (Posible error de permisos o no existe)</span>") . "</li>";
echo "</ul>";

// 3. Consultar Fotos
$sql = "SELECT id, tratamiento_id, ruta FROM tratamiento_fotos LIMIT 5";
$result = $conn->query($sql);

echo "<h3>2. Prueba de Imágenes</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Ruta en BD</th><th>Estado Físico (Servidor)</th><th>Prueba Visual (Navegador)</th></tr>";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $nombre_archivo = $row['ruta'];
        $path_completo = $ruta_fisica_base . '/' . $nombre_archivo;
        $url_web = $ruta_web_base . '/' . $nombre_archivo;

        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($nombre_archivo) . "</td>";
        
        // Verificar existencia física
        if (file_exists($path_completo)) {
            echo "<td style='color:green'>✅ Archivo existe en disco<br><small>$path_completo</small></td>";
        } else {
            echo "<td style='color:red'>❌ Archivo NO encontrado en disco<br><small>Buscado en: $path_completo</small></td>";
        }

        // Intentar renderizar
        echo "<td>";
        echo "<img src='$url_web' style='width:100px; border:2px solid blue;' alt='Prueba'>";
        echo "<br><small>SRC: $url_web</small>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4'>No hay fotos en la base de datos.</td></tr>";
}
echo "</table>";
?>
