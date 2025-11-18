<?php
require_once __DIR__ . '/../database/conexion.php';
require_once __DIR__ . '/../helpers/utils.php';

class Reserva {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Listar reservas con filtros
    public function listar($filtros = []) {
        try {
            $sql = "SELECT r.*, 
                           c.nombre as cliente_nombre, 
                           c.telefono as cliente_telefono,
                           c.cliente_uid,
                           t.nombre as tratamiento_nombre,
                           t.duracion as tratamiento_duracion,
                           t.precio as tratamiento_precio,
                           u.nombre as ubicacion_nombre
                    FROM reservas r
                    LEFT JOIN clientes c ON r.cliente_id = c.id
                    LEFT JOIN tratamientos t ON r.tratamiento_id = t.id
                    LEFT JOIN ubicaciones u ON r.ubicacion_id = u.id
                    WHERE 1=1";
            
            $params = [];
            
            // Filtro por fecha
            if (!empty($filtros['fecha'])) {
                $sql .= " AND r.fecha = ?";
                $params[] = $filtros['fecha'];
            }
            
            // Filtro por rango de fechas
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND r.fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND r.fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
            
            // Filtro por estado
            if (!empty($filtros['estado'])) {
                $sql .= " AND r.estado = ?";
                $params[] = $filtros['estado'];
            }
            
            // Filtro por cliente
            if (!empty($filtros['cliente_id'])) {
                $sql .= " AND r.cliente_id = ?";
                $params[] = $filtros['cliente_id'];
            }
            
            $sql .= " ORDER BY r.fecha DESC, r.hora DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar reservas: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener reserva por ID
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, 
                       c.nombre as cliente_nombre, 
                       c.telefono as cliente_telefono,
                       c.cliente_uid,
                       t.nombre as tratamiento_nombre,
                       t.duracion as tratamiento_duracion,
                       t.precio as tratamiento_precio,
                       u.nombre as ubicacion_nombre
                FROM reservas r
                LEFT JOIN clientes c ON r.cliente_id = c.id
                LEFT JOIN tratamientos t ON r.tratamiento_id = t.id
                LEFT JOIN ubicaciones u ON r.ubicacion_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error al obtener reserva: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Verificar disponibilidad de horario
    public function verificarDisponibilidad($fecha, $hora, $ubicacion_id, $excluir_reserva_id = null) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM reservas 
                    WHERE fecha = ? 
                    AND hora = ? 
                    AND ubicacion_id = ? 
                    AND estado NOT IN ('cancelada')";
            
            $params = [$fecha, $hora, $ubicacion_id];
            
            if ($excluir_reserva_id) {
                $sql .= " AND id != ?";
                $params[] = $excluir_reserva_id;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result['total'] == 0;
        } catch (PDOException $e) {
            registrarLog("Error al verificar disponibilidad: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Crear reserva
    public function crear($datos) {
        try {
            // Verificar disponibilidad
            if (!$this->verificarDisponibilidad($datos['fecha'], $datos['hora'], $datos['ubicacion_id'])) {
                return ['success' => false, 'mensaje' => 'El horario ya está ocupado'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO reservas (
                    cliente_id, tratamiento_id, ubicacion_id, 
                    fecha, hora, estado, comentarios, 
                    pagado, monto_pagado, metodo_pago
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $datos['cliente_id'],
                $datos['tratamiento_id'],
                $datos['ubicacion_id'],
                $datos['fecha'],
                $datos['hora'],
                $datos['estado'] ?? 'pendiente',
                $datos['comentarios'] ?? null,
                $datos['pagado'] ?? 0,
                $datos['monto_pagado'] ?? null,
                $datos['metodo_pago'] ?? null
            ]);
            
            $id = $this->db->lastInsertId();
            return ['success' => true, 'id' => $id];
            
        } catch (PDOException $e) {
            registrarLog("Error al crear reserva: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al crear reserva'];
        }
    }
    
    // Actualizar reserva
    public function actualizar($id, $datos) {
        try {
            // Si se cambia fecha/hora/ubicación, verificar disponibilidad
            if (isset($datos['fecha']) || isset($datos['hora']) || isset($datos['ubicacion_id'])) {
                $reservaActual = $this->obtenerPorId($id);
                $fecha = $datos['fecha'] ?? $reservaActual['fecha'];
                $hora = $datos['hora'] ?? $reservaActual['hora'];
                $ubicacion = $datos['ubicacion_id'] ?? $reservaActual['ubicacion_id'];
                
                if (!$this->verificarDisponibilidad($fecha, $hora, $ubicacion, $id)) {
                    return ['success' => false, 'mensaje' => 'El horario ya está ocupado'];
                }
            }
            
            $campos = [];
            $valores = [];
            
            $camposPermitidos = [
                'cliente_id', 'tratamiento_id', 'ubicacion_id',
                'fecha', 'hora', 'estado', 'comentarios',
                'pagado', 'monto_pagado', 'metodo_pago'
            ];
            
            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    $campos[] = "{$campo} = ?";
                    $valores[] = $datos[$campo];
                }
            }
            
            if (empty($campos)) {
                return ['success' => false, 'mensaje' => 'No hay datos para actualizar'];
            }
            
            $valores[] = $id;
            $sql = "UPDATE reservas SET " . implode(', ', $campos) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute($valores);
            
            return ['success' => $resultado, 'mensaje' => $resultado ? 'Reserva actualizada' : 'Error al actualizar'];
            
        } catch (PDOException $e) {
            registrarLog("Error al actualizar reserva: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al actualizar reserva'];
        }
    }
    
    // Cambiar estado
    public function cambiarEstado($id, $nuevoEstado) {
        try {
            $stmt = $this->db->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
            $resultado = $stmt->execute([$nuevoEstado, $id]);
            return ['success' => $resultado];
        } catch (PDOException $e) {
            registrarLog("Error al cambiar estado: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al cambiar estado'];
        }
    }
    
    // Marcar como pagado
    public function marcarPagado($id, $monto, $metodo) {
        try {
            $stmt = $this->db->prepare("
                UPDATE reservas 
                SET pagado = 1, monto_pagado = ?, metodo_pago = ? 
                WHERE id = ?
            ");
            $resultado = $stmt->execute([$monto, $metodo, $id]);
            return ['success' => $resultado];
        } catch (PDOException $e) {
            registrarLog("Error al marcar pagado: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al registrar pago'];
        }
    }
    
    // Obtener reservas de hoy
    public function reservasHoy() {
        try {
            $hoy = date('Y-m-d');
            return $this->listar(['fecha' => $hoy]);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Contar reservas por estado
    public function contarPorEstado($estado) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE estado = ?");
            $stmt->execute([$estado]);
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
}
