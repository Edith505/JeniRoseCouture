<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;

final class AdminAccess
{
    private const DUMMY_HASH = '$2y$12$E6oUdxbe30f5BYhRggc4u.L.lVkUze3mzBf7I2..mvCkVhIMqLz2u';

    public function __construct(private readonly PDO $database)
    {
    }

    public function authenticate(string $email, string $password): ?int
    {
        $query = $this->database->prepare(
            'SELECT id, password_hash FROM users WHERE email = ? AND disabled_at IS NULL'
        );
        $query->execute([$email]);
        $user = $query->fetch();
        $matches = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH);

        if (!$matches || !$user || !$this->can((int) $user['id'], 'admin.access')) {
            return null;
        }

        return (int) $user['id'];
    }

    public function can(int $userId, string $permission): bool
    {
        $query = $this->database->prepare(
            'SELECT 1 FROM users
             JOIN role_permissions ON role_permissions.role_id = users.role_id
             JOIN permissions ON permissions.id = role_permissions.permission_id
             WHERE users.id = ? AND users.disabled_at IS NULL AND permissions.code = ?'
        );
        $query->execute([$userId, $permission]);

        return $query->fetchColumn() !== false;
    }

    public function recordAttempt(string $address): bool
    {
        return (new AttemptLimiter($this->database))->recordAttempt($address);
    }
}
