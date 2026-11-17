<?php
$title = 'Connexion | Jeni Rose Couture';
$bodyClass = 'login-page';
require dirname(__DIR__) . '/partials/document-start.php';
?>
<main id="contenu" tabindex="-1" class="login-layout">
    <section class="login-intro" aria-labelledby="espace-titre">
        <?php require dirname(__DIR__) . '/partials/brand.php'; ?>
        <div class="login-intro-copy">
            <h2 id="espace-titre">Espace<br> administration</h2>
            <p>Accès réservé à l’équipe de Jeni Rose Couture.</p>
        </div>
        <a class="text-link" href="/">← Retour au site</a>
    </section>
    <section class="login-panel" aria-labelledby="connexion-titre">
        <h1 id="connexion-titre">Connexion</h1>
        <?php if ($message !== null): ?>
            <p class="form-message" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form class="login-form" method="post" action="/index.php?route=admin-login">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label for="email">Adresse e-mail</label>
                <input class="field-input" id="email" name="email" type="email" autocomplete="username" maxlength="254" required>
            </div>
            <div>
                <label for="password">Mot de passe</label>
                <input class="field-input" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="action action-primary" type="submit">Se connecter</button>
        </form>
    </section>
</main>
</body>
</html>
