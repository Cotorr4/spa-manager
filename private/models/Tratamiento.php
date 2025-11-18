<?php
require_once __DIR__ . '/../database/conexion.php';

class Tratamiento {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Listar todos los tratamientos
    public function listar($soloActivos = false) {
        try {
            $sql = "SELECT id, nombre, descripcion, duracion, precio, activo, created_at 
                    FROM tratamientos";
            
            if ($soloActivos) {
                $sql .= " WHERE activo = 1";
            }
            
            $sql .= " ORDER BY nombre";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar tratamientos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener tratamiento por ID
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion, duracion, precio, activo, created_at 
                FROM tratamientos 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error al obtener tratamiento: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Crear tratamiento
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tratamientos (nombre, descripcion, duracion, precio, activo) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'] ?? null,
                $datos['duracion'] ?? null,
                $datos['precio'] ?? null,
                $datos['activo'] ?? 1
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            registrarLog("Error al crear tratamiento: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Actualizar tratamiento
    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE tratamientos 
                SET nombre = ?, descripcion = ?, duracion = ?, precio = ?, activo = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'] ?? null,
                $datos['duracion'] ?? null,
                $datos['precio'] ?? null,
                $datos['activo'] ?? 1,
                $id
            ]);
        } catch (PDOException $e) {
            registrarLog("Error al actualizar tratamiento: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Cambiar estado activo/inactivo
    public function cambiarEstado($id, $activo) {
        try {
            $stmt = $this->db->prepare("UPDATE tratamientos SET activo = ? WHERE id = ?");
            return $stmt->execute([$activo, $id]);
        } catch (PDOException $e) {
            registrarLog("Error al cambiar estado de tratamiento: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Eliminar tratamiento (solo si no tiene reservas asociadas)
    public function eliminar($id) {
        try {
            // Verificar si tiene reservas
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE tratamiento_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['total'] > 0) {
                return ['success' => false, 'mensaje' => 'No se puede eliminar: tiene reservas asociadas'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM tratamientos WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'mensaje' => 'Tratamiento eliminado'];
            
        } catch (PDOException $e) {
            registrarLog("Error al eliminar tratamiento: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al eliminar'];
        }
    }
    
    // Contar tratamientos activos
    public function contarActivos() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM tratamientos WHERE activo = 1");
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
}
