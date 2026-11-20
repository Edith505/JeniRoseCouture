<?php
use App\Finance\InvoiceAmounts;
use App\Sales\Money;

$document = $data['document'];
$issuer = $document['issuer'];
$buyer = $document['details'];
$credit = $document['kind'] === 'credit';
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$day = static fn (string $value): string => implode('/', array_reverse(explode('-', $value)));
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><?php require __DIR__ . '/style.php'; ?></head><body>
<header class="header">
    <h1><?= $credit ? 'Avoir' : 'Facture' ?> <?= $e($document['number']) ?></h1>
    <p>Émise le <?= $e($day($document['issue_on'])) ?> · Commande n° <?= (int) $document['order_id'] ?> · Prestations de services</p>
</header>
<?php if (!empty($document['demo'])): ?><p class="notice"><strong>Exemple de démonstration</strong></p><?php endif; ?>
<table class="parties"><tr>
    <td>
        <strong>Émetteur</strong><br>
        <?= $e($issuer['name']) ?><br>
        <?= nl2br($e($issuer['address'])) ?><br>
        <?= $e($issuer['postal'] . ' ' . $issuer['city']) ?>, France<br>
        SIREN <?= $e($issuer['siren']) ?><br>
        <?= nl2br($e($issuer['registration'])) ?>
        <?php if ($issuer['vat_number'] !== ''): ?><br>TVA <?= $e(strtoupper($issuer['vat_number'])) ?><?php endif; ?>
    </td>
    <td>
        <strong>Client</strong><br>
        <?= $e($buyer['buyer_name']) ?><br>
        <?= nl2br($e($buyer['buyer_address'])) ?><br>
        <?= $e($buyer['buyer_postal'] . ' ' . $buyer['buyer_city']) ?>, France
        <?php if ($buyer['buyer_siren'] !== ''): ?><br>SIREN <?= $e($buyer['buyer_siren']) ?><?php endif; ?>
        <?php if ($buyer['buyer_vat'] !== ''): ?><br>TVA <?= $e(strtoupper($buyer['buyer_vat'])) ?><?php endif; ?>
    </td>
</tr></table>
<p>Date de réalisation : <?= $e($day($buyer['service_on'])) ?></p>
<?php if ($buyer['buyer_reference'] !== ''): ?><p>Bon de commande du client : <?= $e($buyer['buyer_reference']) ?></p><?php endif; ?>
<?php if ($credit): ?><p class="notice wrap">Avoir total sur la facture <?= $e($document['reference']) ?>. Motif : <?= $e($document['reason']) ?></p><?php endif; ?>

<table class="data-table">
    <thead><tr><th>Prestation</th><th class="numeric">Qté</th><th class="numeric">Unité HT</th><th class="numeric">TVA</th><th class="numeric">Montant HT</th><th class="numeric">Montant TTC</th></tr></thead>
    <tbody>
        <?php foreach ($document['lines'] as $line): ?>
            <tr>
                <td class="wrap"><?= $e($line['description']) ?></td><td class="numeric"><?= (int) $line['quantity'] ?></td>
                <td class="numeric"><?= $e(InvoiceAmounts::unitLabel($line['unit_ht'])) ?></td>
                <td class="numeric"><?= $e($issuer['regime'] === 'franchise' ? 'Franchise' : str_replace('.', ',', Money::decimal($line['vat_basis_points'])) . ' %') ?></td>
                <td class="numeric"><?= $e(Money::label($line['amount_ht'])) ?></td>
                <td class="numeric"><?= $e(Money::label($line['amount_ttc'])) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<table class="totals">
    <tr><td>Total HT</td><td><?= $e(Money::label($document['total_ht'])) ?></td></tr>
    <tr><td>TVA</td><td><?= $e(Money::label($document['tax_amount'])) ?></td></tr>
    <tr class="final"><td><?= $credit ? 'Total crédité TTC' : 'Total TTC' ?></td><td><?= $e(Money::label($document['total_ttc'])) ?></td></tr>
</table>

<div class="terms">
<?php if ($issuer['tax_notice'] !== ''): ?><p class="wrap"><?= nl2br($e($issuer['tax_notice'])) ?></p><?php endif; ?>
<?php if (!$credit): ?>
    <p>Règlement au plus tard le <?= $e($day($buyer['due_on'])) ?>.</p>
    <p><?= $e($issuer['discount']) ?></p>
    <?php if ($buyer['buyer_type'] === 'business'): ?>
        <p><?= $e($issuer['late_fees']) ?></p>
        <p>Indemnité forfaitaire de 40 € pour frais de recouvrement en cas de retard.</p>
    <?php endif; ?>
<?php endif; ?>
<?php if ($issuer['additional_mentions'] !== ''): ?><p class="wrap"><?= nl2br($e($issuer['additional_mentions'])) ?></p><?php endif; ?>
</div>
</body></html>
