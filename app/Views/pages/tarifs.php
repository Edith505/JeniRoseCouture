<div class="prose">
    <h2>Exemples de tarifs</h2>
    <p id="notice-tarifs">Ces montants servent à la démonstration. Les tarifs de l’entreprise restent à confirmer.</p>
</div>
<dl class="price-list" aria-describedby="notice-tarifs">
    <?php foreach (require dirname(__DIR__) . '/partials/services.php' as $service): ?>
        <div>
            <dt><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></dt>
            <dd><?= htmlspecialchars($service['price'], ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
    <?php endforeach; ?>
</dl>
