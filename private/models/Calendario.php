<?php
require_once __DIR__ . '/../database/conexion.php';
require_once __DIR__ . '/../helpers/utils.php';

class Calendario {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Habilitar mes completo (todos los días, todos los slots)
    public function habilitarMesCompleto($anio, $mes, $ubicacion_id) {
        try {
            $primerDia = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
            $ultimoDia = date('Y-m-t', strtotime($primerDia));
            
            $fecha = new DateTime($primerDia);
            $fin = new DateTime($ultimoDia);
            
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO calendario_slots (fecha, hora, ubicacion_id, disponible)
                VALUES (?, ?, ?, 1)
            ");
            
            while ($fecha <= $fin) {
                $fechaStr = $fecha->format('Y-m-d');
                $diaSemana = $fecha->format('N'); // 1=Lun, 7=Dom
                
                // Saltar domingos
                if ($diaSemana != 7) {
                    // Todos los slots de 8:00 a 17:30
                    for ($h = 8; $h < 18; $h++) {
                        for ($m = 0; $m < 60; $m += 30) {
                            $hora = sprintf('%02d:%02d:00', $h, $m);
                            $stmt->execute([$fechaStr, $hora, $ubicacion_id]);
                        }
                    }
                }
                
                $fecha->modify('+1 day');
            }
            
            return true;
        } catch (PDOException $e) {
            registrarLog("Error al habilitar mes: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Toggle slot individual
    public function toggleSlot($fecha, $hora, $ubicacion_id, $habilitar) {
        try {
            if ($habilitar) {
                $stmt = $this->db->prepare("
                    INSERT INTO calendario_slots (fecha, hora, ubicacion_id, disponible)
                    VALUES (?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE disponible = 1
                ");
                $stmt->execute([$fecha, $hora, $ubicacion_id]);
            } else {
                $stmt = $this->db->prepare("
                    DELETE FROM calendario_slots
                    WHERE fecha = ? AND hora = ? AND ubicacion_id = ?
                ");
                $stmt->execute([$fecha, $hora, $ubicacion_id]);
            }
            return true;
        } catch (PDOException $e) {
            registrarLog("Error al toggle slot: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    // Obtener slots de un día
    public function obtenerSlotsDia($fecha, $ubicacion_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT fecha, hora, ubicacion_id, disponible
                FROM calendario_slots
                WHERE fecha = ? AND ubicacion_id = ?
                ORDER BY hora
            ");
            $stmt->execute([$fecha, $ubicacion_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al obtener slots: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    // Obtener slots de un rango (semana o mes)
    public function obtenerSlotsRango($fecha_inicio, $fecha_fin) {
        try {
            $stmt = $this->db->prepare("
                SELECT fecha, hora, ubicacion_id, disponible
                FROM calendario_slots
                WHERE fecha BETWEEN ? AND ?
                ORDER BY fecha, hora
            ");
            $stmt->execute([$fecha_inicio, $fecha_fin]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarLog("Error al obtener rango: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
}
