<?php
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
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
        .sidebar { width: 250px; background: #2d2d2d; border-right: 1px solid #404040; padding: 20px 0; position: relative; }
        .sidebar-header .user-info { margin-top: 8px; font-size: 13px; color: #a0a0a0; }
        .nav-menu { list-style: none; padding: 20px 0; }
        .nav-menu li a { display: block; padding: 10px 20px; color: #a0a0a0; text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .nav-menu li a:hover { background: #404040; color: #f5f5f5; }
        .nav-menu li a.active { background: #2563eb; color: white; }
        .sidebar-footer { position: absolute; bottom: 20px; left: 20px; right: 20px; }
        .logout-btn { width: 100%; padding: 10px; background: transparent; border: 1px solid #404040; color: #a0a0a0; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .logout-btn:hover { border-color: #ef4444; color: #ef4444; }
        
        .main-content { flex: 1; padding: 30px;  }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .top-bar h2 { font-size: 24px; font-weight: 500; }
        .top-bar-actions { display: flex; gap: 10px; align-items: center; }
        .view-toggle { display: flex; gap: 5px; background: #2d2d2d; border: 1px solid #404040; border-radius: 4px; padding: 4px; }
        .view-btn { padding: 8px 16px; background: transparent; border: none; color: #a0a0a0; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .view-btn.active { background: #2563eb; color: white; }
        
        .nav-calendar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .nav-calendar button { padding: 8px 12px; background: #2d2d2d; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; cursor: pointer; }
        .nav-calendar .current-period { font-size: 16px; font-weight: 500; min-width: 250px; text-align: center; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-success { background: #10b981; color: white; }
        
        /* SELECTOR DE COLORES */
        .color-selector { display: flex; gap: 10px; padding: 16px; background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; margin-bottom: 20px; align-items: center; }
        .color-selector-label { font-size: 14px; color: #a0a0a0; }
        .color-btn { flex: 1; padding: 12px; border: 2px solid transparent; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s; }
        .color-btn:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .color-btn.selected { border-color: #fff; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.5); }
        
        /* VISTA DÍA */
        .calendar-day { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 20px; }
        .day-slots { display: grid; grid-template-columns: 80px 1fr; gap: 2px; }
        .slot-time { padding: 12px; text-align: right; color: #a0a0a0; font-size: 14px; background: #1a1a1a; border-radius: 4px 0 0 4px; }
        .slot-content { padding: 12px; border: 2px solid #404040; border-radius: 0 4px 4px 0; cursor: pointer; transition: all 0.2s; min-height: 44px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; }
        .slot-content:hover:not(.occupied) { border-color: #2563eb; transform: translateX(2px); }
        .slot-content.occupied { background: #ef4444; color: white; border-color: #ef4444; cursor: not-allowed; font-size: 12px; }
        
        /* VISTA SEMANA */
        .semana-controls { padding: 16px; background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 16px; align-items: center; }
        .semana-controls select { padding: 8px 12px; background: #1a1a1a; border: 1px solid #404040; color: #f5f5f5; border-radius: 4px; font-size: 14px; }
        
        .calendar-week { display: grid; grid-template-columns: 80px repeat(7, 1fr); gap: 1px; background: #404040; border: 1px solid #404040; border-radius: 8px; overflow: hidden; }
        .calendar-header { background: #2d2d2d; padding: 12px; text-align: center; font-size: 13px; font-weight: 500; }
        .calendar-header.corner { background: #1a1a1a; }
        .time-slot { background: #2d2d2d; padding: 8px; text-align: right; font-size: 12px; color: #a0a0a0; }
        .day-cell { background: #2d2d2d; padding: 4px; min-height: 50px; position: relative; }
        .day-cell.has-slot { border: 2px solid; }
        .day-cell.has-reserva { opacity: 0.6; }
        
        /* VISTA MES */
        .calendar-month { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #404040; border: 1px solid #404040; border-radius: 8px; overflow: hidden; }
        .month-day { background: #2d2d2d; padding: 12px; min-height: 100px; cursor: pointer; transition: all 0.2s; }
        .month-day:hover { background: #404040; transform: scale(1.02); }
        .month-day.other-month { opacity: 0.3; }
        .month-day.today { border: 2px solid #2563eb; background: #2563eb15; }
        .day-number { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .day-info { font-size: 11px; color: #a0a0a0; }
        .day-info .slots { color: #10b981; }
        .day-info .reservas { color: #ef4444; }
        
        .loading { text-align: center; padding: 40px; color: #a0a0a0; }
        .error { text-align: center; padding: 40px; color: #ef4444; }
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
                <h2>Calendario de Disponibilidad</h2>
                <div class="top-bar-actions">
                    <div class="view-toggle">
                        <button class="view-btn" onclick="cambiarVista('semana')">Semana</button>
                        <button class="view-btn active" onclick="cambiarVista('dia')">Día</button>
                        <button class="view-btn" onclick="cambiarVista('mes')">Mes</button>
                    </div>
                </div>
            </div>
            
            <div class="nav-calendar">
                <button onclick="navegar('anterior')">← Anterior</button>
                <div class="current-period" id="currentPeriod">Cargando...</div>
                <button onclick="navegar('siguiente')">Siguiente →</button>
                <button onclick="irHoy()" class="btn btn-primary">Hoy</button>
            </div>
            
            <div id="calendarContainer">
                <div class="loading">Cargando calendario...</div>
            </div>
        </main>
    </div>

    <script>
        let vistaActual = 'dia';
        let fechaActual = new Date();
        let ubicacionSeleccionada = 1;
        let ubicaciones = {};
        const API_URL = '../api/calendario.php';
        
        // Inicializar
        cargarUbicaciones();
        
        async function cargarUbicaciones() {
            try {
                const res = await fetch('../api/ubicaciones.php?action=listar', {
                    credentials: 'same-origin'
                });
                const data = await res.json();
                
                if (data.success && data.data) {
                    data.data.forEach(u => {
                        ubicaciones[u.id] = { 
                            nombre: u.nombre, 
                            color: u.color || '#2563eb' 
                        };
                    });
                    console.log('Ubicaciones cargadas:', ubicaciones);
                    cargarCalendario();
                } else {
                    console.error('Error cargando ubicaciones:', data);
                    mostrarError('No se pudieron cargar las ubicaciones');
                }
            } catch (err) {
                console.error('Error en cargarUbicaciones:', err);
                mostrarError('Error de conexión al cargar ubicaciones');
            }
        }
        
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
        
        function irADia(fecha) {
            fechaActual = new Date(fecha + 'T12:00:00');
            vistaActual = 'dia';
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.view-btn')[1].classList.add('active');
            cargarCalendario();
        }
        
        async function cargarCalendario() {
            console.log('Cargando:', vistaActual, fechaActual.toISOString().split('T')[0]);
            
            try {
                let params = new URLSearchParams();
                
                if (vistaActual === 'semana') {
                    params.append('action', 'obtener_semana');
                    params.append('fecha', fechaActual.toISOString().split('T')[0]);
                } else if (vistaActual === 'dia') {
                    params.append('action', 'obtener_dia');
                    params.append('fecha', fechaActual.toISOString().split('T')[0]);
                    params.append('ubicacion_id', ubicacionSeleccionada);
                } else if (vistaActual === 'mes') {
                    params.append('action', 'obtener_mes');
                    params.append('anio', fechaActual.getFullYear());
                    params.append('mes', fechaActual.getMonth() + 1);
                }
                
                const res = await fetch(API_URL + '?' + params.toString(), {
                    credentials: 'same-origin'
                });
                
                if (!res.ok) {
                    throw new Error('Error HTTP: ' + res.status);
                }
                
                const data = await res.json();
                console.log('Respuesta API:', data);
                
                if (data.success) {
                    if (vistaActual === 'semana') renderSemana(data.data);
                    else if (vistaActual === 'dia') renderDia(data.data);
                    else if (vistaActual === 'mes') renderMes(data.data);
                } else {
                    mostrarError(data.mensaje || 'Error desconocido');
                }
            } catch (err) {
                console.error('Error en cargarCalendario:', err);
                mostrarError('Error de conexión: ' + err.message);
            }
        }
        
        function renderDia(data) {
            const fechaFormateada = new Date(data.fecha + 'T12:00:00').toLocaleDateString('es-ES', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
            });
            document.getElementById('currentPeriod').textContent = fechaFormateada;
            
            // Selector de colores
            let html = '<div class="color-selector">';
            html += '<span class="color-selector-label">Selecciona ubicación:</span>';
            
            for (let id in ubicaciones) {
                const ub = ubicaciones[id];
                html += `
                    <button class="color-btn ${ubicacionSeleccionada == id ? 'selected' : ''}" 
                            style="background: ${ub.color}; color: white;"
                            onclick="seleccionarUbicacion(${id})">
                        ${ub.nombre}
                    </button>
                `;
            }
            
            html += `
                <button class="color-btn ${ubicacionSeleccionada == 0 ? 'selected' : ''}" 
                        style="background: #6b7280; color: white;"
                        onclick="seleccionarUbicacion(0)">
                    🚫 Deshabilitar
                </button>
            `;
            html += '</div>';
            
            // Grid de slots
            html += '<div class="calendar-day"><div class="day-slots">';
            
            if (!data.slots || data.slots.length === 0) {
                html += '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #a0a0a0;">No hay slots disponibles</div>';
            } else {
                data.slots.forEach(slot => {
                    if (slot.ocupado && slot.reserva_info) {
                        // Slot con reserva
                        html += `
                            <div class="slot-time">${slot.hora}</div>
                            <div class="slot-content occupied">
                                RESERVADO: ${slot.reserva_info.cliente_codigo} - ${slot.reserva_info.cliente_nombre}
                            </div>
                        `;
                    } else {
                        // Slot normal
                        const bgcolor = slot.ubicacion_id ? (ubicaciones[slot.ubicacion_id]?.color || '#1a1a1a') : '#1a1a1a';
                        const textcolor = slot.ubicacion_id ? 'white' : '#6b7280';
                        const ubicacionNombre = slot.ubicacion_id ? (ubicaciones[slot.ubicacion_id]?.nombre || 'Ubicación') : 'Deshabilitado';
                        
                        html += `
                            <div class="slot-time">${slot.hora}</div>
                            <div class="slot-content" 
                                 style="background: ${bgcolor}; color: ${textcolor};"
                                 onclick="toggleSlot('${data.fecha}', '${slot.hora}')">
                                ${ubicacionNombre}
                            </div>
                        `;
                    }
                });
            }
            
            html += '</div></div>';
            document.getElementById('calendarContainer').innerHTML = html;
        }
        
        function seleccionarUbicacion(ubicacionId) {
            ubicacionSeleccionada = ubicacionId;
            cargarCalendario();
        }
        
        async function toggleSlot(fecha, hora) {
            console.log('Toggle slot:', fecha, hora, 'ubicacion:', ubicacionSeleccionada);
            
            const formData = new FormData();
            formData.append('action', 'toggle_slot');
            formData.append('fecha', fecha);
            formData.append('hora', hora);
            formData.append('ubicacion_id', ubicacionSeleccionada);
            
            try {
                const res = await fetch(API_URL, { 
                    method: 'POST', 
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await res.json();
                
                if (data.success) {
                    cargarCalendario();
                } else {
                    alert(data.mensaje || 'Error al actualizar slot');
                }
            } catch (err) {
                console.error('Error en toggleSlot:', err);
                alert('Error de conexión');
            }
        }
        
        function renderSemana(data) {
            const inicio = new Date(data.inicio + 'T12:00:00');
            const fin = new Date(data.fin + 'T12:00:00');
            document.getElementById('currentPeriod').textContent = 
                `${inicio.toLocaleDateString('es-ES', {day: 'numeric', month: 'short'})} - ${fin.toLocaleDateString('es-ES', {day: 'numeric', month: 'short', year: 'numeric'})}`;
            
            // Controles
            let html = `
                <div class="semana-controls">
                    <span class="color-selector-label">Ubicación para habilitar:</span>
                    <select id="ubicacionSemana" onchange="ubicacionSeleccionada = this.value">
            `;
            for (let id in ubicaciones) {
                html += `<option value="${id}" ${ubicacionSeleccionada == id ? 'selected' : ''}>${ubicaciones[id].nombre}</option>`;
            }
            html += `
                    </select>
                    <button class="btn btn-success" onclick="habilitarSemanaCompleta()">
                        ✓ Habilitar Semana Completa (Lun-Vie 8:00-17:30)
                    </button>
                </div>
            `;
            
            // Grid semana
            html += '<div class="calendar-week">';
            html += '<div class="calendar-header corner">Hora</div>';
            
            const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            (data.dias || []).forEach(dia => {
                const fecha = new Date(dia.fecha + 'T12:00:00');
                html += `<div class="calendar-header">${diasSemana[fecha.getDay()]}<br>${fecha.getDate()}</div>`;
            });
            
            const horas = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', 
                          '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', 
                          '16:00', '16:30', '17:00', '17:30'];
            
            horas.forEach(hora => {
                html += `<div class="time-slot">${hora}</div>`;
                (data.dias || []).forEach(dia => {
                    const slot = (dia.slots || []).find(s => s.hora && s.hora.startsWith(hora));
                    let cellStyle = '';
                    let cellClass = 'day-cell';
                    
                    if (slot && slot.ubicacion_id) {
                        const color = ubicaciones[slot.ubicacion_id]?.color || '#2d2d2d';
                        cellStyle = `border-color: ${color};`;
                        cellClass += ' has-slot';
                        if (slot.tiene_reserva == 1) {
                            cellClass += ' has-reserva';
                        }
                    }
                    
                    html += `<div class="${cellClass}" style="${cellStyle}"></div>`;
                });
            });
            
            html += '</div>';
            document.getElementById('calendarContainer').innerHTML = html;
        }
        
        async function habilitarSemanaCompleta() {
            if (!confirm('¿Habilitar todos los slots Lun-Vie 8:00-17:30 para ' + ubicaciones[ubicacionSeleccionada].nombre + '?')) return;
            
            const formData = new FormData();
            formData.append('action', 'habilitar_semana');
            formData.append('fecha', fechaActual.toISOString().split('T')[0]);
            formData.append('ubicacion_id', ubicacionSeleccionada);
            
            try {
                const res = await fetch(API_URL, { 
                    method: 'POST', 
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('✓ Semana habilitada correctamente');
                    cargarCalendario();
                } else {
                    alert(data.mensaje || 'Error');
                }
            } catch (err) {
                console.error('Error:', err);
                alert('Error de conexión');
            }
        }
        
        function renderMes(data) {
            const fecha = new Date(data.anio, data.mes - 1, 1);
            document.getElementById('currentPeriod').textContent = 
                fecha.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
            
            let html = '<div class="calendar-month">';
            const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            
            diasSemana.forEach(dia => {
                html += `<div class="calendar-header">${dia}</div>`;
            });
            
            (data.dias || []).forEach(dia => {
                const esHoy = dia.fecha === new Date().toISOString().split('T')[0];
                const esOtroMes = dia.es_otro_mes;
                
                html += `
                    <div class="month-day ${esOtroMes ? 'other-month' : ''} ${esHoy ? 'today' : ''}"
                         onclick="irADia('${dia.fecha}')">
                        <div class="day-number">${dia.numero}</div>
                        <div class="day-info">
                            <div class="slots">📅 ${dia.slots_habilitados || 0} slots</div>
                            ${dia.reservas ? `<div class="reservas">🎫 ${dia.reservas} reservas</div>` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            document.getElementById('calendarContainer').innerHTML = html;
        }
        
        function mostrarError(mensaje) {
            document.getElementById('calendarContainer').innerHTML = `
                <div class="error">
                    <p>❌ ${mensaje}</p>
                    <button class="btn btn-primary" onclick="cargarCalendario()" style="margin-top: 20px;">
                        Reintentar
                    </button>
                </div>
            `;
        }
        
        function cerrarSesion() {
            if (confirm('¿Seguro que deseas cerrar sesión?')) {
                fetch('../api/auth.php?action=logout', { 
                    method: 'POST',
                    credentials: 'same-origin'
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) window.location.href = 'login.php';
                    });
            }
        }
    </script>
</body>
</html>
