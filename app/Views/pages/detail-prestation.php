<?php
$imageUrl = \App\Catalog\Catalogue::imageUrl($service);
$examples = array_filter(array_map('trim', explode("\n", $service['examples'])));
?>
<div class="editorial-split">
    <section class="prose" aria-label="Informations sur la prestation">
        <h2>Tarif et modalités</h2>
        <p><?php require dirname(__DIR__) . '/partials/service-price.php'; ?></p>
        <p><?= htmlspecialchars(\App\Catalog\Catalogue::MODES[$service['service_mode']], ENT_QUOTES, 'UTF-8') ?>
            <?php if ($service['duration_minutes'] !== null): ?>
                · Durée estimée : <?= (int) $service['duration_minutes'] ?> minutes
            <?php endif; ?>
        </p>
        <?php if ($examples !== []): ?>
            <h2>Exemples</h2>
            <ul>
                <?php foreach ($examples as $example): ?>
                    <li><?= htmlspecialchars($example, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($service['conditions'] !== ''): ?>
            <h2>Conditions</h2>
            <p class="catalogue-lines"><?= htmlspecialchars($service['conditions'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <a class="action action-outline" href="/index.php/contact">Informations de contact</a>
    </section>
    <?php if ($imageUrl !== null): ?>
        <img class="page-photo" src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
            width="480" height="360" alt="">
    <?php endif; ?>
</div>
