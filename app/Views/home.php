<?php
$activePage = '/';
$title = 'Jeni Rose Couture | Création sur mesure et retouches';
require __DIR__ . '/partials/document-start.php';
require __DIR__ . '/partials/public-header.php';
?>
<main id="contenu" tabindex="-1">
    <section class="hero" aria-labelledby="accueil-titre">
        <div class="site-width hero-inner">
            <div class="hero-copy">
                <h1 id="accueil-titre">Jeni Rose Couture</h1>
                <p class="hero-subtitle">Votre style,<br> notre savoir&#8209;faire.</p>
                <p class="hero-description">Création sur mesure, retouches et entretien de vos vêtements à Marseille et Pertuis. Nous prenons soin de chaque détail pour donner vie à vos envies.</p>
                <div class="hero-actions">
                    <a class="action action-primary" href="#prestations">Découvrir nos services <span aria-hidden="true">↓</span></a>
                    <a class="action action-outline" href="/index.php/reservation">Prendre rendez-vous</a>
                </div>
            </div>
        </div>
        <picture class="hero-visual">
            <source media="(max-width: 720px)" srcset="/assets/images/atelier-lumiere-mobile.webp">
            <img src="/assets/images/atelier-lumiere.webp" width="1440" height="768" fetchpriority="high" alt="Mannequin de couture, mètre ruban rose et étoffes pastel dans un atelier lumineux">
        </picture>
    </section>

    <section id="prestations" class="section site-width home-services" aria-labelledby="prestations-titre">
        <header class="section-heading">
            <h2 id="prestations-titre">Nos prestations</h2>
            <p>De la pièce imaginée sur mesure au vêtement que vous souhaitez continuer à porter.</p>
            <a class="text-link" href="/index.php/prestations">Voir toutes les prestations <span aria-hidden="true">↗</span></a>
        </header>
        <?php require __DIR__ . '/partials/service-list.php'; ?>
    </section>

    <section id="approche" class="approach" aria-labelledby="approche-titre">
        <div class="site-width approach-inner">
            <img class="page-photo" src="/assets/images/galerie-matieres.webp" width="960" height="640"
                loading="lazy" decoding="async" alt="Étoffes pastel, bobines et outils de couture sur une table">
            <div>
                <h2 id="approche-titre">Une attention portée à chaque détail</h2>
                <p>Un vêtement à ajuster, une pièce à créer ou un textile à réparer : nous prenons le temps de comprendre votre besoin pour vous proposer une solution adaptée à la matière et à l’usage.</p>
                <a class="action action-outline" href="/index.php/a-propos">Découvrir notre approche <span aria-hidden="true">↗</span></a>
            </div>
        </div>
    </section>

    <section class="section site-width home-gallery" aria-labelledby="galerie-titre">
        <header class="section-heading">
            <div>
                <h2 id="galerie-titre">Regards sur la couture</h2>
                <p>Gestes, matières et silhouettes en images d’illustration.</p>
            </div>
            <a class="text-link" href="/index.php/galerie">Voir la galerie <span aria-hidden="true">↗</span></a>
        </header>
        <div class="home-gallery-grid">
            <img src="/assets/images/galerie-geste.webp" width="960" height="640" loading="lazy" alt="Mains qui cousent un tissu clair">
            <img src="/assets/images/service-creation.webp" width="480" height="360" loading="lazy" alt="Tissu rose drapé sur un mannequin">
            <img src="/assets/images/service-conseils.webp" width="480" height="360" loading="lazy" alt="Machine à coudre et tissu rose sur une table">
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/public-footer.php'; ?>
</body>
</html>
