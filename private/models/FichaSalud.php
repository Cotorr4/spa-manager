<?php
require_once __DIR__ . '/../database/conexion.php';
require_once __DIR__ . '/../helpers/utils.php';

class FichaSalud {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Obtener ficha por cliente_id
    public function obtenerPorCliente($cliente_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT f.*, 
                       c.nombre as cliente_nombre,
                       c.cliente_codigo,
                       c.telefono,
                       c.email,
                       c.fecha_nacimiento
                FROM fichas_salud f
                INNER JOIN clientes c ON f.cliente_id = c.id
                WHERE f.cliente_id = ?
            ");
            $stmt->execute([$cliente_id]);
            $ficha = $stmt->fetch();
            
            if ($ficha) {
                // Decodificar JSON
                $ficha['datos_clinicos'] = json_decode($ficha['datos_clinicos'], true) ?? [];
                $ficha['habitos_alimenticios'] = json_decode($ficha['habitos_alimenticios'], true) ?? [];
                $ficha['actividad_fisica'] = json_decode($ficha['actividad_fisica'], true) ?? [];
                $ficha['habitos_sueno'] = json_decode($ficha['habitos_sueno'], true) ?? [];
            }
            
            return $ficha;
        } catch (PDOException $e) {
            registrarLog("Error al obtener ficha: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Crear o actualizar ficha
    public function guardar($datos) {
        try {
            $cliente_id = $datos['cliente_id'];
            
            // Verificar si ya existe
            $existe = $this->obtenerPorCliente($cliente_id);
            
            if ($existe) {
                return $this->actualizar($cliente_id, $datos);
            } else {
                return $this->crear($datos);
            }
        } catch (Exception $e) {
            registrarLog("Error al guardar ficha: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al guardar'];
        }
    }
    
    private function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fichas_salud (
                    cliente_id, sexo, tratamiento_recomendado,
                    datos_clinicos, habitos_alimenticios, actividad_fisica, habitos_sueno,
                    completada, actualizada_por
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $datos['cliente_id'],
                $datos['sexo'] ?? null,
                $datos['tratamiento_recomendado'] ?? null,
                json_encode($datos['datos_clinicos'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($datos['habitos_alimenticios'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($datos['actividad_fisica'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($datos['habitos_sueno'] ?? [], JSON_UNESCAPED_UNICODE),
                $datos['completada'] ?? 0,
                $datos['actualizada_por'] ?? 'admin'
            ]);
            
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            registrarLog("Error al crear ficha: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al crear ficha'];
        }
    }
    
    private function actualizar($cliente_id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE fichas_salud SET
                    sexo = ?,
                    tratamiento_recomendado = ?,
                    datos_clinicos = ?,
                    habitos_alimenticios = ?,
                    actividad_fisica = ?,
                    habitos_sueno = ?,
                    completada = ?,
                    actualizada_por = ?
                WHERE cliente_id = ?
            ");
            
            $resultado = $stmt->execute([
                $datos['sexo'] ?? null,
                $datos['tratamiento_recomendado'] ?? null,
                json_encode($datos['datos_clinicos'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($datos['habitos_alimenticios'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($datos['actividad_fisica'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($datos['habitos_sueno'] ?? [], JSON_UNESCAPED_UNICODE),
                $datos['completada'] ?? 0,
                $datos['actualizada_por'] ?? 'admin',
                $cliente_id
            ]);
            
            return ['success' => $resultado, 'mensaje' => 'Ficha actualizada'];
        } catch (PDOException $e) {
            registrarLog("Error al actualizar ficha: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al actualizar'];
        }
    }
    
    // Listar fichas con info de cliente
    public function listar($filtros = []) {
        try {
            $sql = "SELECT f.id, f.cliente_id, f.completada, f.fecha_actualizacion,
                           c.nombre as cliente_nombre,
                           c.cliente_codigo,
                           c.telefono
                    FROM fichas_salud f
                    INNER JOIN clientes c ON f.cliente_id = c.id
                    WHERE 1=1";
            
            $params = [];
            
            if (isset($filtros['completada'])) {
                $sql .= " AND f.completada = ?";
                $params[] = $filtros['completada'];
            }
            
            if (!empty($filtros['busqueda'])) {
                $sql .= " AND (c.nombre LIKE ? OR c.cliente_codigo LIKE ?)";
                $busqueda = "%{$filtros['busqueda']}%";
                $params[] = $busqueda;
                $params[] = $busqueda;
            }
            
            $sql .= " ORDER BY f.fecha_actualizacion DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar fichas: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Eliminar ficha
    public function eliminar($cliente_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM fichas_salud WHERE cliente_id = ?");
            $stmt->execute([$cliente_id]);
            return ['success' => true, 'mensaje' => 'Ficha eliminada'];
        } catch (PDOException $e) {
            registrarLog("Error al eliminar ficha: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al eliminar'];
        }
    }
    
    // Contar fichas
    public function contar() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM fichas_salud");
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Verificar si cliente tiene ficha
    public function clienteTieneFicha($cliente_id) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM fichas_salud WHERE cliente_id = ?");
            $stmt->execute([$cliente_id]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
}
