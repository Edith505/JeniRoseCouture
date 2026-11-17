<div class="catalogue-toolbar">
    <h1>Prestations et tarifs</h1>
    <a class="action action-primary" href="/index.php?route=admin-prestation">Ajouter une prestation</a>
</div>

<?php if ($notice !== null): ?>
    <p class="catalogue-success" role="status"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<div class="catalogue-table-wrap">
    <table class="catalogue-table">
        <thead>
            <tr><th scope="col">Prestation</th><th scope="col">Catégorie</th><th scope="col">Tarif</th><th scope="col">État</th><th scope="col">Gestion</th></tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
                <?php
                $archived = $service['archived_at'] !== null;
                $published = $service['is_enabled'] && $service['category_enabled'];
                ?>
                <tr>
                    <th scope="row"><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></th>
                    <td><?= htmlspecialchars($service['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?php require dirname(__DIR__) . '/partials/service-price.php'; ?></td>
                    <td><?= $archived ? 'Archivée' : ($published ? 'Publiée' : 'Masquée') ?></td>
                    <td>
                        <?php if (!$archived): ?>
                            <a class="text-link" href="/index.php?route=admin-prestation&amp;id=<?= (int) $service['id'] ?>">Modifier</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($services === []): ?>
                <tr><td colspan="5">Aucune prestation enregistrée.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<section class="catalogue-categories" aria-labelledby="categories-titre">
    <div class="catalogue-toolbar">
        <h2 id="categories-titre">Catégories</h2>
        <a class="action action-outline" href="/index.php?route=admin-categorie">Ajouter une catégorie</a>
    </div>
    <?php if ($categories === []): ?>
        <p>Aucune catégorie enregistrée.</p>
    <?php else: ?>
        <ul class="catalogue-category-list">
            <?php foreach ($categories as $category): ?>
                <li>
                    <strong><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= $category['is_enabled'] ? 'Visible' : 'Masquée' ?></span>
                    <a class="text-link" href="/index.php?route=admin-categorie&amp;id=<?= (int) $category['id'] ?>">Modifier</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
