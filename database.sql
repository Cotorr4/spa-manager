-- ============================================
-- SPA MANAGER - SCHEMA DE BASE DE DATOS
-- ============================================

CREATE DATABASE IF NOT EXISTS spa_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spa_manager;

-- ============================================
-- 1. TABLA: usuarios
-- ============================================
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    nombre VARCHAR(100),
    activo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 2. TABLA: clientes
-- ============================================
CREATE TABLE clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_uid VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    direccion TEXT,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 3. TABLA: tratamientos
-- ============================================
CREATE TABLE tratamientos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    duracion INT COMMENT 'minutos',
    precio DECIMAL(10,2),
    activo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 4. TABLA: ubicaciones
-- ============================================
CREATE TABLE ubicaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    pais ENUM('Peru', 'Chile') NOT NULL,
    ciudad VARCHAR(100),
    direccion TEXT,
    es_domicilio BOOLEAN DEFAULT 0,
    activo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 5. TABLA: reservas
-- ============================================
CREATE TABLE reservas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    tratamiento_id INT NOT NULL,
    ubicacion_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada') DEFAULT 'pendiente',
    comentarios TEXT,
    pagado BOOLEAN DEFAULT 0,
    monto_pagado DECIMAL(10,2),
    metodo_pago VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (tratamiento_id) REFERENCES tratamientos(id),
    FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- ============================================
-- 6. TABLA: fichas_salud
-- ============================================
CREATE TABLE fichas_salud (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL UNIQUE,
    alergias TEXT,
    condiciones_medicas TEXT,
    medicamentos TEXT,
    cirugias_previas TEXT,
    embarazo BOOLEAN DEFAULT 0,
    observaciones TEXT,
    completada BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 7. TABLA: controles
-- ============================================
CREATE TABLE controles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    reserva_id INT,
    fecha DATE NOT NULL,
    tratamiento_aplicado VARCHAR(200),
    notas TEXT,
    fotos TEXT COMMENT 'JSON array de rutas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE SET NULL,
    INDEX idx_cliente_fecha (cliente_id, fecha)
) ENGINE=InnoDB;

-- ============================================
-- 8. TABLA: calendario
-- ============================================
CREATE TABLE calendario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fecha DATE NOT NULL,
    ubicacion_id INT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    disponible BOOLEAN DEFAULT 1,
    notas TEXT,
    FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id),
    UNIQUE KEY unique_fecha_ubicacion_hora (fecha, ubicacion_id, hora_inicio)
) ENGINE=InnoDB;

-- ============================================
-- 9. TABLA: blog_articulos
-- ============================================
CREATE TABLE blog_articulos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    contenido TEXT,
    imagen_portada VARCHAR(255),
    estado ENUM('borrador', 'publicado') DEFAULT 'borrador',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- ============================================
-- 10. TABLA: configuracion
-- ============================================
CREATE TABLE configuracion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('texto', 'numero', 'boolean', 'json') DEFAULT 'texto',
    descripcion VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- DATOS INICIALES
-- ============================================

INSERT INTO usuarios (usuario, password, nombre) VALUES 
('admin', 'admin123', 'Administrador');

INSERT INTO configuracion (clave, valor, tipo, descripcion) VALUES
('pais_operacion', 'Peru', 'texto', 'País donde opera actualmente'),
('requiere_pago_previo', '0', 'boolean', 'Si requiere pago antes de confirmar reserva'),
('metodos_pago_peru', '["Plin", "Efectivo"]', 'json', 'Métodos de pago en Perú'),
('metodos_pago_chile', '["Transferencia", "Efectivo"]', 'json', 'Métodos de pago en Chile'),
('sitio_activo', '0', 'boolean', 'Si el sitio público está activo'),
('mensaje_inicio', 'Bienvenido a nuestro centro de estética', 'texto', 'Mensaje en página principal');

INSERT INTO ubicaciones (nombre, pais, ciudad, direccion, activo) VALUES
('Sede Principal Lima', 'Peru', 'Lima', 'Av. Ejemplo 123, Miraflores', 1),
('Sede Secundaria Lima', 'Peru', 'Lima', 'Jr. Ejemplo 456, San Isidro', 1),
('Sede Santiago', 'Chile', 'Santiago', 'Av. Providencia 789', 1),
('Atención a Domicilio', 'Peru', NULL, NULL, 1);

UPDATE ubicaciones SET es_domicilio = 1 WHERE nombre = 'Atención a Domicilio';

INSERT INTO tratamientos (nombre, descripcion, duracion, precio, activo) VALUES
('Masaje Relajante', 'Masaje corporal completo para aliviar tensiones', 60, 80.00, 1),
('Limpieza Facial', 'Limpieza profunda del rostro con extracción', 45, 60.00, 1),
('Tratamiento Anticelulítico', 'Sesión de masaje y cremas reductoras', 90, 120.00, 1),
('Depilación Completa', 'Depilación con cera de piernas y brazos', 75, 90.00, 1);
