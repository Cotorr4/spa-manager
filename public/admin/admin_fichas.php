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
    <title>Fichas de Salud - Spa Manager</title>
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
        .top-bar-actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #10b981; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-danger { background: #ef4444; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-secondary { background: #6b7280; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-info { background: #3b82f6; color: white; padding: 8px 16px; font-size: 13px; }
        
        .search-bar { margin-bottom: 20px; }
        .search-bar input { width: 100%; max-width: 400px; padding: 10px 12px; background: #2d2d2d; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; font-size: 14px; }
        
        table { width: 100%; background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; border-collapse: collapse; overflow: hidden; }
        th { background: #1a1a1a; padding: 12px; text-align: left; font-size: 13px; font-weight: 500; color: #a0a0a0; border-bottom: 1px solid #404040; }
        td { padding: 12px; font-size: 14px; border-bottom: 1px solid #404040; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #404040; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #10b981; color: white; }
        .badge-warning { background: #f59e0b; color: white; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; overflow-y: auto; padding: 20px; }
        .modal.show { display: block; }
        .modal-content { background: #2d2d2d; border: 1px solid #404040; border-radius: 8px; padding: 30px; max-width: 900px; margin: 20px auto; }
        .modal-header { font-size: 20px; font-weight: 500; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #404040; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { display: block; font-size: 13px; color: #a0a0a0; margin-bottom: 6px; }
        input, textarea, select { width: 100%; padding: 10px 12px; background: #1a1a1a; border: 1px solid #404040; border-radius: 4px; color: #f5f5f5; font-size: 14px; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #2563eb; }
        textarea { resize: vertical; min-height: 60px; }
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .checkbox-group label { margin: 0; }
        
        .section-title { font-size: 16px; font-weight: 500; margin: 24px 0 12px; padding-bottom: 8px; border-bottom: 1px solid #404040; grid-column: 1 / -1; }
        .modal-actions { display: flex; gap: 10px; margin-top: 24px; padding-top: 24px; border-top: 1px solid #404040; }
        .loading { text-align: center; padding: 40px; color: #a0a0a0; }
        .empty-state { text-align: center; padding: 60px 20px; color: #a0a0a0; }
        .empty-state h3 { margin-bottom: 10px; color: #f5f5f5; }
        
        .cliente-info { background: #1a1a1a; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
        .cliente-info h3 { font-size: 18px; margin-bottom: 8px; }
        .cliente-info p { font-size: 14px; color: #a0a0a0; margin: 4px 0; }
        .cliente-codigo { display: inline-block; background: #2563eb; color: white; padding: 4px 12px; border-radius: 4px; font-weight: 500; }
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
                    <li><a href="admin_fichas.php" class="active">Fichas de Salud</a></li>
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
                <h2>Fichas de Salud</h2>
                <div class="top-bar-actions">
                    <button class="btn btn-primary" onclick="seleccionarCliente()">+ Nueva Ficha</button>
                </div>
            </div>
            
            <div class="search-bar">
                <input type="text" id="busqueda" placeholder="Buscar por nombre o código de cliente..." onkeyup="buscar()">
            </div>
            
            <div id="contenidoTabla">
                <div class="loading">Cargando fichas...</div>
            </div>
        </main>
    </div>

    <!-- MODAL SELECCIONAR CLIENTE -->
    <div id="modalSeleccionCliente" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">Seleccionar Cliente</div>
            <div class="search-bar">
                <input type="text" id="busquedaCliente" placeholder="Buscar cliente..." onkeyup="buscarCliente()">
            </div>
            <div id="listaClientes" style="max-height: 400px; overflow-y: auto;"></div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="cerrarModalSeleccion()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- MODAL FORMULARIO FICHA -->
    <div id="modalFicha" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitulo">Ficha de Salud</div>
            
            <div class="cliente-info" id="clienteInfo"></div>
            
            <form id="formFicha">
                <input type="hidden" id="clienteId" name="cliente_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="sexo">Sexo</label>
                        <select id="sexo" name="sexo">
                            <option value="">Seleccionar...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tratamiento_recomendado">Tratamiento Recomendado</label>
                        <input type="text" id="tratamiento_recomendado" name="tratamiento_recomendado">
                    </div>
                    
                    <div class="section-title">Datos Clínicos - ¿Padece alguna de las siguientes condiciones?</div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_diabetes" name="clinica_diabetes">
                            <label for="clinica_diabetes">Diabetes</label>
                        </div>
                        <input type="text" id="clinica_diabetes_esp" name="clinica_diabetes_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_hipertension" name="clinica_hipertension">
                            <label for="clinica_hipertension">Hipertensión</label>
                        </div>
                        <input type="text" id="clinica_hipertension_esp" name="clinica_hipertension_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_cardiacas" name="clinica_enfermedades_cardiacas">
                            <label for="clinica_enfermedades_cardiacas">Enfermedades Cardíacas</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_cardiacas_esp" name="clinica_enfermedades_cardiacas_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_circulatorias" name="clinica_enfermedades_circulatorias">
                            <label for="clinica_enfermedades_circulatorias">Enfermedades Circulatorias</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_circulatorias_esp" name="clinica_enfermedades_circulatorias_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_respiratorias" name="clinica_enfermedades_respiratorias">
                            <label for="clinica_enfermedades_respiratorias">Enfermedades Respiratorias</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_respiratorias_esp" name="clinica_enfermedades_respiratorias_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_renales" name="clinica_enfermedades_renales">
                            <label for="clinica_enfermedades_renales">Enfermedades Renales</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_renales_esp" name="clinica_enfermedades_renales_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_digestivas" name="clinica_enfermedades_digestivas">
                            <label for="clinica_enfermedades_digestivas">Enfermedades Digestivas</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_digestivas_esp" name="clinica_enfermedades_digestivas_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_endocrinas" name="clinica_enfermedades_endocrinas">
                            <label for="clinica_enfermedades_endocrinas">Enfermedades Endocrinas</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_endocrinas_esp" name="clinica_enfermedades_endocrinas_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_neurologicas" name="clinica_enfermedades_neurologicas">
                            <label for="clinica_enfermedades_neurologicas">Enfermedades Neurológicas</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_neurologicas_esp" name="clinica_enfermedades_neurologicas_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_dermatologicas" name="clinica_enfermedades_dermatologicas">
                            <label for="clinica_enfermedades_dermatologicas">Enfermedades Dermatológicas</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_dermatologicas_esp" name="clinica_enfermedades_dermatologicas_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_hematologicas" name="clinica_enfermedades_hematologicas">
                            <label for="clinica_enfermedades_hematologicas">Enfermedades Hematológicas</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_hematologicas_esp" name="clinica_enfermedades_hematologicas_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_enfermedades_pulmonares" name="clinica_enfermedades_pulmonares">
                            <label for="clinica_enfermedades_pulmonares">Enfermedades Pulmonares</label>
                        </div>
                        <input type="text" id="clinica_enfermedades_pulmonares_esp" name="clinica_enfermedades_pulmonares_esp" placeholder="Especificación / medicamentos">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_alergias" name="clinica_alergias">
                            <label for="clinica_alergias">Alergias</label>
                        </div>
                        <input type="text" id="clinica_alergias_esp" name="clinica_alergias_esp" placeholder="Especificación">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="clinica_problemas_presion" name="clinica_problemas_presion">
                            <label for="clinica_problemas_presion">Problemas de Presión</label>
                        </div>
                        <input type="text" id="clinica_problemas_presion_esp" name="clinica_problemas_presion_esp" placeholder="Especificación">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="clinica_otros">Otros</label>
                        <textarea id="clinica_otros" name="clinica_otros" placeholder="Especifique otras condiciones o medicamentos"></textarea>
                    </div>
                    
                    <div class="section-title">Hábitos Alimenticios</div>
                    
                    <div class="form-group">
                        <label for="alim_comidas_dia">N° de comidas al día</label>
                        <input type="number" id="alim_comidas_dia" name="alim_comidas_dia" min="0" max="10">
                    </div>
                    
                    <div class="form-group">
                        <label for="alim_agua_litros">Ingesta de agua al día (litros)</label>
                        <input type="number" id="alim_agua_litros" name="alim_agua_litros" min="0" step="0.5">
                    </div>
                    
                    <div class="form-group">
                        <label for="alim_verduras_semanal">Ingesta semanal de verduras (días)</label>
                        <input type="number" id="alim_verduras_semanal" name="alim_verduras_semanal" min="0" max="7">
                    </div>
                    
                    <div class="form-group">
                        <label for="alim_frutas_semanal">Ingesta semanal de frutas (días)</label>
                        <input type="number" id="alim_frutas_semanal" name="alim_frutas_semanal" min="0" max="7">
                    </div>
                    
                    <div class="form-group">
                        <label for="alim_carne_roja_semanal">Ingesta semanal de carne roja (días)</label>
                        <input type="number" id="alim_carne_roja_semanal" name="alim_carne_roja_semanal" min="0" max="7">
                    </div>
                    
                    <div class="form-group">
                        <label for="alim_carne_blanca_semanal">Ingesta semanal de carne blanca (días)</label>
                        <input type="number" id="alim_carne_blanca_semanal" name="alim_carne_blanca_semanal" min="0" max="7">
                    </div>
                    
                    <div class="form-group">
                        <label for="alim_legumbres_semanal">Ingesta semanal de legumbres (días)</label>
                        <input type="number" id="alim_legumbres_semanal" name="alim_legumbres_semanal" min="0" max="7">
                    </div>
                    
                    <div class="form-group">
                        <label for="alim_cereales_semanal">Ingesta semanal de cereales (días)</label>
                        <input type="number" id="alim_cereales_semanal" name="alim_cereales_semanal" min="0" max="7">
                    </div>
                    
                    <div class="section-title">Hábitos de Actividad Física</div>
                    
                    <div class="form-group full-width">
                        <div class="checkbox-group">
                            <input type="checkbox" id="act_realiza_ejercicio" name="act_realiza_ejercicio">
                            <label for="act_realiza_ejercicio">¿Realiza algún deporte o ejercicio?</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="act_tipo_ejercicio">Tipo de ejercicio</label>
                        <input type="text" id="act_tipo_ejercicio" name="act_tipo_ejercicio" placeholder="Ej: Yoga, caminata, natación">
                    </div>
                    
                    <div class="form-group">
                        <label for="act_horas_semanales">Horas a la semana</label>
                        <input type="number" id="act_horas_semanales" name="act_horas_semanales" min="0" step="0.5">
                    </div>
                    
                    <div class="section-title">Hábitos de Sueño</div>
                    
                    <div class="form-group">
                        <label for="sueno_horas">Horas de sueño</label>
                        <input type="number" id="sueno_horas" name="sueno_horas" min="0" max="24" step="0.5">
                    </div>
                    
                    <div class="form-group">
                        <label for="sueno_calidad">Calidad del descanso</label>
                        <select id="sueno_calidad" name="sueno_calidad">
                            <option value="">Seleccionar...</option>
                            <option value="Muy buen descanso">Muy buen descanso</option>
                            <option value="Buen descanso">Buen descanso</option>
                            <option value="Descanso medio">Descanso medio</option>
                            <option value="Descanso malo">Descanso malo</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <div class="checkbox-group">
                            <input type="checkbox" id="completada" name="completada" checked>
                            <label for="completada">Marcar ficha como completada</label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar Ficha</button>
                    <button type="button" class="btn btn-danger" id="btnEliminar" onclick="eliminarFicha()" style="display: none;">Eliminar Ficha</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalFicha()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let fichas = [];
        let clientesDisponibles = [];
        let fichaActual = null;
        const API_URL = '../api/fichas.php';
        
        cargarFichas();
        
        async function cargarFichas() {
            try {
                const res = await fetch(API_URL + '?action=listar');
                const data = await res.json();
                
                if (data.success) {
                    fichas = data.data;
                    renderizarTabla();
                } else {
                    throw new Error(data.mensaje || 'Error al cargar');
                }
            } catch (err) {
                console.error('Error:', err);
                document.getElementById('contenidoTabla').innerHTML = '<div class="empty-state"><h3>Error al cargar fichas</h3></div>';
            }
        }
        
        function renderizarTabla() {
            const contenedor = document.getElementById('contenidoTabla');
            
            if (fichas.length === 0) {
                contenedor.innerHTML = '<div class="empty-state"><h3>No hay fichas de salud</h3><p>Comienza creando la primera ficha para un cliente</p></div>';
                return;
            }
            
            let html = '<table><thead><tr><th>Código</th><th>Cliente</th><th>Teléfono</th><th>Estado</th><th>Última Actualización</th><th>Acciones</th></tr></thead><tbody>';
            
            fichas.forEach(f => {
                const estado = f.completada == 1 
                    ? '<span class="badge badge-success">Completada</span>'
                    : '<span class="badge badge-warning">Incompleta</span>';
                
                const fecha = new Date(f.fecha_actualizacion).toLocaleDateString('es-CL');
                
                html += `
                    <tr>
                        <td><strong class="cliente-codigo">${f.cliente_codigo}</strong></td>
                        <td>${f.cliente_nombre}</td>
                        <td>${f.telefono || '-'}</td>
                        <td>${estado}</td>
                        <td>${fecha}</td>
                        <td>
                            <button class="btn btn-primary" onclick="editarFicha(${f.cliente_id})">Ver/Editar</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            contenedor.innerHTML = html;
        }
        
        let debounceTimer;
        function buscar() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                const busqueda = document.getElementById('busqueda').value;
                const res = await fetch(API_URL + '?action=listar&busqueda=' + encodeURIComponent(busqueda));
                const data = await res.json();
                if (data.success) {
                    fichas = data.data;
                    renderizarTabla();
                }
            }, 300);
        }
        
        async function seleccionarCliente() {
            try {
                const res = await fetch(API_URL + '?action=listar');
                const dataFichas = await res.json();
                
                const res2 = await fetch('../api/clientes.php?action=listar&activos=1');
                const dataClientes = await res2.json();
                
                if (dataClientes.success) {
                    const fichasIds = dataFichas.data.map(f => f.cliente_id);
                    clientesDisponibles = dataClientes.data.filter(c => !fichasIds.includes(c.id));
                    
                    renderizarListaClientes(clientesDisponibles);
                    document.getElementById('modalSeleccionCliente').classList.add('show');
                }
            } catch (err) {
                alert('Error al cargar clientes');
            }
        }
        
        function renderizarListaClientes(clientes) {
            const lista = document.getElementById('listaClientes');
            
            if (clientes.length === 0) {
                lista.innerHTML = '<div class="empty-state"><p>Todos los clientes ya tienen ficha de salud</p></div>';
                return;
            }
            
            let html = '<div style="display: grid; gap: 10px;">';
            clientes.forEach(c => {
                html += `
                    <div style="background: #1a1a1a; padding: 12px; border-radius: 4px; cursor: pointer; border: 1px solid #404040;" onclick="abrirFichaCliente(${c.id})">
                        <strong>${c.nombre}</strong> 
                        <span class="cliente-codigo" style="margin-left: 10px;">${c.cliente_codigo}</span>
                        <div style="font-size: 13px; color: #a0a0a0; margin-top: 4px;">${c.telefono || 'Sin teléfono'}</div>
                    </div>
                `;
            });
            html += '</div>';
            lista.innerHTML = html;
        }
        
        function buscarCliente() {
            const busqueda = document.getElementById('busquedaCliente').value.toLowerCase();
            const filtrados = clientesDisponibles.filter(c => 
                c.nombre.toLowerCase().includes(busqueda) || 
                c.cliente_codigo.toLowerCase().includes(busqueda)
            );
            renderizarListaClientes(filtrados);
        }
        
        function cerrarModalSeleccion() {
            document.getElementById('modalSeleccionCliente').classList.remove('show');
        }
        
        async function abrirFichaCliente(clienteId) {
            cerrarModalSeleccion();
            editarFicha(clienteId);
        }
        
        async function editarFicha(clienteId) {
            try {
                const res = await fetch(API_URL + '?action=obtener&cliente_id=' + clienteId);
                const data = await res.json();
                
                if (data.success) {
                    fichaActual = data.data;
                    cargarFormulario(data.data);
                    document.getElementById('modalFicha').classList.add('show');
                }
            } catch (err) {
                alert('Error al cargar ficha');
            }
        }
        
        function cargarFormulario(datos) {
            document.getElementById('modalTitulo').textContent = datos.ficha_nueva ? 'Nueva Ficha de Salud' : 'Editar Ficha de Salud';
            
            // Info del cliente
            const edad = datos.fecha_nacimiento ? calcularEdad(datos.fecha_nacimiento) : '-';
            document.getElementById('clienteInfo').innerHTML = `
                <h3>${datos.cliente_nombre} <span class="cliente-codigo">${datos.cliente_codigo}</span></h3>
                <p><strong>Edad:</strong> ${edad} años | <strong>Teléfono:</strong> ${datos.telefono || '-'} | <strong>Email:</strong> ${datos.email || '-'}</p>
            `;
            
            document.getElementById('clienteId').value = datos.cliente_id;
            document.getElementById('sexo').value = datos.sexo || '';
            document.getElementById('tratamiento_recomendado').value = datos.tratamiento_recomendado || '';
            
            // Datos clínicos
            const clinicos = datos.datos_clinicos || {};
            cargarCampoClinico('diabetes', clinicos.diabetes);
            cargarCampoClinico('hipertension', clinicos.hipertension);
            cargarCampoClinico('enfermedades_cardiacas', clinicos.enfermedades_cardiacas);
            cargarCampoClinico('enfermedades_circulatorias', clinicos.enfermedades_circulatorias);
            cargarCampoClinico('enfermedades_respiratorias', clinicos.enfermedades_respiratorias);
            cargarCampoClinico('enfermedades_renales', clinicos.enfermedades_renales);
            cargarCampoClinico('enfermedades_digestivas', clinicos.enfermedades_digestivas);
            cargarCampoClinico('enfermedades_endocrinas', clinicos.enfermedades_endocrinas);
            cargarCampoClinico('enfermedades_neurologicas', clinicos.enfermedades_neurologicas);
            cargarCampoClinico('enfermedades_dermatologicas', clinicos.enfermedades_dermatologicas);
            cargarCampoClinico('enfermedades_hematologicas', clinicos.enfermedades_hematologicas);
            cargarCampoClinico('enfermedades_pulmonares', clinicos.enfermedades_pulmonares);
            cargarCampoClinico('alergias', clinicos.alergias);
            cargarCampoClinico('problemas_presion', clinicos.problemas_presion);
            document.getElementById('clinica_otros').value = clinicos.otros || '';
            
            // Hábitos alimenticios
            const alim = datos.habitos_alimenticios || {};
            document.getElementById('alim_comidas_dia').value = alim.comidas_dia || '';
            document.getElementById('alim_agua_litros').value = alim.agua_litros || '';
            document.getElementById('alim_verduras_semanal').value = alim.verduras_semanal || '';
            document.getElementById('alim_frutas_semanal').value = alim.frutas_semanal || '';
            document.getElementById('alim_carne_roja_semanal').value = alim.carne_roja_semanal || '';
            document.getElementById('alim_carne_blanca_semanal').value = alim.carne_blanca_semanal || '';
            document.getElementById('alim_legumbres_semanal').value = alim.legumbres_semanal || '';
            document.getElementById('alim_cereales_semanal').value = alim.cereales_semanal || '';
            
            // Actividad física
            const act = datos.actividad_fisica || {};
            document.getElementById('act_realiza_ejercicio').checked = act.realiza_ejercicio || false;
            document.getElementById('act_tipo_ejercicio').value = act.tipo_ejercicio || '';
            document.getElementById('act_horas_semanales').value = act.horas_semanales || '';
            
            // Sueño
            const sueno = datos.habitos_sueno || {};
            document.getElementById('sueno_horas').value = sueno.horas_sueno || '';
            document.getElementById('sueno_calidad').value = sueno.calidad || '';
            
            document.getElementById('completada').checked = datos.completada == 1 || datos.ficha_nueva;
            
            // Mostrar botón eliminar solo si existe ficha
            document.getElementById('btnEliminar').style.display = datos.ficha_nueva ? 'none' : 'inline-block';
        }
        
        function cargarCampoClinico(campo, valor) {
            document.getElementById('clinica_' + campo).checked = valor?.tiene || false;
            document.getElementById('clinica_' + campo + '_esp').value = valor?.especificacion || '';
        }
        
        function calcularEdad(fechaNacimiento) {
            const hoy = new Date();
            const nacimiento = new Date(fechaNacimiento);
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }
            return edad;
        }
        
        function cerrarModalFicha() {
            document.getElementById('modalFicha').classList.remove('show');
            document.getElementById('formFicha').reset();
            fichaActual = null;
        }
        
        document.getElementById('formFicha').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btnSubmit = e.target.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Guardando...';
            
            try {
                const formData = new FormData(e.target);
                formData.append('action', 'guardar');
                
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    cerrarModalFicha();
                    await cargarFichas();
                } else {
                    alert(data.mensaje || 'Error al guardar');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Guardar Ficha';
            }
        });
        
        async function eliminarFicha() {
            if (!fichaActual || !fichaActual.cliente_id) return;
            
            if (!confirm('¿Eliminar esta ficha de salud? Esta acción no se puede deshacer.')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'eliminar');
                formData.append('cliente_id', fichaActual.cliente_id);
                
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    cerrarModalFicha();
                    await cargarFichas();
                } else {
                    alert(data.mensaje || 'Error al eliminar');
                }
            } catch (err) {
                alert('Error: ' + err.message);
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
