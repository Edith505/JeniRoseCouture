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

Les éléments partagés sont dans `app/Views/partials/`. La charte et les adaptations responsive sont dans `public/assets/css/atelier.css`. Les gabarits utilisent la feuille de style du projet. Les polices Inter et Playfair Display sont servies par Apache avec leurs licences dans `public/assets/fonts/`.

La photographie du bandeau est un visuel d’ambiance généré. Pour la remplacer, mettre à jour les deux fichiers WebP du bandeau et, si nécessaire, les dimensions et le texte alternatif dans `app/Views/home.php`.

## Pages publiques

L’accueil et les pages publiques sont accessibles sur le VirtualHost local. Les pages secondaires utilisent des chemins sous `/index.php/`, compatibles avec la configuration Apache déjà en place. Les textes et tarifs d’exemple partagent une seule source temporaire avant le catalogue administrable.

Les visuels de la galerie et des cartes de prestations sont des illustrations générées. Les cartes utilisent des WebP de 480 × 360 px. La page Contact attend des coordonnées confirmées et les formulaires seront raccordés à la phase prévue. Avant la mise en ligne, compléter les mentions légales à partir des informations de l’éditeur et de l’hébergeur, puis la politique de confidentialité à partir des traitements réellement activés.

- [Mentions obligatoires sur un site professionnel](https://entreprendre.service-public.gouv.fr/vosdroits/F37351?lang=fr)
- [Information des personnes et transparence selon la CNIL](https://www.cnil.fr/fr/conformite-rgpd-information-des-personnes-et-transparence)

## Catalogue administrable

Pour un environnement local où les migrations 001 et 002 sont déjà appliquées, ajouter la migration et les permissions du catalogue, puis initialiser les données d’exemple.

```sh
sudo mysql jeni_rose_couture < database/migrations/003_catalogue.sql &&
sudo mysql jeni_rose_couture < database/seeders/003_permissions_catalogue.sql &&
php bin/seed-catalogue.php
```

Le seeder conserve les entrées déjà présentes et n’active pas le nettoyage. Depuis le tableau de bord, ouvrir **Prestations et tarifs**. La permission `catalog.manage` permet de gérer les catégories et les prestations. Masquer une catégorie retire ses prestations du site. Archiver une prestation la conserve dans la base.

Les photographies envoyées par l’administration sont redimensionnées et réencodées en WebP dans MySQL. L’image d’une prestation masquée ou archivée cesse d’être servie par la route publique du catalogue. Les fichiers d’illustration livrés avec le projet sont des ressources statiques.

Définir `APP_ENV=local` dans le fichier `.env` du poste de développement. Ce réglage permet l’aperçu avant migration et l’affichage des montants de démonstration. Ne pas exécuter le seeder d’exemple sur une base de production. En production, utiliser `APP_ENV=production` et saisir le catalogue confirmé.

## Page À propos

Après la phase catalogue, appliquer la migration et la permission du module :

```sh
sudo mysql jeni_rose_couture < database/migrations/004_a_propos.sql &&
sudo mysql jeni_rose_couture < database/seeders/004_permissions_a_propos.sql &&
php bin/seed-about.php
```

La dernière commande initialise uniquement l’environnement local. Si `storage/local/portrait-jeni-rose.webp` est présent, il devient le portrait initial. Ce fichier est ignoré par Git. Vous pouvez également ajouter le portrait depuis l’éditeur. Les exemples fictifs sont placés dans le brouillon.

Dans **Administration > Contenu du site · À propos**, enregistrer le brouillon, vérifier l’aperçu, puis publier. Les sections et leurs entrées ont des ordres indépendants. L’archivage d’une entrée modifie le brouillon. La page publique conserve sa dernière version jusqu’à la publication suivante. En production, le module refuse la publication d’une biographie ou d’entrées visibles signalées comme fictives.
