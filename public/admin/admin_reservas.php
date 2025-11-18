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
    <title>Reservas - Spa Manager</title>
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
        .filter-group { display: flex; gap: 10px; }
        .filter-select { padding: 10px 12px; background: #2d2d2d; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; font-size: 14px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #10b981; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-warning { background: #f59e0b; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-danger { background: #ef4444; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-secondary { background: #6b7280; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-info { background: #3b82f6; color: white; padding: 8px 16px; font-size: 13px; }
        table { width: 100%; background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; border-collapse: collapse; overflow: hidden; }
        th { background: #1a1a1a; padding: 12px; text-align: left; font-size: 13px; font-weight: 500; color: #a0a0a0; border-bottom: 1px solid #404040; }
        td { padding: 12px; font-size: 14px; border-bottom: 1px solid #404040; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #404040; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-pendiente { background: #f59e0b; color: white; }
        .badge-confirmada { background: #3b82f6; color: white; }
        .badge-completada { background: #10b981; color: white; }
        .badge-cancelada { background: #6b7280; color: white; }
        .badge-pagado { background: #10b981; color: white; }
        .badge-nopagado { background: #ef4444; color: white; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 30px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { font-size: 20px; font-weight: 500; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        label { display: block; font-size: 13px; color: #a0a0a0; margin-bottom: 6px; }
        input, textarea, select { width: 100%; padding: 10px 12px; background: #1a1a1a; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; font-size: 14px; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #2563eb; }
        textarea { resize: vertical; min-height: 60px; }
        .modal-actions { display: flex; gap: 10px; margin-top: 24px; }
        .loading { text-align: center; padding: 40px; color: #a0a0a0; }
        .empty-state { text-align: center; padding: 60px 20px; color: #a0a0a0; }
        .empty-state h3 { margin-bottom: 10px; color: #f5f5f5; }
        .stats { display: flex; gap: 16px; margin-bottom: 20px; }
        .stat-item { padding: 16px; background: #2d2d2d; border: 1px solid #404040; border-radius: 6px; }
        .stat-item .label { font-size: 12px; color: #a0a0a0; }
        .stat-item .value { font-size: 24px; font-weight: 500; margin-top: 4px; }
        .info-box { padding: 12px; background: #1a1a1a; border: 1px solid #404040; border-radius: 6px; margin-top: 8px; }
        .info-box .label { font-size: 12px; color: #a0a0a0; }
        .info-box .value { font-size: 14px; margin-top: 4px; }
        .search-results { max-height: 200px; overflow-y: auto; background: #1a1a1a; border: 1px solid #404040; border-radius: 4px; margin-top: 8px; }
        .search-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #404040; }
        .search-item:hover { background: #404040; }
        .search-item:last-child { border-bottom: none; }
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
                    <li><a href="admin_reservas.php" class="active">Reservas</a></li>
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
                <h2>Reservas</h2>
                <div class="top-bar-actions">
                    <div class="filter-group">
                        <select id="filtroEstado" class="filter-select" onchange="cargarReservas()">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="confirmada">Confirmada</option>
                            <option value="completada">Completada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                        <input type="date" id="filtroFecha" class="filter-select" onchange="cargarReservas()">
                    </div>
                    <button class="btn btn-primary" onclick="abrirModal()">+ Nueva Reserva</button>
                </div>
            </div>
            
            <div class="stats">
                <div class="stat-item">
                    <div class="label">Reservas Hoy</div>
                    <div class="value" id="statHoy">0</div>
                </div>
                <div class="stat-item">
                    <div class="label">Pendientes</div>
                    <div class="value" id="statPendientes">0</div>
                </div>
                <div class="stat-item">
                    <div class="label">Confirmadas</div>
                    <div class="value" id="statConfirmadas">0</div>
                </div>
            </div>
            
            <div id="contenidoTabla">
                <div class="loading">Cargando reservas...</div>
            </div>
        </main>
    </div>

    <!-- MODAL NUEVA RESERVA -->
    <div id="modalReserva" class="modal">
        <div class="modal-content">
            <div class="modal-header">Nueva Reserva</div>
            <form id="formReserva">
                <input type="hidden" id="reservaId" name="id">
                
                <div class="form-group">
                    <label for="buscarCliente">Buscar Cliente (por teléfono o nombre)</label>
                    <input type="text" id="buscarCliente" placeholder="Ingrese teléfono o nombre">
                    <div id="resultadosCliente" class="search-results" style="display:none;"></div>
                    <input type="hidden" id="clienteId" name="cliente_id">
                    <div id="clienteSeleccionado" class="info-box" style="display:none;">
                        <div class="label">Cliente seleccionado:</div>
                        <div class="value" id="clienteNombre"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tratamientoId">Tratamiento *</label>
                    <select id="tratamientoId" name="tratamiento_id" required>
                        <option value="">Seleccione...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ubicacionId">Ubicación *</label>
                    <select id="ubicacionId" name="ubicacion_id" required>
                        <option value="">Seleccione...</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha">Fecha *</label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hora">Hora *</label>
                        <input type="time" id="hora" name="hora" step="1800" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="comentarios">Comentarios</label>
                    <textarea id="comentarios" name="comentarios"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar Reserva</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL PAGO -->
    <div id="modalPago" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">Registrar Pago</div>
            <form id="formPago">
                <input type="hidden" id="pagoReservaId">
                
                <div class="form-group">
                    <label for="monto">Monto *</label>
                    <input type="number" id="monto" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="metodo">Método de Pago *</label>
                    <select id="metodo" required>
                        <option value="">Seleccione...</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Plin">Plin</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Confirmar Pago</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalPago()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let reservas = [];
        let clientes = [];
        let tratamientos = [];
        let ubicaciones = [];
        const API_RESERVAS = '../api/reservas.php';
        const API_CLIENTES = '../api/clientes.php';
        const API_TRATAMIENTOS = '../api/tratamientos.php';
        
        // Cargar datos iniciales
        cargarReservas();
        cargarTratamientos();
        cargarUbicaciones();
        
        // Fecha de hoy por defecto en filtro
        document.getElementById('filtroFecha').valueAsDate = new Date();
        
        // Búsqueda de cliente
        let timeoutBusqueda;
        document.getElementById('buscarCliente').addEventListener('input', (e) => {
            clearTimeout(timeoutBusqueda);
            const valor = e.target.value.trim();
            
            if (valor.length < 2) {
                document.getElementById('resultadosCliente').style.display = 'none';
                return;
            }
            
            timeoutBusqueda = setTimeout(() => buscarClientes(valor), 300);
        });
        
        async function buscarClientes(busqueda) {
            try {
                const res = await fetch(API_CLIENTES + '?action=listar&busqueda=' + encodeURIComponent(busqueda));
                const data = await res.json();
                
                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(c => {
                        html += `
                            <div class="search-item" onclick="seleccionarCliente(${c.id}, '${c.nombre}', '${c.telefono}')">
                                <strong>${c.nombre}</strong><br>
                                <small style="color: #a0a0a0;">${c.telefono} - ${c.cliente_uid}</small>
                            </div>
                        `;
                    });
                    document.getElementById('resultadosCliente').innerHTML = html;
                    document.getElementById('resultadosCliente').style.display = 'block';
                } else {
                    document.getElementById('resultadosCliente').style.display = 'none';
                }
            } catch (err) {
                console.error('Error al buscar clientes:', err);
            }
        }
        
        function seleccionarCliente(id, nombre, telefono) {
            document.getElementById('clienteId').value = id;
            document.getElementById('clienteNombre').textContent = `${nombre} - ${telefono}`;
            document.getElementById('clienteSeleccionado').style.display = 'block';
            document.getElementById('resultadosCliente').style.display = 'none';
            document.getElementById('buscarCliente').value = '';
        }
        
        async function cargarTratamientos() {
            try {
                const res = await fetch(API_TRATAMIENTOS + '?action=listar&activos=1');
                const data = await res.json();
                
                if (data.success) {
                    tratamientos = data.data;
                    let html = '<option value="">Seleccione...</option>';
                    tratamientos.forEach(t => {
                        html += `<option value="${t.id}">${t.nombre} - $${parseFloat(t.precio).toFixed(2)} (${t.duracion} min)</option>`;
                    });
                    document.getElementById('tratamientoId').innerHTML = html;
                }
            } catch (err) {
                console.error('Error al cargar tratamientos:', err);
            }
        }
        
        async function cargarUbicaciones() {
            try {
                // Por ahora, las ubicaciones están en la BD desde el inicio
                // Más adelante crearemos el módulo de ubicaciones
                const ubicacionesDefault = [
                    {id: 1, nombre: 'Sede Principal Lima'},
                    {id: 2, nombre: 'Sede Secundaria Lima'},
                    {id: 3, nombre: 'Sede Santiago'},
                    {id: 4, nombre: 'Atención a Domicilio'}
                ];
                
                let html = '<option value="">Seleccione...</option>';
                ubicacionesDefault.forEach(u => {
                    html += `<option value="${u.id}">${u.nombre}</option>`;
                });
                document.getElementById('ubicacionId').innerHTML = html;
            } catch (err) {
                console.error('Error al cargar ubicaciones:', err);
            }
        }
        
        async function cargarReservas() {
            try {
                const estado = document.getElementById('filtroEstado').value;
                const fecha = document.getElementById('filtroFecha').value;
                
                let url = API_RESERVAS + '?action=listar';
                if (estado) url += '&estado=' + estado;
                if (fecha) url += '&fecha=' + fecha;
                
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.success) {
                    reservas = data.data;
                    actualizarStats();
                    renderizarTabla();
                }
            } catch (err) {
                console.error('Error al cargar reservas:', err);
                document.getElementById('contenidoTabla').innerHTML = '<div class="empty-state"><h3>Error al cargar reservas</h3></div>';
            }
        }
        
        function actualizarStats() {
            const hoy = new Date().toISOString().split('T')[0];
            const reservasHoy = reservas.filter(r => r.fecha === hoy).length;
            const pendientes = reservas.filter(r => r.estado === 'pendiente').length;
            const confirmadas = reservas.filter(r => r.estado === 'confirmada').length;
            
            document.getElementById('statHoy').textContent = reservasHoy;
            document.getElementById('statPendientes').textContent = pendientes;
            document.getElementById('statConfirmadas').textContent = confirmadas;
        }
        
        function renderizarTabla() {
            const contenedor = document.getElementById('contenidoTabla');
            
            if (reservas.length === 0) {
                contenedor.innerHTML = '<div class="empty-state"><h3>No hay reservas</h3><p>Crea tu primera reserva</p></div>';
                return;
            }
            
            let html = '<table><thead><tr><th>Fecha/Hora</th><th>Cliente</th><th>Tratamiento</th><th>Ubicación</th><th>Estado</th><th>Pago</th><th>Acciones</th></tr></thead><tbody>';
            
            reservas.forEach(r => {
                const badgeEstado = `<span class="badge badge-${r.estado}">${r.estado}</span>`;
                const badgePago = r.pagado == 1 
                    ? `<span class="badge badge-pagado">Pagado $${parseFloat(r.monto_pagado).toFixed(2)}</span>`
                    : `<span class="badge badge-nopagado">No pagado</span>`;
                
                html += `
                    <tr>
                        <td><strong>${r.fecha}</strong><br><small>${r.hora}</small></td>
                        <td><strong>${r.cliente_nombre}</strong><br><small>${r.cliente_telefono}</small></td>
                        <td>${r.tratamiento_nombre}<br><small>${r.tratamiento_duracion} min</small></td>
                        <td>${r.ubicacion_nombre}</td>
                        <td>${badgeEstado}</td>
                        <td>${badgePago}</td>
                        <td>
                            ${r.estado === 'pendiente' ? `<button class="btn btn-success" onclick="cambiarEstado(${r.id}, 'confirmada')">Confirmar</button>` : ''}
                            ${r.estado === 'confirmada' ? `<button class="btn btn-info" onclick="cambiarEstado(${r.id}, 'completada')">Completar</button>` : ''}
                            ${r.pagado == 0 && r.estado !== 'cancelada' ? `<button class="btn btn-warning" onclick="abrirModalPago(${r.id}, ${r.tratamiento_precio})">Pago</button>` : ''}
                            ${r.estado !== 'cancelada' && r.estado !== 'completada' ? `<button class="btn btn-danger" onclick="cambiarEstado(${r.id}, 'cancelada')">Cancelar</button>` : ''}
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            contenedor.innerHTML = html;
        }
        
        function abrirModal() {
            document.getElementById('formReserva').reset();
            document.getElementById('reservaId').value = '';
            document.getElementById('clienteId').value = '';
            document.getElementById('clienteSeleccionado').style.display = 'none';
            document.getElementById('resultadosCliente').style.display = 'none';
            document.getElementById('modalReserva').classList.add('show');
        }
        
        function cerrarModal() {
            document.getElementById('modalReserva').classList.remove('show');
        }
        
        function abrirModalPago(reservaId, precio) {
            document.getElementById('pagoReservaId').value = reservaId;
            document.getElementById('monto').value = parseFloat(precio).toFixed(2);
            document.getElementById('modalPago').classList.add('show');
        }
        
        function cerrarModalPago() {
            document.getElementById('modalPago').classList.remove('show');
        }
        
        async function cambiarEstado(id, nuevoEstado) {
            const mensajes = {
                'confirmada': '¿Confirmar esta reserva?',
                'completada': '¿Marcar como completada?',
                'cancelada': '¿Cancelar esta reserva?'
            };
            
            if (!confirm(mensajes[nuevoEstado])) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'cambiar_estado');
                formData.append('id', id);
                formData.append('estado', nuevoEstado);
                
                const res = await fetch(API_RESERVAS, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    await cargarReservas();
                } else {
                    alert(data.mensaje || 'Error al cambiar estado');
                }
            } catch (err) {
                console.error('Error:', err);
                alert('Error de conexión');
            }
        }
        
        document.getElementById('formReserva').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!document.getElementById('clienteId').value) {
                alert('Debe seleccionar un cliente');
                return;
            }
            
            const btnSubmit = e.target.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Guardando...';
            
            try {
                const formData = new FormData(e.target);
                formData.append('action', 'crear');
                
                const res = await fetch(API_RESERVAS, { method: 'POST', body: formData });
                
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                
                const data = await res.json();
                
                if (data.success) {
                    cerrarModal();
                    await cargarReservas();
                } else {
                    alert(data.mensaje || 'Error al guardar');
                }
            } catch (err) {
                console.error('Error:', err);
                alert('Error: ' + err.message);
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Guardar Reserva';
            }
        });
        
        document.getElementById('formPago').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'marcar_pagado');
            formData.append('id', document.getElementById('pagoReservaId').value);
            formData.append('monto', document.getElementById('monto').value);
            formData.append('metodo', document.getElementById('metodo').value);
            
            try {
                const res = await fetch(API_RESERVAS, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    cerrarModalPago();
                    await cargarReservas();
                } else {
                    alert(data.mensaje || 'Error al registrar pago');
                }
            } catch (err) {
                alert('Error de conexión');
            }
        });
        
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
