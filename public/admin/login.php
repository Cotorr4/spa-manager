<?php
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';

// Si ya está autenticado, redirigir al dashboard
if (estaAutenticado()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Spa Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #1a1a1a;
            color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            background: #2d2d2d;
            border: 1px solid #404040;
            border-radius: 8px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        
        h1 {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #f5f5f5;
        }
        
        .subtitle {
            color: #a0a0a0;
            font-size: 14px;
            margin-bottom: 32px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 13px;
            color: #a0a0a0;
            margin-bottom: 6px;
        }
        
        input {
            width: 100%;
            padding: 10px 12px;
            background: #1a1a1a;
            border: 1px solid #404040;
            border-radius: 4px;
            color: #f5f5f5;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #2563eb;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        button:hover {
            background: #1d4ed8;
        }
        
        button:disabled {
            background: #404040;
            cursor: not-allowed;
        }
        
        .error-msg {
            background: #ef4444;
            color: white;
            padding: 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 20px;
            display: none;
        }
        
        .error-msg.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Spa Manager</h1>
        <p class="subtitle">Ingresa tus credenciales</p>
        
        <div class="error-msg" id="errorMsg"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" id="btnLogin">Iniciar Sesión</button>
        </form>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const btnLogin = document.getElementById('btnLogin');
        const errorMsg = document.getElementById('errorMsg');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const usuario = document.getElementById('usuario').value.trim();
            const password = document.getElementById('password').value;
            
            if (!usuario || !password) {
                mostrarError('Por favor completa todos los campos');
                return;
            }
            
            btnLogin.disabled = true;
            btnLogin.textContent = 'Verificando...';
            errorMsg.classList.remove('show');
            
            try {
                const formData = new FormData();
                formData.append('action', 'login');
                formData.append('usuario', usuario);
                formData.append('password', password);
                
                const response = await fetch('/spa-manager/public/api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    mostrarError(data.mensaje || 'Error al iniciar sesión');
                    btnLogin.disabled = false;
                    btnLogin.textContent = 'Iniciar Sesión';
                }
            } catch (error) {
                mostrarError('Error de conexión. Intenta nuevamente.');
                btnLogin.disabled = false;
                btnLogin.textContent = 'Iniciar Sesión';
            }
        });
        
        function mostrarError(mensaje) {
            errorMsg.textContent = mensaje;
            errorMsg.classList.add('show');
        }
    </script>
</body>
</html>
