<?php
// Capturar output accidental
ob_start();

require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/Ubicacion.php';

// Limpiar buffer
ob_end_clean();

// Headers JSON
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
        case 'por_pais':
            listarPorPais();
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
    $soloActivas = isset($_GET['activas']) && $_GET['activas'] == '1';
    $modelo = new Ubicacion();
    $ubicaciones = $modelo->listar($soloActivas);
    responderJSON(true, $ubicaciones);
}

function obtener() {
    $id = limpiarInt($_GET['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Ubicacion();
    $ubicacion = $modelo->obtenerPorId($id);
    
    if ($ubicacion) {
        responderJSON(true, $ubicacion);
    } else {
        responderJSON(false, null, 'Ubicación no encontrada');
    }
}

function crear() {
    $datos = [
        'nombre' => limpiarString($_POST['nombre'] ?? ''),
        'pais' => limpiarString($_POST['pais'] ?? ''),
        'ciudad' => limpiarString($_POST['ciudad'] ?? ''),
        'direccion' => limpiarString($_POST['direccion'] ?? ''),
        'es_domicilio' => isset($_POST['es_domicilio']) ? 1 : 0,
        'activo' => isset($_POST['activo']) ? 1 : 0
    ];
    
    // Validaciones
    if (empty($datos['nombre'])) {
        responderJSON(false, null, 'El nombre es requerido');
    }
    
    $paisesValidos = ['Peru', 'Chile'];
    if (!in_array($datos['pais'], $paisesValidos)) {
        responderJSON(false, null, 'País no válido');
    }
    
    $modelo = new Ubicacion();
    $id = $modelo->crear($datos);
    
    if ($id) {
        registrarLog("Ubicación creada: {$datos['nombre']} (ID: {$id})");
        responderJSON(true, ['id' => $id], 'Ubicación creada exitosamente');
    } else {
        responderJSON(false, null, 'Error al crear ubicación');
    }
}

function actualizar() {
    $id = limpiarInt($_POST['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $datos = [
        'nombre' => limpiarString($_POST['nombre'] ?? ''),
        'pais' => limpiarString($_POST['pais'] ?? ''),
        'ciudad' => limpiarString($_POST['ciudad'] ?? ''),
        'direccion' => limpiarString($_POST['direccion'] ?? ''),
        'es_domicilio' => isset($_POST['es_domicilio']) ? 1 : 0,
        'activo' => isset($_POST['activo']) ? 1 : 0
    ];
    
    // Validaciones
    if (empty($datos['nombre'])) {
        responderJSON(false, null, 'El nombre es requerido');
    }
    
    $paisesValidos = ['Peru', 'Chile'];
    if (!in_array($datos['pais'], $paisesValidos)) {
        responderJSON(false, null, 'País no válido');
    }
    
    $modelo = new Ubicacion();
    $resultado = $modelo->actualizar($id, $datos);
    
    if ($resultado) {
        registrarLog("Ubicación actualizada: {$datos['nombre']} (ID: {$id})");
        responderJSON(true, null, 'Ubicación actualizada exitosamente');
    } else {
        responderJSON(false, null, 'Error al actualizar ubicación');
    }
}

function cambiarEstado() {
    $id = limpiarInt($_POST['id'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Ubicacion();
    $resultado = $modelo->cambiarEstado($id, $activo);
    
    if ($resultado) {
        $estado = $activo ? 'activada' : 'desactivada';
        registrarLog("Ubicación {$estado} (ID: {$id})");
        responderJSON(true, null, "Ubicación {$estado} exitosamente");
    } else {
        responderJSON(false, null, 'Error al cambiar estado');
    }
}

function eliminar() {
    $id = limpiarInt($_POST['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Ubicacion();
    $resultado = $modelo->eliminar($id);
    
    if ($resultado['success']) {
        registrarLog("Ubicación eliminada (ID: {$id})");
    }
    
    responderJSON($resultado['success'], null, $resultado['mensaje']);
}

function listarPorPais() {
    $pais = limpiarString($_GET['pais'] ?? '');
    
    $paisesValidos = ['Peru', 'Chile'];
    if (!in_array($pais, $paisesValidos)) {
        responderJSON(false, null, 'País no válido');
    }
    
    $modelo = new Ubicacion();
    $ubicaciones = $modelo->listarPorPais($pais);
    responderJSON(true, $ubicaciones);
}
