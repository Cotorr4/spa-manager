<?php
// Capturar output accidental
ob_start();

require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/Reserva.php';

// Limpiar buffer
ob_end_clean();

// Headers JSON
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

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
        case 'marcar_pagado':
            marcarPagado();
            break;
        case 'verificar_disponibilidad':
            verificarDisponibilidad();
            break;
        case 'reservas_hoy':
            reservasHoy();
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
    $filtros = [];
    
    if (!empty($_GET['fecha'])) {
        $filtros['fecha'] = limpiarString($_GET['fecha']);
    }
    
    if (!empty($_GET['fecha_desde'])) {
        $filtros['fecha_desde'] = limpiarString($_GET['fecha_desde']);
    }
    
    if (!empty($_GET['fecha_hasta'])) {
        $filtros['fecha_hasta'] = limpiarString($_GET['fecha_hasta']);
    }
    
    if (!empty($_GET['estado'])) {
        $filtros['estado'] = limpiarString($_GET['estado']);
    }
    
    if (!empty($_GET['cliente_id'])) {
        $filtros['cliente_id'] = limpiarInt($_GET['cliente_id']);
    }
    
    $modelo = new Reserva();
    $reservas = $modelo->listar($filtros);
    responderJSON(true, $reservas);
}

function obtener() {
    $id = limpiarInt($_GET['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $modelo = new Reserva();
    $reserva = $modelo->obtenerPorId($id);
    
    if ($reserva) {
        responderJSON(true, $reserva);
    } else {
        responderJSON(false, null, 'Reserva no encontrada');
    }
}

function crear() {
    $datos = [
        'cliente_id' => limpiarInt($_POST['cliente_id'] ?? 0),
        'tratamiento_id' => limpiarInt($_POST['tratamiento_id'] ?? 0),
        'ubicacion_id' => limpiarInt($_POST['ubicacion_id'] ?? 0),
        'fecha' => limpiarString($_POST['fecha'] ?? ''),
        'hora' => limpiarString($_POST['hora'] ?? ''),
        'comentarios' => limpiarString($_POST['comentarios'] ?? ''),
        'estado' => 'pendiente',
        'pagado' => 0
    ];
    
    // Validaciones
    if (!$datos['cliente_id']) {
        responderJSON(false, null, 'Debe seleccionar un cliente');
    }
    
    if (!$datos['tratamiento_id']) {
        responderJSON(false, null, 'Debe seleccionar un tratamiento');
    }
    
    if (!$datos['ubicacion_id']) {
        responderJSON(false, null, 'Debe seleccionar una ubicación');
    }
    
    if (!esFechaValida($datos['fecha'])) {
        responderJSON(false, null, 'Fecha no válida');
    }
    
    if (!esHoraValida($datos['hora']) || !esHoraBloque30Min($datos['hora'])) {
        responderJSON(false, null, 'La hora debe ser en bloques de 30 minutos (ej: 08:00, 08:30, 09:00)');
    }
    
    $modelo = new Reserva();
    $resultado = $modelo->crear($datos);
    
    if ($resultado['success']) {
        registrarLog("Reserva creada (ID: {$resultado['id']})");
        responderJSON(true, ['id' => $resultado['id']], 'Reserva creada exitosamente');
    } else {
        responderJSON(false, null, $resultado['mensaje']);
    }
}

function actualizar() {
    $id = limpiarInt($_POST['id'] ?? 0);
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $datos = [];
    
    $camposPermitidos = [
        'cliente_id', 'tratamiento_id', 'ubicacion_id',
        'fecha', 'hora', 'comentarios'
    ];
    
    foreach ($camposPermitidos as $campo) {
        if (isset($_POST[$campo])) {
            if (in_array($campo, ['cliente_id', 'tratamiento_id', 'ubicacion_id'])) {
                $datos[$campo] = limpiarInt($_POST[$campo]);
            } else {
                $datos[$campo] = limpiarString($_POST[$campo]);
            }
        }
    }
    
    if (empty($datos)) {
        responderJSON(false, null, 'No hay datos para actualizar');
    }
    
    $modelo = new Reserva();
    $resultado = $modelo->actualizar($id, $datos);
    
    if ($resultado['success']) {
        registrarLog("Reserva actualizada (ID: {$id})");
        responderJSON(true, null, 'Reserva actualizada exitosamente');
    } else {
        responderJSON(false, null, $resultado['mensaje']);
    }
}

function cambiarEstado() {
    $id = limpiarInt($_POST['id'] ?? 0);
    $estado = limpiarString($_POST['estado'] ?? '');
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    $estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'completada'];
    if (!in_array($estado, $estadosValidos)) {
        responderJSON(false, null, 'Estado no válido');
    }
    
    $modelo = new Reserva();
    $resultado = $modelo->cambiarEstado($id, $estado);
    
    if ($resultado['success']) {
        registrarLog("Reserva cambió a estado '{$estado}' (ID: {$id})");
        responderJSON(true, null, "Estado actualizado a: {$estado}");
    } else {
        responderJSON(false, null, 'Error al cambiar estado');
    }
}

function marcarPagado() {
    $id = limpiarInt($_POST['id'] ?? 0);
    $monto = limpiarFloat($_POST['monto'] ?? 0);
    $metodo = limpiarString($_POST['metodo'] ?? '');
    
    if (!$id) {
        responderJSON(false, null, 'ID no válido');
    }
    
    if ($monto <= 0) {
        responderJSON(false, null, 'Monto no válido');
    }
    
    if (empty($metodo)) {
        responderJSON(false, null, 'Método de pago requerido');
    }
    
    $modelo = new Reserva();
    $resultado = $modelo->marcarPagado($id, $monto, $metodo);
    
    if ($resultado['success']) {
        registrarLog("Pago registrado: ${monto} via {$metodo} (Reserva ID: {$id})");
        responderJSON(true, null, 'Pago registrado exitosamente');
    } else {
        responderJSON(false, null, 'Error al registrar pago');
    }
}

function verificarDisponibilidad() {
    $fecha = limpiarString($_GET['fecha'] ?? '');
    $hora = limpiarString($_GET['hora'] ?? '');
    $ubicacion_id = limpiarInt($_GET['ubicacion_id'] ?? 0);
    $excluir_id = limpiarInt($_GET['excluir_id'] ?? 0);
    
    if (!esFechaValida($fecha) || !esHoraValida($hora) || !$ubicacion_id) {
        responderJSON(false, null, 'Datos incompletos');
    }
    
    $modelo = new Reserva();
    $disponible = $modelo->verificarDisponibilidad($fecha, $hora, $ubicacion_id, $excluir_id ?: null);
    
    responderJSON(true, ['disponible' => $disponible]);
}

function reservasHoy() {
    $modelo = new Reserva();
    $reservas = $modelo->reservasHoy();
    responderJSON(true, $reservas);
}
