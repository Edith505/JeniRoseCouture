<?php
use App\About\AboutContent;

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$preview = $preview ?? false;
$photoUrl = static fn (string $key): string => $preview
    ? '/index.php?route=admin-a-propos-image&amp;key=' . rawurlencode($key)
    : '/index.php/a-propos/photo/' . rawurlencode($key);
$sections = $content['sections'];
uasort($sections, static fn (array $left, array $right): int => $left['order'] <=> $right['order']);
$groups = [];
foreach ($content['entries'] as $entry) {
    if ($entry['visible'] && !$entry['archived'] && ($entry['kind'] !== 'photo' || $entry['has_image'])) {
        $groups[$entry['kind']][] = $entry;
    }
}
?>
<?php if ($content['is_demo']): ?>
    <p class="about-demo" role="note">Biographie fictive de démonstration.</p>
<?php endif; ?>
<div class="about-layout<?= $content['has_portrait'] ? ' about-with-portrait' : '' ?>">
    <?php if ($content['has_portrait']): ?>
        <figure class="about-portrait">
            <img src="<?= $photoUrl('portrait') ?>" alt="Portrait de <?= $escape($content['display_name']) ?>">
            <figcaption><?= $escape($content['display_name']) ?></figcaption>
        </figure>
    <?php endif; ?>
    <div class="about-sections">
        <?php foreach ($sections as $section => $settings): ?>
            <?php
            if (!isset(AboutContent::SECTIONS[$section]) || !$settings['visible']) {
                continue;
            }
            $entries = $groups[$section] ?? [];
            if (($section === 'story' && $content['story'] === '')
                || ($section === 'quote' && $content['quote'] === '')
                || (isset(AboutContent::KINDS[$section]) && $entries === [])
            ) {
                continue;
            }
            ?>
            <section class="about-section" aria-labelledby="about-<?= $section ?>">
                <h2 id="about-<?= $section ?>"><?= AboutContent::SECTIONS[$section] ?></h2>
                <?php if ($section === 'story'): ?>
                    <p class="catalogue-lines"><?= $escape($content['story']) ?></p>
                <?php elseif ($section === 'quote'): ?>
                    <blockquote><?= $escape($content['quote']) ?></blockquote>
                <?php else: ?>
                    <ul class="about-entries" role="list">
                        <?php foreach ($entries as $entry): ?>
                            <li>
                                <?php if ($entry['has_image']): ?>
                                    <img src="<?= $photoUrl($entry['entry_key']) ?>" loading="lazy"
                                        alt="<?= $section === 'photo' ? $escape($entry['title']) : '' ?>">
                                <?php endif; ?>
                                <h3><?= $escape($entry['title']) ?></h3>
                                <?php if ($entry['is_demo']): ?>
                                    <small class="catalogue-note">Exemple fictif</small>
                                <?php endif; ?>
                                <?php if ($entry['organisation'] !== '' || $entry['period'] !== ''): ?>
                                    <p class="about-meta"><?= $escape(implode(' · ', array_filter([$entry['organisation'], $entry['period']]))) ?></p>
                                <?php endif; ?>
                                <?php if ($entry['description'] !== ''): ?>
                                    <p class="catalogue-lines"><?= $escape($entry['description']) ?></p>
                                <?php endif; ?>
                                <?php if ($entry['responsibilities'] !== ''): ?>
                                    <p class="catalogue-lines"><strong>Missions.</strong> <?= $escape($entry['responsibilities']) ?></p>
                                <?php endif; ?>
                                <?php if ($entry['competencies'] !== ''): ?>
                                    <p class="catalogue-lines"><strong>Compétences acquises.</strong> <?= $escape($entry['competencies']) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
        <p class="page-actions"><a class="action action-outline" href="/index.php/prestations">Voir les prestations</a></p>
    </div>
</div>
