<?php
require_once __DIR__ . '/../../private/database/config_public.php';
header('Content-Type: application/json');

// --- CONFIGURACIÓN TELEGRAM ---
define('TELEGRAM_BOT_TOKEN', '8187690136:AAGIiHHOAbKKiooYvIG59ddpxe-YMYvL-Cw');
define('TELEGRAM_CHAT_ID', '-4829248104');

$action = $_GET['action'] ?? null;
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

switch ($action) {
    case 'get_disponibilidad':
        getDisponibilidad($conn, $_GET['fecha']);
        break;
    case 'crear_reserva':
        crearReserva($conn, $data);
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

// --- FUNCIONES ---

function getDisponibilidad($conn, $fecha) {
    // 1. Slots disponibles
    $sql = "
        SELECT c.hora, c.ubicacion_id, u.nombre as ubicacion_nombre, u.es_domicilio
        FROM calendario_slots c
        JOIN ubicaciones u ON c.ubicacion_id = u.id
        WHERE c.fecha = ? AND c.disponible = 1
        ORDER BY c.hora ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $fecha);
    $stmt->execute();
    $res_slots = $stmt->get_result();
    
    $slots_disponibles = [];
    while ($row = $res_slots->fetch_assoc()) {
        $hora_simple = substr($row['hora'], 0, 5);
        $slots_disponibles[$hora_simple] = [
            'hora' => $hora_simple,
            'ubicacion_id' => $row['ubicacion_id'],
            'ubicacion' => $row['ubicacion_nombre'],
            'es_domicilio' => $row['es_domicilio']
        ];
    }

    // 2. Reservas ocupadas
    $sql_reservas = "SELECT hora FROM reservas WHERE fecha = ? AND estado != 'cancelada'";
    $stmt2 = $conn->prepare($sql_reservas);
    $stmt2->bind_param('s', $fecha);
    $stmt2->execute();
    $res_reservas = $stmt2->get_result();
    
    $horas_ocupadas = [];
    while ($row = $res_reservas->fetch_assoc()) {
        $horas_ocupadas[] = substr($row['hora'], 0, 5);
    }

    // 3. Filtrar
    $resultado_final = [];
    foreach ($slots_disponibles as $hora => $info) {
        if (!in_array($hora, $horas_ocupadas)) {
            $resultado_final[] = $info;
        }
    }
    echo json_encode(['success' => true, 'slots' => $resultado_final]);
}

function crearReserva($conn, $data) {
    $nombre = $conn->real_escape_string($data['nombre'] ?? '');
    $telefono = $conn->real_escape_string($data['telefono'] ?? '');
    $fecha = $conn->real_escape_string($data['fecha'] ?? '');
    $hora = $conn->real_escape_string($data['hora'] ?? '');
    $tratamiento_nombre = $conn->real_escape_string($data['tratamiento_nombre'] ?? 'General');
    $ubicacion_id = intval($data['ubicacion_id'] ?? 0);

    if (!$nombre || !$telefono || !$fecha || !$hora || !$ubicacion_id) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        return;
    }

    // 1. Buscar o Crear Cliente
    $cliente_id = 0;
    $cliente_codigo = '';
    
    $check_cli = $conn->query("SELECT id, cliente_codigo FROM clientes WHERE telefono = '$telefono' LIMIT 1");
    
    if ($check_cli->num_rows > 0) {
        // Cliente EXISTE
        $row = $check_cli->fetch_assoc();
        $cliente_id = $row['id'];
        $cliente_codigo = $row['cliente_codigo'];
        
        $conn->query("UPDATE clientes SET nombre = '$nombre' WHERE id = $cliente_id");
    } else {
        // Cliente NUEVO
        // Generar Código CXXX consecutivo
        $res_last = $conn->query("SELECT cliente_codigo FROM clientes WHERE cliente_codigo LIKE 'C%' AND LENGTH(cliente_codigo) = 4 ORDER BY id DESC LIMIT 1");
        
        $nuevo_num = 1; 
        if ($res_last && $res_last->num_rows > 0) {
            $last_code = $res_last->fetch_assoc()['cliente_codigo'];
            $num_part = intval(substr($last_code, 1));
            if ($num_part > 0) $nuevo_num = $num_part + 1;
        }
        
        // C005
        $cliente_codigo = 'C' . str_pad($nuevo_num, 3, '0', STR_PAD_LEFT);
        
        // CORRECCIÓN AQUI: Usamos rand(1000,9999) para que el total sea < 20 caracteres
        // 'CLI-' (4) + Ymd (8) + '-' (1) + 4 digitos = 17 caracteres. Perfecto.
        $uid = 'CLI-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $sql_cli = "INSERT INTO clientes (cliente_uid, cliente_codigo, nombre, telefono) VALUES ('$uid', '$cliente_codigo', '$nombre', '$telefono')";
        if ($conn->query($sql_cli)) {
            $cliente_id = $conn->insert_id;
        } else {
            // Si falla por duplicado de UID (muy raro), reintentamos una vez
            $uid = 'CLI-' . date('Ymd') . '-' . rand(1000, 9999);
            $sql_cli = "INSERT INTO clientes (cliente_uid, cliente_codigo, nombre, telefono) VALUES ('$uid', '$cliente_codigo', '$nombre', '$telefono')";
             if ($conn->query($sql_cli)) {
                $cliente_id = $conn->insert_id;
             } else {
                echo json_encode(['success' => false, 'message' => 'Error al registrar cliente: ' . $conn->error]);
                return;
             }
        }
    }

    // 2. Buscar ID tratamiento
    $tratamiento_id = 1; 
    $res_trat = $conn->query("SELECT id FROM tratamientos WHERE nombre = '$tratamiento_nombre' LIMIT 1");
    if($res_trat->num_rows > 0) $tratamiento_id = $res_trat->fetch_assoc()['id'];
    
    // 3. Insertar Reserva
    $sql_res = "INSERT INTO reservas (cliente_id, tratamiento_id, ubicacion_id, fecha, hora, estado) 
                VALUES ($cliente_id, $tratamiento_id, $ubicacion_id, '$fecha', '$hora', 'pendiente')";

    if ($conn->query($sql_res)) {
        // Obtener nombre ubicación
        $nom_ubi = "Sede Principal";
        $res_ubi = $conn->query("SELECT nombre FROM ubicaciones WHERE id=$ubicacion_id");
        if($res_ubi->num_rows > 0) $nom_ubi = $res_ubi->fetch_assoc()['nombre'];

        // 4. Telegram
        $mensaje = "💆‍♀️ <b>NUEVA RESERVA</b>\n";
        $mensaje .= "📅 " . date('d/m', strtotime($fecha)) . " a las <b>" . $hora . "</b>\n";
        $mensaje .= "📍 " . $nom_ubi . "\n";
        $mensaje .= "👤 " . $nombre . " (Cod: " . $cliente_codigo . ")\n";
        $mensaje .= "✨ " . $tratamiento_nombre;

        enviarTelegram($mensaje);
        enviarTelegram("📱 " . $telefono);

        echo json_encode([
            'success' => true,
            'cliente_id' => $cliente_id,
            'cliente_codigo' => $cliente_codigo
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error DB: ' . $conn->error]);
    }
}

function enviarTelegram($msg) {
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    $params = ['chat_id' => TELEGRAM_CHAT_ID, 'text' => $msg, 'parse_mode' => 'HTML'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}
?>
