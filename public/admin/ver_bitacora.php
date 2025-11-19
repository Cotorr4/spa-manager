<?php
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/models/Cliente.php';
require_once __DIR__ . '/../../private/models/BitacoraTratamiento.php';
require_once __DIR__ . '/../../private/models/Tratamiento.php';

ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=UTF-8');

requerirAuth();
$usuario = usuarioActual();

// Obtener ID del cliente
$clienteId = intval($_GET['cliente_id'] ?? 0);
if (!$clienteId) {
    header('Location: admin_clientes.php');
    exit;
}

// Cargar datos del cliente
$modeloCliente = new Cliente();
$cliente = $modeloCliente->obtenerPorId($clienteId);

if (!$cliente) {
    header('Location: admin_clientes.php');
    exit;
}

// Cargar bitácora
$modeloBitacora = new BitacoraTratamiento();
$entradas = $modeloBitacora->listarPorCliente($clienteId);

// Cargar tratamientos para el formulario
$modeloTratamiento = new Tratamiento();
$tratamientos = $modeloTratamiento->listar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora - <?= htmlspecialchars($cliente['nombre']) ?> - Spa Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #1a1a1a; color: #f5f5f5; }
        
        /* Header */
        .header {
            background: #2d2d2d;
            border-bottom: 1px solid #404040;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .back-btn {
            padding: 8px 16px;
            background: #404040;
            border: 1px solid #505050;
            color: #f5f5f5;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .back-btn:hover {
            background: #505050;
        }
        .header-info h1 {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .header-info .meta {
            font-size: 14px;
            color: #a0a0a0;
        }
        .uid-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #1a1a1a;
            border: 1px solid #404040;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
            color: #a0a0a0;
            margin-left: 8px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Botones */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        
        /* Contenedor principal */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #404040;
        }
        
        /* Entrada de bitácora */
        .entrada {
            position: relative;
            margin-bottom: 40px;
            background: #2d2d2d;
            border: 1px solid #404040;
            border-radius: 8px;
            padding: 24px;
            transition: all 0.2s;
        }
        .entrada:hover {
            border-color: #505050;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .entrada::before {
            content: '●';
            position: absolute;
            left: -34px;
            top: 24px;
            width: 20px;
            height: 20px;
            background: #2563eb;
            border: 3px solid #1a1a1a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
        }
        
        .entrada-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .entrada-fecha {
            font-size: 18px;
            font-weight: 500;
            color: #2563eb;
        }
        .entrada-tratamiento {
            font-size: 16px;
            color: #f5f5f5;
            margin-top: 4px;
        }
        .entrada-actions {
            display: flex;
            gap: 8px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .entrada-notas {
            margin-top: 16px;
            padding: 16px;
            background: #1a1a1a;
            border-radius: 4px;
            line-height: 1.6;
            white-space: pre-wrap;
            color: #d0d0d0;
        }
        
        /* Galería de fotos */
        .entrada-fotos {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        .foto-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s;
        }
        .foto-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        .foto-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .lightbox.show {
            display: flex;
        }
        .lightbox-content {
            max-width: 90%;
            max-height: 90vh;
            position: relative;
        }
        .lightbox-content img {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
        }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid #404040;
            border-radius: 50%;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .lightbox-close:hover {
            background: #ef4444;
            border-color: #ef4444;
        }
        
        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #2d2d2d;
            border: 2px dashed #404040;
            border-radius: 8px;
            margin-top: 40px;
        }
        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: #a0a0a0;
        }
        .empty-state p {
            color: #808080;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            .timeline {
                padding-left: 20px;
            }
            .timeline::before {
                left: 5px;
            }
            .entrada::before {
                left: -19px;
            }
            .entrada-fotos {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <a href="admin_clientes.php" class="back-btn">← Volver</a>
            <div class="header-info">
                <h1>
                    📋 Bitácora de <?= htmlspecialchars($cliente['nombre']) ?>
                    <span class="uid-badge"><?= htmlspecialchars($cliente['cliente_uid']) ?></span>
                </h1>
                <div class="meta">
                    <?= htmlspecialchars($cliente['telefono']) ?>
                    <?php if ($cliente['email']): ?>
                        | <?= htmlspecialchars($cliente['email']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <a href="pdf_bitacora.php?cliente_id=<?= $clienteId ?>&tipo=completo" target="_blank" class="btn btn-success">
                📄 PDF Completo
            </a>
            <a href="admin_clientes.php#cliente-<?= $clienteId ?>" class="btn btn-primary">
                ➕ Nueva Entrada
            </a>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container">
        <?php if (empty($entradas)): ?>
            <div class="empty-state">
                <h3>📝 No hay entradas en la bitácora</h3>
                <p>Las sesiones del cliente se mostrarán aquí una vez que comiences a registrarlas.</p>
            </div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($entradas as $entrada): ?>
                    <?php
                    $fotos = !empty($entrada['fotos']) ? json_decode($entrada['fotos'], true) : [];
                    $fechaFormato = date('d/m/Y', strtotime($entrada['fecha']));
                    ?>
                    <div class="entrada">
                        <div class="entrada-header">
                            <div>
                                <div class="entrada-fecha">
                                    📅 <?= $fechaFormato ?>
                                </div>
                                <div class="entrada-tratamiento">
                                    <?= htmlspecialchars($entrada['tratamiento_nombre'] ?? 'Tratamiento no especificado') ?>
                                </div>
                            </div>
                            <div class="entrada-actions">
                                <a href="../api/pdf_bitacora.php?entrada_id=<?= $entrada['id'] ?>&tipo=individual" 
                                   target="_blank" 
                                   class="btn btn-success btn-small">
                                    📄 PDF
                                </a>
                            </div>
                        </div>

                        <?php if (!empty($entrada['notas'])): ?>
                            <div class="entrada-notas">
                                <?= nl2br(htmlspecialchars($entrada['notas'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($fotos)): ?>
                            <div class="entrada-fotos">
                                <?php foreach ($fotos as $foto): ?>
                                    <div class="foto-item" onclick="abrirLightbox('<?= htmlspecialchars($foto) ?>')">
                                        <img src="../<?= htmlspecialchars($foto) ?>" 
                                             alt="Foto de tratamiento"
                                             loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Lightbox para ver fotos en grande -->
    <div class="lightbox" id="lightbox" onclick="cerrarLightbox()">
        <div class="lightbox-close">×</div>
        <div class="lightbox-content" onclick="event.stopPropagation()">
            <img id="lightboxImg" src="" alt="Foto ampliada">
        </div>
    </div>

    <script>
        function abrirLightbox(src) {
            const lightbox = document.getElementById('lightbox');
            const img = document.getElementById('lightboxImg');
            img.src = src;
            lightbox.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function cerrarLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cerrarLightbox();
            }
        });
    </script>
</body>
</html>
