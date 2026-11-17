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

## Administration locale

Appliquer les migrations dans l’ordre avec le compte de migration. Sur une base neuve, appliquer également les seeders dans l’ordre. La migration 001 a été vérifiée dans une base dédiée. Les commandes suivantes ciblent la base de développement `jeni_rose_couture` et supposent que les deux migrations y sont encore absentes.

```sh
sudo mysql jeni_rose_couture < database/migrations/001_comptes_et_permissions.sql &&
sudo mysql jeni_rose_couture < database/seeders/001_roles.sql &&
sudo mysql jeni_rose_couture < database/migrations/002_limitation_connexions.sql &&
sudo mysql jeni_rose_couture < database/seeders/002_permissions_administration.sql
```

Donner à Apache la lecture du fichier `.env` hors de `public/`. L’ACL cible son compte système uniquement.

```sh
sudo setfacl -m u:www-data:r-- .env
sudo -u www-data test -r .env
```

Choisir une adresse et un mot de passe propres au compte d’administration. Le mot de passe est saisi sans écho et transmis à PHP via l’entrée standard. Utiliser une phrase de passe de 14 à 72 octets.

```bash
(
  read -rp 'Adresse e-mail administrateur : ' email_admin
  read -rsp 'Mot de passe administrateur : ' mot_de_passe_admin
  printf '\n'
  printf '%s\n' "$mot_de_passe_admin" |
    php bin/create-admin.php "$email_admin" 'Administrateur'
)
```

Ouvrir http://jeni-rose-couture.local/index.php?route=admin-login. Vérifier qu’un accès direct à l’administration redirige vers le formulaire avant la connexion, que la connexion ouvre l’administration et que la déconnexion redirige vers le formulaire.

La session expire après 30 minutes d’inactivité ou 8 heures au total. Le formulaire limite les tentatives à 10 par adresse réseau sur 15 minutes. Le rôle admin reçoit la permission `admin.access`. L’attribution des permissions aux autres rôles se fera avec leurs fonctionnalités. En hébergement, configurer Apache pour servir l’administration en HTTPS et prévoir une purge périodique des compteurs anciens.

## Gabarits et ressources visuelles

Les éléments partagés sont dans `app/Views/partials/`. La charte et les adaptations responsive sont dans `public/assets/css/atelier.css`. Bootstrap 5.3.8 et les fichiers des polices sont servis par Apache avec leurs licences dans `public/assets/`.

La photographie du bandeau est un visuel d’ambiance généré. Pour la remplacer, mettre à jour les deux fichiers WebP du bandeau et, si nécessaire, les dimensions et le texte alternatif dans `app/Views/home.php`.
