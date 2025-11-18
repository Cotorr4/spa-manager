<?php
// NO output antes de esta línea
ob_start(); // Capturar cualquier output accidental

require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/Tratamiento.php';

ob_end_clean(); // Limpiar buffer

// Headers después de limpiar
header('Content-Type: application/json; charset=utf-8');

// Proteger API
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'mensaje' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch($action) {
        case 'listar':
            listar();
            break;
        case 'obtener':
            obtener();
            break;
        case 'crear':
            crear();
            break;
        case 'actualizar':
            actualizar();
            break;
        case 'cambiar_estado':
            cambiarEstado();
            break;
        case 'eliminar':
            eliminar();
            break;
        default:
            responderJSON(false, null, 'Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'mensaje' => 'Error del servidor',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function listar() {
    $soloActivos = isset($_GET['activos']) && $_GET['activos'] == '1';
    $modelo = new Tratamiento();
    $tratamientos = $modelo->listar($soloActivos);
    responderJSON(true, $tratamientos);
}

function obtener() {
    $id = limpiarInt($_GET['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Tratamiento();
    $tratamiento = $modelo->obtenerPorId($id);
    
    if ($tratamiento) {
        responderJSON(true, $tratamiento);
    } else {
        responderJSON(false, null, 'Tratamiento no encontrado');
    }
}

function crear() {
    $datos = [
        'nombre' => limpiarString($_POST['nombre'] ?? ''),
        'descripcion' => limpiarString($_POST['descripcion'] ?? ''),
        'duracion' => limpiarInt($_POST['duracion'] ?? 0),
        'precio' => limpiarFloat($_POST['precio'] ?? 0),
        'activo' => isset($_POST['activo']) ? 1 : 0
    ];
    
    if (empty($datos['nombre'])) {
        responderJSON(false, null, 'El nombre es requerido');
    }
    
    if ($datos['duracion'] <= 0) {
        responderJSON(false, null, 'La duración debe ser mayor a 0');
    }
    
    if ($datos['precio'] < 0) {
        responderJSON(false, null, 'El precio no puede ser negativo');
    }
    
    $modelo = new Tratamiento();
    $id = $modelo->crear($datos);
    
    if ($id) {
        registrarLog("Tratamiento creado: {$datos['nombre']} (ID: {$id})");
        responderJSON(true, ['id' => $id], 'Tratamiento creado exitosamente');
    } else {
        responderJSON(false, null, 'Error al crear tratamiento');
    }
}

function actualizar() {
    $id = limpiarInt($_POST['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $datos = [
        'nombre' => limpiarString($_POST['nombre'] ?? ''),
        'descripcion' => limpiarString($_POST['descripcion'] ?? ''),
        'duracion' => limpiarInt($_POST['duracion'] ?? 0),
        'precio' => limpiarFloat($_POST['precio'] ?? 0),
        'activo' => isset($_POST['activo']) ? 1 : 0
    ];
    
    if (empty($datos['nombre'])) {
        responderJSON(false, null, 'El nombre es requerido');
    }
    
    $modelo = new Tratamiento();
    $resultado = $modelo->actualizar($id, $datos);
    
    if ($resultado) {
        registrarLog("Tratamiento actualizado: {$datos['nombre']} (ID: {$id})");
        responderJSON(true, null, 'Tratamiento actualizado exitosamente');
    } else {
        responderJSON(false, null, 'Error al actualizar tratamiento');
    }
}

function cambiarEstado() {
    $id = limpiarInt($_POST['id'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Tratamiento();
    $resultado = $modelo->cambiarEstado($id, $activo);
    
    if ($resultado) {
        $estado = $activo ? 'activado' : 'desactivado';
        registrarLog("Tratamiento {$estado} (ID: {$id})");
        responderJSON(true, null, "Tratamiento {$estado} exitosamente");
    } else {
        responderJSON(false, null, 'Error al cambiar estado');
    }
}

function eliminar() {
    $id = limpiarInt($_POST['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Tratamiento();
    $resultado = $modelo->eliminar($id);
    
    if ($resultado['success']) {
        registrarLog("Tratamiento eliminado (ID: {$id})");
    }
    
    responderJSON($resultado['success'], null, $resultado['mensaje']);
}
