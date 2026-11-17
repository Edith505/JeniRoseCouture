<?php

declare(strict_types=1);

use App\Database\Connection;

require dirname(__DIR__) . '/vendor/autoload.php';

$email = trim($argv[1] ?? '');
$name = trim($argv[2] ?? '');
$password = rtrim((string) fgets(STDIN), "\r\n");

if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || $name === '' || preg_match('/^.{1,150}$/u', $name) !== 1
    || strlen($password) < 14 || strlen($password) > 72 || str_contains($password, "\0")
) {
    fwrite(STDERR, "Adresse ou nom invalide, ou mot de passe hors de la plage de 14 à 72 octets.\n");
    exit(1);
}

try {
    $database = Connection::open();
    $insert = $database->prepare(
        'INSERT INTO users (role_id, email, display_name, password_hash)
         SELECT roles.id, ?, ?, ? FROM roles
         JOIN role_permissions ON role_permissions.role_id = roles.id
         JOIN permissions ON permissions.id = role_permissions.permission_id
         WHERE roles.code = ? AND permissions.code = ?'
    );
    $insert->execute([$email, $name, password_hash($password, PASSWORD_DEFAULT), 'admin', 'admin.access']);
    if ($insert->rowCount() !== 1) {
        fwrite(STDERR, "Rôle administrateur ou permission manquante. Vérifiez les seeders.\n");
        exit(1);
    }
} catch (PDOException $error) {
    fwrite(STDERR, 'Création impossible. SQLSTATE : ' . $error->getCode() . PHP_EOL);
    exit(1);
} catch (Throwable) {
    fwrite(STDERR, "Création impossible. Vérifiez la configuration locale.\n");
    exit(1);
}

echo "Compte administrateur créé.\n";
