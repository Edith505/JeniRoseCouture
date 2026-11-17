<?php $headingTag = ($serviceHeading ?? null) === 'h2' ? 'h2' : 'h3'; ?>
<?php if ($services === []): ?>
    <p>Les prestations seront affichées dès leur publication.</p>
<?php else: ?>
    <ul class="service-grid" role="list">
        <?php foreach ($services as $service): ?>
            <?php $imageUrl = \App\Catalog\Catalogue::imageUrl($service); ?>
            <li class="service-card">
                <?php if ($imageUrl !== null): ?>
                    <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                        width="480" height="360" loading="lazy" decoding="async" alt="">
                <?php endif; ?>
                <div class="service-copy">
                    <<?= $headingTag ?>>
                        <a href="/index.php/prestations/<?= rawurlencode($service['slug']) ?>">
                            <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </<?= $headingTag ?>>
                    <p><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
