<?php

declare(strict_types=1);

use App\Catalog\DemoCatalogue;
use App\Database\Connection;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $db = Connection::open();
    $db->beginTransaction();

    $category = $db->prepare('INSERT INTO service_categories (slug, name, is_enabled, sort_order)
        VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $entry = $db->prepare('INSERT INTO services (category_id, slug, name, description, conditions, examples, price_type,
        price_amount, price_is_demo, service_mode, action_intent, is_enabled, sort_order, asset_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 1, ?, ?) ON DUPLICATE KEY UPDATE id = services.id');

    foreach (DemoCatalogue::services() as $service) {
        $category->execute([$service['slug'], $service['name'], 1, $service['sort_order']]);
        $entry->execute([$db->lastInsertId(), $service['slug'], $service['name'], $service['description'],
            $service['conditions'], $service['examples'], $service['price_type'], $service['price_amount'], $service['service_mode'],
            $service['action_intent'], $service['sort_order'], $service['asset_name']]);
    }
    $category->execute(['nettoyage', 'Nettoyage de vêtements', 0, 70]);

    $db->commit();
    echo "Catalogue de démonstration initialisé. La catégorie nettoyage est désactivée à sa création.\n";
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Initialisation impossible. Vérifiez la connexion et la migration 003.\n");
    exit(1);
}
