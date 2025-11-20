<?php
ob_start();
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/BitacoraTratamiento.php';
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
    $modelo = new BitacoraTratamiento();
    
    switch($action) {
        case 'listar':
            $cliente_id = limpiarInt($_GET['cliente_id'] ?? 0);
            $entradas = $modelo->listarPorCliente($cliente_id);
            responderJSON(true, $entradas);
            break;
            
        case 'obtener':
            $id = limpiarInt($_GET['id'] ?? 0);
            $entrada = $modelo->obtenerPorId($id);
            if ($entrada) {
                responderJSON(true, $entrada);
            } else {
                responderJSON(false, null, 'Entrada no encontrada');
            }
            break;
            
        case 'crear':
            $datos = [
                'cliente_id' => limpiarInt($_POST['cliente_id'] ?? 0),
                'reserva_id' => !empty($_POST['reserva_id']) ? limpiarInt($_POST['reserva_id']) : null,
                'tratamiento_id' => limpiarInt($_POST['tratamiento_id'] ?? 0),
                'fecha' => limpiarString($_POST['fecha'] ?? date('Y-m-d')),
                'notas' => limpiarString($_POST['notas'] ?? ''),
                'fotos' => []
            ];
            
            if (empty($datos['cliente_id']) || empty($datos['tratamiento_id'])) {
                responderJSON(false, null, 'Datos incompletos');
                break;
            }
            
            $id = $modelo->crear($datos);
            if ($id) {
                responderJSON(true, ['id' => $id], 'Entrada creada exitosamente');
            } else {
                responderJSON(false, null, 'Error al crear entrada');
            }
            break;
            
        case 'actualizar':
            error_log('POST recibido: ' . print_r($_POST, true));
            error_log('POST recibido: ' . print_r($_POST, true));
            $id = limpiarInt($_POST['id'] ?? 0);
            $datos = [
                'tratamiento_id' => limpiarInt($_POST['tratamiento_id'] ?? 0),
                'fecha' => limpiarString($_POST['fecha'] ?? ''),
                'notas' => limpiarString($_POST['notas'] ?? ''),
                'fotos' => json_decode($_POST['fotos'] ?? '[]', true)
            ];
            
            file_put_contents('/tmp/debug_datos.txt', print_r($datos, true));
            $resultado = $modelo->actualizar($id, $datos);
            if ($resultado) {
                responderJSON(true, ['debug_notas' => $datos['notas'], 'debug_post_notas' => $_POST['notas'] ?? 'NO EXISTE'], 'Entrada actualizada');
            } else {
                responderJSON(false, null, 'Error al actualizar entrada');
            }
            break;
            
        case 'eliminar':
            $id = limpiarInt($_POST['id'] ?? 0);
            $resultado = $modelo->eliminar($id);
            if ($resultado) {
                responderJSON(true, null, 'Entrada eliminada exitosamente');
            } else {
                responderJSON(false, null, 'Error al eliminar entrada');
            }
            break;
            
        case 'subir_foto':
            $id = limpiarInt($_POST['id'] ?? 0);
            
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                responderJSON(false, null, 'No se recibió la foto o hubo un error');
                break;
            }
            
            $entrada = $modelo->obtenerPorId($id);
            if (!$entrada) {
                responderJSON(false, null, 'Entrada no encontrada');
                break;
            }
            
            // Validar tipo de archivo
            $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!in_array($_FILES['foto']['type'], $tiposPermitidos)) {
                responderJSON(false, null, 'Tipo de archivo no permitido');
                break;
            }
            
            // Crear directorio si no existe
            $dirCliente = __DIR__ . '/../uploads/bitacora/cliente_' . $entrada['cliente_id'];
            if (!file_exists($dirCliente)) {
                mkdir($dirCliente, 0755, true);
            }
            
            // Generar nombre único
            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'entrada_' . $id . '_' . time() . '_' . uniqid() . '.' . $extension;
            $rutaCompleta = $dirCliente . '/' . $nombreArchivo;
            $rutaRelativa = 'uploads/bitacora/cliente_' . $entrada['cliente_id'] . '/' . $nombreArchivo;
            
            // Mover archivo
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaCompleta)) {
                // Agregar a BD
                if ($modelo->agregarFoto($id, $rutaRelativa)) {
                    responderJSON(true, ['ruta' => $rutaRelativa], 'Foto subida exitosamente');
                } else {
                    unlink($rutaCompleta);
                    responderJSON(false, null, 'Error al registrar foto en BD');
                }
            } else {
                responderJSON(false, null, 'Error al guardar archivo');
            }
            break;
            
        case 'eliminar_foto':
            $id = limpiarInt($_POST['id'] ?? 0);
            $rutaFoto = limpiarString($_POST['ruta_foto'] ?? '');
            
            $resultado = $modelo->eliminarFoto($id, $rutaFoto);
            if ($resultado) {
                responderJSON(true, null, 'Foto eliminada exitosamente');
            } else {
                responderJSON(false, null, 'Error al eliminar foto');
            }
            break;
            
        case 'crear_desde_reserva':
            $reserva_id = limpiarInt($_POST['reserva_id'] ?? 0);
            $id = $modelo->crearDesdeReserva($reserva_id);
            if ($id) {
                responderJSON(true, ['id' => $id], 'Entrada creada desde reserva');
            } else {
                responderJSON(false, null, 'Error al crear entrada desde reserva');
            }
            break;
            
        default:
            responderJSON(false, null, 'Acción no válida');
    }
} catch (Exception $e) {
    registrarLog("Error en API bitacora: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error del servidor'], JSON_UNESCAPED_UNICODE);
}
