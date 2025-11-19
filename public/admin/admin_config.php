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
    <title>Configuración - Spa Manager</title>
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
        .top-bar h2 { font-size: 24px; font-weight: 500; }
        
        .tabs { display: flex; gap: 10px; border-bottom: 1px solid #404040; margin-bottom: 30px; }
        .tab { padding: 12px 20px; background: transparent; border: none; color: #a0a0a0; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; font-size: 14px; }
        .tab:hover { color: #f5f5f5; }
        .tab.active { color: #2563eb; border-bottom-color: #2563eb; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .perfil-activo { background: linear-gradient(135deg, #2563eb15 0%, #2d2d2d 100%); border: 1px solid #2563eb; border-radius: 8px; padding: 20px; margin-bottom: 30px; }
        .perfil-activo h3 { margin-bottom: 10px; }
        .perfil-activo .status { display: inline-block; padding: 6px 16px; background: #10b981; color: white; border-radius: 16px; font-size: 13px; font-weight: 500; }
        
        .perfiles-grid { display: grid; gap: 16px; margin-bottom: 20px; }
        .perfil-card { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 20px; cursor: pointer; transition: all 0.2s; }
        .perfil-card:hover { border-color: #2563eb; }
        .perfil-card.activo { border-color: #10b981; background: linear-gradient(135deg, #10b98115 0%, #2d2d2d 100%); }
        .perfil-card-header { display: flex; justify-content: between; align-items: center; margin-bottom: 12px; }
        .perfil-card h4 { font-size: 18px; flex: 1; }
        .perfil-card .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .perfil-card .badge.activo { background: #10b98120; color: #10b981; }
        .perfil-card .badge.inactivo { background: #6b728020; color: #9ca3af; }
        .perfil-card-body { font-size: 14px; color: #a0a0a0; }
        .perfil-card-actions { display: flex; gap: 10px; margin-top: 16px; }
        
        .testimonios-list { display: grid; gap: 16px; }
        .testimonio-card { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 20px; }
        .testimonio-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .testimonio-header h4 { font-size: 16px; }
        .stars { color: #fbbf24; }
        .testimonio-text { color: #a0a0a0; font-size: 14px; line-height: 1.6; margin-bottom: 12px; }
        .testimonio-actions { display: flex; gap: 10px; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; overflow-y: auto; padding: 20px; }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 30px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; }
        .modal-header { font-size: 20px; font-weight: 500; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #404040; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; color: #a0a0a0; margin-bottom: 6px; }
        input, textarea, select { width: 100%; padding: 10px 12px; background: #1a1a1a; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; }
        .modal-actions { display: flex; gap: 10px; margin-top: 24px; padding-top: 24px; border-top: 1px solid #404040; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
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
                    <li><a href="admin_ubicaciones.php">Ubicaciones</a></li>
                    <li><a href="admin_config.php" class="active">Configuración</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <button class="logout-btn" onclick="cerrarSesion()">Cerrar Sesión</button>
            </div>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h2>Configuración del Sitio</h2>
            </div>
            
            <div class="tabs">
                <button class="tab active" onclick="cambiarTab('perfiles')">Perfiles de Ubicación</button>
                <button class="tab" onclick="cambiarTab('testimonios')">Testimonios</button>
            </div>
            
            <!-- TAB PERFILES -->
            <div id="tab-perfiles" class="tab-content active">
                <div class="perfil-activo" id="perfilActivoCard">
                    <h3>Perfil Activo Actual</h3>
                    <p style="color: #a0a0a0; margin-bottom: 10px;">Este perfil se muestra en el sitio web público</p>
                    <div id="perfilActivoInfo">Cargando...</div>
                </div>
                
                <h3 style="margin-bottom: 16px;">Todos los Perfiles</h3>
                <div class="perfiles-grid" id="perfilesGrid">
                    <div class="empty-state">Cargando perfiles...</div>
                </div>
            </div>
            
            <!-- TAB TESTIMONIOS -->
            <div id="tab-testimonios" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Testimonios del Sitio</h3>
                    <button class="btn btn-primary" onclick="abrirModalTestimonio()">+ Agregar Testimonio</button>
                </div>
                <div class="testimonios-list" id="testimoniosList">
                    <div class="empty-state">Cargando testimonios...</div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL EDITAR PERFIL -->
    <div id="modalPerfil" class="modal">
        <div class="modal-content">
            <div class="modal-header">Editar Perfil</div>
            <form id="formPerfil">
                <input type="hidden" id="perfilId" name="id">
                
                <div class="form-group">
                    <label>Nombre del Perfil</label>
                    <input type="text" id="perfilNombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label>Título Principal (Hero)</label>
                    <input type="text" id="perfilTitulo" name="titulo_hero" placeholder="Ej: Renueva tu Energía">
                </div>
                
                <div class="form-group">
                    <label>Subtítulo</label>
                    <input type="text" id="perfilSubtitulo" name="subtitulo_hero" placeholder="Ej: Masajes terapéuticos y tratamientos de belleza">
                </div>
                
                <div class="form-group">
                    <label>Mensaje de Bienvenida</label>
                    <textarea id="perfilMensaje" name="mensaje_bienvenida"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Estado Operativo</label>
                    <select id="perfilEstado" name="estado_operativo">
                        <option value="operativo">Operativo</option>
                        <option value="no_operativo">No Operativo</option>
                        <option value="vacaciones">Vacaciones</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Mensaje de Estado (si no está operativo)</label>
                    <input type="text" id="perfilMensajeEstado" name="mensaje_estado" placeholder="Ej: Cerrado por vacaciones hasta el 01/12/2025">
                </div>
                
                <div class="form-group">
                    <label>Horarios de Atención</label>
                    <textarea id="perfilHorarios" name="horarios_texto" placeholder="Lunes a Viernes: 9:00 - 18:00&#10;Sábados: 9:00 - 14:00"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Teléfono Público</label>
                    <input type="text" id="perfilTelefono" name="telefono_publico">
                </div>
                
                <div class="form-group">
                    <label>WhatsApp</label>
                    <input type="text" id="perfilWhatsapp" name="whatsapp">
                </div>
                
                <div class="form-group">
                    <label>Email Público</label>
                    <input type="email" id="perfilEmail" name="email_publico">
                </div>
                
                <div class="form-group">
                    <label>Dirección Visible</label>
                    <textarea id="perfilDireccion" name="direccion_visible"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Instagram (usuario)</label>
                    <input type="text" id="perfilInstagram" name="instagram" placeholder="@usuario">
                </div>
                
                <div class="form-group">
                    <label>Facebook (usuario)</label>
                    <input type="text" id="perfilFacebook" name="facebook">
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalPerfil()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TESTIMONIO -->
    <div id="modalTestimonio" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">Agregar Testimonio</div>
            <form id="formTestimonio">
                <input type="hidden" id="testimonioId" name="id">
                
                <div class="form-group">
                    <label>Nombre del Cliente</label>
                    <input type="text" id="testimonioNombre" name="nombre_cliente" required>
                </div>
                
                <div class="form-group">
                    <label>Testimonio</label>
                    <textarea id="testimonioTexto" name="testimonio" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Calificación</label>
                    <select id="testimonioCalificacion" name="calificacion">
                        <option value="5">⭐⭐⭐⭐⭐ (5 estrellas)</option>
                        <option value="4">⭐⭐⭐⭐ (4 estrellas)</option>
                        <option value="3">⭐⭐⭐ (3 estrellas)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="testimonioActivo" name="activo" checked style="width: auto;">
                        Mostrar en el sitio público
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalTestimonio()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_URL = '../api/configuracion.php';
        let perfiles = [];
        let testimonios = [];
        
        cargarPerfiles();
        cargarTestimonios();
        
        function cambiarTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        }
        
        async function cargarPerfiles() {
            try {
                const res = await fetch(API_URL + '?action=listar_perfiles');
                const data = await res.json();
                if (data.success) {
                    perfiles = data.data;
                    renderizarPerfiles();
                }
            } catch (err) {
                console.error(err);
            }
        }
        
        function renderizarPerfiles() {
            const activo = perfiles.find(p => p.activo == 1);
            
            if (activo) {
                document.getElementById('perfilActivoInfo').innerHTML = `
                    <h4 style="font-size: 20px; margin-bottom: 8px;">${activo.nombre}</h4>
                    <div style="margin-bottom: 8px;">
                        <span class="status">${activo.estado_operativo == 'operativo' ? 'OPERATIVO' : 'NO OPERATIVO'}</span>
                    </div>
                    <p style="color: #a0a0a0; font-size: 14px; margin-bottom: 4px;">
                        📞 ${activo.telefono_publico || 'Sin teléfono'} | 
                        💬 ${activo.whatsapp || 'Sin WhatsApp'}
                    </p>
                    <p style="color: #a0a0a0; font-size: 14px;">${activo.titulo_hero || 'Sin título configurado'}</p>
                `;
            }
            
            const grid = document.getElementById('perfilesGrid');
            grid.innerHTML = perfiles.map(p => `
                <div class="perfil-card ${p.activo == 1 ? 'activo' : ''}">
                    <div class="perfil-card-header">
                        <h4>${p.nombre}</h4>
                        <span class="badge ${p.activo == 1 ? 'activo' : 'inactivo'}">
                            ${p.activo == 1 ? '● Activo' : '○ Inactivo'}
                        </span>
                    </div>
                    <div class="perfil-card-body">
                        <div>Estado: <strong>${p.estado_operativo}</strong></div>
                        <div>Tel: ${p.telefono_publico || 'No configurado'}</div>
                    </div>
                    <div class="perfil-card-actions">
                        ${p.activo != 1 ? `<button class="btn btn-success btn-sm" onclick="activarPerfil(${p.id})">Activar</button>` : ''}
                        <button class="btn btn-primary btn-sm" onclick="editarPerfil(${p.id})">Editar</button>
                    </div>
                </div>
            `).join('');
        }
        
        async function activarPerfil(id) {
            if (!confirm('¿Cambiar el perfil activo? El sitio público se actualizará inmediatamente.')) return;
            
            const formData = new FormData();
            formData.append('action', 'activar_perfil');
            formData.append('id', id);
            
            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    await cargarPerfiles();
                } else {
                    alert(data.mensaje);
                }
            } catch (err) {
                alert('Error de conexión');
            }
        }
        
        function editarPerfil(id) {
            const perfil = perfiles.find(p => p.id == id);
            if (!perfil) return;
            
            document.getElementById('perfilId').value = perfil.id;
            document.getElementById('perfilNombre').value = perfil.nombre;
            document.getElementById('perfilTitulo').value = perfil.titulo_hero || '';
            document.getElementById('perfilSubtitulo').value = perfil.subtitulo_hero || '';
            document.getElementById('perfilMensaje').value = perfil.mensaje_bienvenida || '';
            document.getElementById('perfilEstado').value = perfil.estado_operativo;
            document.getElementById('perfilMensajeEstado').value = perfil.mensaje_estado || '';
            document.getElementById('perfilHorarios').value = perfil.horarios_texto || '';
            document.getElementById('perfilTelefono').value = perfil.telefono_publico || '';
            document.getElementById('perfilWhatsapp').value = perfil.whatsapp || '';
            document.getElementById('perfilEmail').value = perfil.email_publico || '';
            document.getElementById('perfilDireccion').value = perfil.direccion_visible || '';
            document.getElementById('perfilInstagram').value = perfil.instagram || '';
            document.getElementById('perfilFacebook').value = perfil.facebook || '';
            
            document.getElementById('modalPerfil').classList.add('show');
        }
        
        function cerrarModalPerfil() {
            document.getElementById('modalPerfil').classList.remove('show');
        }
        
        document.getElementById('formPerfil').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'guardar_perfil');
            
            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    cerrarModalPerfil();
                    await cargarPerfiles();
                } else {
                    alert(data.mensaje);
                }
            } catch (err) {
                alert('Error de conexión');
            }
        });
        
        async function cargarTestimonios() {
            try {
                const res = await fetch(API_URL + '?action=listar_testimonios');
                const data = await res.json();
                if (data.success) {
                    testimonios = data.data;
                    renderizarTestimonios();
                }
            } catch (err) {
                console.error(err);
            }
        }
        
        function renderizarTestimonios() {
            const list = document.getElementById('testimoniosList');
            if (testimonios.length === 0) {
                list.innerHTML = '<div class="empty-state"><p>No hay testimonios aún</p></div>';
                return;
            }
            
            list.innerHTML = testimonios.map(t => `
                <div class="testimonio-card">
                    <div class="testimonio-header">
                        <h4>${t.nombre_cliente}</h4>
                        <div>
                            <span class="stars">${'⭐'.repeat(t.calificacion)}</span>
                            <span class="badge ${t.activo == 1 ? 'activo' : 'inactivo'}" style="margin-left: 8px;">
                                ${t.activo == 1 ? 'Visible' : 'Oculto'}
                            </span>
                        </div>
                    </div>
                    <div class="testimonio-text">"${t.testimonio}"</div>
                    <div class="testimonio-actions">
                        <button class="btn btn-primary btn-sm" onclick="editarTestimonio(${t.id})">Editar</button>
                        <button class="btn btn-danger btn-sm" onclick="eliminarTestimonio(${t.id})">Eliminar</button>
                    </div>
                </div>
            `).join('');
        }
        
        function abrirModalTestimonio() {
            document.getElementById('formTestimonio').reset();
            document.getElementById('testimonioId').value = '';
            document.getElementById('modalTestimonio').classList.add('show');
        }
        
        function editarTestimonio(id) {
            const test = testimonios.find(t => t.id == id);
            if (!test) return;
            
            document.getElementById('testimonioId').value = test.id;
            document.getElementById('testimonioNombre').value = test.nombre_cliente;
            document.getElementById('testimonioTexto').value = test.testimonio;
            document.getElementById('testimonioCalificacion').value = test.calificacion;
            document.getElementById('testimonioActivo').checked = test.activo == 1;
            
            document.getElementById('modalTestimonio').classList.add('show');
        }
        
        function cerrarModalTestimonio() {
            document.getElementById('modalTestimonio').classList.remove('show');
        }
        
        document.getElementById('formTestimonio').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'guardar_testimonio');
            
            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    cerrarModalTestimonio();
                    await cargarTestimonios();
                } else {
                    alert(data.mensaje);
                }
            } catch (err) {
                alert('Error de conexión');
            }
        });
        
        async function eliminarTestimonio(id) {
            if (!confirm('¿Eliminar este testimonio?')) return;
            
            const formData = new FormData();
            formData.append('action', 'eliminar_testimonio');
            formData.append('id', id);
            
            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    await cargarTestimonios();
                } else {
                    alert(data.mensaje);
                }
            } catch (err) {
                alert('Error de conexión');
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
