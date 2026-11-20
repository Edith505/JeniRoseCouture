<?php
use App\Requests\RequestInput;
use App\Sales\Money;
use App\Sales\Sales;
?>
<?php if ($record !== null): ?>
    <div class="sales-inline">
        <p class="sales-total">Total enregistré : <strong><?= $escape(Money::label($record['total_amount'])) ?></strong></p>
        <a class="text-link" href="/index.php?route=admin-clients&amp;id=<?= (int) $record['client_id'] ?>">Ouvrir la fiche client</a>
        <?php if ($financeNavigation): ?>
            <a class="text-link" href="/index.php?route=admin-finances&amp;view=payments&amp;order=<?= (int) $record['id'] ?>">Paiements</a>
            <a class="text-link" href="/index.php?route=admin-finances&amp;view=invoices&amp;order=<?= (int) $record['id'] ?>">Factures et avoirs</a>
        <?php endif; ?>
        <?php if ($record['source_quote_id'] !== null && $requestNavigation): ?>
            <a class="text-link" href="/index.php?route=admin-demandes&amp;view=quotes&amp;id=<?= (int) $record['source_quote_id'] ?>">Ouvrir le devis d’origine</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($options['clients'] === []): ?>
    <p>Ajoutez une fiche client active pour créer une commande.</p>
    <a class="action action-primary" href="/index.php?route=admin-clients&amp;new=1">Ajouter un client</a>
<?php else: ?>
    <form class="catalogue-form admin-card sales-order-form" method="post" action="<?= $escape($formBase) ?>">
        <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
        <?php if (!empty($record['source_quote_id'])): ?>
            <p>Client : <strong><?= $escape($record['client_name']) ?></strong></p>
            <input type="hidden" name="client_id" value="<?= (int) $record['client_id'] ?>">
        <?php else: ?>
            <label>Client
                <select class="field-input" name="client_id" required>
                    <option value="">Choisir une fiche</option>
                    <?php foreach ($options['clients'] as $client): ?>
                        <option value="<?= (int) $client['id'] ?>" <?= (string) $client['id'] === $value('client_id') ? 'selected' : '' ?>>
                            <?= $escape($client['name'] . ' · ' . $client['email']) ?><?= $client['archived_at'] === null ? '' : ' (archivée)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <div class="catalogue-form-row">
            <label>Statut
                <select class="field-input" name="status">
                    <?php foreach (Sales::STATES as $status => $label): ?>
                        <option value="<?= $status ?>" <?= $status === $value('status', 'received') ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date de livraison <input class="field-input" type="date" name="due_on" value="<?= $value('due_on') ?>"></label>
        </div>
        <div class="catalogue-form-row">
            <label>Personne ou équipe responsable
                <select class="field-input" name="assignee">
                    <option value="">Sans affectation</option>
                    <?php foreach ($options['resources'] as $resource): ?>
                        <option value="<?= (int) $resource['id'] ?>"
                            <?= (string) $resource['id'] === $value('assignee', (string) ($record['assignee_resource_id'] ?? '')) ? 'selected' : '' ?>><?= $escape($resource['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Acompte convenu (euros)
                <input class="field-input" name="deposit" inputmode="decimal" maxlength="13" value="<?= $value('deposit') ?>">
            </label>
        </div>
        <fieldset class="sales-fieldset">
            <legend>Lignes de commande</legend>
            <?php foreach ($lines as $position => $line): ?>
                <div class="sales-line">
                    <strong>Ligne <?= $position + 1 ?></strong>
                    <label>Prestation du catalogue (facultatif)
                        <select class="field-input" name="lines[<?= $position ?>][service_id]">
                            <option value="">Prestation libre</option>
                            <?php foreach ($options['services'] as $service): ?>
                                <option value="<?= (int) $service['id'] ?>"
                                    <?= (string) $service['id'] === $escape($line['service_id'] ?? '') ? 'selected' : '' ?>>
                                    <?= $escape($service['name']) ?><?= $service['archived_at'] ? ' (archivée)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Libellé personnalisé
                        <input class="field-input" maxlength="180" name="lines[<?= $position ?>][label]" value="<?= $escape($line['label'] ?? '') ?>">
                    </label>
                    <div class="catalogue-form-row">
                        <label>Quantité
                            <input class="field-input" type="number" name="lines[<?= $position ?>][quantity]" min="1" max="999" value="<?= $escape($line['quantity'] ?? '1') ?>">
                        </label>
                        <label>Prix unitaire (euros)
                            <input class="field-input" inputmode="decimal" maxlength="11" name="lines[<?= $position ?>][unit_price]" value="<?= $escape($line['unit_price'] ?? '') ?>">
                        </label>
                    </div>
                    <?php if (count($lines) > 1): ?>
                        <button class="action action-outline" type="submit" name="remove_line" value="<?= $position ?>" formnovalidate>Retirer la ligne <?= $position + 1 ?></button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (count($lines) < 25): ?>
                <button class="action action-outline" type="submit" name="intent" value="add-line" formnovalidate>Ajouter une ligne</button>
            <?php endif; ?>
        </fieldset>
        <label>Commentaires internes <textarea class="field-input" name="notes" rows="3" maxlength="3000"><?= $value('notes') ?></textarea></label>
        <button class="action action-primary" name="intent" value="save" type="submit">Enregistrer la commande</button>
    </form>
<?php endif; ?>
<?php if ($history !== []): ?>
    <section class="catalogue-categories">
        <h2>Historique de la commande</h2>
        <ol class="catalogue-category-list sales-list">
            <?php foreach ($history as $event): ?>
                <li>
                    <strong><?= $escape(Sales::STATES[$event['status']]) ?></strong>
                    <span><?= $escape(RequestInput::label($event['occurred_at'])) ?></span>
                    <span><?= $escape($event['display_name']) ?></span>
                    <span><?= $escape(Money::label($event['total_amount'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
<?php endif; ?>
