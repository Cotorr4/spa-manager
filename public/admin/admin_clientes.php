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

    <!-- MODAL NUEVA ENTRADA BITÁCORA -->
    <div id="modalNuevaEntrada" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="tituloModalEntrada">Nueva Entrada en Bitácora</div>
            <form id="formEntrada" onsubmit="event.preventDefault(); guardarEntrada();">
                <input type="hidden" id="entradaId">
                
                <div class="form-group">
                    <label>Fecha *</label>
                    <input type="date" id="entradaFecha" name="fecha" required>
                </div>
                
                <div class="form-group">
                    <label>Tratamiento *</label>
                    <select id="entradaTratamiento" name="tratamiento_id" required>
                        <option value="">Selecciona un tratamiento</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea id="entradaNotas" name="notas" rows="6" placeholder="Describe cómo fue la sesión, resultados observados, recomendaciones, etc."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Fotos (máximo 3)</label>
                    <input type="file" id="fotoInput" accept="image/*" onchange="subirFoto(event)" style="display:none;">
                        📷 Subir Foto
                    </button>
                    <div id="fotosPreview" style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;"></div>
                    <small style="color: #a0a0a0; display: block; margin-top: 8px;">
                        💡 Guarda la entrada primero, luego podrás subir fotos
                    </small>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">💾 Guardar</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalNuevaEntrada()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Spa Manager</title>
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
        .search-box { padding: 10px 12px; background: #2d2d2d; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; font-size: 14px; width: 300px; }
        .search-box:focus { outline: none; border-color: #2563eb; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #10b981; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-danger { background: #ef4444; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-secondary { background: #6b7280; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-info { background: #3b82f6; color: white; padding: 8px 16px; font-size: 13px; }
        table { width: 100%; background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; border-collapse: collapse; overflow: hidden; }
        th { background: #1a1a1a; padding: 12px; text-align: left; font-size: 13px; font-weight: 500; color: #a0a0a0; border-bottom: 1px solid #404040; }
        td { padding: 12px; font-size: 14px; border-bottom: 1px solid #404040; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #404040; }
        .uid-badge { display: inline-block; padding: 4px 8px; background: #1a1a1a; border: 1px solid #404040; border-radius: 4px; font-size: 12px; font-family: monospace; color: #a0a0a0; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; }
        #modalNuevaEntrada { z-index: 1100; }
        .modal.show { display: flex; }
        .modal-content { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 30px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-content.large { max-width: 700px; }
        .modal-header { font-size: 20px; font-weight: 500; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; color: #a0a0a0; margin-bottom: 6px; }
        input, textarea { width: 100%; padding: 10px 12px; background: #1a1a1a; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; font-size: 14px; }
        input:focus, textarea:focus { outline: none; border-color: #2563eb; }
        textarea { resize: vertical; min-height: 80px; }
        .modal-actions { display: flex; gap: 10px; margin-top: 24px; }
        .loading { text-align: center; padding: 40px; color: #a0a0a0; }
        .empty-state { text-align: center; padding: 60px 20px; color: #a0a0a0; }
        .empty-state h3 { margin-bottom: 10px; color: #f5f5f5; }
        .stats { display: flex; gap: 16px; margin-bottom: 20px; }
        .stat-item { padding: 16px; background: #2d2d2d; border: 1px solid #404040; border-radius: 6px; }
        .stat-item .label { font-size: 12px; color: #a0a0a0; }
        .stat-item .value { font-size: 24px; font-weight: 500; margin-top: 4px; }
        .historial-item { padding: 12px; background: #1a1a1a; border: 1px solid #404040; border-radius: 6px; margin-bottom: 10px; }
        .historial-item .fecha { font-size: 12px; color: #a0a0a0; }
        .historial-item .tratamiento { font-weight: 500; margin-top: 4px; }
        .historial-item .estado { font-size: 12px; margin-top: 4px; }
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
                    <li><a href="admin_clientes.php" class="active">Clientes</a></li>
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
                <h2>Clientes</h2>
                <div class="top-bar-actions">
                    <input type="text" id="busqueda" class="search-box" placeholder="Buscar por nombre, teléfono o UID...">
                    <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo Cliente</button>
                </div>
            </div>
            
            <div class="stats">
                <div class="stat-item">
                    <div class="label">Total Clientes</div>
                    <div class="value" id="totalClientes">0</div>
                </div>
            </div>
            
            <div id="contenidoTabla">
                <div class="loading">Cargando clientes...</div>
            </div>
        </main>
    </div>

    <!-- MODAL FORMULARIO -->
    <div id="modalCliente" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitulo">Nuevo Cliente</div>
            <form id="formCliente">
                <input type="hidden" id="clienteId" name="id">
                
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono *</label>
                    <input type="tel" id="telefono" name="telefono" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
                
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="notas">Notas</label>
                    <textarea id="notas" name="notas"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL HISTORIAL -->
    <div id="modalHistorial" class="modal">
        <div class="modal-content large">
            <div class="modal-header">Bitácora de Tratamientos</div>
            <div id="contenidoHistorial"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalHistorial()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        let clientes = [];
        const API_URL = '../api/clientes.php';
        
        cargarClientes();
        
        // Búsqueda en tiempo real
        let timeoutBusqueda;
        document.getElementById('busqueda').addEventListener('input', (e) => {
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(() => {
                cargarClientes(e.target.value);
            }, 300);
        });
        
        async function cargarClientes(busqueda = '') {
            try {
                const url = API_URL + '?action=listar' + (busqueda ? '&busqueda=' + encodeURIComponent(busqueda) : '');
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.success) {
                    clientes = data.data;
                    document.getElementById('totalClientes').textContent = clientes.length;
                    renderizarTabla();
                } else {
                    throw new Error(data.mensaje || 'Error al cargar');
                }
            } catch (err) {
                console.error('Error al cargar clientes:', err);
                document.getElementById('contenidoTabla').innerHTML = '<div class="empty-state"><h3>Error al cargar clientes</h3><p>' + err.message + '</p></div>';
            }
        }
        
        function renderizarTabla() {
            const contenedor = document.getElementById('contenidoTabla');
            if (clientes.length === 0) {
                contenedor.innerHTML = '<div class="empty-state"><h3>No hay clientes</h3><p>Comienza registrando tu primer cliente</p></div>';
                return;
            }
            
            let html = '<table><thead><tr><th>UID</th><th>Nombre</th><th>Teléfono</th><th>Email</th><th>Acciones</th></tr></thead><tbody>';
            clientes.forEach(c => {
                html += `
                    <tr>
                        <td><span class="uid-badge">${c.cliente_uid}</span></td>
                        <td><strong>${c.nombre}</strong></td>
                        <td>${c.telefono}</td>
                        <td>${c.email || '-'}</td>
                        <td>
                            <button class="btn btn-info" onclick="verHistorial(${c.id})">📋 Bitácora</button>
                            <button class="btn btn-primary" onclick="editarCliente(${c.id})">Editar</button>
                            <button class="btn btn-danger" onclick="eliminarCliente(${c.id})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
            contenedor.innerHTML = html;
        }
        
        function abrirModal(id = null) {
            document.getElementById('modalTitulo').textContent = id ? 'Editar Cliente' : 'Nuevo Cliente';
            document.getElementById('formCliente').reset();
            document.getElementById('clienteId').value = '';
            
            if (id) {
                const c = clientes.find(cl => cl.id == id);
                if (c) {
                    document.getElementById('clienteId').value = c.id;
                    document.getElementById('nombre').value = c.nombre;
                    document.getElementById('telefono').value = c.telefono;
                    document.getElementById('email').value = c.email || '';
                    document.getElementById('direccion').value = c.direccion || '';
                    document.getElementById('notas').value = c.notas || '';
                }
            }
            
            document.getElementById('modalCliente').classList.add('show');
        }
        
        function cerrarModal() {
            document.getElementById('modalCliente').classList.remove('show');
        }
        
        function editarCliente(id) {
            abrirModal(id);
        }
        
        async function eliminarCliente(id) {
            if (!confirm('¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer.')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'eliminar');
                formData.append('id', id);
                
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    await cargarClientes();
                } else {
                    alert(data.mensaje || 'Error al eliminar cliente');
                }
            } catch (err) {
                console.error('Error:', err);
                alert('Error de conexión');
            }
        }
        
                        let clienteActualBitacora = null;

        async function verHistorial(clienteId) {
            clienteActualBitacora = clienteId;
            const cliente = clientes.find(c => c.id == clienteId);
            
            try {
                const res = await fetch(`../api/bitacora.php?action=listar&cliente_id=${clienteId}`, {
                    credentials: 'same-origin'
                });
                const data = await res.json();
                
                let html = `
                    <div style="margin-bottom: 20px;">
                        <h3 style="margin-bottom: 10px;">${cliente.nombre} (${cliente.cliente_codigo})</h3>
                        <button class="btn btn-primary" onclick="abrirModalNuevaEntrada()">+ Nueva Entrada</button>
                    </div>
                `;
                
                if (data.success && data.data && data.data.length > 0) {
                    data.data.forEach(entrada => {
                        const fotos = JSON.parse(entrada.fotos || '[]');
                        const fechaFormato = new Date(entrada.fecha + 'T12:00:00').toLocaleDateString('es-ES', {
                            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                        });
                        html += `<div class="historial-item" style="padding: 16px; margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                <div>
                                    <div class="fecha">📅 ${fechaFormato}</div>
                                    <div class="tratamiento" style="font-size: 16px; font-weight: 500; margin-top: 4px;">${entrada.tratamiento_nombre}</div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button class="btn btn-success" onclick="editarEntrada(${entrada.id})" style="padding: 6px 12px; font-size: 13px;">✏️ Editar</button>
                                    <button class="btn btn-danger" onclick="eliminarEntrada(${entrada.id})" style="padding: 6px 12px; font-size: 13px;">🗑️ Eliminar</button>
                                </div>
                            </div>
                            ${entrada.notas ? `<div style="background: #2d2d2d; padding: 12px; border-radius: 6px; margin-bottom: 12px; border-left: 3px solid #2563eb;"><div style="font-size: 12px; color: #a0a0a0; margin-bottom: 6px;">Observaciones:</div><div style="white-space: pre-wrap; font-size: 14px; line-height: 1.6;">${entrada.notas}</div></div>` : '<div style="color: #6b7280; font-size: 13px; font-style: italic;">Sin observaciones</div>'}
                            ${fotos.length > 0 ? `<div style="margin-top: 12px;"><div style="font-size: 12px; color: #a0a0a0; margin-bottom: 8px;">Fotos (${fotos.length}):</div><div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px;">${fotos.map(foto => `<div style="position: relative; aspect-ratio: 1; border-radius: 6px; overflow: hidden; border: 2px solid #404040; cursor: pointer;" onclick="verFotoAmpliada('${foto}')"><img src="../${foto}" style="width: 100%; height: 100%; object-fit: cover;"></div>`).join('')}</div></div>` : '<div style="color: #6b7280; font-size: 13px; font-style: italic; margin-top: 8px;">📷 Sin fotos</div>'}
                        </div>`;
                    });
                } else {
                    html += '<div style="text-align: center; padding: 40px; color: #6b7280;"><p style="font-size: 16px; margin-bottom: 10px;">📋 Sin entradas en la bitácora</p><p style="font-size: 14px;">Agrega la primera entrada para comenzar el seguimiento</p></div>';
                }
                
                document.getElementById('contenidoHistorial').innerHTML = html;
                document.getElementById('modalHistorial').classList.add('show');
            } catch (err) {
                console.error('Error:', err);
                alert('Error al cargar bitácora');
            }
        }

        function cerrarModalHistorial() {
            document.getElementById('modalHistorial').classList.remove('show');
        }

        function abrirModalNuevaEntrada() {
            document.getElementById('tituloModalEntrada').textContent = 'Nueva Entrada en Bitácora';
            document.getElementById('entradaId').value = '';
            document.getElementById('entradaFecha').value = new Date().toISOString().split('T')[0];
            document.getElementById('entradaNotas').value = '';
            document.getElementById('fotosPreview').innerHTML = '';
            cargarTratamientosSelect();
            document.getElementById('modalNuevaEntrada').classList.add('show');
        }

        function cerrarModalNuevaEntrada() {
            document.getElementById('modalNuevaEntrada').classList.remove('show');
        }

        async function cargarTratamientosSelect() {
            try {
                const res = await fetch('../api/tratamientos.php?action=listar', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success) {
                    const select = document.getElementById('entradaTratamiento');
                    select.innerHTML = '<option value="">Selecciona un tratamiento</option>';
                    data.data.forEach(t => {
                        if (t.activo == 1) {
                            select.innerHTML += `<option value="${t.id}">${t.nombre}</option>`;
                        }
                    });
                }
            } catch (err) {
                console.error('Error cargando tratamientos:', err);
            }
        }

        async function guardarEntrada() {
            const btnSubmit = document.querySelector('#formEntrada button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Guardando...';
            
            const id = document.getElementById('entradaId').value;
            const formData = new FormData(document.getElementById('formEntrada'));
            formData.append('action', id ? 'actualizar' : 'crear');
            formData.append('cliente_id', clienteActualBitacora);
            
            try {
                const res = await fetch('../api/bitacora.php', { method: 'POST', body: formData, credentials: 'same-origin' });
                const data = await res.json();
                
                if (data.success) {
                    if (!id && data.data && data.data.id) {
                        // Entrada recién creada - mantener modal abierto para fotos
                        entradaActual = { id: data.data.id, fotos: [] };
                        document.getElementById('entradaId').value = data.data.id;
                        document.getElementById('fotosPreview').style.display = 'block';
                        document.getElementById('fotoInput').style.display = 'block';
                        alert('✅ Entrada guardada. Ahora puedes subir fotos.');
                    } else {
                        cerrarModalNuevaEntrada();
                    }
                    verHistorial(clienteActualBitacora);
                } else {
                    alert(data.mensaje || 'Error al guardar');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = '💾 Guardar';
            }
        }

                async function subirFoto(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const id = document.getElementById('entradaId').value;
            if (!id) {
                alert('Primero guarda la entrada');
                event.target.value = '';
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'subir_foto');
            formData.append('id', id);
            formData.append('foto', file);
            
            try {
                const res = await fetch('../api/bitacora.php', { 
                    method: 'POST', 
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('✅ Foto subida exitosamente');
                    event.target.value = '';
                    
                    // Recargar entrada para ver la foto
                    const resEntrada = await fetch(`../api/bitacora.php?action=obtener&id=${id}`, { 
                        credentials: 'same-origin' 
                    });
                    const dataEntrada = await resEntrada.json();
                    if (dataEntrada.success) {
                        const fotos = JSON.parse(dataEntrada.data.fotos || '[]');
                        const fotosHTML = fotos.map(foto => 
                            `<div style="position: relative; width: 100px; height: 100px; display: inline-block; margin: 5px;">
                                <img src="../${foto}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 2px solid #10b981;">
                            </div>`
                        ).join('');
                        document.getElementById('fotosPreview').innerHTML = fotosHTML;
                        document.getElementById('fotoInput').style.display = fotos.length < 3 ? 'block' : 'none';
                    }
                } else {
                    alert(data.mensaje || 'Error al subir foto');
                    event.target.value = '';
                }
            } catch (err) {
                console.error('Error completo:', err);
                alert('Error al subir foto: ' + err.message);
                event.target.value = '';
            }
        }


        async function editarEntrada(id) {
            try {
                const res = await fetch(`../api/bitacora.php?action=obtener&id=${id}`, { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success) {
                    const entrada = data.data;
                    entradaActual = { id: entrada.id, fotos: JSON.parse(entrada.fotos || '[]') };
                    
                    document.getElementById('tituloModalEntrada').textContent = 'Editar Entrada';
                    document.getElementById('entradaId').value = entrada.id;
                    document.getElementById('entradaFecha').value = entrada.fecha;
                    document.getElementById('entradaNotas').value = entrada.notas || '';
                    await cargarTratamientosSelect();
                    document.getElementById('entradaTratamiento').value = entrada.tratamiento_id;
                    
                    // Mostrar fotos existentes
                    const fotosHTML = entradaActual.fotos.map(foto => 
                        `<div style="position: relative; width: 100px; height: 100px; display: inline-block; margin: 5px;">
                            <img src="../${foto}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 2px solid #10b981;">
                            <button onclick="eliminarFotoExistente(${entrada.id}, '${foto}')" type="button" style="position: absolute; top: 2px; right: 2px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer;">×</button>
                        </div>`
                    ).join('');
                    document.getElementById('fotosPreview').innerHTML = fotosHTML;
                    document.getElementById('fotosPreview').style.display = 'block';
                    document.getElementById('fotoInput').style.display = entradaActual.fotos.length < 3 ? 'block' : 'none';
                    
                    document.getElementById('modalNuevaEntrada').classList.add('show');
                }
            } catch (err) {
                alert('Error al cargar entrada: ' + err.message);
            }
        }

        async function eliminarFotoExistente(entradaId, rutaFoto) {
            if (!confirm('¿Eliminar esta foto?')) return;
            const formData = new FormData();
            formData.append('action', 'eliminar_foto');
            formData.append('id', entradaId);
            formData.append('ruta_foto', rutaFoto);
            try {
                const res = await fetch('../api/bitacora.php', { method: 'POST', body: formData, credentials: 'same-origin' });
                const data = await res.json();
                if (data.success) {
                    editarEntrada(entradaId);
                } else {
                    alert(data.mensaje || 'Error al eliminar foto');
                }
            } catch (err) {
                alert('Error de conexión');
            }
        }

        async function eliminarEntrada(id) {
            if (!confirm('¿Eliminar esta entrada? Esta acción no se puede deshacer.')) return;
            const formData = new FormData();
            formData.append('action', 'eliminar');
            formData.append('id', id);
            try {
                const res = await fetch('../api/bitacora.php', { method: 'POST', body: formData, credentials: 'same-origin' });
                const data = await res.json();
                if (data.success) {
                    verHistorial(clienteActualBitacora);
                } else {
                    alert(data.mensaje || 'Error al eliminar');
                }
            } catch (err) {
                alert('Error de conexión');
            }
        }
        function verFotoAmpliada(ruta) {
            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px; cursor: pointer;';
            modal.onclick = () => modal.remove();
            const img = document.createElement('img');
            img.src = '../' + ruta;
            img.style.cssText = 'max-width: 90%; max-height: 90%; border-radius: 8px; box-shadow: 0 0 50px rgba(0,0,0,0.5);';
            modal.appendChild(img);
            document.body.appendChild(modal);
        }


        
        document.getElementById('formCliente').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btnSubmit = e.target.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Guardando...';
            
            try {
                const formData = new FormData(e.target);
                const id = document.getElementById('clienteId').value;
                formData.append('action', id ? 'actualizar' : 'crear');
                
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }
                
                const data = await res.json();
                
                if (data.success) {
                    cerrarModal();
                    await cargarClientes();
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
