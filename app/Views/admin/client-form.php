<?php
use App\Requests\ClientRequests;
use App\Requests\RequestInput;
use App\Sales\Money;
use App\Sales\Sales;
?>
<?php if ($record !== null && $record['archived_at'] !== null): ?><p class="form-message">Cette fiche est archivée.</p><?php endif; ?>
<form class="catalogue-form admin-card" method="post" action="<?= $escape($formBase) ?>">
    <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
    <label>Nom <input class="field-input" name="name" maxlength="140" required value="<?= $value('name') ?>"></label>
    <div class="catalogue-form-row">
        <label>E-mail <input class="field-input" type="email" name="email" maxlength="254" required value="<?= $value('email') ?>"></label>
        <label>Téléphone <input class="field-input" type="tel" name="phone" maxlength="40" value="<?= $value('phone') ?>"></label>
    </div>
    <fieldset class="sales-fieldset">
        <legend>Adresse postale (facultatif)</legend>
        <label>Adresse <input class="field-input" name="address_line" maxlength="180" value="<?= $value('address_line') ?>"></label>
        <div class="catalogue-form-row">
            <label>Code postal <input class="field-input" name="postal_code" maxlength="16" value="<?= $value('postal_code') ?>"></label>
            <label>Ville <input class="field-input" name="city" maxlength="100" value="<?= $value('city') ?>"></label>
        </div>
        <label>Pays <input class="field-input" name="country" maxlength="80" value="<?= $value('country') ?>"></label>
    </fieldset>
    <label>Notes internes <textarea class="field-input" name="notes" rows="3" maxlength="3000"><?= $value('notes') ?></textarea></label>
    <button class="action action-primary" type="submit">Enregistrer</button>
</form>
<?php if ($record !== null): ?>
    <div class="sales-inline">
        <?php if ($record['archived_at'] === null): ?>
            <a class="action action-primary" href="/index.php?route=admin-commandes&amp;new=1&amp;client=<?= (int) $record['id'] ?>">Créer une commande pour ce client</a>
        <?php endif; ?>
        <form method="post" action="<?= $escape($formBase) ?>">
            <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
            <button class="action action-outline" name="intent" value="<?= $record['archived_at'] === null ? 'archive' : 'restore' ?>">
                <?= $record['archived_at'] === null ? 'Archiver la fiche' : 'Réactiver la fiche' ?>
            </button>
        </form>
    </div>
    <section class="catalogue-categories">
        <h2>Historique client</h2>
        <?php foreach ([
            'orders' => ['Commandes', Sales::STATES],
            'appointments' => ['Rendez-vous', ClientRequests::APPOINTMENT_STATES],
            'quotes' => ['Devis', ClientRequests::QUOTE_STATES],
        ] as $kind => [$label, $labels]): ?>
            <h3><?= $label ?></h3>
            <?php if ($activity[$kind] === []): ?><p>Aucun élément.</p><?php endif; ?>
            <ul class="catalogue-category-list sales-list" role="list">
                <?php foreach ($activity[$kind] as $item): ?>
                    <li>
                        <strong><?= $kind === 'orders' ? 'n° ' . (int) $item['id'] : $escape($item['name']) ?></strong>
                        <span><?= $escape($labels[$item['status']]) ?></span>
                        <span><?= $escape(RequestInput::label($item['created_at'])) ?></span>
                        <?php if ($kind === 'orders'): ?>
                            <span><?= $escape(Money::label($item['total_amount'])) ?></span>
                            <a class="text-link" href="/index.php?route=admin-commandes&amp;id=<?= (int) $item['id'] ?>">Ouvrir</a>
                        <?php elseif ($requestNavigation): ?>
                            <a class="text-link" href="/index.php?route=admin-demandes&amp;view=<?= $kind ?>&amp;id=<?= (int) $item['id'] ?>">Ouvrir</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
