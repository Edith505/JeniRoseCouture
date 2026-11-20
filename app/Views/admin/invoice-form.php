<form class="catalogue-form admin-card" method="post" action="<?= $e($base) ?>">
    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
    <h2>Émettre la facture</h2>
    <p>Le prix enregistré sur la commande est repris TTC. Vérifiez la date de la prestation et les coordonnées de facturation.</p>
    <label>Client
        <select class="field-input" name="buyer_type"><option value="individual">Particulier</option><option value="business" <?= $value('buyer_type') === 'business' ? 'selected' : '' ?>>Professionnel</option></select>
    </label>
    <?php foreach ([
        'buyer_name' => ['Nom de facturation', 180, true, 'name'], 'buyer_address' => ['Adresse de facturation', 200, true, 'address_line'],
        'buyer_postal' => ['Code postal de facturation', 5, true, 'postal_code'], 'buyer_city' => ['Ville de facturation', 120, true, 'city'],
        'buyer_siren' => ['SIREN du client professionnel', 9, false, ''], 'buyer_vat' => ['Numéro de TVA du client, si applicable', 24, false, ''],
        'buyer_reference' => ['Numéro du bon de commande du client, s’il existe', 100, false, ''],
    ] as $field => [$label, $size, $required, $source]): ?>
        <label><?= $e($label) ?><input class="field-input" name="<?= $field ?>" maxlength="<?= $size ?>" <?= $required ? 'required' : '' ?> value="<?= $value($field, $buyer[$source] ?? '') ?>"></label>
    <?php endforeach; ?>
    <div class="catalogue-form-row">
        <label>Date de réalisation <input type="date" class="field-input" name="service_on" max="<?= $today ?>" value="<?= $value('service_on') ?>" required></label>
        <label>Date limite de règlement <input type="date" class="field-input" name="due_on" min="<?= $today ?>" value="<?= $value('due_on', $today) ?>" required></label>
    </div>
    <?php if ($profile['regime'] === 'taxable'): ?>
        <label>Taux de TVA pour l’ensemble de la facture (%) <input name="vat_rate" inputmode="decimal" class="field-input" maxlength="6" required value="<?= $value('vat_rate') ?>"></label>
    <?php endif; ?>
    <p class="catalogue-note">L’émission attribue un numéro et archive le PDF. Une correction se fait par un avoir.</p>
    <button class="action action-primary" name="intent" value="invoice">Émettre et archiver le PDF</button>
</form>
