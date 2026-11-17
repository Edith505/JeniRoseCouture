# Jeni Rose Couture

Php mysql Js css html MVC API

Application PHP pour le site public et la gestion privée de Jeni Rose Couture.

## Démarrage local

Le poste de développement utilise Ubuntu 26.04, Apache et PHP 8.5. La cible de compatibilité est PHP 8.3.

Exécuter `composer install` depuis la racine du projet, puis ouvrir http://jeni-rose-couture.local/. Le VirtualHost Apache doit pointer vers le dossier `public/`. Cette étape affiche une page d’accueil minimale sans utiliser la base de données.

Pour un contrôle sans Apache, exécuter depuis la racine du projet :

```sh
php -S 127.0.0.1:8080 -t public public/index.php
```

Ouvrir ensuite http://127.0.0.1:8080/. Ce serveur est réservé au développement.

## Organisation

- `public/index.php` reçoit les requêtes et sélectionne le traitement.
- `app/Controllers/` contient les contrôleurs.
- `app/Views/` contient les gabarits HTML.
- Les modèles seront introduits avec les premiers besoins de persistance.

## Base MySQL locale

Copier `.env.example` vers `.env`, réserver le fichier à votre utilisateur et compléter `DB_PASSWORD` avec le mot de passe du compte applicatif local.

```sh
cp -n .env.example .env
chmod 600 .env
php bin/check-db.php
```

Le résultat attendu est `Connexion PDO : 1`. Ne pas ajouter `.env` au dépôt. L’accueil reste accessible sans base et le contrôle PDO s’exécute en ligne de commande.
