<?php
use App\Dashboard\Period;
use App\Requests\ClientRequests;
use App\Requests\RequestInput;

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<h1>Vue d’ensemble</h1>
<?php if ($period === null): ?>
    <p>Votre compte peut accéder aux modules affichés dans le menu.</p>
<?php else: ?>
    <?php
    $lastDay = $period->end->modify('-1 day');
    ?>
    <section class="dashboard-period" aria-labelledby="dashboard-periode">
        <div>
            <h2 id="dashboard-periode">Période</h2>
            <p><?= $period->start->format('d/m/Y') ?><?= $lastDay != $period->start ? ' au ' . $lastDay->format('d/m/Y') : '' ?> · Heure de Paris</p>
        </div>
        <nav class="dashboard-presets" aria-label="Périodes du tableau de bord">
            <?php foreach (array_diff_key(Period::CHOICES, ['custom' => true]) as $key => $label): ?>
                <a class="action <?= $period->choice === $key ? 'action-primary' : 'action-outline' ?>"
                    href="/index.php?route=admin&amp;period=<?= $key ?>" <?= $period->choice === $key ? 'aria-current="page"' : '' ?>><?= $escape($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <form action="/index.php" method="get" class="dashboard-dates">
            <input type="hidden" name="route" value="admin">
            <input type="hidden" name="period" value="custom">
            <label>Du <input class="field-input" name="from" type="date" required value="<?= $period->start->format('Y-m-d') ?>"></label>
            <label>Au <input class="field-input" name="to" type="date" required value="<?= $lastDay->format('Y-m-d') ?>"></label>
            <button type="submit" class="action action-outline">Appliquer</button>
        </form>
        <?php if ($periodError !== null): ?>
            <p class="form-message" role="alert"><?= $escape($periodError) ?> Les données affichées concernent ce mois.</p>
        <?php endif; ?>
    </section>

<?php endif; ?>
<?php if ($overview !== null): ?>
    <?php
    $summary = $overview['summary'];
    $hasActivity = $summary['manual'] + $summary['quotes'] > 0;
    ?>
    <dl class="dashboard-counters">
        <div><dt>Rendez-vous confirmés</dt><dd><span><?= $summary['scheduled'] ?></span><small>Sur les créneaux de la période</small></dd></div>
        <div><dt>Demandes de rendez-vous</dt><dd><span><?= $summary['manual'] ?></span><small>Demandes manuelles reçues</small></dd></div>
        <div><dt>Demandes de devis</dt><dd><span><?= $summary['quotes'] ?></span><small>Devis reçus sur la période</small></dd></div>
        <div><dt>Demandes à traiter</dt><dd><span><?= $summary['pending'] ?></span><small>Rendez-vous en attente et devis nouveaux ou en étude</small></dd></div>
    </dl>

    <div class="dashboard-grid">
        <section class="dashboard-panel" aria-labelledby="dashboard-activite">
            <h2 id="dashboard-activite">Demandes reçues</h2>
            <?php if (!$hasActivity): ?>
                <p class="dashboard-empty">Aucune demande de rendez-vous ou de devis sur cette période.</p>
            <?php else: ?>
                <div class="dashboard-chart" hidden>
                    <canvas data-activity="<?= $escape(json_encode($overview['activity'], JSON_THROW_ON_ERROR)) ?>" aria-hidden="true"></canvas>
                </div>
                <details class="dashboard-data" open>
                    <summary>Consulter les chiffres</summary>
                    <div class="catalogue-table-wrap" tabindex="0" role="region" aria-label="Chiffres par intervalle">
                        <table class="catalogue-table">
                            <thead><tr><th scope="col">Intervalle</th><th scope="col">Rendez-vous</th><th scope="col">Devis</th></tr></thead>
                            <tbody>
                                <?php foreach ($overview['activity'] as $row): ?>
                                    <tr><th scope="row"><?= $escape($row['label']) ?></th><td><?= $row['appointments'] ?></td><td><?= $row['quotes'] ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
                <script defer src="/assets/js/chart.umd.min.js"></script>
                <script defer src="/assets/js/dashboard.js"></script>
            <?php endif; ?>
        </section>

        <section class="dashboard-panel" aria-labelledby="dashboard-popularite">
            <h2 id="dashboard-popularite">Prestations les plus demandées</h2>
            <?php if ($overview['popular'] === []): ?>
                <p class="dashboard-empty">Aucune prestation demandée sur cette période.</p>
            <?php else: ?>
                <ol class="dashboard-rank">
                    <?php $maximum = (int) $overview['popular'][0]['total']; ?>
                    <?php foreach ($overview['popular'] as $item): ?>
                        <li>
                            <div><span><?= $escape($item['name']) ?></span><strong><?= (int) $item['total'] ?></strong></div>
                            <meter min="0" max="<?= $maximum ?>" value="<?= (int) $item['total'] ?>" aria-hidden="true"></meter>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
            <?php if ($overview['popular'] !== []): ?><p class="catalogue-note">Demandes de devis et rendez-vous reçus, tous statuts.</p><?php endif; ?>
        </section>
    </div>

    <section class="dashboard-panel dashboard-agenda" aria-labelledby="dashboard-agenda">
        <div class="catalogue-toolbar">
            <h2 id="dashboard-agenda">Prochains rendez-vous</h2>
            <div class="dashboard-shortcuts">
                <a class="text-link" href="/index.php?route=admin-demandes&amp;view=appointments">Rendez-vous</a>
                <a class="text-link" href="/index.php?route=admin-demandes&amp;view=quotes">Devis</a>
                <a class="text-link" href="/index.php?route=admin-demandes&amp;view=agenda">Créneaux</a>
            </div>
        </div>
        <?php if ($overview['upcoming'] === []): ?>
            <p class="dashboard-empty">Aucun rendez-vous à venir.</p>
        <?php else: ?>
            <ul class="dashboard-timeline" role="list">
                <?php foreach ($overview['upcoming'] as $appointment): ?>
                    <li>
                        <strong><?= $escape(RequestInput::label($appointment['starts_utc'])) ?></strong>
                        <span><?= $escape($appointment['service_name'] . ' · ' . $appointment['location']) ?></span>
                        <span><?= $escape($appointment['contact_name']) ?> · <?= $escape(ClientRequests::APPOINTMENT_STATES[$appointment['status']]) ?></span>
                        <a class="text-link" href="/index.php?route=admin-demandes&amp;view=appointments&amp;id=<?= (int) $appointment['id'] ?>">Ouvrir</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($salesStats !== null): ?>
    <section class="dashboard-panel dashboard-agenda">
        <div class="catalogue-toolbar">
            <h2>Clients et commandes</h2>
            <div class="dashboard-shortcuts">
                <a class="text-link" href="/index.php?route=admin-clients">Clients</a>
                <a class="text-link" href="/index.php?route=admin-commandes">Commandes</a>
            </div>
        </div>
        <dl class="dashboard-counters dashboard-sales">
            <div><dt>Nouvelles fiches clients</dt><dd><span><?= $salesStats['clients'] ?></span><small>Créées sur la période</small></dd></div>
            <div><dt>Nouvelles commandes</dt><dd><span><?= $salesStats['new_orders'] ?></span><small>Créées sur la période</small></dd></div>
            <div><dt>Commandes à traiter</dt><dd><span><?= $salesStats['open_orders'] ?></span><small>Reçues ou en cours, toutes dates</small></dd></div>
        </dl>
    </section>
<?php endif; ?>
