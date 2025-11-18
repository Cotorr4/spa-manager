<?php
// Iniciar sesión segura
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_path', '/spa-manager/');
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_name(SESSION_NAME);
        session_start();
    }
}

// Verificar si el usuario está autenticado
function estaAutenticado() {
    iniciarSesion();
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['ultimo_acceso'])) {
        return false;
    }
    
    // Verificar timeout
    if (time() - $_SESSION['ultimo_acceso'] > SESSION_TIMEOUT) {
        cerrarSesion();
        return false;
    }
    
    // Actualizar último acceso
    $_SESSION['ultimo_acceso'] = time();
    return true;
}

// Requerir autenticación (usar en páginas admin)
function requerirAuth() {
    if (!estaAutenticado()) {
        header('Location: /spa-manager/private/admin/login.php');
        exit;
    }
}

// Iniciar sesión de usuario
function loginUsuario($usuario_id, $nombre) {
    iniciarSesion();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $usuario_id;
    $_SESSION['admin_nombre'] = $nombre;
    $_SESSION['ultimo_acceso'] = time();
}

// Cerrar sesión
function cerrarSesion() {
    iniciarSesion();
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/spa-manager/');
    }
    session_destroy();
}

// Obtener datos del usuario actual
function usuarioActual() {
    iniciarSesion();
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'nombre' => $_SESSION['admin_nombre'] ?? null
    ];
}
