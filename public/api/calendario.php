<?php
ob_start();
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
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

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = getDB();

try {
    switch($action) {
        case 'obtener_dia':
            $fecha = limpiarString($_GET['fecha'] ?? date('Y-m-d'));
            $ubicacionId = limpiarInt($_GET['ubicacion_id'] ?? 1);
            
            // Generar todos los slots del día
            $slots = [];
            $horaInicio = 8;
            $horaFin = 18;
            
            for ($h = $horaInicio; $h < $horaFin; $h++) {
                foreach ([0, 30] as $minutos) {
                    $hora = sprintf('%02d:%02d:00', $h, $minutos);
                    $slots[] = [
                        'hora' => substr($hora, 0, 5),
                        'ubicacion_id' => null, 
                        'ocupado' => false,
                        'reserva_info' => null
                    ];
                }
            }
            
            // Obtener slots habilitados
            $stmt = $db->prepare("SELECT hora, ubicacion_id FROM disponibilidad WHERE fecha = ?");
            $stmt->execute([$fecha]);
            $habilitados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $habilitadosMap = [];
            foreach ($habilitados as $h) {
                $habilitadosMap[substr($h['hora'], 0, 5)] = $h['ubicacion_id'];
            }
            
            // Obtener reservas con información del cliente
            $stmt = $db->prepare("
                SELECT r.hora, c.nombre as cliente_nombre, c.cliente_codigo, t.nombre as tratamiento_nombre
                FROM reservas r
                JOIN clientes c ON r.cliente_id = c.id
                JOIN tratamientos t ON r.tratamiento_id = t.id
                WHERE r.fecha = ? AND r.estado != 'cancelada'
            ");
            $stmt->execute([$fecha]);
            $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $reservasMap = [];
            foreach ($reservas as $res) {
                $reservasMap[substr($res['hora'], 0, 5)] = $res;
            }
            
            // Marcar slots
            foreach ($slots as &$slot) {
                $horaKey = $slot['hora'];
                
                if (isset($habilitadosMap[$horaKey])) {
                    $slot['ubicacion_id'] = $habilitadosMap[$horaKey];
                }
                
                if (isset($reservasMap[$horaKey])) {
                    $slot['ocupado'] = true;
                    $slot['reserva_info'] = $reservasMap[$horaKey];
                }
            }
            
            responderJSON(true, ['fecha' => $fecha, 'slots' => $slots]);
            break;
            
        case 'toggle_slot':
            $fecha = limpiarString($_POST['fecha'] ?? '');
            $hora = limpiarString($_POST['hora'] ?? '');
            $ubicacionId = limpiarInt($_POST['ubicacion_id'] ?? 0);
            
            // Verificar que no haya reserva
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM reservas WHERE fecha = ? AND hora = ? AND estado != 'cancelada'");
            $stmt->execute([$fecha, $hora . ':00']);
            if ($stmt->fetch()['total'] > 0) {
                responderJSON(false, null, 'No se puede modificar: hay una reserva en este horario');
                break;
            }
            
            if ($ubicacionId == 0) {
                // Deshabilitar
                $stmt = $db->prepare("DELETE FROM disponibilidad WHERE fecha = ? AND hora = ?");
                $stmt->execute([$fecha, $hora . ':00']);
            } else {
                // Habilitar o cambiar ubicación
                $stmt = $db->prepare("
                    INSERT INTO disponibilidad (fecha, hora, ubicacion_id) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE ubicacion_id = ?
                ");
                $stmt->execute([$fecha, $hora . ':00', $ubicacionId, $ubicacionId]);
            }
            
            responderJSON(true, null, 'Slot actualizado');
            break;
            
        case 'obtener_semana':
            $fecha = limpiarString($_GET['fecha'] ?? date('Y-m-d'));
            $fechaObj = new DateTime($fecha);
            
            // Obtener inicio de semana (domingo)
            $diaSemana = $fechaObj->format('w');
            $fechaObj->modify('-' . $diaSemana . ' days');
            $inicio = $fechaObj->format('Y-m-d');
            
            $dias = [];
            for ($i = 0; $i < 7; $i++) {
                $fechaDia = $fechaObj->format('Y-m-d');
                
                // Obtener slots del día con reservas
                $stmt = $db->prepare("
                    SELECT d.hora, d.ubicacion_id, 
                           CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as tiene_reserva
                    FROM disponibilidad d
                    LEFT JOIN reservas r ON d.fecha = r.fecha AND d.hora = r.hora AND r.estado != 'cancelada'
                    WHERE d.fecha = ?
                ");
                $stmt->execute([$fechaDia]);
                $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $dias[] = ['fecha' => $fechaDia, 'slots' => $slots];
                $fechaObj->modify('+1 day');
            }
            
            $fin = $fechaObj->modify('-1 day')->format('Y-m-d');
            
            responderJSON(true, ['inicio' => $inicio, 'fin' => $fin, 'dias' => $dias]);
            break;
            
        case 'habilitar_semana':
            $fecha = limpiarString($_POST['fecha'] ?? date('Y-m-d'));
            $ubicacionId = limpiarInt($_POST['ubicacion_id'] ?? 1);
            
            $fechaObj = new DateTime($fecha);
            $diaSemana = $fechaObj->format('w');
            $fechaObj->modify('-' . $diaSemana . ' days');
            
            $db->beginTransaction();
            
            try {
                for ($i = 1; $i <= 5; $i++) { // Lun-Vie
                    $fechaObj->modify('+1 day');
                    $fechaDia = $fechaObj->format('Y-m-d');
                    
                    for ($h = 8; $h < 18; $h++) {
                        foreach ([0, 30] as $minutos) {
                            $hora = sprintf('%02d:%02d:00', $h, $minutos);
                            
                            $stmt = $db->prepare("
                                INSERT INTO disponibilidad (fecha, hora, ubicacion_id) 
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE ubicacion_id = ?
                            ");
                            $stmt->execute([$fechaDia, $hora, $ubicacionId, $ubicacionId]);
                        }
                    }
                }
                
                $db->commit();
                responderJSON(true, null, 'Semana habilitada');
            } catch (Exception $e) {
                $db->rollBack();
                responderJSON(false, null, 'Error al habilitar semana');
            }
            break;
            
        case 'obtener_mes':
            $anio = limpiarInt($_GET['anio'] ?? date('Y'));
            $mes = limpiarInt($_GET['mes'] ?? date('n'));
            
            $primerDia = new DateTime("$anio-$mes-01");
            $ultimoDia = new DateTime($primerDia->format('Y-m-t'));
            
            // Ajustar para empezar en domingo
            $inicioCal = clone $primerDia;
            $diaSemana = $inicioCal->format('w');
            if ($diaSemana > 0) {
                $inicioCal->modify('-' . $diaSemana . ' days');
            }
            
            // Ajustar para terminar en sábado
            $finCal = clone $ultimoDia;
            $diaSemana = $finCal->format('w');
            if ($diaSemana < 6) {
                $finCal->modify('+' . (6 - $diaSemana) . ' days');
            }
            
            $dias = [];
            $fechaActual = clone $inicioCal;
            
            while ($fechaActual <= $finCal) {
                $fechaStr = $fechaActual->format('Y-m-d');
                
                // Contar slots habilitados
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM disponibilidad WHERE fecha = ?");
                $stmt->execute([$fechaStr]);
                $slots = $stmt->fetch()['total'];
                
                // Contar reservas
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM reservas WHERE fecha = ? AND estado != 'cancelada'");
                $stmt->execute([$fechaStr]);
                $reservas = $stmt->fetch()['total'];
                
                $dias[] = [
                    'fecha' => $fechaStr,
                    'numero' => $fechaActual->format('j'),
                    'es_otro_mes' => $fechaActual->format('n') != $mes,
                    'slots_habilitados' => $slots,
                    'reservas' => $reservas
                ];
                
                $fechaActual->modify('+1 day');
            }
            
            responderJSON(true, ['anio' => $anio, 'mes' => $mes, 'dias' => $dias]);
            break;
            
        default:
            responderJSON(false, null, 'Acción no válida');
    }
} catch (Exception $e) {
    registrarLog("Error en calendario API: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error del servidor: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
