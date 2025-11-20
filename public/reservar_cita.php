<?php
require_once __DIR__ . '/../private/database/config_public.php';
$tratamiento_pre = $_GET['t'] ?? '';
$trats = $conn->query("SELECT id, nombre FROM tratamientos WHERE activo = 1");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Cita</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        /* Estilos Formulario */
        .form-container { max-width: 600px; margin: 40px auto; padding: 20px; background: #1a1a1a; border-radius: 8px; }
        .step-title { color: var(--accent); font-family: 'Oswald'; font-size: 1.1rem; margin-bottom: 15px; border-bottom: 1px solid #333; padding-bottom: 5px; text-transform: uppercase; }
        .form-group { margin-bottom: 25px; }
        label { display: block; color: #bbb; font-size: 0.9rem; margin-bottom: 8px; }
        input, select { width: 100%; padding: 12px; background: #252525; border: 1px solid #444; color: white; border-radius: 4px; font-size: 1rem; font-family: 'Roboto Condensed'; }
        input:focus, select:focus { border-color: var(--accent); outline: none; }
        
        .horas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; margin-top: 10px; }
        .hora-btn { background: #252525; border: 1px solid #444; color: white; padding: 10px; border-radius: 4px; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 3px; }
        .hora-btn:hover { border-color: var(--accent); transform: translateY(-2px); }
        .hora-btn.selected { background: var(--accent); border-color: var(--accent); box-shadow: 0 0 10px rgba(59, 130, 246, 0.3); }
        .hora-time { font-family: 'Oswald'; font-size: 1.1rem; }
        .hora-loc { font-size: 0.7rem; color: #888; text-align: center; line-height: 1.2; }
        .hora-btn.selected .hora-loc { color: #eee; }
        .loading-horas { color: var(--accent); font-style: italic; margin-top: 10px; display: none; text-align: center; }
        .empty-msg { grid-column: 1/-1; text-align: center; padding: 20px; color: #ef4444; background: rgba(239,68,68,0.1); border-radius: 4px; }

        /* Estilos Pantalla Exito */
        .success-screen { text-align: center; padding: 40px 20px; animation: fadeIn 0.5s; }
        .success-icon { font-size: 60px; margin-bottom: 20px; }
        .client-code { background: #333; padding: 10px 20px; border-radius: 8px; font-family: 'Oswald'; font-size: 2rem; color: var(--accent); letter-spacing: 2px; margin: 20px 0; display: inline-block; border: 1px dashed #555; }
        .action-btn { display: block; width: 100%; padding: 15px; margin: 10px 0; text-decoration: none; border-radius: 6px; font-family: 'Oswald'; text-transform: uppercase; }
        .btn-whatsapp { background: #25D366; color: white; border: none; }
        .btn-ficha { background: #2563eb; color: white; border: none; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>

<div class="app-container" id="main-container">
    <header style="padding: 20px; text-align: center; background: #151515; border-bottom: 1px solid #333;">
        <a href="index.php" class="btn-outline" style="float: left; padding: 5px 15px; font-size: 0.8rem; border-radius: 20px;">← Volver</a>
        <h1 style="margin: 0; font-size: 1.4rem; letter-spacing: 1px;">AGENDA TU CITA</h1>
    </header>

    <div class="form-container">
        <form id="reservaForm">
            <div class="form-group">
                <div class="step-title">1. Selecciona el Servicio</div>
                <select id="tratamiento" required>
                    <option value="">-- Elige un tratamiento --</option>
                    <?php while($t = $trats->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($t['nombre']); ?>" <?php echo ($tratamiento_pre == $t['nombre']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <div class="step-title">2. Elige Fecha y Hora</div>
                <label>Fecha deseada</label>
                <input type="date" id="fecha" required min="<?php echo date('Y-m-d'); ?>">
                <div id="contenedor-horas" style="display:none; margin-top: 20px;">
                    <label>Horarios Disponibles</label>
                    <div id="loading-horas" class="loading-horas">Consultando agenda...</div>
                    <div id="horas-grid" class="horas-grid"></div>
                    <input type="hidden" id="hora_seleccionada" required>
                    <input type="hidden" id="ubicacion_seleccionada" required>
                </div>
            </div>

            <div class="form-group">
                <div class="step-title">3. Tus Datos</div>
                <label>Nombre Completo</label>
                <input type="text" id="nombre" placeholder="Tu nombre" required>
            </div>
            <div class="form-group">
                <label>WhatsApp de contacto</label>
                <input type="tel" id="telefono" placeholder="+51 ..." required>
            </div>

            <button type="submit" class="btn" style="width: 100%; padding: 15px; font-size: 1.1rem; margin-top: 10px;">CONFIRMAR RESERVA</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('fecha').addEventListener('change', cargarHoras);

    async function cargarHoras() {
        const fecha = this.value;
        if(!fecha) return;
        const cont = document.getElementById('contenedor-horas');
        const grid = document.getElementById('horas-grid');
        const load = document.getElementById('loading-horas');
        
        document.getElementById('hora_seleccionada').value = '';
        cont.style.display = 'block'; grid.innerHTML = ''; load.style.display = 'block';

        try {
            const res = await fetch(`api/reservar.php?action=get_disponibilidad&fecha=${fecha}`);
            const data = await res.json();
            load.style.display = 'none';
            if(data.success && data.slots.length > 0) {
                data.slots.forEach(slot => {
                    const btn = document.createElement('div');
                    btn.className = 'hora-btn';
                    const icon = slot.es_domicilio == 1 ? '🏠' : '📍';
                    btn.innerHTML = `<span class="hora-time">${slot.hora}</span><span class="hora-loc">${icon} ${slot.ubicacion}</span>`;
                    btn.onclick = () => seleccionarHora(btn, slot.hora, slot.ubicacion_id);
                    grid.appendChild(btn);
                });
            } else {
                grid.innerHTML = '<div class="empty-msg">No hay horarios disponibles para esta fecha.</div>';
            }
        } catch(e) { load.textContent = 'Error al cargar horas.'; }
    }

    function seleccionarHora(btn, hora, ubicacionId) {
        document.querySelectorAll('.hora-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('hora_seleccionada').value = hora;
        document.getElementById('ubicacion_seleccionada').value = ubicacionId;
    }

    document.getElementById('reservaForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const data = {
            tratamiento_nombre: document.getElementById('tratamiento').value,
            fecha: document.getElementById('fecha').value,
            hora: document.getElementById('hora_seleccionada').value,
            ubicacion_id: document.getElementById('ubicacion_seleccionada').value,
            nombre: document.getElementById('nombre').value,
            telefono: document.getElementById('telefono').value
        };

        if(!data.hora) { alert('⚠️ Por favor selecciona un horario disponible.'); return; }
        btn.disabled = true; btn.textContent = 'Procesando...';

        try {
            const res = await fetch('api/reservar.php?action=crear_reserva', {
                method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
            });
            const resp = await res.json();
            
            if(resp.success) {
                // --- PANTALLA DE ÉXITO DINÁMICA ---
                const msgWhatsapp = `Hola, soy ${data.nombre} (${resp.cliente_codigo}). Acabo de reservar para el ${data.fecha} a las ${data.hora}.`;
                const linkWhatsapp = `https://wa.me/?text=${encodeURIComponent(msgWhatsapp)}`; // Auto-envío a uno mismo (o se puede poner el número del spa)
                
                // Enlace para mandárselo al Spa
                const linkSpa = `https://wa.me/51951342753?text=${encodeURIComponent(msgWhatsapp)}`;

                document.getElementById('main-container').innerHTML = `
                    <div class="success-screen">
                        <div class="success-icon">🎉</div>
                        <h1 style="font-family:'Oswald'; line-height:1.2; margin-bottom:10px;">¡RESERVA CONFIRMADA!</h1>
                        <p style="color:#aaa;">Tu código de cliente es:</p>
                        
                        <div class="client-code">${resp.cliente_codigo}</div>
                        
                        <p style="color:#ccc; font-size:0.9rem; margin-bottom:30px;">
                            Hemos recibido tu solicitud. Por favor sigue estos pasos:
                        </p>

                        <a href="${linkSpa}" target="_blank" class="action-btn btn-whatsapp">
                            📱 1. Confirmar por WhatsApp
                        </a>
                        
                        <a href="ficha_salud.php?cid=${resp.cliente_id}" class="action-btn btn-ficha">
                            📋 2. Llenar Ficha de Salud
                        </a>

                        <a href="index.php" style="display:block; margin-top:30px; color:#666; font-size:0.9rem;">Volver al Inicio</a>
                    </div>
                `;
            } else {
                alert('Error: ' + resp.message);
                btn.disabled = false; btn.textContent = 'CONFIRMAR RESERVA';
            }
        } catch(e) {
            alert('Error de conexión');
            btn.disabled = false; btn.textContent = 'CONFIRMAR RESERVA';
        }
    });
</script>
</body>
</html>
