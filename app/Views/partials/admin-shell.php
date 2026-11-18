<?php
$bodyClass = 'admin-page';
require __DIR__ . '/document-start.php';
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require __DIR__ . '/brand.php'; ?>
        <nav aria-label="Navigation de l’administration">
            <a href="/index.php?route=admin" <?= ($activeAdminPage ?? '') === 'overview' ? 'aria-current="page"' : '' ?>>Vue d’ensemble</a>
            <?php if ($catalogueNavigation ?? false): ?>
                <a href="/index.php?route=admin-catalogue" <?= ($activeAdminPage ?? '') === 'catalogue' ? 'aria-current="page"' : '' ?>>Prestations et tarifs</a>
            <?php endif; ?>
            <?php if ($requestNavigation ?? false): ?>
                <a href="/index.php?route=admin-demandes" <?= ($activeAdminPage ?? '') === 'requests' ? 'aria-current="page"' : '' ?>>Rendez-vous et devis</a>
            <?php endif; ?>
            <?php if ($salesNavigation ?? false): ?>
                <a href="/index.php?route=admin-clients" <?= ($activeAdminPage ?? '') === 'clients' ? 'aria-current="page"' : '' ?>>Clients</a>
                <a href="/index.php?route=admin-commandes" <?= ($activeAdminPage ?? '') === 'orders' ? 'aria-current="page"' : '' ?>>Commandes</a>
            <?php endif; ?>
            <?php if ($aboutNavigation ?? false): ?>
                <a href="/index.php?route=admin-a-propos" <?= ($activeAdminPage ?? '') === 'about' ? 'aria-current="page"' : '' ?>>Contenu du site · À propos</a>
            <?php endif; ?>
        </nav>
    </aside>
    <div class="admin-content">
        <header class="admin-bar">
            <?php if (!empty($adminName)): ?>
                <span class="admin-identity"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <a class="text-link" href="/">Voir le site <span aria-hidden="true">↗</span></a>
            <form method="post" action="/index.php?route=admin-logout">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button class="action action-outline" type="submit">Se déconnecter</button>
            </form>
        </header>
        <main id="contenu" tabindex="-1" class="admin-main">
            <?php require $contentView; ?>
        </main>
    </div>
</div>
</body>
</html>
