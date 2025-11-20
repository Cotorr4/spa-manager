<?php
$cliente_id = $_GET['cid'] ?? '';
// Validar que sea número para evitar inyecciones básicas
$cliente_id = intval($cliente_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Ficha de Salud</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        .form-container { max-width: 800px; margin: 40px auto; padding: 30px; background: #1a1a1a; border-radius: 8px; }
        .section-title { color: var(--accent); font-family:'Oswald'; font-size:1.2rem; margin: 25px 0 15px; border-bottom:1px solid #333; text-align:left; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .checkbox-group input { width: auto; }
        .checkbox-group label { margin: 0; cursor: pointer; color: #ddd; }
        textarea { width: 100%; background: #252525; border: 1px solid #444; color: white; padding: 10px; min-height: 80px; }
        input[type="text"], input[type="number"], select { width: 100%; background: #252525; border: 1px solid #444; color: white; padding: 10px; }
    </style>
</head>
<body>
<div class="app-container">
    <header style="padding:20px;text-align:center;background:#151515;">
        <h1 style="margin:0;font-size:1.5rem;">FICHA DE SALUD</h1>
        <p style="color:#888;font-size:0.9rem;">Por favor completa tus datos para una mejor atención</p>
    </header>

    <div class="form-container">
        <form id="formFicha">
            <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
            
            <div class="form-group">
                <label>Sexo</label>
                <select name="sexo">
                    <option value="">Seleccionar...</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Masculino">Masculino</option>
                </select>
            </div>

            <div class="section-title">1. Antecedentes Médicos</div>
            <p style="color:#666;font-size:0.85rem;margin-bottom:15px;">Marca si padeces alguna condición:</p>
            
            <?php 
            $condiciones = ['Diabetes', 'Hipertensión', 'Alergias', 'Problemas Cardíacos', 'Problemas de Piel'];
            foreach($condiciones as $c): 
                $slug = strtolower(str_replace(' ', '_', $c));
            ?>
            <div class="form-group" style="background:#222; padding:10px; border-radius:4px;">
                <div class="checkbox-group">
                    <input type="checkbox" id="<?php echo $slug; ?>" name="clinica_<?php echo $slug; ?>">
                    <label for="<?php echo $slug; ?>"><?php echo $c; ?></label>
                </div>
                <input type="text" name="clinica_<?php echo $slug; ?>_esp" placeholder="Detalles / Medicamentos (Opcional)" style="font-size:0.9rem;">
            </div>
            <?php endforeach; ?>

            <div class="form-group">
                <label>Otros antecedentes relevantes</label>
                <textarea name="clinica_otros"></textarea>
            </div>

            <div class="section-title">2. Estilo de Vida</div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label>Agua diaria (Litros)</label>
                    <input type="number" name="alim_agua_litros" step="0.5" placeholder="Ej: 2">
                </div>
                <div class="form-group">
                    <label>Horas de sueño</label>
                    <input type="number" name="sueno_horas" placeholder="Ej: 7">
                </div>
            </div>
            
            <div class="form-group">
                <label>Actividad Física</label>
                <input type="text" name="act_tipo_ejercicio" placeholder="Ej: Yoga, Gym, Ninguna">
            </div>

            <button type="submit" class="btn" style="width:100%; margin-top:20px; padding:15px;">GUARDAR FICHA</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('formFicha').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button');
        btn.disabled = true; btn.textContent = 'Guardando...';

        const formData = new FormData(e.target);
        formData.append('action', 'guardar_publico');

        try {
            const res = await fetch('api/public_ficha.php', { method:'POST', body:formData });
            const data = await res.json();
            
            if(data.success) {
                document.body.innerHTML = `
                    <div style="height:100vh;display:flex;justify-content:center;align-items:center;flex-direction:column;background:#1a1a1a;color:white;text-align:center;">
                        <div style="font-size:50px;">✅</div>
                        <h1 style="font-family:'Oswald';">¡Ficha Guardada!</h1>
                        <p style="color:#aaa;">Tus datos han sido actualizados correctamente.</p>
                        <a href="index.php" class="btn" style="margin-top:20px;display:inline-block;">Volver al Inicio</a>
                    </div>
                `;
            } else {
                alert(data.message || 'Error al guardar');
                btn.disabled = false; btn.textContent = 'GUARDAR FICHA';
            }
        } catch(err) {
            alert('Error de conexión');
            btn.disabled = false; btn.textContent = 'GUARDAR FICHA';
        }
    });
</script>
</body>
</html>
