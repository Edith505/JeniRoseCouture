<?php
require __DIR__ . '/document-start.php';
require __DIR__ . '/public-header.php';
?>
<main id="contenu" tabindex="-1">
    <header class="page-heading">
        <div class="site-width">
            <h1><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </header>
    <div class="site-width page-body">
        <?php require $contentView; ?>
    </div>
</main>
<?php require __DIR__ . '/public-footer.php'; ?>
</body>
</html>
