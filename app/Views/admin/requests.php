<?php
use App\Requests\RequestInput;

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$address = static fn (string $email): string => str_replace('%40', '@', rawurlencode($email));
$dateLabel = static fn (?string $value): string => $value === null ? '' : RequestInput::label($value);
?>
<h1><?= $escape($heading) ?></h1>
<nav class="request-modes" aria-label="Gestion des demandes">
    <?php foreach (['appointments' => 'Rendez-vous', 'quotes' => 'Devis', 'agenda' => 'Créneaux'] as $key => $label): ?>
        <a class="action <?= $view === $key ? 'action-primary' : 'action-outline' ?>"
            href="/index.php?route=admin-demandes&amp;view=<?= $key ?>" <?= $view === $key ? 'aria-current="page"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
</nav>
<?php if ($saved): ?><p class="catalogue-success" role="status">Changements enregistrés.</p><?php endif; ?>
<?php if ($error !== null): ?><p class="form-message" role="alert"><?= $escape($error) ?></p><?php endif; ?>

<?php if ($agenda): ?>
    <form class="catalogue-form admin-card" action="<?= $escape($base) ?>" method="post">
        <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
        <h2>Ouvrir un créneau</h2>
        <div class="catalogue-form-row">
            <label>Personne ou équipe <input class="field-input" name="resource" maxlength="140" required></label>
            <label>Atelier ou zone publique <input class="field-input" name="location" maxlength="160" required></label>
        </div>
        <label>Mode
            <select name="mode" class="field-input"><option value="workshop">Atelier</option><option value="home">À domicile</option></select>
        </label>
        <div class="catalogue-form-row">
            <label>Début, heure de Paris <input class="field-input" type="datetime-local" name="start" required></label>
            <label>Fin, heure de Paris <input class="field-input" type="datetime-local" name="end" required></label>
        </div>
        <label>Nombre de semaines consécutives
            <input class="field-input" type="number" name="weeks" value="1" min="1" max="12" required>
        </label>
        <button class="action action-primary" type="submit">Enregistrer les créneaux</button>
    </form>
    <form class="catalogue-form admin-card request-edit" action="<?= $escape($base) ?>" method="post">
        <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
        <h2>Fermer les créneaux d’une journée</h2>
        <p>Les rendez-vous déjà attribués restent dans l’agenda.</p>
        <div class="catalogue-form-row">
            <label>Date, heure de Paris <input class="field-input" type="date" name="day" required></label>
            <label>Personne ou équipe (facultatif) <input class="field-input" name="resource" maxlength="140"></label>
        </div>
        <button class="action action-outline" type="submit" name="intent" value="close-day">Fermer aux nouvelles réservations</button>
    </form>
    <section class="catalogue-categories">
        <h2>Créneaux à venir</h2>
        <?php if ($rows === []): ?><p>Aucun créneau ouvert.</p><?php endif; ?>
        <ul class="catalogue-category-list" role="list">
            <?php foreach ($rows as $row): ?>
                <li>
                    <strong><?= $escape($dateLabel($row['starts_utc'])) ?></strong>
                    <span><?= $escape($row['resource_name'] . ' · ' . $row['location']) ?></span>
                    <?php if ($row['occupied']): ?><span>Attribué</span><?php endif; ?>
                    <?php if ($row['enabled']): ?>
                        <form action="<?= $escape($base) ?>" method="post">
                            <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
                            <input type="hidden" name="slot" value="<?= (int) $row['id'] ?>">
                            <button class="action action-outline" name="intent" value="close">Fermer aux nouvelles réservations</button>
                        </form>
                    <?php else: ?><span>Fermé</span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php elseif ($record === null): ?>
    <form class="request-filter" action="/index.php" method="get">
        <input type="hidden" name="route" value="admin-demandes">
        <input type="hidden" name="view" value="<?= $view ?>">
        <label>Statut
            <select class="field-input" name="status">
                <option value="">Tous les statuts</option>
                <?php foreach ($states as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $key === $filter ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Nom ou prestation <input class="field-input" type="search" name="search" value="<?= $escape($search) ?>" maxlength="100"></label>
        <button class="action action-outline" type="submit">Filtrer</button>
    </form>
    <ul class="catalogue-category-list" role="list">
        <?php foreach ($rows as $row): ?>
            <li>
                <strong><?= $escape($row['contact_name']) ?></strong>
                <span><?= $escape($row['service_name']) ?></span>
                <span><?= $states[$row['status']] ?></span>
                <?php if (!empty($row['starts_utc'])): ?><span><?= $escape($dateLabel($row['starts_utc'])) ?></span><?php endif; ?>
                <a class="text-link" href="<?= $escape($base . '&id=' . $row['id']) ?>">Ouvrir la demande</a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($rows === []): ?><p>Aucune demande pour ce filtre.</p><?php endif; ?>
<?php else: ?>
    <p><a class="text-link" href="<?= $escape($base) ?>">← Retour à la liste</a></p>
    <section class="admin-card">
        <h2><?= $escape($record['contact_name']) ?></h2>
        <dl class="request-summary">
            <dt>Prestation</dt><dd><?= $escape($record['service_name']) ?></dd>
            <dt>E-mail</dt><dd><a href="mailto:<?= $escape($address($record['contact_email'])) ?>"><?= $escape($record['contact_email']) ?></a></dd>
            <?php if ($record['contact_phone'] !== ''): ?><dt>Téléphone</dt><dd><?= $escape($record['contact_phone']) ?></dd><?php endif; ?>
            <?php if ($quote): ?><dt>Contact souhaité</dt><dd><?= $record['contact_method'] === 'phone' ? 'Téléphone' : 'E-mail' ?></dd><?php endif; ?>
            <?php if ($record['preference'] !== ''): ?><dt>Préférences</dt><dd><?= $escape($record['preference']) ?></dd><?php endif; ?>
            <?php if ($record['notes'] !== ''): ?><dt><?= $quote ? 'Projet' : 'Informations' ?></dt><dd class="catalogue-lines"><?= $escape($record['notes']) ?></dd><?php endif; ?>
            <?php if (!empty($record['starts_utc'])): ?><dt>Créneau attribué</dt><dd><?= $escape($dateLabel($record['starts_utc']) . ' · ' . $record['location']) ?></dd><?php endif; ?>
        </dl>
        <?php if ($photos !== []): ?>
            <ul class="request-photos" role="list">
                <?php foreach ($photos as $key => $photo): ?>
                    <li><a href="/index.php?route=admin-demandes&amp;view=photo&amp;id=<?= (int) $photo ?>">Ouvrir la photo <?= $key + 1 ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <form class="catalogue-form admin-card request-edit" action="<?= $escape($base . '&id=' . $record['id']) ?>" method="post">
        <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
        <label>Statut
            <select class="field-input" name="status">
                <?php foreach ($states as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $record['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if ($quote): ?>
            <label>Montant proposé (euros) <input class="field-input" inputmode="decimal" name="amount" value="<?= $escape($record['proposed_amount']) ?>"></label>
            <label>Réponse <textarea class="field-input" rows="4" maxlength="3000" name="reply"><?= $escape($record['reply']) ?></textarea></label>
        <?php else: ?>
            <label>Créneau à proposer ou confirmer
                <select class="field-input" name="slot">
                    <option value="">Sans créneau attribué</option>
                    <?php if ($record['slot_id'] !== null): ?>
                        <option value="<?= (int) $record['slot_id'] ?>" selected>Garder le créneau attribué</option>
                    <?php endif; ?>
                    <?php foreach ($slots as $slot): ?>
                        <?php if ((int) $slot['id'] === (int) $record['slot_id']) { continue; } ?>
                        <option value="<?= (int) $slot['id'] ?>"><?= $escape($dateLabel($slot['starts_utc']) . ' · ' . $slot['location'] . ' · ' . $slot['resource_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label>Notes internes <textarea class="field-input" rows="3" maxlength="3000" name="admin_notes"><?= $escape($record['admin_notes']) ?></textarea></label>
        <button class="action action-primary" type="submit">Enregistrer</button>
        <?php if ($quote && $record['reply']): ?>
            <a class="text-link" href="<?= $escape('mailto:' . $address($record['contact_email']) . '?subject=' . rawurlencode('Votre devis Jeni Rose Couture')
                . '&body=' . rawurlencode($record['reply'] . ($record['proposed_amount'] === null ? '' : "\n\nMontant proposé : " . $record['proposed_amount'] . ' €'))) ?>">Ouvrir la réponse dans votre messagerie</a>
        <?php endif; ?>
    </form>
    <?php if ($history !== []): ?>
        <section class="catalogue-categories">
            <h2>Historique</h2>
            <ol class="catalogue-category-list">
                <?php foreach ($history as $event): ?>
                    <li><?= $states[$event['status']] ?> <?= $event['starts_utc'] ? ' · ' . $escape($dateLabel($event['starts_utc'])) : '' ?>
                        <?= $event['display_name'] ? ' · ' . $escape($event['display_name']) : '' ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endif; ?>
<?php endif; ?>
