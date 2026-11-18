# État du projet

- Phases 1 à 3 validées. Apache exécute PHP 8.5 sur le domaine local. La connexion PDO à MySQL et le débogage Xdebug en ligne de commande ont été testés.
- Phase 4 validée. Le socle MVC comprend le point d’entrée public, le contrôleur, la vue d’accueil et le traitement des requêtes non prises en charge.
- Phase 5 validée. L’autoload Composer et la fabrique PDO sont intégrés. Le contrôle sur la base locale a renvoyé `Connexion PDO : 1`.
- Phases suivantes à venir. Base de données, authentification, identité visuelle, site public, réservations, devis, administration, finances, notifications, SEO, tests et déploiement.

## Vérifications de la phase 4

- PHP 8.5.4 : contrôle de syntaxe réussi sur les quatre fichiers PHP.
- Point d’entrée exercé en ligne de commande avec six requêtes simulées : accueil, accès direct à l’index avec paramètres, HEAD, POST (405) et route inconnue (404).
- Contrôle HTTP de l’accueil via Apache confirmé sur le poste.
- La compatibilité PHP 8.3 reste à tester.

## Vérifications de la phase 5

- Dépendances installées et exigences de plateforme vérifiées sous PHP 8.5.4.
- Accueil vérifié via Apache après le passage à l’autoload Composer.

## Phase 6 vérifiée

- Modèle relationnel documenté dans `docs/modele-relationnel.md`.
- Migration 001 appliquée sans erreur sur la base MySQL de test selon le retour utilisateur. Les trois rôles attendus apparaissent après deux exécutions du seeder.
- Les migrations métier seront ajoutées avec les modules qui les utilisent.

## Phase 7 vérifiée en local

- Authentification, permission `admin.access`, sessions, CSRF et limitation des tentatives implémentés.
- Vérifié via Apache : accueil public, formulaire, redirection pour les visiteurs, rejet d’un formulaire sans jeton, refus de la déconnexion par GET et en-têtes de session et de cache.
- Les migrations et la création du premier compte ont été vérifiées par son accès aux pages protégées via Apache. Les identifiants de session changent à la connexion et à la déconnexion. Une ancienne session est refusée après déconnexion. Le rejet des identifiants inconnus et la limitation des tentatives ont été vérifiés sur la base locale.
- La réinitialisation du mot de passe par e-mail dépendra du module de notifications.

## Phase 8 validée

- Gabarits adaptatifs pour l’accueil, la connexion, l’administration et les erreurs publiques.
- Bootstrap 5.3.8 et polices Inter et Playfair Display servis localement. La photographie d’ambiance de l’accueil est une illustration originale générée, optimisée en WebP pour deux tailles d’écran.
- Contrôles sous Chrome à 320, 768 et 1440 px : absence de défilement horizontal, chargement des polices, choix du bon fichier image, connexion et déconnexion.
- La navigation publique renvoie aux sections disponibles. Les pages publiques détaillées et les modules du tableau de bord seront introduits dans leurs phases.

## Phase 9 validée

- Accueil et huit pages publiques disponibles avec le gabarit partagé et la navigation fonctionnelle.
- Tarifs identifiés comme démonstration. Galerie composée de visuels d’illustration.
- Coordonnées et mentions de publication à compléter après confirmation. Réservations et devis restent prévus à la phase 12.
- Réponses HTTP, ressources et accès anonyme à l’administration vérifiés via Apache.
- Les neuf pages ont été contrôlées dans Chrome à 320, 768 et 1440 px. Les images de la galerie se chargent et les questions de la FAQ s’ouvrent avec Entrée.
- Affinage du design effectué avant validation de la phase 9. Bootstrap n’est plus chargé. Les neuf pages publiques et le formulaire de connexion ont été vérifiés sous Chrome à 320, 390, 768 et 1440 px avec les nouveaux styles.
- Nouvelle variante visuelle intégrée à la phase 9. Accueil, pages publiques et formulaire vérifiés sous Chrome aux quatre largeurs prévues. Contraste des accents, boutons et textes de pied de page contrôlé.

## Phase 10 vérifiée en local

- Catalogue MySQL, formulaire de gestion, permission `catalog.manage` et fiches publiques réalisés.
- Vérifications locales avant migration : affichage des six exemples, fiches individuelles, prix de démonstration, nettoyage absent du catalogue, contrôle d’accès et CSRF.
- Migration 003 appliquée. Création, modification, publication, téléversement WebP, rejet d’un fichier texte, masquage par catégorie et archivage vérifiés via Apache. La prestation de test et sa catégorie ont été retirées après les essais.
- Le seeder a été relancé sans modifier les entrées existantes. Les formulaires d’administration et la fiche publique ont été vérifiés dans Chrome à 320, 768 et 1440 px.

## Phase 11 vérifiée en local

- Éditeur, aperçu privé, publication transactionnelle et gestion des photographies développés.
- Portrait optimisé et conservé hors du dépôt. Biographie et parcours fictifs disponibles dans le brouillon après initialisation.
- Migration 004 appliquée. Portrait publié, aperçu privé, téléversement, archivage, restauration et séparation du brouillon ont été vérifiés via Apache. Le refus de publier une biographie fictive en mode production a été vérifié.

## Phase 12 vérifiée en local

- Migration 005 appliquée. Réservation automatique et demande de rendez-vous testées via Apache, y compris la concurrence sur un créneau.
- Créneaux, fermeture de journée, réaffectation et historique vérifiés. Les créneaux de rendez-vous futurs utilisent l’heure de Paris à l’écran et UTC en base.
- Devis avec photographie, protection de son accès, montant décimal et réponse administrateur vérifiés. Les données fonctionnelles de test ont été retirées.
- La conversion du devis en commande et les envois SMTP sont prévus dans les phases Commandes et Notifications.
- Formulaires publics, liste des rendez-vous et écrans de créneaux contrôlés dans Chrome à 320, 768 et 1440 px.

## Phase 13 vérifiée en local

- Tableau de bord alimenté par MySQL et protégé par la permission du module. Aucun changement de schéma.
- Vérifiés sur des données temporaires : quatre compteurs, agrégation aux bornes d’un changement d’heure, filtres, protection des données, classement et prochains rendez-vous.
- Graphique Chart.js et rendu contrôlés dans Chrome à 320, 768 et 1440 px. Les données temporaires ont été retirées.
- Clients, commandes et indicateurs financiers seront raccordés dans leurs phases respectives.

## Phase 14 vérifiée en local

- Fiches clients, historique, création et modification de commandes, et conversion des devis développés.
- Contrôles de syntaxe, calcul exact des montants et regroupement des contacts vérifiés.
- Migration 006 appliquée. Via Apache, le rattachement des rendez-vous et devis à une fiche unique, l’archivage, la réactivation, les commandes multilignes et la conservation des prix ont été vérifiés. Deux conversions simultanées d’un devis accepté ont produit une seule commande. Les données de test ont été retirées.
