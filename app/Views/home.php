<?php
$title = 'Jeni Rose Couture | Création sur mesure et retouches';
require __DIR__ . '/partials/document-start.php';
require __DIR__ . '/partials/public-header.php';
?>
<main id="contenu" tabindex="-1">
    <section class="hero" aria-labelledby="accueil-titre">
        <div class="hero-copy">
            <p class="eyebrow">Couture sur mesure et retouches</p>
            <h1 id="accueil-titre">Jeni Rose<br> Couture</h1>
            <p class="hero-subtitle">Votre style, notre savoir-faire.</p>
            <p class="hero-description">Création sur mesure, retouches et entretien de vos vêtements. Nous prenons soin de chaque détail pour donner vie à vos envies.</p>
            <a class="action action-primary" href="#prestations">Découvrir nos services <span aria-hidden="true">↗</span></a>
        </div>
        <picture class="hero-visual">
            <source media="(max-width: 720px)" srcset="/assets/images/atelier-hero-mobile.webp">
            <img src="/assets/images/atelier-hero.webp" width="1120" height="900" fetchpriority="high" alt="Tissu rose drapé sur un mannequin de couture et ciseaux de tailleur">
        </picture>
    </section>

    <section id="prestations" class="section site-width" aria-labelledby="prestations-titre">
        <header class="section-heading">
            <h2 id="prestations-titre">Nos prestations</h2>
            <p>De la pièce imaginée sur mesure au vêtement que vous souhaitez continuer à porter.</p>
        </header>
        <ul class="service-grid list-unstyled">
            <li><h3>Création sur mesure</h3><p>Une pièce conçue à partir de vos idées et de vos mesures.</p></li>
            <li><h3>Retouches de vêtements</h3><p>Ourlets, ajustements et transformations pour un tombé qui vous convient.</p></li>
            <li><h3>Repassage professionnel</h3><p>Un soin adapté aux chemises, robes et textiles délicats.</p></li>
            <li><h3>Boutonnage et réparation</h3><p>Boutons, fermetures et coutures à reprendre pour prolonger la vie de vos vêtements.</p></li>
            <li><h3>Rideaux et linge de maison</h3><p>Ourlets, coussins et ajustements pour les textiles de votre intérieur.</p></li>
            <li><h3>Conseils en couture</h3><p>Des séances pour prendre en main votre machine ou avancer sur un projet.</p></li>
        </ul>
    </section>

    <section id="approche" class="approach" aria-labelledby="approche-titre">
        <div class="section site-width approach-inner">
            <h2 id="approche-titre">Une attention portée à chaque détail</h2>
            <p>Un vêtement à ajuster, une pièce à créer ou un textile à réparer : nous prenons le temps de comprendre votre besoin pour vous proposer une solution adaptée à la matière et à l’usage.</p>
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/public-footer.php'; ?>
</body>
</html>
