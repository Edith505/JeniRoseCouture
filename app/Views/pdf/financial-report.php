<?php
use App\Finance\InvoiceAmounts;

$report = $data['report'];
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><?php require __DIR__ . '/style.php'; ?></head><body>
<header class="header">
    <h1>Rapport financier</h1>
    <p>Jeni Rose Couture · Du <?= $e($report['from']) ?> au <?= $e($report['until']) ?></p>
</header>
<table class="data-table">
    <thead><tr><th>Indicateur</th><th class="numeric">Montant</th></tr></thead>
    <tbody>
        <?php foreach ($report['labels'] as $key => $label): ?>
            <tr><td><?= $e($label) ?></td><td class="numeric"><?= $e(InvoiceAmounts::label($report['totals'][$key])) ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<h2>Dépenses par catégorie</h2>
<?php if ($report['categories'] === []): ?>
    <p>Aucune dépense sur cette période.</p>
<?php else: ?>
    <table class="data-table">
        <thead><tr><th>Catégorie</th><th class="numeric">Montant TTC</th></tr></thead>
        <tbody>
            <?php foreach ($report['categories'] as $row): ?>
                <tr><td><?= $e($row['category']) ?></td><td class="numeric"><?= $e(InvoiceAmounts::label($row['amount'])) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<p class="notice">Le facturé est calculé net des avoirs. Le solde des factures ouvertes concerne toutes les dates.</p>
</body></html>
