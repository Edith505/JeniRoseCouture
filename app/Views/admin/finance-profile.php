<p>Renseignez les informations fiscales et légales validées pour votre entreprise. Elles seront reprises sur les prochains documents.</p>
<form class="catalogue-form admin-card" method="post" action="<?= $e($base) ?>">
    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
    <?php foreach ([
        'name' => ['Dénomination légale', 190, true], 'address' => ['Adresse du siège', 200, true],
        'postal' => ['Code postal', 5, true], 'city' => ['Ville', 120, true], 'siren' => ['SIREN', 9, true],
        'registration' => ['Forme juridique et immatriculation', 200, true],
    ] as $field => [$label, $size, $required]): ?>
        <label><?= $e($label) ?><input class="field-input" name="<?= $field ?>" maxlength="<?= $size ?>" <?= $required ? 'required' : '' ?> value="<?= $value($field, $profile[$field] ?? '') ?>"></label>
    <?php endforeach; ?>
    <label>Régime de TVA
        <select name="regime" class="field-input" required>
            <option value="">Choisir le régime</option>
            <option value="franchise" <?= $value('regime', $profile['regime'] ?? '') === 'franchise' ? 'selected' : '' ?>>Franchise en base</option>
            <option value="taxable" <?= $value('regime', $profile['regime'] ?? '') === 'taxable' ? 'selected' : '' ?>>TVA facturée</option>
        </select>
    </label>
    <label>Numéro de TVA si applicable <input class="field-input" name="vat_number" maxlength="24" value="<?= $value('vat_number', $profile['vat_number'] ?? '') ?>"></label>
    <label>Mention du régime de TVA (requise pour la franchise) <textarea class="field-input" name="tax_notice" rows="2" maxlength="300"><?= $value('tax_notice', $profile['tax_notice'] ?? '') ?></textarea></label>
    <label>Conditions d’escompte <input class="field-input" name="discount" maxlength="220" required value="<?= $value('discount', $profile['discount'] ?? '') ?>"></label>
    <label>Pénalités de retard pour les professionnels <input class="field-input" name="late_fees" maxlength="220" value="<?= $value('late_fees', $profile['late_fees'] ?? '') ?>"></label>
    <label>Mentions propres à votre activité, dont l’assurance si applicable <textarea class="field-input" name="additional_mentions" rows="4" maxlength="900"><?= $value('additional_mentions', $profile['additional_mentions'] ?? '') ?></textarea></label>
    <button class="action action-primary" name="intent" value="profile">Enregistrer le profil</button>
</form>
