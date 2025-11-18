<?php
require_once __DIR__ . '/../database/conexion.php';

class Ubicacion {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Listar todas las ubicaciones
    public function listar($soloActivas = false) {
        try {
            $sql = "SELECT id, nombre, pais, ciudad, direccion, es_domicilio, activo, created_at 
                    FROM ubicaciones";
            
            if ($soloActivas) {
                $sql .= " WHERE activo = 1";
            }
            
            $sql .= " ORDER BY pais, nombre";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar ubicaciones: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener ubicación por ID
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, pais, ciudad, direccion, es_domicilio, activo, created_at 
                FROM ubicaciones 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error al obtener ubicación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Crear ubicación
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ubicaciones (nombre, pais, ciudad, direccion, es_domicilio, activo) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $datos['nombre'],
                $datos['pais'],
                $datos['ciudad'] ?? null,
                $datos['direccion'] ?? null,
                $datos['es_domicilio'] ?? 0,
                $datos['activo'] ?? 1
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            registrarLog("Error al crear ubicación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Actualizar ubicación
    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE ubicaciones 
                SET nombre = ?, pais = ?, ciudad = ?, direccion = ?, es_domicilio = ?, activo = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $datos['nombre'],
                $datos['pais'],
                $datos['ciudad'] ?? null,
                $datos['direccion'] ?? null,
                $datos['es_domicilio'] ?? 0,
                $datos['activo'] ?? 1,
                $id
            ]);
        } catch (PDOException $e) {
            registrarLog("Error al actualizar ubicación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Cambiar estado activo/inactivo
    public function cambiarEstado($id, $activo) {
        try {
            $stmt = $this->db->prepare("UPDATE ubicaciones SET activo = ? WHERE id = ?");
            return $stmt->execute([$activo, $id]);
        } catch (PDOException $e) {
            registrarLog("Error al cambiar estado de ubicación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Eliminar ubicación (solo si no tiene reservas asociadas)
    public function eliminar($id) {
        try {
            // Verificar si tiene reservas
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE ubicacion_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['total'] > 0) {
                return ['success' => false, 'mensaje' => 'No se puede eliminar: tiene reservas asociadas'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM ubicaciones WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'mensaje' => 'Ubicación eliminada'];
            
        } catch (PDOException $e) {
            registrarLog("Error al eliminar ubicación: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al eliminar'];
        }
    }
    
    // Listar por país
    public function listarPorPais($pais) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, pais, ciudad, direccion, es_domicilio, activo, created_at 
                FROM ubicaciones 
                WHERE pais = ? AND activo = 1
                ORDER BY nombre
            ");
            $stmt->execute([$pais]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar ubicaciones por país: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Contar ubicaciones activas
    public function contarActivas() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM ubicaciones WHERE activo = 1");
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
}
