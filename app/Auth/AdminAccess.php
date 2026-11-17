<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;
use Throwable;

final class AdminAccess
{
    private const DUMMY_HASH = '$2y$12$E6oUdxbe30f5BYhRggc4u.L.lVkUze3mzBf7I2..mvCkVhIMqLz2u';
    private const MAX_ATTEMPTS = 10;

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
        $source = hash('sha256', $address);
        $this->database->beginTransaction();

        try {
            $insert = $this->database->prepare(
                'INSERT INTO login_attempts (source_hash, window_started_at)
                 VALUES (?, UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE source_hash = login_attempts.source_hash'
            );
            $insert->execute([$source]);

            $read = $this->database->prepare(
                'SELECT attempts, window_started_at <= UTC_TIMESTAMP(6) - INTERVAL 15 MINUTE AS expired
                 FROM login_attempts WHERE source_hash = ? FOR UPDATE'
            );
            $read->execute([$source]);
            $window = $read->fetch();

            if ((int) $window['expired'] === 1) {
                $update = $this->database->prepare(
                    'UPDATE login_attempts SET attempts = 1, window_started_at = UTC_TIMESTAMP(6) WHERE source_hash = ?'
                );
                $update->execute([$source]);
                $allowed = true;
            } elseif ((int) $window['attempts'] < self::MAX_ATTEMPTS) {
                $update = $this->database->prepare(
                    'UPDATE login_attempts SET attempts = attempts + 1 WHERE source_hash = ?'
                );
                $update->execute([$source]);
                $allowed = true;
            } else {
                $allowed = false;
            }

            $this->database->commit();

            return $allowed;
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $error;
        }
    }
}
