<?php
require_once __DIR__ . '/../private/database/config_public.php';

// 1. Obtener Perfil Activo
$perfil = [];
$res_perfil = $conn->query("SELECT * FROM perfiles_ubicacion WHERE activo = 1 LIMIT 1");
$perfil = ($res_perfil && $res_perfil->num_rows > 0) ? $res_perfil->fetch_assoc() : [];

// Helpers de Texto
$titulo = $perfil['titulo_hero'] ?? 'Spa Manager';
$subtitulo = $perfil['subtitulo_hero'] ?? '';
$bienvenida = $perfil['mensaje_bienvenida'] ?? '';

// Helpers de Estado
$estado = $perfil['estado_operativo'] ?? 'cerrado';
$horarios = $perfil['horarios_texto'] ?? '';
$ubicacion_nombre = $perfil['nombre'] ?? '';

// Helpers de Contacto (Prioridad: Perfil > Config global)
$tel_publico = $perfil['telefono_publico'] ?? '';
$email_publico = $perfil['email_publico'] ?? '';
$direccion = $perfil['direccion_visible'] ?? '';
$instagram = $perfil['instagram'] ?? '';
$facebook = $perfil['facebook'] ?? '';

// WhatsApp (Importante: sanitizar link)
function sanitizeLink($link) {
    if (empty($link)) return '#';
    if (strpos($link, 'http') === 0) return $link;
    return 'https://' . $link; // Forzar protocolo si falta
}
$wa_link = sanitizeLink($perfil['whatsapp']);

// 2. Obtener Tratamientos
$sql_tratamientos = "
    SELECT t.*, 
    (SELECT ruta FROM tratamiento_fotos tf WHERE tf.tratamiento_id = t.id ORDER BY orden ASC LIMIT 1) as foto
    FROM tratamientos t 
    WHERE t.activo = 1
";
$tratamientos = $conn->query($sql_tratamientos);

// Helper Imagen
function getUrlImagen($nombreArchivo) {
    if (empty($nombreArchivo)) return 'assets/img/no-image.png';
    return 'storage/tratamientos/' . $nombreArchivo;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>

<div class="app-container">
    
    <nav class="top-nav">
        <button class="hamburger" onclick="toggleMenu()">☰</button>
        <div class="nav-links" id="navMenu">
            <a href="<?php echo htmlspecialchars($wa_link); ?>" target="_blank" class="nav-link">WhatsApp</a>
            <a href="reservar_cita.php" class="nav-link">Reservar Cita</a>
            <a href="blog.php" class="nav-link">Blog</a>
            <a href="acerca_de.php" class="nav-link">Acerca de</a>
        </div>
    </nav>

    <div class="logo-section">
        <img src="assets/img/logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'">
    </div>

    <div class="status-bar">
        <div class="status-group">
            <span class="dot <?php echo ($estado=='operativo')?'active':''; ?>"></span>
            <span>📍 <?php echo htmlspecialchars($ubicacion_nombre); ?></span>
            <span style="color:#666;">|</span>
            <span style="text-transform:uppercase; font-weight:bold;"><?php echo htmlspecialchars($estado); ?></span>
        </div>
        <div class="status-hours">
            🕐 <?php echo htmlspecialchars($horarios); ?>
        </div>
    </div>

    <div class="hero-wrapper">
        <img src="assets/img/portada.jpg" alt="Portada" class="hero-img" onerror="this.style.display='none'">
        
        <div class="hero-overlay">
            <h2 class="hero-title"><?php echo htmlspecialchars($titulo); ?></h2>
            <p class="hero-subtitle"><?php echo htmlspecialchars($subtitulo); ?></p>
            
            <?php if($bienvenida): ?>
                <p class="hero-welcome">"<?php echo htmlspecialchars($bienvenida); ?>"</p>
            <?php endif; ?>

            <div class="hero-info-box">
                <?php if($direccion): ?>
                    <div class="info-item">📍 <?php echo htmlspecialchars($direccion); ?></div>
                <?php endif; ?>
                
                <?php if($tel_publico): ?>
                    <div class="info-item">📞 <?php echo htmlspecialchars($tel_publico); ?></div>
                <?php endif; ?>

                <?php if($email_publico): ?>
                    <div class="info-item">✉️ <?php echo htmlspecialchars($email_publico); ?></div>
                <?php endif; ?>
            </div>

            <div class="social-row">
                <a href="<?php echo htmlspecialchars($wa_link); ?>" target="_blank" class="btn">Agendar Cita</a>
                
                <?php if($instagram): ?>
                    <a href="<?php echo sanitizeLink('instagram.com/'.$instagram); ?>" target="_blank" class="social-link">📸 Instagram</a>
                <?php endif; ?>
                
                <?php if($facebook): ?>
                    <a href="<?php echo sanitizeLink('facebook.com/'.$facebook); ?>" target="_blank" class="social-link">📘 Facebook</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section id="servicios" class="services-section">
        <h2 class="section-title">NUESTROS SERVICIOS</h2>
        <div class="grid">
            <?php if ($tratamientos && $tratamientos->num_rows > 0): ?>
                <?php while($t = $tratamientos->fetch_assoc()): ?>
                    <div class="card">
                        <img src="<?php echo htmlspecialchars(getUrlImagen($t['foto'])); ?>" 
                             class="card-img" 
                             alt="<?php echo htmlspecialchars($t['nombre']); ?>"
                             onerror="this.src='assets/img/no-image.png'">
                        
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($t['nombre']); ?></h3>
                            <p class="card-desc">
                                <?php echo htmlspecialchars(substr($t['descripcion'] ?? '', 0, 100)); ?>...
                            </p>
                            
                            <div class="card-footer">
                                <div>
                                    <div class="price">$<?php echo number_format($t['precio'], 0); ?></div>
                                    <span style="font-size:0.8rem; color:#666;"><?php echo $t['duracion']; ?> min</span>
                                </div>
                                <a href="reservar_cita.php?t=<?php echo urlencode($t['nombre']); ?>" 
                                   class="btn" style="font-size:0.8rem; padding:8px 15px;">RESERVAR</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="width:100%; text-align:center; color:var(--text-muted);">No hay tratamientos activos.</p>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <p style="margin-bottom:10px;">Contacto: <?php echo htmlspecialchars($tel_publico); ?></p>
        <p style="margin-bottom:20px; font-size:0.8rem;">Desarrollado por <a href="https://bitnergia.cl" target="_blank" style="color:#3b82f6;">Bitnergia.cl</a></p>
        <small>© <?php echo date('Y'); ?> Todos los derechos reservados.</small>
    </footer>
</div>

<script>
    function toggleMenu() {
        document.getElementById('navMenu').classList.toggle('active');
    }
</script>

</body>
</html>
