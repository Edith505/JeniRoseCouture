<?php $headingTag = ($serviceHeading ?? null) === 'h2' ? 'h2' : 'h3'; ?>
<ul class="service-grid" role="list">
    <?php foreach (require __DIR__ . '/services.php' as $service): ?>
        <li class="service-card">
            <img src="/assets/images/<?= htmlspecialchars($service['image'], ENT_QUOTES, 'UTF-8') ?>"
                width="480" height="360" loading="lazy" decoding="async" alt="">
            <div class="service-copy">
                <<?= $headingTag ?>><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></<?= $headingTag ?>>
                <p><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
