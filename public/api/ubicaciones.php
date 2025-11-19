<?php
ob_start();
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/Ubicacion.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'mensaje' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $modelo = new Ubicacion();
    
    switch($action) {
        case 'listar':
            $ubicaciones = $modelo->listar();
            responderJSON(true, $ubicaciones);
            break;
            
        case 'obtener':
            $id = limpiarInt($_GET['id'] ?? 0);
            $ubicacion = $modelo->obtenerPorId($id);
            if ($ubicacion) {
                responderJSON(true, $ubicacion);
            } else {
                responderJSON(false, null, 'Ubicación no encontrada');
            }
            break;
            
        case 'crear':
            $datos = [
                'nombre' => limpiarString($_POST['nombre'] ?? ''),
                'direccion' => limpiarString($_POST['direccion'] ?? ''),
                'telefono' => limpiarString($_POST['telefono'] ?? ''),
                'email' => limpiarString($_POST['email'] ?? ''),
                'color' => limpiarString($_POST['color'] ?? '#2563eb'),
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];
            
            if (empty($datos['nombre'])) {
                responderJSON(false, null, 'El nombre es requerido');
                break;
            }
            
            $id = $modelo->crear($datos);
            if ($id) {
                responderJSON(true, ['id' => $id], 'Ubicación creada exitosamente');
            } else {
                responderJSON(false, null, 'Error al crear ubicación');
            }
            break;
            
        case 'actualizar':
            $id = limpiarInt($_POST['id'] ?? 0);
            $datos = [
                'nombre' => limpiarString($_POST['nombre'] ?? ''),
                'direccion' => limpiarString($_POST['direccion'] ?? ''),
                'telefono' => limpiarString($_POST['telefono'] ?? ''),
                'email' => limpiarString($_POST['email'] ?? ''),
                'color' => limpiarString($_POST['color'] ?? '#2563eb'),
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];
            
            if (empty($datos['nombre'])) {
                responderJSON(false, null, 'El nombre es requerido');
                break;
            }
            
            $resultado = $modelo->actualizar($id, $datos);
            if ($resultado) {
                responderJSON(true, null, 'Ubicación actualizada exitosamente');
            } else {
                responderJSON(false, null, 'Error al actualizar ubicación');
            }
            break;
            
        case 'eliminar':
            $id = limpiarInt($_POST['id'] ?? 0);
            $resultado = $modelo->eliminar($id);
            if ($resultado) {
                responderJSON(true, null, 'Ubicación eliminada exitosamente');
            } else {
                responderJSON(false, null, 'Error al eliminar ubicación. Puede estar en uso.');
            }
            break;
            
        default:
            responderJSON(false, null, 'Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error del servidor'], JSON_UNESCAPED_UNICODE);
}
