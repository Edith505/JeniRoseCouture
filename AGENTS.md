# Règles du projet

- Avancer par phases et obtenir la validation de l’utilisateur avant la phase suivante.
- Maintenir la compatibilité PHP 8.3 et vérifier les comportements depuis leurs points d’entrée habituels.
- Conserver l’architecture MVC PHP personnalisée. Utiliser Composer pour l’autoload et les dépendances à partir de la phase dédiée, et PDO pour la persistance.
- Garder `public/` comme seule racine Web. Conserver secrets, journaux, sauvegardes et documents confidentiels hors de ce dossier et du dépôt Git.
- Séparer les données de démonstration des données réelles. Utiliser des données confirmées pour la publication.
- Mettre à jour `PROJECT_STATUS.md` et `CHANGELOG.md` avec les changements et les résultats des vérifications.
