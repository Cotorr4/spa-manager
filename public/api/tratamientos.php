<?php
ob_start();

require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/Tratamiento.php';

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
        case 'subir_foto':
            subirFoto();
            break;
        case 'eliminar_foto':
            eliminarFoto();
            break;
        case 'reordenar_fotos':
            reordenarFotos();
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
        'subtitulo' => limpiarString($_POST['subtitulo'] ?? ''),
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
        'subtitulo' => limpiarString($_POST['subtitulo'] ?? ''),
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


function subirFoto() {
    $tratamiento_id = limpiarInt($_POST['tratamiento_id'] ?? 0);
    
    if (!$tratamiento_id) {
        responderJSON(false, null, 'ID de tratamiento no válido');
    }
    
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        responderJSON(false, null, 'Error al subir archivo');
    }
    
    $foto = $_FILES['foto'];
    
    // Validar tipo
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $foto['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $tiposPermitidos)) {
        responderJSON(false, null, 'Solo se permiten imágenes JPG, PNG o WEBP');
    }
    
    // Validar tamaño (2MB)
    if ($foto['size'] > 2 * 1024 * 1024) {
        responderJSON(false, null, 'La imagen no debe superar 2MB');
    }
    
    // Generar nombre único
    $extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
    $nombreArchivo = 'tratamiento_' . $tratamiento_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $rutaDestino = __DIR__ . '/../../storage/tratamientos/' . $nombreArchivo;
    
    // Mover archivo
    if (!move_uploaded_file($foto['tmp_name'], $rutaDestino)) {
        responderJSON(false, null, 'Error al guardar imagen');
    }
    
    // Guardar en BD
    $modelo = new Tratamiento();
    $resultado = $modelo->agregarFoto($tratamiento_id, $nombreArchivo);
    
    if ($resultado['success']) {
        registrarLog("Foto agregada al tratamiento ID: {$tratamiento_id}");
        responderJSON(true, [
            'foto_id' => $resultado['id'],
            'ruta' => $nombreArchivo
        ], 'Foto subida exitosamente');
    } else {
        @unlink($rutaDestino);
        responderJSON(false, null, $resultado['mensaje']);
    }
}
function eliminarFoto() {
    $foto_id = limpiarInt($_POST['foto_id'] ?? 0);
    
    if (!$foto_id) {
        responderJSON(false, null, 'ID de foto no válido');
    }
    
    $modelo = new Tratamiento();
    $resultado = $modelo->eliminarFoto($foto_id);
    
    if ($resultado) {
        registrarLog("Foto eliminada (ID: {$foto_id})");
        responderJSON(true, null, 'Foto eliminada');
    } else {
        responderJSON(false, null, 'Error al eliminar foto');
    }
}

function reordenarFotos() {
    $tratamiento_id = limpiarInt($_POST['tratamiento_id'] ?? 0);
    $orden = json_decode($_POST['orden'] ?? '[]', true);
    
    if (!$tratamiento_id || !is_array($orden)) {
        responderJSON(false, null, 'Datos no válidos');
    }
    
    $modelo = new Tratamiento();
    $resultado = $modelo->reordenarFotos($tratamiento_id, $orden);
    
    if ($resultado) {
        registrarLog("Fotos reordenadas (Tratamiento ID: {$tratamiento_id})");
        responderJSON(true, null, 'Fotos reordenadas');
    } else {
        responderJSON(false, null, 'Error al reordenar');
    }
}

