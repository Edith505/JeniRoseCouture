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
