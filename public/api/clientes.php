<?php
// Capturar output accidental
ob_start();

require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/Cliente.php';

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
        case 'buscar_telefono':
            buscarPorTelefono();
            break;
        case 'crear':
            crear();
            break;
        case 'actualizar':
            actualizar();
            break;
        case 'eliminar':
            eliminar();
            break;
        case 'historial':
            obtenerHistorial();
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
    $busqueda = limpiarString($_GET['busqueda'] ?? '');
    $orden = limpiarString($_GET['orden'] ?? 'nombre');
    
    $modelo = new Cliente();
    $clientes = $modelo->listar($orden, $busqueda);
    responderJSON(true, $clientes);
}

function obtener() {
    $id = limpiarInt($_GET['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Cliente();
    $cliente = $modelo->obtenerPorId($id);
    
    if ($cliente) {
        responderJSON(true, $cliente);
    } else {
        responderJSON(false, null, 'Cliente no encontrado');
    }
}

function buscarPorTelefono() {
    $telefono = limpiarTelefono($_GET['telefono'] ?? '');
    
    if (empty($telefono)) {
        responderJSON(false, null, 'Teléfono no válido');
    }
    
    $modelo = new Cliente();
    $cliente = $modelo->obtenerPorTelefono($telefono);
    
    if ($cliente) {
        responderJSON(true, $cliente);
    } else {
        responderJSON(false, null, 'Cliente no encontrado');
    }
}

function crear() {
    $datos = [
        'nombre' => limpiarString($_POST['nombre'] ?? ''),
        'telefono' => limpiarTelefono($_POST['telefono'] ?? ''),
        'email' => limpiarEmail($_POST['email'] ?? ''),
        'direccion' => limpiarString($_POST['direccion'] ?? ''),
        'notas' => limpiarString($_POST['notas'] ?? '')
    ];
    
    // Validaciones
    if (empty($datos['nombre'])) {
        responderJSON(false, null, 'El nombre es requerido');
    }
    
    if (empty($datos['telefono'])) {
        responderJSON(false, null, 'El teléfono es requerido');
    }
    
    if (!empty($datos['email']) && !esEmailValido($datos['email'])) {
        responderJSON(false, null, 'El email no es válido');
    }
    
    $modelo = new Cliente();
    $resultado = $modelo->crear($datos);
    
    if ($resultado['success']) {
        registrarLog("Cliente creado: {$datos['nombre']} (UID: {$resultado['uid']})");
        responderJSON(true, [
            'id' => $resultado['id'],
            'uid' => $resultado['uid']
        ], 'Cliente creado exitosamente');
    } else {
        responderJSON(false, null, $resultado['mensaje']);
    }
}

function actualizar() {
    $id = limpiarInt($_POST['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $datos = [
        'nombre' => limpiarString($_POST['nombre'] ?? ''),
        'telefono' => limpiarTelefono($_POST['telefono'] ?? ''),
        'email' => limpiarEmail($_POST['email'] ?? ''),
        'direccion' => limpiarString($_POST['direccion'] ?? ''),
        'notas' => limpiarString($_POST['notas'] ?? '')
    ];
    
    // Validaciones
    if (empty($datos['nombre'])) {
        responderJSON(false, null, 'El nombre es requerido');
    }
    
    if (empty($datos['telefono'])) {
        responderJSON(false, null, 'El teléfono es requerido');
    }
    
    if (!empty($datos['email']) && !esEmailValido($datos['email'])) {
        responderJSON(false, null, 'El email no es válido');
    }
    
    $modelo = new Cliente();
    $resultado = $modelo->actualizar($id, $datos);
    
    if ($resultado['success']) {
        registrarLog("Cliente actualizado: {$datos['nombre']} (ID: {$id})");
        responderJSON(true, null, 'Cliente actualizado exitosamente');
    } else {
        responderJSON(false, null, $resultado['mensaje']);
    }
}

function eliminar() {
    $id = limpiarInt($_POST['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Cliente();
    $resultado = $modelo->eliminar($id);
    
    if ($resultado['success']) {
        registrarLog("Cliente eliminado (ID: {$id})");
    }
    
    responderJSON($resultado['success'], null, $resultado['mensaje']);
}

function obtenerHistorial() {
    $id = limpiarInt($_GET['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Cliente();
    $historial = $modelo->obtenerHistorialReservas($id);
    responderJSON(true, $historial);
}
