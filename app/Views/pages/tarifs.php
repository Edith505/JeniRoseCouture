<?php if ($services === []): ?>
    <p>Les tarifs seront affichés dès la publication des prestations.</p>
<?php else: ?>
    <dl class="price-list">
        <?php foreach ($services as $service): ?>
            <div>
                <dt><a href="/index.php/prestations/<?= rawurlencode($service['slug']) ?>">
                    <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>
                </a></dt>
                <dd><?php require dirname(__DIR__) . '/partials/service-price.php'; ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
<?php endif; ?>
