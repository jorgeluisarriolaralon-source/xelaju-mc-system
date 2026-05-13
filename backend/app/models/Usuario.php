<?php
/**
 * MODELO - USUARIO
 * Sistema Xelajú MC
 */

namespace App\Models;

use App\Database\Database;

class Usuario
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crear nuevo usuario
     */
    public function crear(array $data): bool
    {
        $sql = "INSERT INTO usuarios (nombre, apellido, email, password, rol, foto_url, activo, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $password = hashPassword($data['password']);

        $result = $this->db->execute($sql, [
            $data['nombre'],
            $data['apellido'],
            $data['email'],
            $password,
            $data['rol'] ?? 'usuario',
            $data['foto_url'] ?? null,
            true
        ]);

        return $result !== false;
    }

    /**
     * Obtener usuario por email
     */
    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM usuarios WHERE email = ? AND activo = true";
        return $this->db->fetchOne($sql, [$email]);
    }

    /**
     * Obtener usuario por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT id, nombre, apellido, email, rol, foto_url, activo, created_at, updated_at 
                FROM usuarios WHERE id = ? AND activo = true";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Obtener todos los usuarios
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT id, nombre, apellido, email, rol, foto_url, activo, created_at 
                FROM usuarios WHERE activo = true ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    /**
     * Obtener usuarios por rol
     */
    public function getByRole(string $role, int $limit = 50): array
    {
        $sql = "SELECT id, nombre, apellido, email, rol, foto_url, created_at 
                FROM usuarios WHERE rol = ? AND activo = true ORDER BY created_at DESC LIMIT ?";
        return $this->db->fetchAll($sql, [$role, $limit]);
    }

    /**
     * Actualizar usuario
     */
    public function actualizar(int $id, array $data): bool
    {
        unset($data['password'], $data['id']);
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $sql = "UPDATE usuarios SET $set WHERE id = ?";

        $params = array_merge(array_values($data), [$id]);
        return $this->db->execute($sql, $params) !== false;
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(int $id, string $newPassword): bool
    {
        $hashedPassword = hashPassword($newPassword);
        $sql = "UPDATE usuarios SET password = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$hashedPassword, $id]) !== false;
    }

    /**
     * Desactivar usuario
     */
    public function desactivar(int $id): bool
    {
        $sql = "UPDATE usuarios SET activo = false, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$id]) !== false;
    }

    /**
     * Verificar si el email ya existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM usuarios WHERE email = ? AND id != ?";
            $result = $this->db->fetchOne($sql, [$email, $excludeId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM usuarios WHERE email = ?";
            $result = $this->db->fetchOne($sql, [$email]);
        }

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Obtener total de usuarios
     */
    public function getTotalCount(): int
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM usuarios WHERE activo = true");
        return $result['count'] ?? 0;
    }

    /**
     * Obtener total por rol
     */
    public function getTotalByRole(string $role): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM usuarios WHERE rol = ? AND activo = true",
            [$role]
        );
        return $result['count'] ?? 0;
    }

    /**
     * Obtener usuarios activos hoy
     */
    public function getActiveToday(): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM usuarios WHERE activo = true AND DATE(updated_at) = DATE(NOW())"
        );
        return $result['count'] ?? 0;
    }
}
