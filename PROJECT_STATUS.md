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
