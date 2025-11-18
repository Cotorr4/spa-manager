<?php
ob_start();

require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/FichaSalud.php';
require_once __DIR__ . '/../../private/models/Cliente.php';

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
    switch($action) {
        case 'listar':
            listar();
            break;
        case 'obtener':
            obtener();
            break;
        case 'guardar':
            guardar();
            break;
        case 'eliminar':
            eliminar();
            break;
        case 'clientes_sin_ficha':
            clientesSinFicha();
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
    $filtros = [
        'completada' => $_GET['completada'] ?? null,
        'busqueda' => limpiarString($_GET['busqueda'] ?? '')
    ];
    
    $modelo = new FichaSalud();
    $fichas = $modelo->listar($filtros);
    responderJSON(true, $fichas);
}

function obtener() {
    $cliente_id = limpiarInt($_GET['cliente_id'] ?? 0);
    
    if (!$cliente_id) {
        responderJSON(false, null, 'ID de cliente no válido');
    }
    
    $modelo = new FichaSalud();
    $ficha = $modelo->obtenerPorCliente($cliente_id);
    
    if ($ficha) {
        responderJSON(true, $ficha);
    } else {
        // No tiene ficha aún, obtener datos del cliente
        $modeloCliente = new Cliente();
        $cliente = $modeloCliente->obtenerPorId($cliente_id);
        
        if ($cliente) {
            responderJSON(true, [
                'cliente_id' => $cliente['id'],
                'cliente_nombre' => $cliente['nombre'],
                'cliente_codigo' => $cliente['cliente_codigo'],
                'telefono' => $cliente['telefono'],
                'email' => $cliente['email'],
                'fecha_nacimiento' => $cliente['fecha_nacimiento'],
                'ficha_nueva' => true,
                'sexo' => null,
                'tratamiento_recomendado' => null,
                'datos_clinicos' => [],
                'habitos_alimenticios' => [],
                'actividad_fisica' => [],
                'habitos_sueno' => [],
                'completada' => 0
            ]);
        } else {
            responderJSON(false, null, 'Cliente no encontrado');
        }
    }
}

function guardar() {
    $cliente_id = limpiarInt($_POST['cliente_id'] ?? 0);
    
    if (!$cliente_id) {
        responderJSON(false, null, 'ID de cliente no válido');
    }
    
    // Procesar datos clínicos
    $datos_clinicos = [];
    $enfermedades = [
        'diabetes', 'enfermedades_endocrinas', 'hipertension', 'enfermedades_neurologicas',
        'enfermedades_renales', 'enfermedades_hematologicas', 'enfermedades_cardiacas',
        'enfermedades_dermatologicas', 'enfermedades_circulatorias', 'enfermedades_respiratorias',
        'enfermedades_digestivas', 'alergias', 'enfermedades_pulmonares', 'problemas_presion'
    ];
    
    foreach ($enfermedades as $enfermedad) {
        $datos_clinicos[$enfermedad] = [
            'tiene' => isset($_POST['clinica_' . $enfermedad]),
            'especificacion' => limpiarString($_POST['clinica_' . $enfermedad . '_esp'] ?? '')
        ];
    }
    $datos_clinicos['otros'] = limpiarString($_POST['clinica_otros'] ?? '');
    
    // Procesar hábitos alimenticios
    $habitos_alimenticios = [
        'comidas_dia' => limpiarInt($_POST['alim_comidas_dia'] ?? 0),
        'agua_litros' => limpiarFloat($_POST['alim_agua_litros'] ?? 0),
        'verduras_semanal' => limpiarInt($_POST['alim_verduras_semanal'] ?? 0),
        'frutas_semanal' => limpiarInt($_POST['alim_frutas_semanal'] ?? 0),
        'carne_roja_semanal' => limpiarInt($_POST['alim_carne_roja_semanal'] ?? 0),
        'carne_blanca_semanal' => limpiarInt($_POST['alim_carne_blanca_semanal'] ?? 0),
        'legumbres_semanal' => limpiarInt($_POST['alim_legumbres_semanal'] ?? 0),
        'cereales_semanal' => limpiarInt($_POST['alim_cereales_semanal'] ?? 0)
    ];
    
    // Procesar actividad física
    $actividad_fisica = [
        'realiza_ejercicio' => isset($_POST['act_realiza_ejercicio']),
        'tipo_ejercicio' => limpiarString($_POST['act_tipo_ejercicio'] ?? ''),
        'horas_semanales' => limpiarFloat($_POST['act_horas_semanales'] ?? 0)
    ];
    
    // Procesar hábitos de sueño
    $habitos_sueno = [
        'horas_sueno' => limpiarFloat($_POST['sueno_horas'] ?? 0),
        'calidad' => limpiarString($_POST['sueno_calidad'] ?? '')
    ];
    
    $datos = [
        'cliente_id' => $cliente_id,
        'sexo' => limpiarString($_POST['sexo'] ?? ''),
        'tratamiento_recomendado' => limpiarString($_POST['tratamiento_recomendado'] ?? ''),
        'datos_clinicos' => $datos_clinicos,
        'habitos_alimenticios' => $habitos_alimenticios,
        'actividad_fisica' => $actividad_fisica,
        'habitos_sueno' => $habitos_sueno,
        'completada' => isset($_POST['completada']) ? 1 : 0,
        'actualizada_por' => 'admin'
    ];
    
    $modelo = new FichaSalud();
    $resultado = $modelo->guardar($datos);
    
    if ($resultado['success']) {
        registrarLog("Ficha de salud guardada para cliente ID: {$cliente_id}");
        responderJSON(true, $resultado, 'Ficha guardada exitosamente');
    } else {
        responderJSON(false, null, $resultado['mensaje'] ?? 'Error al guardar ficha');
    }
}

function eliminar() {
    $cliente_id = limpiarInt($_POST['cliente_id'] ?? 0);
    
    if (!$cliente_id) {
        responderJSON(false, null, 'ID de cliente no válido');
    }
    
    $modelo = new FichaSalud();
    $resultado = $modelo->eliminar($cliente_id);
    
    if ($resultado['success']) {
        registrarLog("Ficha eliminada para cliente ID: {$cliente_id}");
    }
    
    responderJSON($resultado['success'], null, $resultado['mensaje']);
}

function clientesSinFicha() {
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT c.id, c.nombre, c.cliente_codigo, c.telefono
            FROM clientes c
            LEFT JOIN fichas_salud f ON c.id = f.cliente_id
            WHERE f.id IS NULL AND c.activo = 1
            ORDER BY c.nombre
        ");
        $clientes = $stmt->fetchAll();
        responderJSON(true, $clientes);
    } catch (PDOException $e) {
        responderJSON(false, null, 'Error al obtener clientes');
    }
}
