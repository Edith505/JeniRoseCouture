# Modèle relationnel

MySQL 8.4 est le moteur de référence. Les tables utilisent InnoDB et utf8mb4. Les dates techniques sont stockées en UTC et converties en Europe/Paris pour les horaires métier. Les montants sont stockés en DECIMAL et les documents commerciaux conservent des instantanés de leurs données.

| Domaine | Tables et relations |
| --- | --- |
| Comptes | Un `user` a un `role`. `role_permissions` associe rôles et permissions. `password_resets` conserve le condensat et l’échéance d’un jeton par utilisateur. `login_attempts` conserve les tentatives nécessaires à la limitation des connexions. |
| Clients | `clients` possède plusieurs `client_addresses`. Devis, commandes et rendez-vous référencent le client. Les coordonnées opératoires et leur historique commercial ont des durées de conservation distinctes. |
| Entreprise | `business_locations` représente les lieux physiques. `service_areas` décrit les zones couvertes à domicile sans créer un atelier fictif. `business_hours` stocke les horaires publics et `settings` les paramètres non secrets. |
| Catalogue | `service_categories` regroupe les `services`. Un service a des images, une durée et un mode de tarification. Des tables de liaison le relient aux zones, lieux et employés qui le proposent. La catégorie nettoyage est inactive tant que l’offre reste à confirmer. |
| Employés | `employees` représente les personnes planifiables. Le lien vers `users` est facultatif, car un employé peut être planifié sans accès au tableau de bord. |
| Réservations | `appointments` référence le client, le mode d’intervention, le créneau et l’employé selon le statut. `appointment_services` enregistre les prestations et les durées retenues. `availability_rules` définit les plages récurrentes. `availability_exceptions` décrit les fermetures, congés et ouvertures exceptionnelles. |
| Devis | `quotes` référence le client. `quote_items` garde libellé, quantité et montant proposés. Les pièces jointes sont liées au devis par des métadonnées et stockées hors du dossier public. |
| Commandes | `orders` référence le client et, si applicable, un devis accepté. L’identifiant du devis est unique dans les commandes afin qu’une nouvelle tentative ne crée pas de doublon. `order_items` conserve les prix et quantités convenus. |
| Finances | `payments` référence la commande et conserve les encaissements et remboursements. `expenses` référence `expense_categories`. `invoices` référence la commande et `invoice_items` conserve les libellés et les montants émis. |
| Contenu | `pages` stocke les versions de contenu éditorial et leur état de publication. `about_profiles` détient les versions brouillon et publiée. `about_entries` rattache les formations, expériences, compétences, valeurs et photographies à chaque version par un type explicite. `gallery_images`, `testimonials` et `faqs` ont un ordre d’affichage et un état de publication. |
| Suivi | `notifications` stocke événements, statut et tentatives de livraison. `audit_logs` journalise acteur, action et date sans conserver de secrets. |

## Transactions et index

- Réservations automatiques. Prendre un verrou de ligne sur la ressource planifiable avant de relire les disponibilités et vérifier les conflits de créneaux. Toutes les écritures de planning doivent suivre ce protocole. En cas de conflit, la transaction s’annule et le créneau est reproposé. Indexer les recherches par employé et début de créneau.
- Devis acceptés. Créer la commande et ses lignes dans une transaction et protéger la conversion par la contrainte unique sur le devis d’origine.
- Factures. Réserver le numéro avec un compteur transactionnel, protéger l’unicité de la série et du numéro et émettre le document dans la même transaction. Conserver le document émis et suivre les corrections par des documents liés.
- Paiements. Conserver chaque mouvement et sa date. Calculer le facturé depuis les factures et l’encaissé depuis les mouvements validés. Restreindre la suppression des documents ayant des dépendances.
- Recherche et conservation. Indexer les clés étrangères et cibler les combinaisons utilisées par les filtres de statut et de date. Archiver les comptes et le catalogue en conservant leurs liens historiques.

## Ordre des migrations

La migration 001 introduit les rôles, les permissions et les comptes. Le seeder associé insère uniquement les trois rôles de référence. Aucun compte, mot de passe ou contenu fictif n’est livré. Les réinitialisations et tentatives de connexion seront introduites avec le module d’authentification. Les migrations métier suivront l’ordre des dépendances du modèle.

Pour vérifier la migration, utiliser une base MySQL de test vide avec un compte disposant des droits DDL sur cette base :

```sh
mysql -u compte_migrations -p base_de_test < database/migrations/001_comptes_et_permissions.sql
mysql -u compte_migrations -p base_de_test < database/seeders/001_roles.sql
```

Appliquer chaque migration de structure une fois et arrêter en cas d’erreur. Les commandes DDL MySQL provoquent des commits implicites. En cas d’échec sur une base dédiée aux tests, repartir d’une base vierge. Le compte PDO de l’application conserve ses droits DML.
