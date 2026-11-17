<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration | Jeni Rose Couture</title>
</head>
<body>
    <main>
        <h1>Administration</h1>
        <form method="post" action="/index.php?route=admin-logout">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Se déconnecter</button>
        </form>
    </main>
</body>
</html>
