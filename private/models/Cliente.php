<?php
require_once __DIR__ . '/../database/conexion.php';
require_once __DIR__ . '/../helpers/utils.php';

class Cliente {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Listar todos los clientes
    public function listar($orden = 'nombre', $busqueda = '') {
        try {
            $sql = "SELECT id, cliente_uid, cliente_codigo, nombre, telefono, fecha_nacimiento, email, direccion, notas, activo, created_at 
                    FROM clientes";
            
            $params = [];
            
            if (!empty($busqueda)) {
                $sql .= " WHERE nombre LIKE ? OR telefono LIKE ? OR cliente_uid LIKE ?";
                $busqueda = "%{$busqueda}%";
                $params = [$busqueda, $busqueda, $busqueda];
            }
            
            $ordenPermitido = in_array($orden, ['nombre', 'created_at', 'telefono']) ? $orden : 'nombre';
            $sql .= " ORDER BY {$ordenPermitido}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar clientes: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener cliente por ID
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, cliente_uid, cliente_codigo, nombre, telefono, fecha_nacimiento, email, direccion, notas, activo, created_at 
                FROM clientes 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error al obtener cliente: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener cliente por teléfono
    public function obtenerPorTelefono($telefono) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, cliente_uid, cliente_codigo, nombre, telefono, fecha_nacimiento, email, direccion, notas, activo, created_at 
                FROM clientes 
                WHERE telefono = ?
            ");
            $stmt->execute([$telefono]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error al buscar cliente por teléfono: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener cliente por UID
    public function obtenerPorUID($uid) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, cliente_uid, cliente_codigo, nombre, telefono, fecha_nacimiento, email, direccion, notas, activo, created_at 
                FROM clientes 
                WHERE cliente_uid = ?
            ");
            $stmt->execute([$uid]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error al buscar cliente por UID: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Crear cliente
    public function crear($datos) {
        try {
            // Verificar si ya existe por teléfono
            if ($this->obtenerPorTelefono($datos['telefono'])) {
                return ['success' => false, 'mensaje' => 'Ya existe un cliente con ese teléfono'];
            }
            
            // Generar UID único
            do {
                $uid = generarClienteUID();
            } while ($this->obtenerPorUID($uid));
            
            $stmt = $this->db->prepare("
                INSERT INTO clientes (cliente_uid, nombre, telefono, email, direccion, notas) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $uid,
                $datos['nombre'],
                $datos['telefono'],
                $datos['email'] ?? null,
                $datos['direccion'] ?? null,
                $datos['notas'] ?? null
            ]);
            
            $id = $this->db->lastInsertId();
            // Generar código simple
            $stmtCount = $this->db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(cliente_codigo, 2) AS UNSIGNED)), 0) + 1 as next FROM clientes");
            $nextNum = $stmtCount->fetch()['next'];
            $codigo = 'C' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            
            $stmtCodigo = $this->db->prepare("UPDATE clientes SET cliente_codigo = ? WHERE id = ?");
            $stmtCodigo->execute([$codigo, $id]);
            return ['success' => true, 'id' => $id, 'uid' => $uid];
            
        } catch (PDOException $e) {
            registrarLog("Error al crear cliente: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al crear cliente'];
        }
    }
    
    // Actualizar cliente
    public function actualizar($id, $datos) {
        try {
            // Verificar si el teléfono ya existe en otro cliente
            $clienteExistente = $this->obtenerPorTelefono($datos['telefono']);
            if ($clienteExistente && $clienteExistente['id'] != $id) {
                return ['success' => false, 'mensaje' => 'Ya existe otro cliente con ese teléfono'];
            }
            
            $stmt = $this->db->prepare("
                UPDATE clientes 
                SET nombre = ?, telefono = ?, email = ?, direccion = ?, notas = ?
                WHERE id = ?
            ");
            
            $resultado = $stmt->execute([
                $datos['nombre'],
                $datos['telefono'],
                $datos['email'] ?? null,
                $datos['direccion'] ?? null,
                $datos['notas'] ?? null,
                $id
            ]);
            
            return ['success' => $resultado, 'mensaje' => $resultado ? 'Cliente actualizado' : 'Error al actualizar'];
            
        } catch (PDOException $e) {
            registrarLog("Error al actualizar cliente: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al actualizar cliente'];
        }
    }
    
    // Eliminar cliente (solo si no tiene reservas)
    public function eliminar($id) {
        try {
            // Verificar si tiene reservas
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE cliente_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['total'] > 0) {
                return ['success' => false, 'mensaje' => 'No se puede eliminar: tiene reservas asociadas'];
            }
            
            // Verificar si tiene ficha de salud
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM fichas_salud WHERE cliente_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['total'] > 0) {
                return ['success' => false, 'mensaje' => 'No se puede eliminar: tiene ficha de salud asociada'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM clientes WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'mensaje' => 'Cliente eliminado'];
            
        } catch (PDOException $e) {
            registrarLog("Error al eliminar cliente: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'mensaje' => 'Error al eliminar'];
        }
    }
    
    // Contar clientes totales
    public function contar() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM clientes");
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Obtener historial de reservas del cliente
    public function obtenerHistorialReservas($cliente_id, $limite = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, t.nombre as tratamiento_nombre, u.nombre as ubicacion_nombre
                FROM reservas r
                LEFT JOIN tratamientos t ON r.tratamiento_id = t.id
                LEFT JOIN ubicaciones u ON r.ubicacion_id = u.id
                WHERE r.cliente_id = ?
                ORDER BY r.fecha DESC, r.hora DESC
                LIMIT ?
            ");
            $stmt->execute([$cliente_id, $limite]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al obtener historial: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
}
