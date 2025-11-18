<?php
ob_start();
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/Configuracion.php';
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
    $modelo = new Configuracion();
    
    switch($action) {
        case 'listar_perfiles':
            responderJSON(true, $modelo->listarPerfiles());
            break;
        case 'activar_perfil':
            $id = limpiarInt($_POST['id'] ?? 0);
            $resultado = $modelo->activarPerfil($id);
            responderJSON($resultado, null, $resultado ? 'Perfil activado' : 'Error');
            break;
        case 'guardar_perfil':
            $id = limpiarInt($_POST['id'] ?? 0);
            $datos = [
                'nombre' => limpiarString($_POST['nombre'] ?? ''),
                'titulo_hero' => limpiarString($_POST['titulo_hero'] ?? ''),
                'subtitulo_hero' => limpiarString($_POST['subtitulo_hero'] ?? ''),
                'mensaje_bienvenida' => limpiarString($_POST['mensaje_bienvenida'] ?? ''),
                'estado_operativo' => limpiarString($_POST['estado_operativo'] ?? 'operativo'),
                'mensaje_estado' => limpiarString($_POST['mensaje_estado'] ?? ''),
                'horarios_texto' => limpiarString($_POST['horarios_texto'] ?? ''),
                'telefono_publico' => limpiarString($_POST['telefono_publico'] ?? ''),
                'whatsapp' => limpiarString($_POST['whatsapp'] ?? ''),
                'email_publico' => limpiarString($_POST['email_publico'] ?? ''),
                'direccion_visible' => limpiarString($_POST['direccion_visible'] ?? ''),
                'instagram' => limpiarString($_POST['instagram'] ?? ''),
                'facebook' => limpiarString($_POST['facebook'] ?? '')
            ];
            $resultado = $modelo->guardarPerfil($id, $datos);
            responderJSON($resultado, null, $resultado ? 'Guardado' : 'Error');
            break;
        case 'listar_testimonios':
            responderJSON(true, $modelo->listarTestimonios());
            break;
        case 'guardar_testimonio':
            $datos = [
                'id' => limpiarInt($_POST['id'] ?? 0),
                'nombre_cliente' => limpiarString($_POST['nombre_cliente'] ?? ''),
                'testimonio' => limpiarString($_POST['testimonio'] ?? ''),
                'calificacion' => limpiarInt($_POST['calificacion'] ?? 5),
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];
            $resultado = $modelo->guardarTestimonio($datos);
            responderJSON($resultado, null, $resultado ? 'Guardado' : 'Error');
            break;
        case 'eliminar_testimonio':
            $id = limpiarInt($_POST['id'] ?? 0);
            $resultado = $modelo->eliminarTestimonio($id);
            responderJSON($resultado, null, $resultado ? 'Eliminado' : 'Error');
            break;
        default:
            responderJSON(false, null, 'Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error del servidor'], JSON_UNESCAPED_UNICODE);
}
