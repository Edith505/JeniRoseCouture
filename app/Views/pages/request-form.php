<?php
use App\Requests\RequestInput;

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$value = static fn (string $key): string => $escape($values[$key] ?? '');
?>
<?php if ($done): ?>
    <section class="info-card" role="status">
        <h2><?= $automatic ? 'Votre rendez-vous est confirmé' : 'Votre demande a été enregistrée' ?></h2>
        <p><?= $automatic ? 'Le créneau choisi vous est réservé.' : 'Les coordonnées indiquées permettront à l’équipe de vous répondre.' ?></p>
    </section>
<?php endif; ?>
<?php if ($message !== null): ?>
    <p class="form-message" role="alert"><?= $escape($message) ?></p>
<?php endif; ?>

<?php if (!$quote): ?>
    <nav class="request-modes" aria-label="Mode de rendez-vous">
        <a class="action <?= $automatic ? 'action-outline' : 'action-primary' ?>" href="/index.php/reservation" <?= !$automatic ? 'aria-current="page"' : '' ?>>Envoyer une demande</a>
        <a class="action <?= $automatic ? 'action-primary' : 'action-outline' ?>" href="/index.php/reservation?mode=automatic" <?= $automatic ? 'aria-current="page"' : '' ?>>Choisir un créneau</a>
    </nav>
<?php endif; ?>
<?php if ($review !== null): ?>
    <section class="request-card">
        <h2>Vérifier votre rendez-vous</h2>
        <dl class="request-summary">
            <dt>Prestation</dt><dd><?= $escape($selected['name']) ?></dd>
            <dt>Date et heure</dt><dd><?= $escape(RequestInput::label($review['starts_utc'])) ?>, heure de Paris</dd>
            <dt>Lieu</dt><dd><?= $escape($review['location']) ?></dd>
            <dt>Contact</dt><dd><?= $value('name') ?> · <?= $value('email') ?></dd>
            <?php if ($value('phone') !== ''): ?><dt>Téléphone</dt><dd><?= $value('phone') ?></dd><?php endif; ?>
            <?php if ($value('preference') !== ''): ?><dt>Préférences</dt><dd><?= $value('preference') ?></dd><?php endif; ?>
            <?php if ($value('notes') !== ''): ?><dt>Informations</dt><dd class="catalogue-lines"><?= $value('notes') ?></dd><?php endif; ?>
        </dl>
        <form method="post" action="<?= $escape($formPath) ?>">
            <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
            <input type="hidden" name="review_key" value="<?= $escape($reviewKey) ?>">
            <button class="action action-primary" type="submit" name="intent" value="confirm">Confirmer ce rendez-vous</button>
            <a class="text-link" href="<?= $escape($formPath . '&service=' . $selected['id'] . '&edit=1') ?>">Modifier</a>
        </form>
    </section>
<?php else: ?>
    <?php if ($automatic): ?>
        <form class="request-card catalogue-form" method="get" action="/index.php/reservation">
            <input type="hidden" name="mode" value="automatic">
            <label>Prestation
                <select class="field-input" name="service" required>
                    <option value="">Choisir une prestation</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= (int) $service['id'] ?>" <?= $selected && $selected['id'] === $service['id'] ? 'selected' : '' ?>>
                            <?= $escape($service['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="action action-outline" type="submit">Afficher les créneaux</button>
        </form>
        <?php if ($selected !== null && $slots === []): ?>
            <p class="page-actions">Aucun créneau en ligne pour cette prestation. <a class="text-link" href="/index.php/reservation?service=<?= (int) $selected['id'] ?>">Envoyer une demande</a></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($services === []): ?>
        <p>Aucune prestation réservable pour le moment.</p>
    <?php elseif (!$automatic || $slots !== []): ?>
        <form class="request-card catalogue-form" method="post" action="<?= $escape($formPath) ?>" <?= $quote ? 'enctype="multipart/form-data"' : '' ?>>
            <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
            <div class="request-website" aria-hidden="true">
                <label>Site Web <input name="website" tabindex="-1" autocomplete="off"></label>
            </div>
            <?php if ($automatic): ?>
                <input type="hidden" name="service" value="<?= (int) $selected['id'] ?>">
                <label>Créneau (heure de Paris)
                    <select class="field-input" name="slot" required>
                        <option value="">Choisir un créneau</option>
                        <?php foreach ($slots as $slot): ?>
                            <option value="<?= (int) $slot['id'] ?>" <?= (string) $slot['id'] === $value('slot') ? 'selected' : '' ?>>
                                <?= $escape(RequestInput::label($slot['starts_utc']) . ' · ' . $slot['location'] . ($slot['mode'] === 'home' ? ' (à domicile)' : ' (atelier)')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php else: ?>
                <label>Prestation
                    <select class="field-input" name="service" required>
                        <option value="">Choisir une prestation</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= (int) $service['id'] ?>" <?= $selected && $selected['id'] === $service['id'] ? 'selected' : '' ?>>
                                <?= $escape($service['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <label>Nom <input class="field-input" name="name" value="<?= $value('name') ?>" maxlength="140" autocomplete="name" required></label>
            <div class="catalogue-form-row">
                <label>Adresse e-mail <input class="field-input" type="email" name="email" value="<?= $value('email') ?>" maxlength="254" autocomplete="email" required></label>
                <label>Téléphone (facultatif) <input class="field-input" type="tel" name="phone" value="<?= $value('phone') ?>" maxlength="40" autocomplete="tel"></label>
            </div>
            <?php if ($quote): ?>
                <label>Mode de contact préféré
                    <select class="field-input" name="channel">
                        <option value="email">E-mail</option>
                        <option value="phone" <?= $value('channel') === 'phone' ? 'selected' : '' ?>>Téléphone</option>
                    </select>
                </label>
            <?php endif; ?>
            <label><?= $quote ? 'Préférences et délai souhaité' : 'Date, commune ou lieu souhaité (facultatif)' ?>
                <input class="field-input" name="preference" maxlength="450" value="<?= $value('preference') ?>">
            </label>
            <label><?= $quote ? 'Description du projet' : 'Informations complémentaires (facultatif)' ?>
                <textarea class="field-input" name="notes" rows="4" maxlength="3000" <?= $quote ? 'required' : '' ?>><?= $value('notes') ?></textarea>
            </label>
            <?php if ($quote): ?>
                <label>Photographies (facultatif)
                    <input class="field-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <small>Trois images au maximum, de 2 Mio chacune.</small>
                </label>
            <?php endif; ?>
            <p class="catalogue-note">Les informations servent à traiter votre demande. <a href="/index.php/confidentialite">Confidentialité</a>.</p>
            <button class="action action-primary" type="submit"><?= $automatic ? 'Vérifier le rendez-vous' : 'Envoyer la demande' ?></button>
        </form>
    <?php endif; ?>
<?php endif; ?>
