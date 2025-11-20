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

// WhatsApp (Sanitizar)
function sanitizeWhatsApp($link) {
    if (empty($link)) return '#';
    if (preg_match('/^\+?[0-9\s]+$/', $link)) {
        $clean = preg_replace('/[^0-9]/', '', $link);
        return 'https://wa.me/' . $clean;
    }
    if (strpos($link, 'http') === false) return 'https://' . $link;
    return $link;
}
$wa_link = sanitizeWhatsApp($perfil['whatsapp'] ?? '');

function sanitizeLink($link) {
    if (empty($link)) return '#';
    if (strpos($link, 'http') === false) return 'https://' . $link;
    return $link;
}

// 2. Obtener Tratamientos
$sql_tratamientos = "
    SELECT t.*, 
    (SELECT ruta FROM tratamiento_fotos tf WHERE tf.tratamiento_id = t.id ORDER BY orden ASC LIMIT 1) as foto
    FROM tratamientos t 
    WHERE t.activo = 1
";
$tratamientos = $conn->query($sql_tratamientos);

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
                <?php while($t = $tratamientos->fetch_assoc()): 
                    $imgUrl = getUrlImagen($t['foto']);
                ?>
                    <div class="card" 
                         onclick='abrirDetalle(<?php echo json_encode([
                             "nombre" => $t["nombre"],
                             "subtitulo" => $t["subtitulo"] ?? "",
                             "descripcion" => $t["descripcion"],
                             "precio" => number_format($t["precio"], 0),
                             "duracion" => $t["duracion"],
                             "img" => $imgUrl
                         ]); ?>)'>
                        
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" class="card-img" alt="Img" onerror="this.src='assets/img/no-image.png'">
                        
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
                                <span class="btn" style="font-size:0.8rem; padding:6px 15px;">VER MÁS</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <h3 style="color:var(--text-main); margin-bottom:15px;">UBICACIÓN Y CONTACTO</h3>
        
        <?php if($direccion): ?>
            <p>📍 <?php echo htmlspecialchars($direccion); ?></p>
        <?php endif; ?>
        
        <?php if($horarios): ?>
            <p>🕐 <?php echo htmlspecialchars($horarios); ?></p>
        <?php endif; ?>
        
        <p>📞 <a href="<?php echo htmlspecialchars($wa_link); ?>" target="_blank"><?php echo htmlspecialchars($tel_publico); ?></a></p>
        
        <?php if($email_publico): ?>
            <p>✉️ <a href="mailto:<?php echo htmlspecialchars($email_publico); ?>"><?php echo htmlspecialchars($email_publico); ?></a></p>
        <?php endif; ?>

        <?php if($instagram || $facebook): ?>
        <div class="footer-social">
            <?php if($instagram): ?>
                <a href="<?php echo sanitizeLink('instagram.com/'.$instagram); ?>" target="_blank">INSTAGRAM</a>
            <?php endif; ?>
            <?php if($facebook): ?>
                <a href="<?php echo sanitizeLink('facebook.com/'.$facebook); ?>" target="_blank">FACEBOOK</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="border-top:1px solid #333; margin-top:30px; padding-top:20px; font-size:0.8rem;">
            Desarrollado por <a href="https://bitnergia.cl" target="_blank" style="color:#3b82f6;">Bitnergia.cl</a>
            <br>
            © <?php echo date('Y'); ?> <?php echo htmlspecialchars($titulo); ?>
        </div>
    </footer>
</div>

<div class="tm-overlay" id="tmOverlay" onclick="cerrarDetalle(event)">
    <div class="tm-content">
        <button class="tm-close" onclick="cerrarDetalleBtn()">✕</button>
        
        <div class="tm-img-container">
            <img src="" id="tmImg" class="tm-img" alt="Detalle">
        </div>
        
        <div class="tm-body">
            <h2 id="tmTitle" class="tm-title"></h2>
            <div id="tmSubtitle" class="tm-subtitle"></div>
            <div id="tmDesc" class="tm-desc"></div>
            
            <div class="tm-footer">
                <div>
                    <div id="tmPrice" class="tm-price"></div>
                    <div class="tm-duration">⏱ <span id="tmDuration"></span> min</div>
                </div>
                <a href="#" id="tmBtn" class="btn" style="padding: 12px 30px; font-size: 1rem;">RESERVAR CITA</a>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleMenu() {
        document.getElementById('navMenu').classList.toggle('active');
    }

    const overlay = document.getElementById('tmOverlay');
    
    function abrirDetalle(data) {
        document.getElementById('tmImg').src = data.img;
        document.getElementById('tmTitle').innerText = data.nombre;
        
        const sub = document.getElementById('tmSubtitle');
        if(data.subtitulo) {
            sub.innerText = data.subtitulo;
            sub.style.display = 'block';
        } else {
            sub.style.display = 'none';
        }

        document.getElementById('tmDesc').innerText = data.descripcion;
        document.getElementById('tmPrice').innerText = '$' + data.precio;
        document.getElementById('tmDuration').innerText = data.duracion;
        
        const btn = document.getElementById('tmBtn');
        btn.href = 'reservar_cita.php?t=' + encodeURIComponent(data.nombre);
        
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden'; 
    }

    function cerrarDetalle(e) {
        if (e.target === overlay) cerrarDetalleBtn();
    }
    
    function cerrarDetalleBtn() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
</script>

</body>
</html>
