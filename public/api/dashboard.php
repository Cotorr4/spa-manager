<?php
ob_start();
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/database/conexion.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'mensaje' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $db = getDB();
    
    if ($action === 'visitas') {
        $stmt = $db->query("
            SELECT fecha, SUM(visitas) as visitas, SUM(visitantes_unicos) as visitantes_unicos
            FROM visitas_sitio
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY fecha
            ORDER BY fecha ASC
        ");
        $datos = $stmt->fetchAll();
        responderJSON(true, $datos);
    } else {
        responderJSON(false, null, 'Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error del servidor'], JSON_UNESCAPED_UNICODE);
}
