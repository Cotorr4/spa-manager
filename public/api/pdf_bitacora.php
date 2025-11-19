<?php
require_once __DIR__ . '/../../private/database/config.php';
require_once __DIR__ . '/../../private/helpers/session.php';
require_once __DIR__ . '/../../private/models/Cliente.php';
require_once __DIR__ . '/../../private/models/BitacoraTratamiento.php';

ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=UTF-8');

requerirAuth();

// Obtener parámetros
$tipo = $_GET['tipo'] ?? 'individual'; // 'individual' o 'completo'
$clienteId = intval($_GET['cliente_id'] ?? 0);
$entradaId = intval($_GET['entrada_id'] ?? 0);

if ($tipo === 'completo' && !$clienteId) {
    die('Error: cliente_id requerido');
}

if ($tipo === 'individual' && !$entradaId) {
    die('Error: entrada_id requerido');
}


// Cargar datos
$modeloCliente = new Cliente();
$modeloBitacora = new BitacoraTratamiento();

if ($tipo === 'completo') {
    $cliente = $modeloCliente->obtenerPorId($clienteId);
    if (!$cliente) {
        die('Cliente no encontrado');
    }
    $entradas = $modeloBitacora->listarPorCliente($clienteId);
} else {
    $entrada = $modeloBitacora->obtenerPorId($entradaId);
    if (!$entrada) {
        die('Entrada no encontrada');
    }
    $cliente = $modeloCliente->obtenerPorId($entrada['cliente_id']);
    if (!$cliente) {
        die('Cliente no encontrado');
    }
    $entradas = [$entrada];
}

$titulo = $tipo === 'completo' ? 'Bitácora Completa' : 'Entrada de Bitácora';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> - <?= htmlspecialchars($cliente['nombre']) ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            body { margin: 0; padding: 20px; }
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6;
            background: white;
            color: #000;
        }
        
        /* Botón de imprimir */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .print-button:hover {
            background: #1d4ed8;
        }
        
        /* Contenedor */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Cabecera */
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
        
        /* Info del cliente */
        .cliente-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .cliente-info h2 {
            color: #000;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .cliente-info p {
            margin: 5px 0;
            color: #333;
        }
        .uid-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #2563eb;
            color: white;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }
        
        /* Entrada */
        .entrada {
            margin-bottom: 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }
        .entrada-fecha {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .entrada-tratamiento {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            padding: 10px;
            background: #f0f0f0;
            border-left: 4px solid #2563eb;
        }
        .entrada-notas {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            white-space: pre-wrap;
        }
        .entrada-notas-titulo {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        /* Fotos */
        .fotos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .foto-item {
            page-break-inside: avoid;
        }
        .foto-item img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .fotos-titulo {
            font-weight: bold;
            margin: 20px 0 10px 0;
        }
        
        /* Pie de página */
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    
    <div class="container">
        <!-- Cabecera -->
        <div class="header">
            <!-- Logo (si existe) -->
            <?php if (file_exists(__DIR__ . '/../assets/logo.png')): ?>
                <img src="../assets/logo.png" alt="Logo" class="logo">
            <?php endif; ?>
            <h1><?= $titulo ?></h1>
        </div>
        
        <!-- Información del cliente -->
        <div class="cliente-info">
            <h2><?= htmlspecialchars($cliente['nombre']) ?></h2>
            <p><strong>UID:</strong> <span class="uid-badge"><?= htmlspecialchars($cliente['cliente_uid']) ?></span></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono']) ?></p>
            <?php if (!empty($cliente['email'])): ?>
                <p><strong>Email:</strong> <?= htmlspecialchars($cliente['email']) ?></p>
            <?php endif; ?>
            <?php if (!empty($cliente['direccion'])): ?>
                <p><strong>Dirección:</strong> <?= htmlspecialchars($cliente['direccion']) ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Entradas -->
        <?php if (empty($entradas)): ?>
            <p style="text-align: center; color: #666; padding: 40px;">No hay entradas registradas en la bitácora.</p>
        <?php else: ?>
            <?php foreach ($entradas as $index => $entrada): ?>
                <?php if ($index > 0): ?>
                    <div class="page-break"></div>
                <?php endif; ?>
                
                <?php
                $fotos = !empty($entrada['fotos']) ? json_decode($entrada['fotos'], true) : [];
                $fechaFormato = date('d/m/Y', strtotime($entrada['fecha']));
                ?>
                
                <div class="entrada">
                    <div class="entrada-fecha">
                        📅 <?= $fechaFormato ?>
                    </div>
                    
                    <div class="entrada-tratamiento">
                        <strong>Tratamiento:</strong> <?= htmlspecialchars($entrada['tratamiento_nombre'] ?? 'No especificado') ?>
                    </div>
                    
                    <?php if (!empty($entrada['notas'])): ?>
                        <div class="entrada-notas-titulo">Observaciones:</div>
                        <div class="entrada-notas">
                            <?= nl2br(htmlspecialchars($entrada['notas'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($fotos)): ?>
                        <div class="fotos-titulo">Fotografías del tratamiento:</div>
                        <div class="fotos-grid">
                            <?php foreach ($fotos as $foto): ?>
                                <div class="foto-item">
                                    <img src="../<?= htmlspecialchars($foto) ?>" alt="Foto de tratamiento">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Pie de página -->
        <div class="footer">
            <p>Documento generado el <?= date('d/m/Y H:i') ?></p>
            <p>Este documento es confidencial y contiene información médica protegida</p>
        </div>
    </div>
    
    <script>
        // Auto-abrir diálogo de impresión al cargar (opcional)
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>
