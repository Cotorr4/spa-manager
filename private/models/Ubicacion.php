<?php
require_once __DIR__ . '/../database/conexion.php';
require_once __DIR__ . '/../helpers/utils.php';

class Ubicacion {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function listar() {
        try {
            $stmt = $this->db->query("SELECT * FROM ubicaciones ORDER BY nombre");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar ubicaciones: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ubicaciones WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error al obtener ubicación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ubicaciones (nombre, direccion, telefono, email, color, activo) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $datos['nombre'],
                $datos['direccion'],
                $datos['telefono'],
                $datos['email'],
                $datos['color'] ?? '#2563eb',
                $datos['activo'] ?? 1
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            registrarLog("Error al crear ubicación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE ubicaciones 
                SET nombre = ?, direccion = ?, telefono = ?, email = ?, color = ?, activo = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $datos['nombre'],
                $datos['direccion'],
                $datos['telefono'],
                $datos['email'],
                $datos['color'] ?? '#2563eb',
                $datos['activo'] ?? 1,
                $id
            ]);
        } catch (PDOException $e) {
            registrarLog("Error al actualizar ubicación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function eliminar($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM ubicaciones WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            registrarLog("Error al eliminar ubicación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
