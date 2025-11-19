<?php
require_once __DIR__ . '/../database/conexion.php';
require_once __DIR__ . '/../helpers/utils.php';

class BitacoraTratamiento {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function listarPorCliente($cliente_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, 
                       t.nombre as tratamiento_nombre,
                       r.id as reserva_id,
                       r.estado as reserva_estado
                FROM bitacora_tratamientos b
                JOIN tratamientos t ON b.tratamiento_id = t.id
                LEFT JOIN reservas r ON b.reserva_id = r.id
                WHERE b.cliente_id = ?
                ORDER BY b.fecha DESC, b.created_at DESC
            ");
            $stmt->execute([$cliente_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar bitácora: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, 
                       t.nombre as tratamiento_nombre,
                       c.nombre as cliente_nombre,
                       c.cliente_codigo
                FROM bitacora_tratamientos b
                JOIN tratamientos t ON b.tratamiento_id = t.id
                JOIN clientes c ON b.cliente_id = c.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error al obtener entrada: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO bitacora_tratamientos 
                (cliente_id, reserva_id, tratamiento_id, fecha, notas, fotos) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $fotos = isset($datos['fotos']) ? json_encode($datos['fotos']) : '[]';
            $stmt->execute([
                $datos['cliente_id'],
                $datos['reserva_id'] ?? null,
                $datos['tratamiento_id'],
                $datos['fecha'],
                $datos['notas'] ?? '',
                $fotos
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            registrarLog("Error al crear entrada: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE bitacora_tratamientos 
                SET tratamiento_id = ?, fecha = ?, notas = ?, fotos = ?
                WHERE id = ?
            ");
            $fotos = isset($datos['fotos']) ? json_encode($datos['fotos']) : '[]';
            return $stmt->execute([
                $datos['tratamiento_id'],
                $datos['fecha'],
                $datos['notas'] ?? '',
                $fotos,
                $id
            ]);
        } catch (PDOException $e) {
            registrarLog("Error al actualizar entrada: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function eliminar($id) {
        try {
            // Obtener fotos para eliminarlas del disco
            $entrada = $this->obtenerPorId($id);
            if ($entrada && $entrada['fotos']) {
                $fotos = json_decode($entrada['fotos'], true);
                foreach ($fotos as $foto) {
                    $rutaFoto = __DIR__ . '/../../public/' . $foto;
                    if (file_exists($rutaFoto)) {
                        unlink($rutaFoto);
                    }
                }
            }
            
            $stmt = $this->db->prepare("DELETE FROM bitacora_tratamientos WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            registrarLog("Error al eliminar entrada: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function agregarFoto($id, $rutaFoto) {
        try {
            $entrada = $this->obtenerPorId($id);
            if (!$entrada) return false;
            
            $fotos = json_decode($entrada['fotos'], true) ?: [];
            
            // Máximo 3 fotos
            if (count($fotos) >= 3) {
                return false;
            }
            
            $fotos[] = $rutaFoto;
            
            $stmt = $this->db->prepare("UPDATE bitacora_tratamientos SET fotos = ? WHERE id = ?");
            return $stmt->execute([json_encode($fotos), $id]);
        } catch (PDOException $e) {
            registrarLog("Error al agregar foto: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function eliminarFoto($id, $rutaFoto) {
        try {
            $entrada = $this->obtenerPorId($id);
            if (!$entrada) return false;
            
            $fotos = json_decode($entrada['fotos'], true) ?: [];
            $fotos = array_values(array_filter($fotos, fn($f) => $f !== $rutaFoto));
            
            // Eliminar archivo físico
            $rutaCompleta = __DIR__ . '/../../public/' . $rutaFoto;
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
            
            $stmt = $this->db->prepare("UPDATE bitacora_tratamientos SET fotos = ? WHERE id = ?");
            return $stmt->execute([json_encode($fotos), $id]);
        } catch (PDOException $e) {
            registrarLog("Error al eliminar foto: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function crearDesdeReserva($reserva_id) {
        try {
            // Obtener datos de la reserva
            $stmt = $this->db->prepare("
                SELECT cliente_id, tratamiento_id, fecha 
                FROM reservas 
                WHERE id = ?
            ");
            $stmt->execute([$reserva_id]);
            $reserva = $stmt->fetch();
            
            if (!$reserva) return false;
            
            return $this->crear([
                'cliente_id' => $reserva['cliente_id'],
                'reserva_id' => $reserva_id,
                'tratamiento_id' => $reserva['tratamiento_id'],
                'fecha' => $reserva['fecha'],
                'notas' => 'Sesión realizada. Pendiente agregar observaciones.',
                'fotos' => []
            ]);
        } catch (PDOException $e) {
            registrarLog("Error al crear desde reserva: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
