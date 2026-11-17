<?php
use App\About\AboutContent;

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="catalogue-toolbar">
    <h1>Modifier la page À propos</h1>
    <a class="action action-outline" href="/index.php?route=admin-a-propos-apercu">Prévisualiser le brouillon</a>
</div>
<?php if ($notice !== null): ?>
    <p class="catalogue-success" role="status"><?= $escape($notice) ?></p>
<?php endif; ?>
<?php if ($message !== null): ?>
    <p class="form-message" role="alert"><?= $escape($message) ?></p>
<?php endif; ?>

<form class="catalogue-form admin-card" method="post" enctype="multipart/form-data" action="/index.php?route=admin-a-propos">
    <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
    <div class="catalogue-form-row">
        <label>Nom affiché
            <input class="field-input" name="display_name" maxlength="140" value="<?= $escape($content['display_name']) ?>" required>
        </label>
        <label>Titre
            <input class="field-input" name="headline" maxlength="180" value="<?= $escape($content['headline']) ?>" required>
        </label>
    </div>
    <label>Notre histoire
        <textarea class="field-input" name="story" rows="7" maxlength="8000"><?= $escape($content['story']) ?></textarea>
    </label>
    <label>Citation
        <textarea class="field-input" name="quote" rows="2" maxlength="500"><?= $escape($content['quote']) ?></textarea>
    </label>
    <label class="catalogue-check">
        <input type="checkbox" name="is_demo" value="1" <?= $content['is_demo'] ? 'checked' : '' ?>>
        Ce texte contient une biographie fictive de démonstration
    </label>
    <label>Remplacer le portrait
        <input class="field-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        <small>JPEG, PNG ou WebP, 2 Mio maximum.</small>
    </label>
    <?php if ($content['has_portrait']): ?>
        <label class="catalogue-check"><input type="checkbox" name="remove_image" value="1"> Retirer le portrait du brouillon</label>
    <?php endif; ?>

    <fieldset class="about-settings">
        <legend>Visibilité et ordre des sections</legend>
        <?php foreach (AboutContent::SECTIONS as $key => $label): ?>
            <div class="about-setting">
                <label class="catalogue-check">
                    <input type="checkbox" name="section_visible[<?= $key ?>]" value="1"
                        <?= $content['sections'][$key]['visible'] ? 'checked' : '' ?>>
                    <?= $label ?>
                </label>
                <label>Ordre
                    <input class="field-input" type="number" min="0" max="65535" required name="section_order[<?= $key ?>]" aria-label="Ordre de la section <?= $escape($label) ?>"
                        value="<?= (int) $content['sections'][$key]['order'] ?>">
                </label>
            </div>
        <?php endforeach; ?>
    </fieldset>
    <button class="action action-primary" name="intent" value="save" type="submit">Enregistrer le brouillon</button>
</form>

<?php foreach (AboutContent::KINDS as $kind => $label): ?>
    <section class="catalogue-categories" aria-labelledby="about-admin-<?= $kind ?>">
        <div class="catalogue-toolbar">
            <h2 id="about-admin-<?= $kind ?>"><?= AboutContent::SECTIONS[$kind] ?></h2>
            <a class="action action-outline" href="/index.php?route=admin-a-propos-entree&amp;kind=<?= $kind ?>">Ajouter</a>
        </div>
        <ul class="catalogue-category-list">
            <?php foreach ($content['entries'] as $entry): ?>
                <?php if ($entry['kind'] !== $kind) { continue; } ?>
                <li>
                    <strong><?= $escape($entry['title']) ?></strong>
                    <span><?= $entry['archived'] ? 'Archivée' : ($entry['visible'] ? 'Visible dans l’aperçu' : 'Masquée') ?></span>
                    <?php if ($entry['is_demo']): ?><small>Exemple fictif</small><?php endif; ?>
                    <a class="text-link" href="/index.php?route=admin-a-propos-entree&amp;key=<?= rawurlencode($entry['entry_key']) ?>"><?= $entry['archived'] ? 'Gérer' : 'Modifier' ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>

<section class="catalogue-categories" aria-labelledby="about-publish">
    <h2 id="about-publish">Publication</h2>
    <p>Enregistrez les modifications et vérifiez l’aperçu avant de publier le brouillon.</p>
    <form method="post" action="/index.php?route=admin-a-propos">
        <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
        <button class="action action-primary" type="submit" name="intent" value="publish">Publier la page À propos</button>
    </form>
</section>
