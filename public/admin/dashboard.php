<?php
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/helpers/utils.php';

requerirAuth();
$usuario = usuarioActual();

// Obtener estadísticas
require_once __DIR__ . '/../../private/database/conexion.php';
$db = getDB();

// Estadísticas básicas
$totalClientes = $db->query("SELECT COUNT(*) as total FROM clientes WHERE activo = 1")->fetch()['total'];
$totalTratamientos = $db->query("SELECT COUNT(*) as total FROM tratamientos WHERE activo = 1")->fetch()['total'];
$totalReservas = $db->query("SELECT COUNT(*) as total FROM reservas")->fetch()['total'];
$totalFichas = $db->query("SELECT COUNT(*) as total FROM fichas_salud")->fetch()['total'];

// Reservas este mes
$reservasMes = $db->query("SELECT COUNT(*) as total FROM reservas WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())")->fetch()['total'];

// Próximas reservas (7 días)
$proximasReservas = $db->query("
    SELECT r.*, c.nombre as cliente_nombre, c.cliente_codigo, t.nombre as tratamiento_nombre, u.nombre as ubicacion_nombre
    FROM reservas r
    JOIN clientes c ON r.cliente_id = c.id
    JOIN tratamientos t ON r.tratamiento_id = t.id
    JOIN ubicaciones u ON r.ubicacion_id = u.id
    WHERE r.fecha >= CURDATE() AND r.fecha <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND r.estado != 'cancelada'
    ORDER BY r.fecha, r.hora
    LIMIT 10
")->fetchAll();

// Ingresos del mes (solo completadas y pagadas)
$ingresosMes = $db->query("
    SELECT COALESCE(SUM(t.precio), 0) as total 
    FROM reservas r
    JOIN tratamientos t ON r.tratamiento_id = t.id
    WHERE YEAR(r.fecha) = YEAR(CURDATE()) 
    AND MONTH(r.fecha) = MONTH(CURDATE())
    AND r.estado = 'completada'
    AND r.pagado = 1
")->fetch()['total'];

// Reservas por estado este mes
$reservasPorEstado = $db->query("
    SELECT estado, COUNT(*) as cantidad
    FROM reservas
    WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())
    GROUP BY estado
")->fetchAll();

// Tratamientos más populares
$tratamientosPopulares = $db->query("
    SELECT t.nombre, COUNT(r.id) as total_reservas
    FROM tratamientos t
    LEFT JOIN reservas r ON t.id = r.tratamiento_id
    WHERE r.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY t.id
    ORDER BY total_reservas DESC
    LIMIT 5
")->fetchAll();

// Perfil activo
$perfilActivo = $db->query("SELECT nombre, estado_operativo FROM perfiles_ubicacion WHERE activo = 1")->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Spa Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #1a1a1a; color: #f5f5f5; }
        .layout { display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background: #2d2d2d; border-right: 1px solid #404040; padding: 20px 0; position: relative; }
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid #404040; }
        .sidebar-header h1 { font-size: 18px; font-weight: 500; }
        .sidebar-header .user-info { margin-top: 8px; font-size: 13px; color: #a0a0a0; }
        .nav-menu { list-style: none; padding: 20px 0; }
        .nav-menu li a { display: block; padding: 10px 20px; color: #a0a0a0; text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .nav-menu li a:hover { background: #404040; color: #f5f5f5; }
        .nav-menu li a.active { background: #2563eb; color: white; }
        .sidebar-footer { position: absolute; bottom: 20px; left: 20px; right: 20px; }
        .logout-btn { width: 100%; padding: 10px; background: transparent; border: 1px solid #404040; color: #a0a0a0; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .logout-btn:hover { border-color: #ef4444; color: #ef4444; }
        
        .main-content { flex: 1; padding: 30px; }
        .top-bar { margin-bottom: 30px; }
        .top-bar h2 { font-size: 28px; font-weight: 500; margin-bottom: 8px; }
        .top-bar .subtitle { color: #a0a0a0; font-size: 14px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 20px; }
        .stat-card .stat-label { font-size: 13px; color: #a0a0a0; margin-bottom: 8px; }
        .stat-card .stat-value { font-size: 32px; font-weight: 600; color: #f5f5f5; }
        .stat-card .stat-change { font-size: 12px; margin-top: 8px; }
        .stat-card .stat-change.positive { color: #10b981; }
        .stat-card .stat-change.negative { color: #ef4444; }
        .stat-card.highlight { border-color: #2563eb; background: linear-gradient(135deg, #2563eb15 0%, #2d2d2d 100%); }
        
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 24px; }
        .card-header { font-size: 18px; font-weight: 500; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #404040; }
        
        .reserva-item { padding: 12px; background: #1a1a1a; border: 1px solid #404040; border-radius: 6px; margin-bottom: 10px; }
        .reserva-item:hover { border-color: #2563eb; }
        .reserva-fecha { font-size: 13px; color: #a0a0a0; }
        .reserva-cliente { font-weight: 500; margin: 4px 0; }
        .reserva-detalles { font-size: 13px; color: #a0a0a0; }
        
        .chart-bar { display: flex; align-items: center; margin-bottom: 12px; }
        .chart-label { width: 120px; font-size: 13px; color: #a0a0a0; }
        .chart-bar-bg { flex: 1; height: 24px; background: #1a1a1a; border-radius: 4px; position: relative; overflow: hidden; }
        .chart-bar-fill { height: 100%; background: linear-gradient(90deg, #2563eb, #3b82f6); border-radius: 4px; display: flex; align-items: center; padding-left: 10px; font-size: 12px; font-weight: 500; }
        
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-badge.operativo { background: #10b98120; color: #10b981; }
        .status-badge.no-operativo { background: #ef444420; color: #ef4444; }
        .status-badge.confirmada { background: #3b82f620; color: #3b82f6; }
        .status-badge.completada { background: #10b98120; color: #10b981; }
        .status-badge.cancelada { background: #6b728020; color: #9ca3af; }
        
        .empty-state { text-align: center; padding: 40px; color: #6b7280; }
        
        @media (max-width: 1024px) {
            .content-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="layout">
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
        
        <main class="main-content">
            <div class="top-bar">
                <h2>Dashboard</h2>
                <div class="subtitle">
                    <?php echo date('l, d \de F \de Y'); ?> | 
                    Estado: <span class="status-badge <?php echo $perfilActivo ? strtolower(str_replace('_', '-', $perfilActivo['estado_operativo'])) : 'no-operativo'; ?>">
                        <?php echo $perfilActivo ? ucfirst($perfilActivo['estado_operativo']) : 'No configurado'; ?>
                    </span>
                    <?php if ($perfilActivo): ?>
                        | Ubicación: <strong><?php echo $perfilActivo['nombre']; ?></strong>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Estadísticas principales -->
            <div class="stats-grid">
                <div class="stat-card highlight">
                    <div class="stat-label">Ingresos del Mes</div>
                    <div class="stat-value">$<?php echo number_format($ingresosMes, 0, ',', '.'); ?></div>
                    <div class="stat-change positive">↑ Solo reservas completadas y pagadas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Reservas del Mes</div>
                    <div class="stat-value"><?php echo $reservasMes; ?></div>
                    <div class="stat-change">Total en <?php echo date('F'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Clientes Activos</div>
                    <div class="stat-value"><?php echo $totalClientes; ?></div>
                    <div class="stat-change"><?php echo $totalFichas; ?> con fichas de salud</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Total Reservas</div>
                    <div class="stat-value"><?php echo $totalReservas; ?></div>
                    <div class="stat-change"><?php echo $totalTratamientos; ?> tratamientos disponibles</div>
                </div>
            </div>
            
            <!-- Visitas del Sitio -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">Visitas al Sitio Web (Últimos 30 Días)</div>
                <div style="height: 300px; position: relative;">
                    <canvas id="graficoVisitas"></canvas>
                </div>
            </div>
            <div class="content-grid">
                <!-- Próximas reservas -->
                <div class="card">
                    <div class="card-header">Próximas Reservas (7 días)</div>
                    <?php if (count($proximasReservas) > 0): ?>
                        <?php foreach ($proximasReservas as $reserva): ?>
                            <div class="reserva-item">
                                <div class="reserva-fecha">
                                    <?php 
                                    $fecha = new DateTime($reserva['fecha']);
                                    echo $fecha->format('D d/m/Y'); 
                                    ?> • <?php echo substr($reserva['hora'], 0, 5); ?>
                                </div>
                                <div class="reserva-cliente">
                                    <span class="status-badge <?php echo $reserva['cliente_codigo']; ?>" style="font-size: 11px;"><?php echo $reserva['cliente_codigo']; ?></span>
                                    <?php echo htmlspecialchars($reserva['cliente_nombre']); ?>
                                </div>
                                <div class="reserva-detalles">
                                    <?php echo htmlspecialchars($reserva['tratamiento_nombre']); ?> • 
                                    <?php echo htmlspecialchars($reserva['ubicacion_nombre']); ?> •
                                    <span class="status-badge <?php echo $reserva['estado']; ?>"><?php echo ucfirst($reserva['estado']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No hay reservas próximas</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar con métricas -->
                <div>
                    <!-- Reservas por estado -->
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-header">Reservas por Estado (Este Mes)</div>
                        <?php 
                        $estados = ['confirmada' => 0, 'completada' => 0, 'cancelada' => 0, 'pendiente' => 0];
                        foreach ($reservasPorEstado as $est) {
                            $estados[$est['estado']] = $est['cantidad'];
                        }
                        $total_estado = array_sum($estados);
                        ?>
                        <?php foreach ($estados as $estado => $cant): ?>
                            <?php if ($cant > 0): ?>
                                <div class="chart-bar">
                                    <div class="chart-label"><?php echo ucfirst($estado); ?></div>
                                    <div class="chart-bar-bg">
                                        <div class="chart-bar-fill" style="width: <?php echo $total_estado > 0 ? ($cant / $total_estado * 100) : 0; ?>%;">
                                            <?php echo $cant; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Tratamientos populares -->
                    <div class="card">
                        <div class="card-header">Tratamientos Más Solicitados</div>
                        <?php if (count($tratamientosPopulares) > 0): ?>
                            <?php foreach ($tratamientosPopulares as $trat): ?>
                                <div class="chart-bar">
                                    <div class="chart-label" style="width: 150px;"><?php echo htmlspecialchars($trat['nombre']); ?></div>
                                    <div class="chart-bar-bg">
                                        <div class="chart-bar-fill" style="width: <?php echo min(100, $trat['total_reservas'] * 10); ?>%;">
                                            <?php echo $trat['total_reservas']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No hay datos suficientes</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function cerrarSesion() {
            if (confirm('¿Seguro que deseas cerrar sesión?')) {
                fetch('../api/auth.php?action=logout', { method: 'POST' })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) window.location.href = 'login.php';
                    });
            }
        }
    </script>
</body>
</html>

<!-- SCRIPT PARA GRÁFICOS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Obtener datos de visitas
fetch('../api/dashboard.php?action=visitas')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderizarGraficoVisitas(data.data);
        }
    })
    .catch(err => console.error('Error al cargar visitas:', err));

function renderizarGraficoVisitas(datos) {
    const ctx = document.getElementById('graficoVisitas').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: datos.map(d => {
                const fecha = new Date(d.fecha);
                return fecha.getDate() + '/' + (fecha.getMonth() + 1);
            }),
            datasets: [{
                label: 'Visitas',
                data: datos.map(d => d.visitas),
                borderColor: '#2563eb',
                backgroundColor: '#2563eb20',
                tension: 0.4,
                fill: true
            }, {
                label: 'Visitantes Únicos',
                data: datos.map(d => d.visitantes_unicos),
                borderColor: '#10b981',
                backgroundColor: '#10b98120',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#f5f5f5' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#a0a0a0' },
                    grid: { color: '#404040' }
                },
                x: {
                    ticks: { color: '#a0a0a0' },
                    grid: { color: '#404040' }
                }
            }
        }
    });
}
</script>
