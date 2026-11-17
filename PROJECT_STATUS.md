# État du projet

- Phases 1 à 3 validées. Apache exécute PHP 8.5 sur le domaine local. La connexion PDO à MySQL et le débogage Xdebug en ligne de commande ont été testés.
- Phase 4 en attente de validation. Le socle MVC comprend le point d’entrée public, le contrôleur, la vue d’accueil et le traitement des requêtes non prises en charge.
- Phase 5 à venir. Configurer l’autoload Composer et la connexion PDO de l’application.
- Phases suivantes à venir. Base de données, authentification, identité visuelle, site public, réservations, devis, administration, finances, notifications, SEO, tests et déploiement.

## Vérifications de la phase 4

- PHP 8.5.4 : contrôle de syntaxe réussi sur les quatre fichiers PHP.
- Point d’entrée exercé en ligne de commande avec six requêtes simulées : accueil, accès direct à l’index avec paramètres, HEAD, POST (405) et route inconnue (404).
- Contrôle HTTP du nouveau socle à effectuer depuis le terminal du poste. Les connexions locales sont bloquées dans le terminal Codex.
- La compatibilité PHP 8.3 reste à tester.
