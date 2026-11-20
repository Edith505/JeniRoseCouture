<?php
use App\Finance\InvoiceAmounts;
use App\Finance\Ledger;
use App\Sales\Money;

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$value = static fn (string $key, mixed $default = ''): string => $e($form[$key] ?? $default);
$from = $period->start->format('Y-m-d');
$to = $period->end->modify('-1 day')->format('Y-m-d');
$queryPeriod = http_build_query(['period' => 'custom', 'from' => $from, 'to' => $to]);
$amount = static fn (string $value): string => $e(InvoiceAmounts::label($value));
?>
<h1><?= $e($views[$view]) ?></h1>
<nav class="request-modes" aria-label="Modules financiers">
    <?php foreach ($views as $keyName => $label): ?>
        <a class="action <?= $keyName === $view ? 'action-primary' : 'action-outline' ?>" href="/index.php?route=admin-finances&amp;view=<?= $keyName ?>"
            <?= $keyName === $view ? 'aria-current="page"' : '' ?>><?= $e($label) ?></a>
    <?php endforeach; ?>
</nav>
<?php if ($saved): ?><p class="catalogue-success" role="status">Changements enregistrés.</p><?php endif; ?>
<?php if ($error !== null): ?><p class="form-message" role="alert"><?= $e($error) ?></p><?php endif; ?>

<?php if ($view !== 'profile' && $invoice === null): ?>
    <form class="dashboard-dates finance-period" method="get" action="/index.php">
        <input type="hidden" name="route" value="admin-finances">
        <input type="hidden" name="view" value="<?= $view ?>">
        <?php if ($orderId !== null): ?><input type="hidden" name="order" value="<?= $orderId ?>"><?php endif; ?>
        <label>Période <select class="field-input" name="period">
            <?php foreach (['month' => 'Ce mois', 'year' => 'Cette année', 'custom' => 'Dates ci-contre'] as $choice => $label): ?>
                <option value="<?= $choice ?>" <?= $period->choice === $choice ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select></label>
        <label>Du <input type="date" class="field-input" name="from" value="<?= $e($from) ?>"></label>
        <label>Au <input type="date" class="field-input" name="to" value="<?= $e($to) ?>"></label>
        <button class="action action-outline">Appliquer</button>
    </form>
<?php endif; ?>

