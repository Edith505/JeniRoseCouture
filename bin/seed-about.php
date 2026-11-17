<?php

declare(strict_types=1);

use App\About\AboutContent;
use App\Database\Connection;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $db = Connection::open();
    if (!AboutContent::local()) {
        throw new RuntimeException('L’initialisation des exemples est réservée à APP_ENV=local.');
    }

    $db->beginTransaction();
    if ($db->query('SELECT 1 FROM about_profiles LIMIT 1 FOR UPDATE')->fetchColumn()) {
        $db->commit();
        echo "La page À propos est déjà initialisée. Les contenus existants sont conservés.\n";
        exit;
    }

    $portraitPath = dirname(__DIR__) . '/storage/local/portrait-jeni-rose.webp';
    $portrait = is_file($portraitPath) ? file_get_contents($portraitPath) : null;
    if ($portrait !== null && ($portrait === false || !str_starts_with($portrait, 'RIFF') || substr($portrait, 8, 4) !== 'WEBP')) {
        throw new RuntimeException('Le portrait local doit être un fichier WebP valide.');
    }

    $live = AboutContent::defaults();
    $draft = $live;
    $draft['story'] = "Depuis plus de 15 ans, je mets mon savoir-faire et ma passion pour la couture au service de vos vêtements.\n\nCe qui a commencé comme un loisir est devenu une vocation. Après une formation en couture, j’ai perfectionné ma technique auprès de différents artisans.\n\nMon objectif est de redonner vie à vos vêtements et de créer des pièces qui reflètent votre personnalité.";
    $draft['quote'] = 'La couture est un art, chaque vêtement une toile.';

    $profile = $db->prepare('INSERT INTO about_profiles (version, content, is_demo, portrait_webp) VALUES (?, ?, ?, ?)');
    foreach (['draft' => $draft, 'published' => $live] as $version => $content) {
        $profile->bindValue(1, $version);
        $profile->bindValue(2, json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $profile->bindValue(3, (int) ($version === 'draft'), PDO::PARAM_INT);
        $profile->bindValue(4, $portrait, $portrait === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        $profile->execute();
    }

    $entry = $db->prepare('INSERT INTO about_entries
        (version, entry_key, kind, title, organisation, period, description, responsibilities, competencies,
            sort_order, visible, is_demo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
    $real = [
        'skill' => ['Création sur mesure', 'Couture', 'Retouches', 'Réparation', 'Entretien textile', 'Conseils personnalisés'],
        'value' => ['Passion', 'Précision', 'Créativité', 'Qualité', 'Écoute', 'Satisfaction client'],
    ];
    foreach ($real as $kind => $titles) {
        foreach ($titles as $i => $title) {
            $key = bin2hex(random_bytes(16));
            foreach (['draft', 'published'] as $version) {
                $entry->execute([$version, $key, $kind, $title, '', '', '', '', '', ($i + 1) * 10, 0]);
            }
        }
    }
    $entry->execute(['draft', bin2hex(random_bytes(16)), 'education', 'Apprentissage des techniques de couture',
        'Atelier-école fictif', 'Exemple : 2012', 'Pratique de la coupe, du patronage et des finitions.', '', '', 10, 1]);
    $entry->execute(['draft', bin2hex(random_bytes(16)), 'experience', 'Couturière en atelier',
        'Atelier fictif', 'Période d’exemple : 2014 à 2018', 'Confection et retouches de vêtements.',
        'Préparer les pièces et effectuer les ajustements.', 'Essayage et finitions à la main.', 10, 1]);

    $db->commit();
    echo $portrait === null
        ? "Page À propos initialisée. Ajoutez un portrait dans l’éditeur. Les exemples restent dans le brouillon.\n"
        : "Page À propos initialisée. Le portrait est visible sur le site local. Les exemples restent dans le brouillon.\n";
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, 'Initialisation impossible. Vérifiez la migration et APP_ENV=local, ainsi que le fichier WebP si un portrait local est présent.' . PHP_EOL);
    exit(1);
}
