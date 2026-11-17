<?php
$bodyClass = 'admin-page';
require __DIR__ . '/document-start.php';
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require __DIR__ . '/brand.php'; ?>
        <nav aria-label="Navigation de l’administration">
            <a class="admin-nav-current" href="/index.php?route=admin" aria-current="page">Vue d’ensemble</a>
        </nav>
    </aside>
    <div class="admin-content">
        <header class="admin-bar">
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
