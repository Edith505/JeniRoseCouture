<?php
$showExample = ($catalogueNavigation ?? false) || ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? '') === 'local';
$priceDemo = (bool) $service['price_is_demo'];
$priceLabel = $priceDemo && !$showExample ? 'Tarif à confirmer' : \App\Catalog\Catalogue::priceLabel($service);
?>
<?= htmlspecialchars($priceLabel, ENT_QUOTES, 'UTF-8') ?>
<?php if ($priceDemo && $showExample): ?>
    <small class="catalogue-note">Exemple de démonstration</small>
<?php endif; ?>
