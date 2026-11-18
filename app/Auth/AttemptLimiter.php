<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;
use Throwable;

final class AttemptLimiter
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function recordAttempt(string $address, int $limit = 10): bool
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
            } elseif ((int) $window['attempts'] < $limit) {
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
