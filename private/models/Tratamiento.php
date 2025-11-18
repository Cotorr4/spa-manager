<?php
require_once __DIR__ . '/../database/conexion.php';
require_once __DIR__ . '/../helpers/utils.php';

class Tratamiento {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function listar($soloActivos = false) {
        try {
            $sql = "SELECT t.*, 
                    (SELECT COUNT(*) FROM tratamiento_fotos WHERE tratamiento_id = t.id) as total_fotos
                    FROM tratamientos t";
            
            if ($soloActivos) {
                $sql .= " WHERE t.activo = 1";
            }
            
            $sql .= " ORDER BY t.nombre";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar tratamientos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, subtitulo, descripcion, duracion, precio, activo, created_at 
                FROM tratamientos 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $tratamiento = $stmt->fetch();
            
            if ($tratamiento) {
                $tratamiento['fotos'] = $this->obtenerFotos($id);
            }
            
            return $tratamiento;
        } catch (PDOException $e) {
            registrarLog("Error al obtener tratamiento: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function obtenerFotos($tratamiento_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, ruta, orden 
                FROM tratamiento_fotos 
                WHERE tratamiento_id = ? 
                ORDER BY orden, id
            ");
            $stmt->execute([$tratamiento_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al obtener fotos: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tratamientos (nombre, subtitulo, descripcion, duracion, precio, activo) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $datos['nombre'],
                $datos['subtitulo'] ?? '',
                $datos['descripcion'] ?? '',
                $datos['duracion'],
                $datos['precio'],
                $datos['activo'] ?? 1
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            registrarLog("Error al crear tratamiento: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE tratamientos 
                SET nombre = ?, subtitulo = ?, descripcion = ?, duracion = ?, precio = ?, activo = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $datos['nombre'],
                $datos['subtitulo'] ?? '',
                $datos['descripcion'] ?? '',
                $datos['duracion'],
                $datos['precio'],
                $datos['activo'] ?? 1,
                $id
            ]);
        } catch (PDOException $e) {
            registrarLog("Error al actualizar tratamiento: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function agregarFoto($tratamiento_id, $ruta, $orden = 0) {
        try {
            // Contar fotos actuales
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM tratamiento_fotos WHERE tratamiento_id = ?");
            $stmt->execute([$tratamiento_id]);
            $result = $stmt->fetch();
            
            if ($result['total'] >= 3) {
                return ['success' => false, 'mensaje' => 'Máximo 3 fotos por tratamiento'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO tratamiento_fotos (tratamiento_id, ruta, orden) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([$tratamiento_id, $ruta, $orden]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            registrarLog("Error al agregar foto: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al guardar foto'];
        }
    }
    
    public function eliminarFoto($foto_id) {
        try {
            // Obtener ruta antes de eliminar
            $stmt = $this->db->prepare("SELECT ruta FROM tratamiento_fotos WHERE id = ?");
            $stmt->execute([$foto_id]);
            $foto = $stmt->fetch();
            
            if ($foto) {
                $rutaCompleta = __DIR__ . '/../../storage/tratamientos/' . basename($foto['ruta']);
                if (file_exists($rutaCompleta)) {
                    @unlink($rutaCompleta);
                }
                
                $stmt = $this->db->prepare("DELETE FROM tratamiento_fotos WHERE id = ?");
                $stmt->execute([$foto_id]);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            registrarLog("Error al eliminar foto: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function reordenarFotos($tratamiento_id, $orden_ids) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("UPDATE tratamiento_fotos SET orden = ? WHERE id = ? AND tratamiento_id = ?");
            
            foreach ($orden_ids as $orden => $foto_id) {
                $stmt->execute([$orden, $foto_id, $tratamiento_id]);
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            registrarLog("Error al reordenar fotos: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function cambiarEstado($id, $activo) {
        try {
            $stmt = $this->db->prepare("UPDATE tratamientos SET activo = ? WHERE id = ?");
            return $stmt->execute([$activo, $id]);
        } catch (PDOException $e) {
            registrarLog("Error al cambiar estado: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function eliminar($id) {
        try {
            // Verificar si tiene reservas
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE tratamiento_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['total'] > 0) {
                return ['success' => false, 'mensaje' => 'No se puede eliminar: tiene reservas asociadas'];
            }
            
            // Eliminar fotos físicas
            $fotos = $this->obtenerFotos($id);
            foreach ($fotos as $foto) {
                $rutaCompleta = __DIR__ . '/../../storage/tratamientos/' . basename($foto['ruta']);
                if (file_exists($rutaCompleta)) {
                    @unlink($rutaCompleta);
                }
            }
            
            // Eliminar tratamiento (CASCADE eliminará fotos de BD)
            $stmt = $this->db->prepare("DELETE FROM tratamientos WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'mensaje' => 'Tratamiento eliminado'];
        } catch (PDOException $e) {
            registrarLog("Error al eliminar tratamiento: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al eliminar'];
        }
    }
    
    public function contar() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM tratamientos");
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
}
