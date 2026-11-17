<div class="catalogue-toolbar">
    <h1>Aperçu du brouillon</h1>
    <a class="text-link" href="/index.php?route=admin-a-propos">Revenir à l’éditeur</a>
</div>
<p>Ces changements deviennent visibles sur le site après publication.</p>
<section class="about-preview">
    <header>
        <h2><?= htmlspecialchars($content['headline'], ENT_QUOTES, 'UTF-8') ?></h2>
    </header>
    <?php $preview = true; require dirname(__DIR__) . '/partials/about-content.php'; ?>
</section>
