<?php
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../../private/helpers/utils.php';

requerirAuth();
$usuario = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - Spa Manager</title>
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
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .top-bar h2 { font-size: 24px; font-weight: 500; }
        .top-bar-actions { display: flex; gap: 10px; align-items: center; }
        .view-toggle { display: flex; gap: 5px; background: #2d2d2d; border: 1px solid #404040; border-radius: 4px; padding: 4px; }
        .view-btn { padding: 8px 16px; background: transparent; border: none; color: #a0a0a0; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .view-btn.active { background: #2563eb; color: white; }
        .nav-calendar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .nav-calendar button { padding: 8px 12px; background: #2d2d2d; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; cursor: pointer; }
        .nav-calendar .current-period { font-size: 16px; font-weight: 500; min-width: 200px; text-align: center; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-success { background: #10b981; color: white; }
        
        .calendar-week { display: grid; grid-template-columns: 80px repeat(7, 1fr); gap: 1px; background: #404040; border: 1px solid #404040; }
        .calendar-header { background: #2d2d2d; padding: 12px; text-align: center; font-size: 13px; font-weight: 500; }
        .calendar-header.corner { background: #1a1a1a; }
        .time-slot { background: #2d2d2d; padding: 8px; text-align: right; font-size: 12px; color: #a0a0a0; }
        .day-cell { background: #2d2d2d; padding: 8px; min-height: 60px; position: relative; }
        .day-cell.available { background: #10b981; opacity: 0.3; }
        .day-cell.closed { background: #1a1a1a; opacity: 0.5; }
        .reservation-block { background: #2563eb; color: white; padding: 4px 6px; border-radius: 4px; font-size: 11px; margin-bottom: 4px; cursor: pointer; }
        .location-badge { font-size: 10px; color: #10b981; }
        
        .calendar-day { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 20px; }
        .day-header { margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #404040; }
        .day-slots { display: grid; grid-template-columns: 100px 1fr; gap: 10px; }
        .slot-time { padding: 10px; text-align: right; color: #a0a0a0; font-size: 14px; }
        .slot-content { padding: 10px; background: #1a1a1a; border: 1px solid #404040; border-radius: 4px; }
        .slot-content.available { background: #10b981; color: white; border-color: #10b981; }
        .slot-content.occupied { background: #2563eb; color: white; }
        
        .calendar-month { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #404040; border: 1px solid #404040; margin-top: 20px; }
        .month-day { background: #2d2d2d; padding: 8px; min-height: 100px; }
        .month-day.other-month { opacity: 0.3; }
        .month-day.today { border: 2px solid #2563eb; }
        .day-number { font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        .day-info { font-size: 11px; color: #a0a0a0; }
        
        .month-controls { margin-bottom: 20px; padding: 16px; background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; }
        .month-controls label { font-size: 13px; color: #a0a0a0; margin-right: 8px; }
        .month-controls select { padding: 8px; background: #1a1a1a; border: 1px solid #404040; color: #f5f5f5; border-radius: 4px; margin-right: 16px; }
        
        .loading { text-align: center; padding: 40px; color: #a0a0a0; }
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
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="admin_tratamientos.php">Tratamientos</a></li>
                    <li><a href="admin_clientes.php">Clientes</a></li>
                    <li><a href="admin_reservas.php">Reservas</a></li>
                    <li><a href="admin_fichas.php">Fichas de Salud</a></li>
                    <li><a href="admin_calendario.php" class="active">Calendario</a></li>
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
                <h2>Calendario</h2>
                <div class="top-bar-actions">
                    <div class="view-toggle">
                        <button class="view-btn" onclick="cambiarVista('semana')">Semana</button>
                        <button class="view-btn active" onclick="cambiarVista('dia')">Día</button>
                        <button class="view-btn" onclick="cambiarVista('mes')">Mes</button>
                    </div>
                </div>
            </div>
            
            <div class="nav-calendar">
                <button onclick="navegar('anterior')">←</button>
                <div class="current-period" id="currentPeriod"></div>
                <button onclick="navegar('siguiente')">→</button>
                <button onclick="irHoy()">Hoy</button>
            </div>
            
            <div id="calendarContainer">
                <div class="loading">Cargando calendario...</div>
            </div>
        </main>
    </div>

    <script>
        let vistaActual = 'dia';
        let fechaActual = new Date();
        let ubicacionActualDia = 1;
        const API_URL = '../api/calendario.php';
        const ubicaciones = {
            1: 'Sede Principal Pucallpa',
            2: 'Sede Secundaria Pucallpa',
            3: 'Sede Antofagasta',
            4: 'Atención a Domicilio'
        };
        
        cargarCalendario();
        
        function cambiarVista(vista) {
            vistaActual = vista;
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            cargarCalendario();
        }
        
        function navegar(direccion) {
            if (vistaActual === 'semana') {
                fechaActual.setDate(fechaActual.getDate() + (direccion === 'siguiente' ? 7 : -7));
            } else if (vistaActual === 'dia') {
                fechaActual.setDate(fechaActual.getDate() + (direccion === 'siguiente' ? 1 : -1));
            } else if (vistaActual === 'mes') {
                fechaActual.setMonth(fechaActual.getMonth() + (direccion === 'siguiente' ? 1 : -1));
            }
            cargarCalendario();
        }
        
        function irHoy() {
            fechaActual = new Date();
            cargarCalendario();
        }
        
        async function cargarCalendario() {
            try {
                let url = API_URL + '?';
                let params = new URLSearchParams();
                
                if (vistaActual === 'semana') {
                    params.append('action', 'obtener_semana');
                    params.append('fecha', fechaActual.toISOString().split('T')[0]);
                } else if (vistaActual === 'dia') {
                    params.append('action', 'obtener_dia');
                    params.append('fecha', fechaActual.toISOString().split('T')[0]);
                    params.append('ubicacion_id', ubicacionActualDia);
                } else if (vistaActual === 'mes') {
                    params.append('action', 'obtener_mes');
                    params.append('anio', fechaActual.getFullYear());
                    params.append('mes', fechaActual.getMonth() + 1);
                }
                
                const res = await fetch(url + params.toString());
                const data = await res.json();
                
                if (data.success) {
                    if (vistaActual === 'semana') renderSemana(data.data);
                    else if (vistaActual === 'dia') renderDia(data.data);
                    else if (vistaActual === 'mes') renderMes(data.data);
                } else {
                    throw new Error(data.mensaje || 'Error al cargar');
                }
            } catch (err) {
                console.error('Error:', err);
                document.getElementById('calendarContainer').innerHTML = '<div class="loading">Error: ' + err.message + '</div>';
            }
        }
        
        function renderDia(data) {
            document.getElementById('currentPeriod').textContent = formatearFecha(data.fecha);
            
            let html = '<div class="calendar-day">';
            html += '<div class="day-header"><h3>' + formatearFecha(data.fecha) + '</h3>';
            html += '<div style="margin-top: 10px;"><label>Ubicación: <select id="ubicacionDia" onchange="cambiarUbicacionDia()" style="padding: 8px; background: #1a1a1a; border: 1px solid #404040; color: #f5f5f5; border-radius: 4px;">';
            html += '<option value="1" ' + (ubicacionActualDia == 1 ? 'selected' : '') + '>Sede Principal Pucallpa</option>';
            html += '<option value="2" ' + (ubicacionActualDia == 2 ? 'selected' : '') + '>Sede Secundaria Pucallpa</option>';
            html += '<option value="3" ' + (ubicacionActualDia == 3 ? 'selected' : '') + '>Sede Antofagasta</option>';
            html += '<option value="4" ' + (ubicacionActualDia == 4 ? 'selected' : '') + '>Atención a Domicilio</option>';
            html += '</select></label></div></div>';
            
            html += '<div class="day-slots">';
            
            // Crear mapa de slots habilitados
            const slotsMap = {};
            data.slots.forEach(s => {
                const horaKey = s.hora.substring(0, 5);
                slotsMap[horaKey] = true;
            });
            
            // Todos los slots 8:00-17:30
            for (let hora = 8; hora < 18; hora++) {
                for (let min = 0; min < 60; min += 30) {
                    const horaStr = String(hora).padStart(2,'0') + ':' + String(min).padStart(2,'0');
                    const reserva = data.reservas.find(r => r.hora.startsWith(horaStr) && r.ubicacion_id == ubicacionActualDia);
                    const isDisponible = slotsMap[horaStr] || false;
                    
                    let slotClass = 'slot-content';
                    let slotContent = '';
                    
                    if (reserva && reserva.estado !== 'cancelada') {
                        slotClass += ' occupied';
                        slotContent = '<strong>' + reserva.cliente_nombre + '</strong><br>' + reserva.tratamiento_nombre;
                    } else if (isDisponible) {
                        slotClass += ' available';
                        slotContent = '<button class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;" onclick="toggleSlot(\'' + data.fecha + '\', \'' + horaStr + '\', false)">Deshabilitar</button>';
                    } else {
                        slotContent = '<button class="btn btn-primary" style="font-size: 12px; padding: 6px 12px;" onclick="toggleSlot(\'' + data.fecha + '\', \'' + horaStr + '\', true)">Habilitar</button>';
                    }
                    
                    html += '<div class="slot-time">' + horaStr + '</div>';
                    html += '<div class="' + slotClass + '">' + slotContent + '</div>';
                }
            }
            
            html += '</div></div>';
            document.getElementById('calendarContainer').innerHTML = html;
        }
        
        async function toggleSlot(fecha, hora, habilitar) {
            const formData = new FormData();
            formData.append('action', 'toggle_slot');
            formData.append('fecha', fecha);
            formData.append('hora', hora);
            formData.append('ubicacion_id', ubicacionActualDia);
            if (habilitar) formData.append('habilitar', '1');
            
            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    cargarCalendario();
                } else {
                    alert(data.mensaje || 'Error al actualizar');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }
        
        function cambiarUbicacionDia() {
            ubicacionActualDia = parseInt(document.getElementById('ubicacionDia').value);
            cargarCalendario();
        }
        
        function renderSemana(data) {
            const dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
            document.getElementById('currentPeriod').textContent = formatearRango(data.inicio_semana, data.fin_semana);
            
            let html = '<div class="calendar-week">';
            html += '<div class="calendar-header corner"></div>';
            
            const inicio = new Date(data.inicio_semana + 'T00:00:00');
            for (let i = 0; i < 7; i++) {
                const dia = new Date(inicio);
                dia.setDate(inicio.getDate() + i);
                html += '<div class="calendar-header">' + dias[i] + '<br><small>' + dia.getDate() + '/' + (dia.getMonth()+1) + '</small></div>';
            }
            
            // Crear mapa de slots
            const slotsMap = {};
            data.slots.forEach(s => {
                const key = s.fecha + '_' + s.hora.substring(0, 5);
                slotsMap[key] = s.ubicacion_id;
            });
            
            for (let hora = 8; hora < 18; hora++) {
                for (let min = 0; min < 60; min += 30) {
                    const horaStr = String(hora).padStart(2,'0') + ':' + String(min).padStart(2,'0');
                    html += '<div class="time-slot">' + horaStr + '</div>';
                    
                    for (let i = 0; i < 7; i++) {
                        const dia = new Date(inicio);
                        dia.setDate(inicio.getDate() + i);
                        const fechaStr = dia.toISOString().split('T')[0];
                        const key = fechaStr + '_' + horaStr;
                        
                        const reservasEnSlot = data.reservas.filter(r => 
                            r.fecha === fechaStr && r.hora.startsWith(horaStr)
                        );
                        
                        let cellClass = 'day-cell';
                        let cellContent = '';
                        
                        if (slotsMap[key]) {
                            cellClass += ' available';
                            cellContent = '<div class="location-badge">' + ubicaciones[slotsMap[key]] + '</div>';
                        }
                        
                        if (reservasEnSlot.length > 0) {
                            reservasEnSlot.forEach(r => {
                                cellContent += '<div class="reservation-block">' + r.cliente_nombre + '</div>';
                            });
                        }
                        
                        html += '<div class="' + cellClass + '">' + cellContent + '</div>';
                    }
                }
            }
            
            html += '</div>';
            document.getElementById('calendarContainer').innerHTML = html;
        }
        
        function renderMes(data) {
            const nombreMes = new Date(data.anio, data.mes - 1).toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
            document.getElementById('currentPeriod').textContent = nombreMes.charAt(0).toUpperCase() + nombreMes.slice(1);
            
            let html = '<div class="month-controls">';
            html += '<label>Ubicación:</label>';
            html += '<select id="mesUbicacion">';
            html += '<option value="1">Sede Principal Pucallpa</option>';
            html += '<option value="2">Sede Secundaria Pucallpa</option>';
            html += '<option value="3">Sede Antofagasta</option>';
            html += '<option value="4">Atención a Domicilio</option>';
            html += '</select>';
            html += '<button class="btn btn-success" onclick="habilitarMesCompleto()">Habilitar Mes Completo</button>';
            html += '</div>';
            
            const dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
            html += '<div class="calendar-month">';
            
            dias.forEach(dia => {
                html += '<div class="calendar-header">' + dia + '</div>';
            });
            
            const primerDia = new Date(data.anio, data.mes - 1, 1);
            const ultimoDia = new Date(data.anio, data.mes, 0);
            const primerDiaSemana = primerDia.getDay() === 0 ? 7 : primerDia.getDay();
            
            // Contar slots por día
            const slotsCountMap = {};
            data.slots.forEach(s => {
                slotsCountMap[s.fecha] = (slotsCountMap[s.fecha] || 0) + 1;
            });
            
            for (let i = 1; i < primerDiaSemana; i++) {
                html += '<div class="month-day other-month"></div>';
            }
            
            for (let dia = 1; dia <= ultimoDia.getDate(); dia++) {
                const fecha = new Date(data.anio, data.mes - 1, dia);
                const fechaStr = fecha.toISOString().split('T')[0];
                const slotsCount = slotsCountMap[fechaStr] || 0;
                const reservasDelDia = data.reservas.filter(r => r.fecha === fechaStr);
                
                const hoy = new Date().toISOString().split('T')[0];
                let dayClass = 'month-day';
                if (fechaStr === hoy) dayClass += ' today';
                
                html += '<div class="' + dayClass + '">';
                html += '<div class="day-number">' + dia + '</div>';
                if (slotsCount > 0) {
                    html += '<div class="day-info">' + slotsCount + ' slots habilitados</div>';
                }
                if (reservasDelDia.length > 0) {
                    html += '<div class="day-info" style="color: #2563eb;">' + reservasDelDia.length + ' reservas</div>';
                }
                html += '</div>';
            }
            
            html += '</div>';
            document.getElementById('calendarContainer').innerHTML = html;
        }
        
        async function habilitarMesCompleto() {
            const ubicacionId = document.getElementById('mesUbicacion').value;
            const anio = fechaActual.getFullYear();
            const mes = fechaActual.getMonth() + 1;
            
            if (!confirm('¿Habilitar TODOS los días del mes con todos los horarios (8:00-17:30)?')) return;
            
            const formData = new FormData();
            formData.append('action', 'habilitar_mes');
            formData.append('anio', anio);
            formData.append('mes', mes);
            formData.append('ubicacion_id', ubicacionId);
            
            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    alert('Mes completo habilitado');
                    cargarCalendario();
                } else {
                    alert(data.mensaje || 'Error al habilitar mes');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }
        
        function formatearFecha(fecha) {
            const d = new Date(fecha + 'T00:00:00');
            return d.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        
        function formatearRango(inicio, fin) {
            const d1 = new Date(inicio + 'T00:00:00');
            const d2 = new Date(fin + 'T00:00:00');
            return d1.getDate() + '/' + (d1.getMonth()+1) + ' - ' + d2.getDate() + '/' + (d2.getMonth()+1) + '/' + d2.getFullYear();
        }
        
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
