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

## Rendez-vous et devis

Appliquer la migration et le seeder de permissions après la phase À propos.

```sh
sudo mysql jeni_rose_couture < database/migrations/005_reservations_devis.sql &&
sudo mysql jeni_rose_couture < database/seeders/005_permissions_demandes.sql
```

- **Site public.** `/index.php/reservation` propose une demande de rendez-vous et une réservation automatique avec récapitulatif. `/index.php/devis` enregistre le projet, les préférences de contact et jusqu’à trois photographies. Les photos sont réencodées en WebP et servies uniquement dans l’administration.
- **Disponibilités.** Dans **Rendez-vous et devis > Créneaux**, créer les créneaux en heure de Paris, avec une personne, un atelier ou une zone d’intervention. La réservation automatique nécessite une durée et un mode de prestation configurés dans le catalogue. Un créneau couvre un seul rendez-vous et peut être répété jusqu’à douze semaines.
- **Fermetures.** Fermer un créneau ou les créneaux d’une journée pour une ou toutes les personnes. Les rendez-vous déjà attribués restent visibles dans l’administration. Les changements de réservation et de disponibilité acquièrent le même verrou MySQL avant de vérifier les chevauchements.
- **Gestion.** Le rôle disposant de `requests.manage` peut proposer un créneau, confirmer, refuser ou annuler une demande. Une proposition réserve le créneau jusqu’à sa modification ou son annulation. Les statuts et changements de créneau sont historisés.
- **Devis.** L’administration permet de suivre le statut, le montant proposé, la réponse et les notes internes. La réponse peut être ouverte dans la messagerie du poste. Les notifications SMTP et la transformation d’un devis en commande seront raccordées dans les phases correspondantes.

## Tableau de bord

**Administration > Vue d’ensemble** affiche les données des rendez-vous et des devis. Aucune migration supplémentaire n’est nécessaire après la migration 005. L’affichage des données demande la permission `requests.manage`.

Les périodes sont calculées à l’heure de Paris. Un rendez-vous confirmé compte selon la date du créneau. Les demandes et devis comptent selon la date de dépôt. La liste des prochains rendez-vous reste indépendante du filtre de période.

Le graphique utilise Chart.js 4.5.1, servi localement, et les mêmes chiffres sont disponibles sous forme de tableau. Le code, la version et la licence de la bibliothèque se trouvent dans `public/assets/js/`. [Documentation Chart.js](https://www.chartjs.org/docs/latest/getting-started/integration.html)

## Clients et commandes

Appliquer la migration 006 et attribuer la permission du module :

```sh
sudo mysql jeni_rose_couture < database/migrations/006_clients_commandes.sql &&
sudo mysql jeni_rose_couture < database/seeders/006_permissions_ventes.sql
```

- **Clients.** La migration rattache l’historique des demandes à une fiche unique par adresse e-mail. Les nouveaux formulaires sont liés automatiquement à leur fiche. Une fiche contient les coordonnées, une adresse postale principale et des notes internes. L’archivage conserve son historique.
- **Commandes.** Depuis une fiche client ou la liste des commandes, créer une commande avec ses lignes, quantités et prix unitaires. Le total est calculé au centime côté serveur. L’historique contient un instantané des montants et des lignes à chaque enregistrement.
- **Devis acceptés.** Ouvrir le devis dans **Rendez-vous et devis**, préciser le montant accepté, puis utiliser **Convertir ou ouvrir la commande**. La commande reprend le montant accepté et garde le lien vers son devis d’origine. Une nouvelle conversion ouvre la même commande.
- **Acomptes.** Le champ de la commande indique l’acompte convenu. Le suivi des encaissements sera ajouté avec les paiements. L’affectation utilise provisoirement les personnes et équipes configurées dans le calendrier.

## Finances et factures

Appliquer la migration 007, puis ouvrir **Administration > Finances et factures**.

```sh
composer install
sudo mysql jeni_rose_couture < database/migrations/007_finances_factures.sql &&
sudo mysql jeni_rose_couture < database/seeders/007_permissions_finances.sql
```

- **Paiements.** Enregistrer les règlements et remboursements par commande. Les acomptes convenus dans le module Commandes restent visibles séparément des encaissements.
- **Dépenses.** Créer les catégories puis inscrire les dépenses TTC avec date, libellé et montant. La vue de suivi exporte une synthèse PDF et les registres de la période au format CSV.
- **Factures.** Compléter le profil de facturation avec les données légales confirmées. Depuis une commande, confirmer l’adresse de facturation et les dates. Le modèle couvre les prestations domestiques en euros, avec prix contractuels TTC et un taux de TVA par facture ou la franchise en base. L’émission archive le PDF et son instantané. Un avoir total, numéroté dans la même série, conserve le lien vers la facture corrigée. Enregistrer séparément les remboursements effectués.
- **Numérotation.** Une seule séquence est attribuée dans la même transaction que le stockage du document. Vérifier la série et les mentions applicables à l’entreprise avant toute émission réelle. Pour les opérations concernées par la réforme électronique, la transmission via une plateforme agréée reste à intégrer. [Mentions obligatoires sur une facture](https://entreprendre.service-public.gouv.fr/vosdroits/F31808) · [Facturation électronique et plateformes agréées](https://www.impots.gouv.fr/facturation-electronique-et-plateformes-agreees)
