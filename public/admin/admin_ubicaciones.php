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
    <title>Ubicaciones - Spa Manager</title>
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
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #10b981; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-danger { background: #ef4444; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-secondary { background: #6b7280; color: white; padding: 8px 16px; font-size: 13px; }
        table { width: 100%; background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; border-collapse: collapse; overflow: hidden; }
        th { background: #1a1a1a; padding: 12px; text-align: left; font-size: 13px; font-weight: 500; color: #a0a0a0; border-bottom: 1px solid #404040; }
        td { padding: 12px; font-size: 14px; border-bottom: 1px solid #404040; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #404040; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #10b981; color: white; }
        .badge-danger { background: #ef4444; color: white; }
        .badge-peru { background: #ef4444; color: white; }
        .badge-chile { background: #3b82f6; color: white; }
        .badge-domicilio { background: #f59e0b; color: white; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 30px; max-width: 500px; width: 90%; }
        .modal-header { font-size: 20px; font-weight: 500; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; color: #a0a0a0; margin-bottom: 6px; }
        input, textarea, select { width: 100%; padding: 10px 12px; background: #1a1a1a; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; font-size: 14px; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #2563eb; }
        textarea { resize: vertical; min-height: 80px; }
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .modal-actions { display: flex; gap: 10px; margin-top: 24px; }
        .loading { text-align: center; padding: 40px; color: #a0a0a0; }
        .empty-state { text-align: center; padding: 60px 20px; color: #a0a0a0; }
        .empty-state h3 { margin-bottom: 10px; color: #f5f5f5; }
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
                    <li><a href="admin_calendario.php">Calendario</a></li>
                    <li><a href="admin_ubicaciones.php" class="active">Ubicaciones</a></li>
                    <li><a href="admin_config.php">Configuración</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <button class="logout-btn" onclick="cerrarSesion()">Cerrar Sesión</button>
            </div>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h2>Ubicaciones</h2>
                <button class="btn btn-primary" onclick="abrirModal()">+ Nueva Ubicación</button>
            </div>
            
            <div id="contenidoTabla">
                <div class="loading">Cargando ubicaciones...</div>
            </div>
        </main>
    </div>

    <!-- MODAL FORMULARIO -->
    <div id="modalUbicacion" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitulo">Nueva Ubicación</div>
            
            <form id="formUbicacion">
                <input type="hidden" id="ubicacionId" name="id">
                
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="pais">País *</label>
                    <select id="pais" name="pais" required>
                        <option value="">Seleccione...</option>
                        <option value="Peru">Perú</option>
                        <option value="Chile">Chile</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ciudad">Ciudad</label>
                    <input type="text" id="ciudad" name="ciudad">
                </div>
                
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion"></textarea>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="es_domicilio" name="es_domicilio">
                    <label for="es_domicilio" style="margin: 0;">Es servicio a domicilio</label>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="activo" name="activo" checked>
                    <label for="activo" style="margin: 0;">Ubicación activa</label>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let ubicaciones = [];
        const API_URL = '../api/ubicaciones.php';
        
        cargarUbicaciones();
        
        async function cargarUbicaciones() {
            try {
                const res = await fetch(API_URL + '?action=listar');
                const data = await res.json();
                
                if (data.success) {
                    ubicaciones = data.data;
                    renderizarTabla();
                } else {
                    throw new Error(data.mensaje || 'Error al cargar');
                }
            } catch (err) {
                console.error('Error al cargar ubicaciones:', err);
                document.getElementById('contenidoTabla').innerHTML = '<div class="empty-state"><h3>Error al cargar ubicaciones</h3><p>' + err.message + '</p></div>';
            }
        }
        
        function renderizarTabla() {
            const contenedor = document.getElementById('contenidoTabla');
            
            if (ubicaciones.length === 0) {
                contenedor.innerHTML = '<div class="empty-state"><h3>No hay ubicaciones</h3><p>Comienza creando tu primera ubicación</p></div>';
                return;
            }
            
            let html = '<table><thead><tr><th>Nombre</th><th>País</th><th>Ciudad</th><th>Dirección</th><th>Tipo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
            
            ubicaciones.forEach(u => {
                const estadoBadge = u.activo == 1 
                    ? '<span class="badge badge-success">Activo</span>'
                    : '<span class="badge badge-danger">Inactivo</span>';
                
                const paisBadge = u.pais === 'Peru' 
                    ? '<span class="badge badge-peru">Perú</span>'
                    : '<span class="badge badge-chile">Chile</span>';
                
                const tipoBadge = u.es_domicilio == 1
                    ? '<span class="badge badge-domicilio">Domicilio</span>'
                    : '<span class="badge badge-secondary">Sede</span>';
                
                const btnEstado = u.activo == 1
                    ? `<button class="btn btn-secondary" onclick="cambiarEstado(${u.id}, 0)">Desactivar</button>`
                    : `<button class="btn btn-success" onclick="cambiarEstado(${u.id}, 1)">Activar</button>`;
                
                html += `
                    <tr>
                        <td><strong>${u.nombre}</strong></td>
                        <td>${paisBadge}</td>
                        <td>${u.ciudad || '-'}</td>
                        <td>${u.direccion || '-'}</td>
                        <td>${tipoBadge}</td>
                        <td>${estadoBadge}</td>
                        <td>
                            <button class="btn btn-primary" onclick="editarUbicacion(${u.id})">Editar</button>
                            ${btnEstado}
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            contenedor.innerHTML = html;
        }
        
        function abrirModal(id = null) {
            document.getElementById('modalTitulo').textContent = id ? 'Editar Ubicación' : 'Nueva Ubicación';
            document.getElementById('formUbicacion').reset();
            document.getElementById('ubicacionId').value = '';
            document.getElementById('activo').checked = true;
            document.getElementById('es_domicilio').checked = false;
            
            if (id) {
                const u = ubicaciones.find(ub => ub.id == id);
                if (u) {
                    document.getElementById('ubicacionId').value = u.id;
                    document.getElementById('nombre').value = u.nombre;
                    document.getElementById('pais').value = u.pais;
                    document.getElementById('ciudad').value = u.ciudad || '';
                    document.getElementById('direccion').value = u.direccion || '';
                    document.getElementById('es_domicilio').checked = u.es_domicilio == 1;
                    document.getElementById('activo').checked = u.activo == 1;
                }
            }
            
            document.getElementById('modalUbicacion').classList.add('show');
        }
        
        function cerrarModal() {
            document.getElementById('modalUbicacion').classList.remove('show');
        }
        
        function editarUbicacion(id) {
            abrirModal(id);
        }
        
        async function cambiarEstado(id, nuevoEstado) {
            if (!confirm('¿Confirmar cambio de estado?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'cambiar_estado');
                formData.append('id', id);
                if (nuevoEstado) formData.append('activo', '1');
                
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    await cargarUbicaciones();
                } else {
                    alert(data.mensaje || 'Error al cambiar estado');
                }
            } catch (err) {
                console.error('Error:', err);
                alert('Error de conexión');
            }
        }
        
        document.getElementById('formUbicacion').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btnSubmit = e.target.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Guardando...';
            
            try {
                const formData = new FormData(e.target);
                const id = document.getElementById('ubicacionId').value;
                formData.append('action', id ? 'actualizar' : 'crear');
                
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                
                const data = await res.json();
                
                if (data.success) {
                    cerrarModal();
                    await cargarUbicaciones();
                } else {
                    alert(data.mensaje || 'Error al guardar');
                }
            } catch (err) {
                console.error('Error completo:', err);
                alert('Error: ' + err.message);
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Guardar';
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
