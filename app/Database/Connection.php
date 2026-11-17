<?php

declare(strict_types=1);

namespace App\Database;

use Dotenv\Dotenv;
use PDO;
use RuntimeException;

final class Connection
{
    public static function open(): PDO
    {
        $environment = Dotenv::createImmutable(dirname(__DIR__, 2));
        $environment->safeLoad();
        $environment->required([
            'DB_HOST',
            'DB_PORT',
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
        ])->notEmpty();

        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'];
        $port = filter_var($_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        $database = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'];

        if ($port === false || preg_match('/[;\x00-\x1F]/', $host . $database) === 1) {
            throw new RuntimeException('Configuration de la base de données invalide.');
        }

        return new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $_ENV['DB_USER'] ?? $_SERVER['DB_USER'],
            $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
}
