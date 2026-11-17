<?php
$navigation = [
    '/' => 'Accueil',
    '/a-propos' => 'À propos',
    '/prestations' => 'Prestations',
    '/tarifs' => 'Tarifs',
    '/galerie' => 'Galerie',
    '/contact' => 'Contact',
];
?>
<header class="site-header">
    <div class="site-width header-inner">
        <?php require __DIR__ . '/brand.php'; ?>
        <nav class="public-nav" aria-label="Navigation principale">
            <?php foreach ($navigation as $pagePath => $linkLabel): ?>
                <a href="<?= $pagePath === '/' ? '/' : '/index.php' . $pagePath ?>"
                    <?= $pagePath === ($activePage ?? null) ? 'aria-current="page"' : '' ?>><?= $linkLabel ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
