<?php
ob_start();

require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/Calendario.php';
require_once __DIR__ . '/../../private/models/Reserva.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'mensaje' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch($action) {
        case 'habilitar_mes':
            habilitarMes();
            break;
        case 'toggle_slot':
            toggleSlot();
            break;
        case 'obtener_dia':
            obtenerDia();
            break;
        case 'obtener_semana':
            obtenerSemana();
            break;
        case 'obtener_mes':
            obtenerMes();
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

function habilitarMes() {
    $anio = limpiarInt($_POST['anio'] ?? 0);
    $mes = limpiarInt($_POST['mes'] ?? 0);
    $ubicacion_id = limpiarInt($_POST['ubicacion_id'] ?? 0);
    
    if (!$anio || !$mes || !$ubicacion_id) {
        responderJSON(false, null, 'Datos incompletos');
    }
    
    $modelo = new Calendario();
    $resultado = $modelo->habilitarMesCompleto($anio, $mes, $ubicacion_id);
    
    if ($resultado) {
        registrarLog("Mes habilitado: {$anio}-{$mes} - Ubicación {$ubicacion_id}");
        responderJSON(true, null, 'Mes completo habilitado');
    } else {
        responderJSON(false, null, 'Error al habilitar mes');
    }
}

function toggleSlot() {
    $fecha = limpiarString($_POST['fecha'] ?? '');
    $hora = limpiarString($_POST['hora'] ?? '');
    $ubicacion_id = limpiarInt($_POST['ubicacion_id'] ?? 0);
    $habilitar = isset($_POST['habilitar']) ? 1 : 0;
    
    if (!esFechaValida($fecha) || !esHoraValida($hora) || !$ubicacion_id) {
        responderJSON(false, null, 'Datos incompletos');
    }
    
    $horaCompleta = substr($hora, 0, 5) . ':00';
    
    $modelo = new Calendario();
    $resultado = $modelo->toggleSlot($fecha, $horaCompleta, $ubicacion_id, $habilitar);
    
    if ($resultado) {
        $accion = $habilitar ? 'habilitado' : 'deshabilitado';
        registrarLog("Slot {$accion}: {$fecha} {$hora}");
        responderJSON(true, null, "Slot {$accion}");
    } else {
        responderJSON(false, null, 'Error al actualizar slot');
    }
}

function obtenerDia() {
    $fecha = limpiarString($_GET['fecha'] ?? '');
    $ubicacion_id = limpiarInt($_GET['ubicacion_id'] ?? 1);
    
    if (!esFechaValida($fecha)) {
        responderJSON(false, null, 'Fecha no válida');
    }
    
    $modelo = new Calendario();
    $slots = $modelo->obtenerSlotsDia($fecha, $ubicacion_id);
    
    $modeloReserva = new Reserva();
    $reservas = $modeloReserva->listar(['fecha' => $fecha, 'ubicacion_id' => $ubicacion_id]);
    
    responderJSON(true, [
        'fecha' => $fecha,
        'slots' => $slots,
        'reservas' => $reservas
    ]);
}

function obtenerSemana() {
    $fecha = limpiarString($_GET['fecha'] ?? date('Y-m-d'));
    
    $timestamp = strtotime($fecha);
    $diaSemana = date('N', $timestamp);
    $inicioSemana = date('Y-m-d', strtotime("-" . ($diaSemana - 1) . " days", $timestamp));
    $finSemana = date('Y-m-d', strtotime("+" . (7 - $diaSemana) . " days", $timestamp));
    
    $modelo = new Calendario();
    $slots = $modelo->obtenerSlotsRango($inicioSemana, $finSemana);
    
    $modeloReserva = new Reserva();
    $reservas = $modeloReserva->listar([
        'fecha_desde' => $inicioSemana,
        'fecha_hasta' => $finSemana
    ]);
    
    responderJSON(true, [
        'inicio_semana' => $inicioSemana,
        'fin_semana' => $finSemana,
        'slots' => $slots,
        'reservas' => $reservas
    ]);
}

function obtenerMes() {
    $anio = limpiarInt($_GET['anio'] ?? date('Y'));
    $mes = limpiarInt($_GET['mes'] ?? date('m'));
    
    $inicioMes = sprintf('%04d-%02d-01', $anio, $mes);
    $finMes = date('Y-m-t', strtotime($inicioMes));
    
    $modelo = new Calendario();
    $slots = $modelo->obtenerSlotsRango($inicioMes, $finMes);
    
    $modeloReserva = new Reserva();
    $reservas = $modeloReserva->listar([
        'fecha_desde' => $inicioMes,
        'fecha_hasta' => $finMes
    ]);
    
    responderJSON(true, [
        'anio' => $anio,
        'mes' => $mes,
        'inicio_mes' => $inicioMes,
        'fin_mes' => $finMes,
        'slots' => $slots,
        'reservas' => $reservas
    ]);
}
