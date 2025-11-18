<?php
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/sanitizar.php';
require_once __DIR__ . '/../helpers/utils.php';
require_once __DIR__ . '/../models/Usuario.php';

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
            'nombre' => $usuarioData['nombre'],
            'redirect' => '/spa-manager/private/admin/dashboard.php'
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
    responderJSON(true, ['redirect' => '/spa-manager/private/admin/login.php'], 'Sesión cerrada');
}

function verificarSesion() {
    if (estaAutenticado()) {
        responderJSON(true, usuarioActual());
    } else {
        responderJSON(false, null, 'No autenticado');
    }
}
