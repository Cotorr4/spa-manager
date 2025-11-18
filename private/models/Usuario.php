<?php
require_once __DIR__ . '/../database/conexion.php';

class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Autenticar usuario
    public function autenticar($usuario, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, usuario, nombre, activo 
                FROM usuarios 
                WHERE usuario = ? AND password = ? AND activo = 1
                LIMIT 1
            ");
            $stmt->execute([$usuario, $password]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error en autenticación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener usuario por ID
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, usuario, nombre, activo, created_at 
                FROM usuarios 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            registrarLog("Error al obtener usuario: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Listar todos los usuarios
    public function listar() {
        try {
            $stmt = $this->db->query("
                SELECT id, usuario, nombre, activo, created_at 
                FROM usuarios 
                ORDER BY nombre
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar usuarios: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Crear usuario
    public function crear($usuario, $password, $nombre) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (usuario, password, nombre) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$usuario, $password, $nombre]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            registrarLog("Error al crear usuario: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Actualizar usuario
    public function actualizar($id, $datos) {
        try {
            $campos = [];
            $valores = [];
            
            if (isset($datos['nombre'])) {
                $campos[] = "nombre = ?";
                $valores[] = $datos['nombre'];
            }
            
            if (isset($datos['password'])) {
                $campos[] = "password = ?";
                $valores[] = $datos['password'];
            }
            
            if (isset($datos['activo'])) {
                $campos[] = "activo = ?";
                $valores[] = $datos['activo'];
            }
            
            if (empty($campos)) return false;
            
            $valores[] = $id;
            $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($valores);
        } catch (PDOException $e) {
            registrarLog("Error al actualizar usuario: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