<?php if ($view === 'reports'): ?>
    <section class="dashboard-panel">
        <div class="catalogue-toolbar">
            <h2>Du <?= $e($report['from']) ?> au <?= $e($report['until']) ?></h2>
            <div class="dashboard-shortcuts">
                <a class="action action-outline" href="<?= $e($base . '&format=csv&' . $queryPeriod) ?>">Exporter en CSV</a>
                <a class="action action-outline" href="<?= $e($base . '&format=pdf&' . $queryPeriod) ?>">Rapport PDF</a>
            </div>
        </div>
        <dl class="dashboard-counters dashboard-finance">
            <?php foreach ($report['labels'] as $metric => $label): ?>
                <div><dt><?= $e($label) ?></dt><dd><span><?= $amount($report['totals'][$metric]) ?></span>
                <?php if ($metric === 'outstanding'): ?><small>Toutes dates</small><?php endif; ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <?php if (array_filter($financeActivity, static fn (array $row): bool
            => $row['invoiced_ht'] !== '0.00' || $row['net_received'] !== '0.00' || $row['expenses'] !== '0.00') !== []): ?>
            <h3>Évolution sur la période</h3>
            <div class="dashboard-chart" hidden>
                <canvas data-finance-activity="<?= $e(json_encode($financeActivity, JSON_THROW_ON_ERROR)) ?>" aria-hidden="true"></canvas>
            </div>
            <details class="dashboard-data" open>
                <summary>Consulter les chiffres par intervalle</summary>
                <div class="catalogue-table-wrap" tabindex="0" role="region" aria-label="Évolution financière">
                    <table class="catalogue-table">
                        <thead><tr><th scope="col">Intervalle</th><th scope="col">Facturé HT</th><th scope="col">Encaissement net</th><th scope="col">Dépenses TTC</th></tr></thead>
                        <tbody>
                            <?php foreach ($financeActivity as $activity): ?>
                                <tr><th scope="row"><?= $e($activity['label']) ?></th><td><?= $amount($activity['invoiced_ht']) ?></td>
                                <td><?= $amount($activity['net_received']) ?></td><td><?= $amount($activity['expenses']) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
            <script defer src="/assets/js/chart.umd.min.js"></script>
            <script defer src="/assets/js/finance-chart.js"></script>
        <?php endif; ?>
        <?php if ($report['categories'] !== []): ?>
            <h3>Dépenses par catégorie</h3>
            <ul class="catalogue-category-list" role="list">
                <?php foreach ($report['categories'] as $category): ?>
                    <li><strong><?= $e($category['category']) ?></strong><span><?= $amount($category['amount']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php elseif ($view === 'profile'): ?>
    <?php require __DIR__ . '/finance-profile.php'; ?>
<?php elseif ($view === 'invoices' && $invoice !== null): ?>
    <?php
    $doc = json_decode($invoice['snapshot'], true, flags: JSON_THROW_ON_ERROR);
    $credit = $invoice['kind'] === 'credit';
    ?>
    <section class="admin-card">
        <h2><?= $credit ? 'Avoir' : 'Facture' ?> <?= $e($invoice['number']) ?></h2>
        <p><?= $e($doc['details']['buyer_name']) ?> · <?= $e($doc['issue_on']) ?> · <?= $amount($invoice['total_ttc']) ?></p>
        <?php if ($credit): ?><p>Facture d’origine : <?= $e($doc['reference']) ?></p><?php endif; ?>
        <?php if ($invoice['credited_by']): ?><p>Avoir émis : <?= $e($invoice['credited_by']) ?></p><?php endif; ?>
        <div class="sales-inline">
            <a class="action action-primary" href="<?= $e($base . '&id=' . $invoice['id'] . '&format=pdf') ?>">Télécharger le PDF</a>
            <a class="text-link" href="/index.php?route=admin-finances&amp;view=payments&amp;order=<?= (int) $invoice['order_id'] ?>">Paiements de la commande</a>
        </div>
    </section>
    <?php if (!$credit && !$invoice['credited_by']): ?>
        <details class="catalogue-archive">
            <summary>Émettre un avoir total</summary>
            <form class="catalogue-form admin-card" method="post" action="<?= $e($base . '&id=' . $invoice['id']) ?>">
                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                <p>L’avoir porte son propre numéro. Enregistrez séparément tout remboursement effectué.</p>
                <label>Motif <textarea class="field-input" name="reason" maxlength="450" rows="3" required><?= $value('reason') ?></textarea></label>
                <button class="action action-outline" name="intent" value="credit">Émettre l’avoir</button>
            </form>
        </details>
    <?php endif; ?>
<?php else: ?>
    <?php if ($view === 'payments' || $view === 'invoices'): ?>
        <form class="request-filter" method="get" action="/index.php">
            <input type="hidden" name="route" value="admin-finances"><input type="hidden" name="view" value="<?= $view ?>">
            <label>Commande
                <select class="field-input" name="order" required>
                    <option value="">Choisir une commande</option>
                    <?php foreach ($orderChoices as $choice): ?>
                        <option value="<?= (int) $choice['id'] ?>" <?= (int) $choice['id'] === $orderId ? 'selected' : '' ?>>
                            n° <?= (int) $choice['id'] ?> · <?= $e($choice['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="action action-outline">Ouvrir</button>
        </form>
        <?php if ($order !== null): ?>
            <div class="sales-inline">
                <strong>Commande n° <?= $orderId ?> · <?= $e($order['name']) ?></strong>
                <span>Total convenu : <?= $amount($order['total_amount']) ?></span>
                <span>Encaissement net : <?= $amount($order['net_paid']) ?></span>
                <?php
                $balance = Money::cents($order['total_amount'], 10) - Money::cents($order['net_paid'], 10);
                ?>
                <span><?= $balance < 0 ? 'Trop-perçu' : 'Solde de la commande' ?> : <?= $amount(Money::decimal(abs($balance))) ?></span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($view === 'payments' && $order !== null): ?>
        <form class="catalogue-form admin-card" method="post" action="<?= $e($base) ?>">
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="request_key" value="<?= $e($key) ?>">
            <h2>Enregistrer un mouvement</h2>
            <div class="catalogue-form-row">
                <label>Type <select name="direction" class="field-input"><option value="payment">Encaissement</option><option value="refund" <?= $value('direction') === 'refund' ? 'selected' : '' ?>>Remboursement</option></select></label>
                <label>Moyen <select class="field-input" name="method">
                    <?php foreach (Ledger::METHODS as $methodKey => $label): ?><option value="<?= $methodKey ?>" <?= $value('method') === $methodKey ? 'selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?>
                </select></label>
            </div>
            <div class="catalogue-form-row">
                <label>Date <input class="field-input" name="booked_on" type="date" max="<?= $today ?>" required value="<?= $value('booked_on', $today) ?>"></label>
                <label>Montant (euros) <input class="field-input" name="amount" inputmode="decimal" maxlength="13" required value="<?= $value('amount') ?>"></label>
            </div>
            <label>Référence (facultative) <input class="field-input" name="reference" maxlength="140" value="<?= $value('reference') ?>"></label>
            <button class="action action-primary" name="intent" value="payment">Enregistrer</button>
        </form>
    <?php elseif ($view === 'invoices' && $order !== null): ?>
        <?php if ($activeInvoice !== null): ?>
            <a class="action action-primary" href="/index.php?route=admin-finances&amp;view=invoices&amp;id=<?= $activeInvoice ?>">Ouvrir la facture émise</a>
        <?php elseif ($order['status'] === 'cancelled'): ?>
            <p>Cette commande est annulée.</p>
        <?php elseif ($profile === null): ?>
            <p>Complétez le <a href="/index.php?route=admin-finances&amp;view=profile">profil de facturation</a> avant d’émettre un document.</p>
        <?php else: ?>
            <?php require __DIR__ . '/invoice-form.php'; ?>
        <?php endif; ?>
    <?php elseif ($view === 'expenses'): ?>
        <form class="catalogue-form admin-card" method="post" action="<?= $e($base) ?>">
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
            <label>Nouvelle catégorie <input class="field-input" name="category_name" maxlength="140" required value="<?= $value('category_name') ?>"></label>
            <button class="action action-outline" name="intent" value="category">Ajouter la catégorie</button>
        </form>
        <?php if ($categories !== []): ?>
            <form class="catalogue-form admin-card request-edit" method="post" action="<?= $e($base) ?>">
                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="request_key" value="<?= $e($key) ?>">
                <h2>Enregistrer une dépense</h2>
                <label>Catégorie <select name="category" class="field-input">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $value('category') === (string) $category['id'] ? 'selected' : '' ?>><?= $e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label>Description <input name="description" class="field-input" maxlength="180" required value="<?= $value('description') ?>"></label>
                <div class="catalogue-form-row">
                    <label>Date <input class="field-input" name="booked_on" type="date" max="<?= $today ?>" value="<?= $value('booked_on', $today) ?>" required></label>
                    <label>Montant TTC (euros) <input class="field-input" name="amount" inputmode="decimal" maxlength="13" required value="<?= $value('amount') ?>"></label>
                </div>
                <button class="action action-primary" name="intent" value="expense">Enregistrer</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <section class="catalogue-categories">
        <h2>Historique de la période</h2>
        <?php if ($rows === []): ?><p>Aucune opération pour les filtres sélectionnés.</p><?php endif; ?>
        <ul class="catalogue-category-list sales-list" role="list">
            <?php foreach ($rows as $row): ?>
                <li>
                    <?php if ($view === 'invoices'): ?>
                        <?php
                        $status = $row['kind'] === 'credit' ? 'Avoir' : ($row['credited_by'] ? 'Créditée'
                            : (Money::cents($row['net_paid'], 10) >= Money::cents($row['total_ttc'], 10) ? 'Réglée'
                                : (Money::cents($row['net_paid'], 10) > 0 ? 'Partiellement réglée' : 'À régler')));
                        ?>
                        <strong><?= $e($row['number']) ?></strong>
                        <span><?= $e($row['issue_on'] . ' · ' . $row['name'] . ' · ' . $status) ?></span>
                        <span><?= $amount($row['total_ttc']) ?></span>
                        <a class="text-link" href="/index.php?route=admin-finances&amp;view=invoices&amp;id=<?= (int) $row['id'] ?>">Ouvrir</a>
                    <?php elseif ($view === 'payments'): ?>
                        <strong><?= $e($row['booked_on'] . ' · ' . $row['name']) ?></strong>
                        <span><?= $e(($row['direction'] === 'refund' ? 'Remboursement' : 'Encaissement') . ' · ' . Ledger::METHODS[$row['method']]) ?></span>
                        <span><?= $amount($row['amount']) ?></span>
                        <?php if ($row['reference'] !== ''): ?><span><?= $e($row['reference']) ?></span><?php endif; ?>
                        <a class="text-link" href="/index.php?route=admin-finances&amp;view=payments&amp;order=<?= (int) $row['order_id'] ?>">Commande n° <?= (int) $row['order_id'] ?></a>
                    <?php else: ?>
                        <strong><?= $e($row['booked_on'] . ' · ' . $row['category']) ?></strong>
                        <span><?= $e($row['description']) ?></span><span><?= $amount($row['amount']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
