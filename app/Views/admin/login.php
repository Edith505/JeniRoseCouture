<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion | Jeni Rose Couture</title>
</head>
<body>
    <main>
        <h1>Connexion à l’administration</h1>
        <?php if ($message !== null): ?>
            <p role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form method="post" action="/index.php?route=admin-login">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <p>
                <label for="email">Adresse e-mail</label>
                <input id="email" name="email" type="email" autocomplete="username" maxlength="254" required>
            </p>
            <p>
                <label for="password">Mot de passe</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </p>
            <button type="submit">Se connecter</button>
        </form>
    </main>
</body>
</html>
