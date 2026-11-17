<?php

declare(strict_types=1);

use App\Database\Connection;
use Dotenv\Exception\ExceptionInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $result = Connection::open()->query('SELECT 1')->fetchColumn();

    if ((string) $result !== '1') {
        fwrite(STDERR, "Résultat inattendu pour SELECT 1.\n");
        exit(1);
    }

    echo "Connexion PDO : 1\n";
} catch (PDOException $error) {
    fwrite(STDERR, 'Connexion PDO impossible. SQLSTATE : ' . $error->getCode() . PHP_EOL);
    exit(1);
} catch (ExceptionInterface | RuntimeException) {
    fwrite(STDERR, "Configuration de la base absente ou invalide. Vérifiez le fichier .env.\n");
    exit(1);
}
