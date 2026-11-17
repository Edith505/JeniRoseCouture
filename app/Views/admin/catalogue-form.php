<?php
$value = static fn (string $key, string $default = ''): string
    => htmlspecialchars((string) ($item[$key] ?? $default), ENT_QUOTES, 'UTF-8');
$formAction = '/index.php?route=' . ($categoryForm ? 'admin-categorie' : 'admin-prestation')
    . ($id === null ? '' : '&amp;id=' . $id);
?>
<p><a class="text-link" href="/index.php?route=admin-catalogue">← Retour au catalogue</a></p>
<h1><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
<?php if ($message !== null): ?>
    <p class="form-message" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form class="catalogue-form admin-card" method="post" action="<?= $formAction ?>"
    <?= $categoryForm ? '' : 'enctype="multipart/form-data"' ?>>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="catalogue-form-row">
        <label>Nom
            <input class="field-input" name="name" maxlength="140" value="<?= $value('name') ?>" required>
        </label>
        <label>Ordre d’affichage
            <input class="field-input" name="sort_order" type="number" min="0" max="65535" value="<?= $value('sort_order', '0') ?>" required>
        </label>
    </div>
    <?php if (!$categoryForm): ?>
        <label>Catégorie
            <select class="field-input" name="category_id" required>
                <option value="">Choisir une catégorie</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= $value('category_id') === (string) $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?><?= $category['is_enabled'] ? '' : ' (masquée)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Description
            <textarea class="field-input" name="description" rows="4" maxlength="2000" required><?= $value('description') ?></textarea>
        </label>
        <label>Exemples, un par ligne
            <textarea class="field-input" name="examples" rows="3" maxlength="2000"><?= $value('examples') ?></textarea>
        </label>
        <label>Conditions
            <textarea class="field-input" name="conditions" rows="3" maxlength="2000"><?= $value('conditions') ?></textarea>
        </label>
        <div class="catalogue-form-row">
            <label>Type de tarif
                <select class="field-input" name="price_type">
                    <?php foreach (\App\Catalog\Catalogue::PRICE_TYPES as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $value('price_type', 'quote') === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Montant en euros
                <input class="field-input" name="price_amount" inputmode="decimal" maxlength="12" value="<?= $value('price_amount') ?>">
                <small>Laissez vide pour un devis ou un tarif personnalisé.</small>
            </label>
        </div>
        <label class="catalogue-check">
            <input type="checkbox" name="price_is_demo" value="1" <?= ($item['price_is_demo'] ?? true) ? 'checked' : '' ?>>
            Identifier ce prix comme exemple de démonstration
        </label>
        <div class="catalogue-form-row">
            <label>Durée estimée en minutes
                <input class="field-input" name="duration_minutes" type="number" min="1" max="65535" value="<?= $value('duration_minutes') ?>">
            </label>
            <label>Mode de prestation
                <select class="field-input" name="service_mode">
                    <?php foreach (\App\Catalog\Catalogue::MODES as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $value('service_mode', 'confirm') === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Action à proposer après l’activation des formulaires
            <select class="field-input" name="action_intent">
                <?php foreach (\App\Catalog\Catalogue::ACTIONS as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $value('action_intent', 'information') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Photographie
            <input class="field-input" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
            <small>JPEG, PNG ou WebP, 2 Mio maximum.</small>
        </label>
        <?php if (!empty($item['asset_name']) || !empty($item['has_upload'])): ?>
            <label class="catalogue-check"><input type="checkbox" name="remove_image" value="1"> Supprimer la photographie actuelle</label>
        <?php endif; ?>
    <?php endif; ?>
    <label class="catalogue-check">
        <input type="checkbox" name="is_enabled" value="1" <?= !empty($item['is_enabled']) ? 'checked' : '' ?>>
        <?= $categoryForm ? 'Afficher cette catégorie' : 'Publier cette prestation' ?>
    </label>
    <button class="action action-primary" type="submit">Enregistrer</button>
</form>
<?php if (!$categoryForm && $id !== null): ?>
    <details class="catalogue-archive">
        <summary>Archiver cette prestation</summary>
        <p>La prestation sera retirée du site et conservée dans l’historique du catalogue.</p>
        <form method="post" action="<?= $formAction ?>">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button class="action action-outline" type="submit" name="intent" value="archive">Confirmer l’archivage</button>
        </form>
    </details>
<?php endif; ?>
