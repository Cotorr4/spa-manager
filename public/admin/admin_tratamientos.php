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
    <title>Tratamientos - Spa Manager</title>
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
        .btn-info { background: #3b82f6; color: white; padding: 8px 16px; font-size: 13px; }
        table { width: 100%; background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; border-collapse: collapse; overflow: hidden; }
        th { background: #1a1a1a; padding: 12px; text-align: left; font-size: 13px; font-weight: 500; color: #a0a0a0; border-bottom: 1px solid #404040; }
        td { padding: 12px; font-size: 14px; border-bottom: 1px solid #404040; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #404040; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #10b981; color: white; }
        .badge-danger { background: #ef4444; color: white; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; overflow-y: auto; padding: 20px; }
        .modal.show { display: flex; }
        .modal-content { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 30px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; margin: auto; }
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
        
        .fotos-container { margin-top: 20px; padding-top: 20px; border-top: 1px solid #404040; }
        .fotos-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
        .foto-item { position: relative; aspect-ratio: 1; border-radius: 8px; overflow: hidden; background: #1a1a1a; border: 2px solid #404040; }
        .foto-item img { width: 100%; height: 100%; object-fit: cover; }
        .foto-item .foto-orden { position: absolute; top: 5px; left: 5px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .foto-item .foto-eliminar { position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .foto-item.dragging { opacity: 0.5; }
        
        .upload-area { border: 2px dashed #404040; border-radius: 8px; padding: 30px; text-align: center; background: #1a1a1a; cursor: pointer; transition: all 0.3s; }
        .upload-area:hover { border-color: #2563eb; background: #2d2d2d; }
        .upload-area.dragover { border-color: #10b981; background: #2d2d2d; }
        .upload-area input[type="file"] { display: none; }
        .upload-icon { font-size: 48px; margin-bottom: 10px; }
        .upload-text { color: #a0a0a0; }
        .upload-limit { font-size: 12px; color: #6b7280; margin-top: 10px; }
        
        .fotos-count { display: inline-block; padding: 4px 8px; background: #2563eb; color: white; border-radius: 4px; font-size: 12px; margin-left: 8px; }
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
                    <li><a href="admin_tratamientos.php" class="active">Tratamientos</a></li>
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
                <h2>Tratamientos</h2>
                <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo Tratamiento</button>
            </div>
            
            <div id="contenidoTabla">
                <div class="loading">Cargando tratamientos...</div>
            </div>
        </main>
    </div>

    <!-- MODAL FORMULARIO -->
    <div id="modalTratamiento" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitulo">Nuevo Tratamiento</div>
            
            <form id="formTratamiento">
                <input type="hidden" id="tratamientoId" name="id">
                
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="subtitulo">Subtítulo</label>
                    <input type="text" id="subtitulo" name="subtitulo" placeholder="Ej: Libera tensiones y renueva tu energía">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="duracion">Duración (minutos) *</label>
                    <input type="number" id="duracion" name="duracion" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="precio">Precio *</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="activo" name="activo" checked>
                    <label for="activo" style="margin: 0;">Tratamiento activo</label>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
            
            <div class="fotos-container" id="fotosContainer" style="display: none;">
                <h3 style="margin-bottom: 10px;">Fotos del Tratamiento <span id="fotosCount" class="fotos-count">0/3</span></h3>
                
                <div class="upload-area" id="uploadArea" onclick="document.getElementById('fotoInput').click()">
                    <div class="upload-icon">📸</div>
                    <div class="upload-text">Click para subir o arrastra fotos aquí</div>
                    <div class="upload-limit">Máximo 3 fotos | JPG, PNG, WEBP | Max 2MB por foto</div>
                    <input type="file" id="fotoInput" accept="image/jpeg,image/png,image/webp" onchange="subirFoto(event)">
                </div>
                
                <div class="fotos-grid" id="fotosGrid"></div>
            </div>
        </div>
    </div>

    <script>
        let tratamientos = [];
        let tratamientoActual = null;
        const API_URL = '../api/tratamientos.php';
        
        cargarTratamientos();
        
        async function cargarTratamientos() {
            try {
                const res = await fetch(API_URL + '?action=listar');
                const data = await res.json();
                
                if (data.success) {
                    tratamientos = data.data;
                    renderizarTabla();
                } else {
                    throw new Error(data.mensaje || 'Error al cargar');
                }
            } catch (err) {
                console.error('Error:', err);
                document.getElementById('contenidoTabla').innerHTML = '<div class="empty-state"><h3>Error al cargar tratamientos</h3><p>' + err.message + '</p></div>';
            }
        }
        
        function renderizarTabla() {
            const contenedor = document.getElementById('contenidoTabla');
            
            if (tratamientos.length === 0) {
                contenedor.innerHTML = '<div class="empty-state"><h3>No hay tratamientos</h3><p>Comienza creando tu primer tratamiento</p></div>';
                return;
            }
            
            let html = '<table><thead><tr><th>Nombre</th><th>Subtítulo</th><th>Duración</th><th>Precio</th><th>Fotos</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
            
            tratamientos.forEach(t => {
                const estadoBadge = t.activo == 1 
                    ? '<span class="badge badge-success">Activo</span>'
                    : '<span class="badge badge-danger">Inactivo</span>';
                
                const btnEstado = t.activo == 1
                    ? `<button class="btn btn-secondary" onclick="cambiarEstado(${t.id}, 0)">Desactivar</button>`
                    : `<button class="btn btn-success" onclick="cambiarEstado(${t.id}, 1)">Activar</button>`;
                
                const fotosInfo = t.total_fotos > 0 
                    ? `<span class="fotos-count">${t.total_fotos} foto${t.total_fotos > 1 ? 's' : ''}</span>`
                    : '<span style="color: #6b7280;">Sin fotos</span>';
                
                html += `
                    <tr>
                        <td><strong>${t.nombre}</strong></td>
                        <td>${t.subtitulo || '-'}</td>
                        <td>${t.duracion} min</td>
                        <td>$${parseFloat(t.precio).toFixed(2)}</td>
                        <td>${fotosInfo}</td>
                        <td>${estadoBadge}</td>
                        <td>
                            <button class="btn btn-primary" onclick="editarTratamiento(${t.id})">Editar</button>
                            ${btnEstado}
                            <button class="btn btn-danger" onclick="eliminarTratamiento(${t.id})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            contenedor.innerHTML = html;
        }
        
        async function abrirModal(id = null) {
            document.getElementById('modalTitulo').textContent = id ? 'Editar Tratamiento' : 'Nuevo Tratamiento';
            document.getElementById('formTratamiento').reset();
            document.getElementById('tratamientoId').value = '';
            document.getElementById('activo').checked = true;
            document.getElementById('fotosContainer').style.display = 'none';
            tratamientoActual = null;
            
            if (id) {
                try {
                    const res = await fetch(API_URL + '?action=obtener&id=' + id);
                    const data = await res.json();
                    
                    if (data.success) {
                        const t = data.data;
                        tratamientoActual = t;
                        document.getElementById('tratamientoId').value = t.id;
                        document.getElementById('nombre').value = t.nombre;
                        document.getElementById('subtitulo').value = t.subtitulo || '';
                        document.getElementById('descripcion').value = t.descripcion || '';
                        document.getElementById('duracion').value = t.duracion;
                        document.getElementById('precio').value = t.precio;
                        document.getElementById('activo').checked = t.activo == 1;
                        
                        document.getElementById('fotosContainer').style.display = 'block';
                        renderizarFotos(t.fotos || []);
                    }
                } catch (err) {
                    alert('Error al cargar tratamiento');
                }
            }
            
            document.getElementById('modalTratamiento').classList.add('show');
        }
        
        function cerrarModal() {
            document.getElementById('modalTratamiento').classList.remove('show');
            tratamientoActual = null;
        }
        
        function editarTratamiento(id) {
            abrirModal(id);
        }
        
        async function cambiarEstado(id, nuevoEstado) {
            try {
                const formData = new FormData();
                formData.append('action', 'cambiar_estado');
                formData.append('id', id);
                if (nuevoEstado) formData.append('activo', '1');
                
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    await cargarTratamientos();
                } else {
                    alert(data.mensaje || 'Error al cambiar estado');
                }
            } catch (err) {
                alert('Error de conexión');
            }
        }
        
        async function eliminarTratamiento(id) {
            if (!confirm('¿Estás seguro de eliminar este tratamiento?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'eliminar');
                formData.append('id', id);
                
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    await cargarTratamientos();
                } else {
                    alert(data.mensaje || 'Error al eliminar');
                }
            } catch (err) {
                alert('Error de conexión');
            }
        }
        
        document.getElementById('formTratamiento').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btnSubmit = e.target.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Guardando...';
            
            try {
                const formData = new FormData(e.target);
                const id = document.getElementById('tratamientoId').value;
                formData.append('action', id ? 'actualizar' : 'crear');
                
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    if (!id && data.data && data.data.id) {
                        // Tratamiento recién creado - mostrar sección de fotos
                        tratamientoActual = { id: data.data.id, fotos: [] };
                        document.getElementById('tratamientoId').value = data.data.id;
                        document.getElementById('fotosContainer').style.display = 'block';
                        renderizarFotos([]);
                    } else {
                        cerrarModal();
                    }
                    await cargarTratamientos();
                } else {
                    alert(data.mensaje || 'Error al guardar');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Guardar';
            }
        });
        
        function renderizarFotos(fotos) {
            const grid = document.getElementById('fotosGrid');
            const count = document.getElementById('fotosCount');
            const uploadArea = document.getElementById('uploadArea');
            
            count.textContent = fotos.length + '/3';
            
            if (fotos.length >= 3) {
                uploadArea.style.display = 'none';
            } else {
                uploadArea.style.display = 'block';
            }
            
            if (fotos.length === 0) {
                grid.innerHTML = '';
                return;
            }
            
            let html = '';
            fotos.forEach((foto, index) => {
                html += `
                    <div class="foto-item" draggable="true" data-foto-id="${foto.id}" data-orden="${index}">
                        <img src="../../storage/tratamientos/${foto.ruta}" alt="Foto ${index + 1}">
                        <div class="foto-orden">${index + 1}</div>
                        <button class="foto-eliminar" onclick="eliminarFoto(${foto.id})">✕</button>
                    </div>
                `;
            });
            
            grid.innerHTML = html;
            inicializarDragAndDrop();
        }
        
        async function subirFoto(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (!tratamientoActual || !tratamientoActual.id) {
                alert('Primero guarda el tratamiento');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'subir_foto');
            formData.append('tratamiento_id', tratamientoActual.id);
            formData.append('foto', file);
            
            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    // Recargar fotos
                    const resFotos = await fetch(API_URL + '?action=obtener&id=' + tratamientoActual.id);
                    const dataFotos = await resFotos.json();
                    if (dataFotos.success) {
                        tratamientoActual.fotos = dataFotos.data.fotos;
                        renderizarFotos(tratamientoActual.fotos);
                    }
                } else {
                    alert(data.mensaje || 'Error al subir foto');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
            
            event.target.value = '';
        }
        
        async function eliminarFoto(fotoId) {
            if (!confirm('¿Eliminar esta foto?')) return;
            
            const formData = new FormData();
            formData.append('action', 'eliminar_foto');
            formData.append('foto_id', fotoId);
            
            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    tratamientoActual.fotos = tratamientoActual.fotos.filter(f => f.id != fotoId);
                    renderizarFotos(tratamientoActual.fotos);
                } else {
                    alert(data.mensaje || 'Error al eliminar foto');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }
        
        function inicializarDragAndDrop() {
            const items = document.querySelectorAll('.foto-item');
            
            items.forEach(item => {
                item.addEventListener('dragstart', (e) => {
                    item.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', item.innerHTML);
                });
                
                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                });
                
                item.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const dragging = document.querySelector('.dragging');
                    const afterElement = getDragAfterElement(e.currentTarget.parentElement, e.clientY);
                    
                    if (afterElement == null) {
                        e.currentTarget.parentElement.appendChild(dragging);
                    } else {
                        e.currentTarget.parentElement.insertBefore(dragging, afterElement);
                    }
                });
                
                item.addEventListener('drop', async (e) => {
                    e.preventDefault();
                    await guardarNuevoOrden();
                });
            });
            
            const uploadArea = document.getElementById('uploadArea');
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    const fakeEvent = { target: { files: [file] } };
                    subirFoto(fakeEvent);
                }
            });
        }
        
        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.foto-item:not(.dragging)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
        
        async function guardarNuevoOrden() {
            const items = document.querySelectorAll('.foto-item');
            const orden = Array.from(items).map(item => parseInt(item.dataset.fotoId));
            
            const formData = new FormData();
            formData.append('action', 'reordenar_fotos');
            formData.append('tratamiento_id', tratamientoActual.id);
            formData.append('orden', JSON.stringify(orden));
            
            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    const resFotos = await fetch(API_URL + '?action=obtener&id=' + tratamientoActual.id);
                    const dataFotos = await resFotos.json();
                    if (dataFotos.success) {
                        tratamientoActual.fotos = dataFotos.data.fotos;
                        renderizarFotos(tratamientoActual.fotos);
                    }
                }
            } catch (err) {
                console.error('Error al reordenar:', err);
            }
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
