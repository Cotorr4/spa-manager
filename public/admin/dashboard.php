<?php
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/utils.php';

// Proteger página
requerirAuth();

$usuario = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Spa Manager</title>
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
        }
        
        .layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background: #2d2d2d;
            border-right: 1px solid #404040;
            padding: 20px 0;
            position: relative;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #404040;
        }
        
        .sidebar-header h1 {
            font-size: 18px;
            font-weight: 500;
            color: #f5f5f5;
        }
        
        .sidebar-header .user-info {
            margin-top: 8px;
            font-size: 13px;
            color: #a0a0a0;
        }
        
        .nav-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .nav-menu li a {
            display: block;
            padding: 10px 20px;
            color: #a0a0a0;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .nav-menu li a:hover {
            background: #404040;
            color: #f5f5f5;
        }
        
        .nav-menu li a.active {
            background: #2563eb;
            color: white;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }
        
        .logout-btn {
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid #404040;
            color: #a0a0a0;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .logout-btn:hover {
            border-color: #ef4444;
            color: #ef4444;
        }
        
        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .top-bar h2 {
            font-size: 24px;
            font-weight: 500;
        }
        
        .welcome-card {
            background: #2d2d2d;
            border: 1px solid #404040;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .welcome-card h3 {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #a0a0a0;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: #2d2d2d;
            border: 1px solid #404040;
            border-radius: 8px;
            padding: 24px;
        }
        
        .stat-card .label {
            font-size: 13px;
            color: #a0a0a0;
            margin-bottom: 8px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 500;
            color: #f5f5f5;
        }
        
        .stat-card .subtitle {
            font-size: 12px;
            color: #a0a0a0;
            margin-top: 8px;
        }
        
        .quick-actions {
            margin-top: 30px;
        }
        
        .quick-actions h3 {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .action-btn {
            display: block;
            padding: 16px;
            background: #2d2d2d;
            border: 1px solid #404040;
            border-radius: 6px;
            text-decoration: none;
            color: #f5f5f5;
            font-size: 14px;
            text-align: center;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            border-color: #2563eb;
            background: #1d4ed8;
        }
        
        .action-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>Spa Manager</h1>
                <div class="user-info"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="admin_tratamientos.php">Tratamientos</a></li>
                    <li><a href="admin_clientes.php">Clientes</a></li>
                    <li><a href="admin_reservas.php">Reservas</a></li>
                    <li><a href="admin_fichas.php">Fichas de Salud</a></li>
                    <li><a href="admin_calendario.php">Calendario</a></li>
                    <li><a href="admin_ubicaciones.php">Ubicaciones</a></li>
                    <li><a href="admin_config.php">Configuración</a></li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <button class="logout-btn" onclick="cerrarSesion()">Cerrar Sesión</button>
            </div>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="top-bar">
                <h2>Dashboard</h2>
            </div>
            
            <div class="welcome-card">
                <h3>Bienvenido, <?php echo htmlspecialchars($usuario['nombre']); ?></h3>
                <p>Este es tu panel de administración. Desde aquí puedes gestionar tratamientos, clientes, reservas y más.</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Reservas Hoy</div>
                    <div class="value" id="statReservasHoy">0</div>
                    <div class="subtitle">Próximamente con datos reales</div>
                </div>
                
                <div class="stat-card">
                    <div class="label">Clientes Totales</div>
                    <div class="value" id="statClientes">0</div>
                    <div class="subtitle">Próximamente con datos reales</div>
                </div>
                
                <div class="stat-card">
                    <div class="label">Tratamientos Activos</div>
                    <div class="value" id="statTratamientos">4</div>
                    <div class="subtitle">Datos de ejemplo en BD</div>
                </div>
                
                <div class="stat-card">
                    <div class="label">Ingresos del Mes</div>
                    <div class="value" id="statIngresos">$0</div>
                    <div class="subtitle">Próximamente con datos reales</div>
                </div>
            </div>
            
            <div class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="actions-grid">
                    <a href="#" class="action-btn disabled">Nueva Reserva</a>
                    <a href="#" class="action-btn disabled">Nuevo Cliente</a>
                    <a href="#" class="action-btn disabled">Nuevo Tratamiento</a>
                    <a href="#" class="action-btn disabled">Ver Calendario</a>
                </div>
                <p style="color: #a0a0a0; font-size: 12px; margin-top: 16px;">
                    ✅ Sistema básico funcionando. Las funcionalidades completas se implementarán en los próximos pasos.
                </p>
            </div>
        </main>
    </div>

    <script>
        function cerrarSesion() {
            if (confirm('¿Seguro que deseas cerrar sesión?')) {
                fetch('/spa-manager/public/api/auth.php?action=logout', {
                    method: 'POST'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'login.php';
                    }
                });
            }
        }
    </script>
</body>
</html>
