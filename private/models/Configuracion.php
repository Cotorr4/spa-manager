<?php
require_once __DIR__ . '/../database/conexion.php';
require_once __DIR__ . '/../helpers/utils.php';

class Configuracion {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Perfiles
    public function listarPerfiles() {
        try {
            $stmt = $this->db->query("SELECT * FROM perfiles_ubicacion ORDER BY activo DESC, nombre");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al listar perfiles: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    public function obtenerPerfilActivo() {
        try {
            $stmt = $this->db->query("SELECT * FROM perfiles_ubicacion WHERE activo = 1 LIMIT 1");
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function activarPerfil($id) {
        try {
            $this->db->beginTransaction();
            $this->db->exec("UPDATE perfiles_ubicacion SET activo = 0");
            $stmt = $this->db->prepare("UPDATE perfiles_ubicacion SET activo = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            registrarLog("Error al activar perfil: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function guardarPerfil($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE perfiles_ubicacion SET
                    nombre = ?, titulo_hero = ?, subtitulo_hero = ?, mensaje_bienvenida = ?,
                    estado_operativo = ?, mensaje_estado = ?, horarios_texto = ?,
                    telefono_publico = ?, whatsapp = ?, email_publico = ?, direccion_visible = ?,
                    instagram = ?, facebook = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $datos['nombre'], $datos['titulo_hero'], $datos['subtitulo_hero'], $datos['mensaje_bienvenida'],
                $datos['estado_operativo'], $datos['mensaje_estado'], $datos['horarios_texto'],
                $datos['telefono_publico'], $datos['whatsapp'], $datos['email_publico'], $datos['direccion_visible'],
                $datos['instagram'], $datos['facebook'], $id
            ]);
        } catch (PDOException $e) {
            registrarLog("Error al guardar perfil: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Testimonios
    public function listarTestimonios() {
        try {
            $stmt = $this->db->query("SELECT * FROM testimonios ORDER BY orden, id DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function guardarTestimonio($datos) {
        try {
            if (isset($datos['id']) && $datos['id']) {
                $stmt = $this->db->prepare("
                    UPDATE testimonios SET nombre_cliente = ?, testimonio = ?, calificacion = ?, activo = ?
                    WHERE id = ?
                ");
                return $stmt->execute([
                    $datos['nombre_cliente'], $datos['testimonio'], $datos['calificacion'], 
                    $datos['activo'] ?? 1, $datos['id']
                ]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO testimonios (nombre_cliente, testimonio, calificacion, activo)
                    VALUES (?, ?, ?, ?)
                ");
                return $stmt->execute([
                    $datos['nombre_cliente'], $datos['testimonio'], $datos['calificacion'], $datos['activo'] ?? 1
                ]);
            }
        } catch (PDOException $e) {
            registrarLog("Error al guardar testimonio: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function eliminarTestimonio($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM testimonios WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
