<?php
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/sanitizar.php';
require_once __DIR__ . '/../../private/helpers/utils.php';
require_once __DIR__ . '/../../private/models/Usuario.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'verificar':
        verificarSesion();
        break;
    default:
        responderJSON(false, null, 'Acción no válida');
}

function login() {
    $usuario = limpiarString($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        responderJSON(false, null, 'Usuario y contraseña son requeridos');
    }
    
    $modeloUsuario = new Usuario();
    $usuarioData = $modeloUsuario->autenticar($usuario, $password);
    
    if ($usuarioData) {
        loginUsuario($usuarioData['id'], $usuarioData['nombre']);
        registrarLog("Login exitoso: {$usuario}");
        responderJSON(true, [
            'nombre' => $usuarioData['nombre']
        ], 'Login exitoso');
    } else {
        registrarLog("Intento de login fallido: {$usuario}", 'WARNING');
        responderJSON(false, null, 'Usuario o contraseña incorrectos');
    }
}

function logout() {
    $usuario = usuarioActual();
    cerrarSesion();
    registrarLog("Logout: " . ($usuario['nombre'] ?? 'Desconocido'));
    responderJSON(true, null, 'Sesión cerrada');
}

function verificarSesion() {
    if (estaAutenticado()) {
        responderJSON(true, usuarioActual());
    } else {
        responderJSON(false, null, 'No autenticado');
    }
}
