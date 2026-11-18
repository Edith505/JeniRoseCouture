<?php
use App\Sales\Money;
use App\Sales\Sales;

$escape = static fn (mixed $value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$value = static fn (string $key, string $default = ''): string => $escape($values[$key] ?? $default);
?>
<div class="catalogue-toolbar">
    <h1><?= $escape($heading) ?></h1>
    <?php if (!$editing): ?>
        <a class="action action-primary" href="<?= $escape($base . '&new=1') ?>"><?= $clientPage ? 'Ajouter un client' : 'Créer une commande' ?></a>
    <?php endif; ?>
</div>
<?php if ($success): ?><p class="catalogue-success" role="status">Changements enregistrés.</p><?php endif; ?>
<?php if ($error !== null): ?><p class="form-message" role="alert"><?= $escape($error) ?></p><?php endif; ?>

<?php if ($editing): ?>
    <p><a class="text-link" href="<?= $escape($base) ?>">← Retour à la liste</a></p>
    <?php require __DIR__ . ($clientPage ? '/client-form.php' : '/order-form.php'); ?>
<?php else: ?>
    <form class="request-filter" action="/index.php" method="get">
        <input type="hidden" name="route" value="<?= $escape($route) ?>">
        <label>Recherche
            <input class="field-input" name="search" type="search" maxlength="100" value="<?= $escape($search) ?>"
                placeholder="<?= $clientPage ? 'Nom, e-mail ou téléphone' : 'Nom du client ou n° de commande' ?>">
        </label>
        <label>Statut
            <select class="field-input" name="status">
                <?php $statuses = $clientPage ? ['active' => 'Actifs', 'archived' => 'Archivés', 'all' => 'Tous'] : ['' => 'Tous'] + Sales::STATES; ?>
                <?php foreach ($statuses as $status => $label): ?>
                    <option value="<?= $escape($status) ?>" <?= $filter === $status ? 'selected' : '' ?>><?= $escape($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="action action-outline" type="submit">Filtrer</button>
    </form>
    <?php if ($rows === []): ?>
        <p><?= $clientPage ? 'Aucun client pour ce filtre.' : 'Aucune commande pour ce filtre.' ?></p>
    <?php else: ?>
        <ul class="catalogue-category-list sales-list" role="list">
            <?php foreach ($rows as $row): ?>
                <li>
                    <?php if ($clientPage): ?>
                        <strong><?= $escape($row['name']) ?></strong>
                        <span><?= $escape($row['email']) ?></span>
                        <span><?= $row['archived_at'] === null ? 'Active' : 'Archivée' ?></span>
                    <?php else: ?>
                        <strong>n° <?= (int) $row['id'] ?> · <?= $escape($row['client_name']) ?></strong>
                        <span><?= Sales::STATES[$row['status']] ?></span>
                        <span><?= $escape(Money::label($row['total_amount'])) ?></span>
                        <?php if ($row['due_on']): ?><span>Livraison <?= $escape($row['due_on']) ?></span><?php endif; ?>
                    <?php endif; ?>
                    <a class="text-link" href="<?= $escape($base . '&id=' . $row['id']) ?>">Ouvrir</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>
