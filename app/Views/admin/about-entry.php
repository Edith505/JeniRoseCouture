<?php
use App\About\AboutContent;

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$timeline = $kind === 'education' || $kind === 'experience';
$base = '/index.php?route=admin-a-propos-entree&amp;';
$action = $base . ($key === null ? 'kind=' . rawurlencode($kind) : 'key=' . rawurlencode($key));
$archived = !empty($item['archived']);
?>
<p><a class="text-link" href="/index.php?route=admin-a-propos">← Revenir à l’éditeur</a></p>
<h1><?= AboutContent::KINDS[$kind] ?></h1>
<?php if ($message !== null): ?>
    <p class="form-message" role="alert"><?= $escape($message) ?></p>
<?php endif; ?>
<?php if (!$archived): ?>
    <form class="catalogue-form admin-card" method="post" enctype="multipart/form-data" action="<?= $action ?>">
        <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
        <label>Intitulé
            <input class="field-input" name="title" maxlength="160" value="<?= $escape($item['title'] ?? '') ?>" required>
        </label>
        <?php if ($timeline): ?>
            <div class="catalogue-form-row">
                <label>Établissement ou entreprise
                    <input class="field-input" name="organisation" maxlength="160" value="<?= $escape($item['organisation'] ?? '') ?>">
                </label>
                <label>Année ou période
                    <input class="field-input" name="period" maxlength="100" value="<?= $escape($item['period'] ?? '') ?>">
                </label>
            </div>
        <?php endif; ?>
        <label>Description
            <textarea class="field-input" name="description" rows="4" maxlength="4000"><?= $escape($item['description'] ?? '') ?></textarea>
        </label>
        <?php if ($kind === 'experience'): ?>
            <label>Missions
                <textarea class="field-input" name="responsibilities" rows="3" maxlength="2500"><?= $escape($item['responsibilities'] ?? '') ?></textarea>
            </label>
            <label>Compétences acquises
                <textarea class="field-input" name="competencies" rows="3" maxlength="2500"><?= $escape($item['competencies'] ?? '') ?></textarea>
            </label>
        <?php endif; ?>
        <label>Ordre d’affichage
            <input class="field-input" type="number" min="0" max="65535" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>" required>
        </label>
        <label>Photographie facultative
            <input class="field-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        </label>
        <?php if (!empty($item['has_image'])): ?>
            <label class="catalogue-check"><input type="checkbox" name="remove_image" value="1"> Retirer la photo</label>
        <?php endif; ?>
        <label class="catalogue-check"><input type="checkbox" name="visible" value="1" <?= ($item['visible'] ?? true) ? 'checked' : '' ?>> Afficher cette entrée</label>
        <label class="catalogue-check"><input type="checkbox" name="is_demo" value="1" <?= !empty($item['is_demo']) ? 'checked' : '' ?>> Exemple fictif de démonstration</label>
        <button class="action action-primary" type="submit">Enregistrer</button>
    </form>
<?php else: ?>
    <p>Cette entrée est archivée.</p>
<?php endif; ?>
<?php if ($key !== null): ?>
    <form class="catalogue-archive" method="post" action="<?= $action ?>">
        <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
        <button class="action action-outline" type="submit" name="intent" value="<?= $archived ? 'restore' : 'archive' ?>">
            <?= $archived ? 'Restaurer cette entrée' : 'Archiver cette entrée' ?>
        </button>
    </form>
<?php endif; ?>
