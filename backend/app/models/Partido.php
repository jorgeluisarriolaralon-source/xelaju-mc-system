<?php
/**
 * MODELO - PARTIDO
 * Sistema Xelajú MC
 */

namespace App\Models;

use App\Database\Database;

class Partido
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crear nuevo partido
     */
    public function crear(array $data): bool
    {
        $sql = "INSERT INTO partidos (temporada_id, local, visitante, fecha, estadio, capacidad, estado, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        return $this->db->execute($sql, [
            $data['temporada_id'],
            $data['local'],
            $data['visitante'],
            $data['fecha'],
            $data['estadio'],
            $data['capacidad'] ?? 6000,
            $data['estado'] ?? 'programado'
        ]) !== false;
    }

    /**
     * Obtener partido por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM partidos WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Obtener todos los partidos
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM partidos ORDER BY fecha DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    /**
     * Obtener partidos programados
     */
    public function getProgramados(int $limit = 10): array
    {
        $sql = "SELECT * FROM partidos WHERE estado = 'programado' AND fecha >= NOW() 
                ORDER BY fecha ASC LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Obtener partidos próximos (siguientes 7 días)
     */
    public function getProximos(): array
    {
        $sql = "SELECT * FROM partidos 
                WHERE estado = 'programado' 
                AND fecha >= NOW() 
                AND fecha <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                ORDER BY fecha ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener partidos por temporada
     */
    public function getByTemporada(int $temporadaId, int $limit = 50): array
    {
        $sql = "SELECT * FROM partidos WHERE temporada_id = ? ORDER BY fecha DESC LIMIT ?";
        return $this->db->fetchAll($sql, [$temporadaId, $limit]);
    }

    /**
     * Actualizar partido
     */
    public function actualizar(int $id, array $data): bool
    {
        unset($data['id']);
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $sql = "UPDATE partidos SET $set WHERE id = ?";

        $params = array_merge(array_values($data), [$id]);
        return $this->db->execute($sql, $params) !== false;
    }

    /**
     * Cambiar estado del partido
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $sql = "UPDATE partidos SET estado = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$estado, $id]) !== false;
    }

    /**
     * Obtener ocupación del partido
     */
    public function getOcupacion(int $partidoId): array
    {
        $sql = "SELECT 
                COUNT(*) as total_boletos,
                SUM(CASE WHEN estado = 'vendido' THEN 1 ELSE 0 END) as vendidos,
                SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN estado = 'usado' THEN 1 ELSE 0 END) as usados
                FROM boletos WHERE partido_id = ?";
        
        return $this->db->fetchOne($sql, [$partidoId]) ?? [];
    }

    /**
     * Obtener ingresos por partido
     */
    public function getIngresos(int $partidoId): array
    {
        $sql = "SELECT 
                SUM(p.monto) as total_ingresos,
                COUNT(DISTINCT p.id) as total_transacciones
                FROM pagos p
                INNER JOIN boletos b ON p.boleto_id = b.id
                WHERE b.partido_id = ? AND p.estado = 'completado'";
        
        return $this->db->fetchOne($sql, [$partidoId]) ?? [];
    }

    /**
     * Obtener total de partidos
     */
    public function getTotalCount(): int
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM partidos");
        return $result['count'] ?? 0;
    }

    /**
     * Verificar si existe conflicto de horario
     */
    public function hasConflict(string $fecha, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) as count FROM partidos 
                WHERE DATE(fecha) = DATE(?) 
                AND estado != 'cancelado'
                AND id != ?";
        
        $result = $this->db->fetchOne($sql, [$fecha, $excludeId]);
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Eliminar partido
     */
    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM partidos WHERE id = ?";
        return $this->db->execute($sql, [$id]) !== false;
    }
}
