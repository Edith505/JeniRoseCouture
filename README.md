# Jeni Rose Couture

Php mysql Js css html MVC API

Application PHP pour le site public et la gestion privée de Jeni Rose Couture.

## Démarrage local

Le poste de développement utilise Ubuntu 26.04, Apache et PHP 8.5. La cible de compatibilité est PHP 8.3.

Sur le poste configuré, ouvrir http://jeni-rose-couture.local/. Le VirtualHost Apache doit pointer vers le dossier `public/`. Cette étape affiche une page d’accueil minimale sans utiliser la base de données.

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
