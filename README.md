Akouss Kalara est une application de vente et de gestion de livres en ligne développée dans le cadre d'un projet académique.

Le projet propose une plateforme permettant aux utilisateurs de consulter un catalogue de livres, rechercher des ouvrages, consulter leurs détails et leurs offres, gérer un panier, passer des commandes et suivre leur historique.

L'application comprend également un espace vendeur ainsi qu'une architecture de paiement intégrant une simulation MTN MoMo et Orange Money, en attendant la réception des spécifications et identifiants officiels nécessaires à l'intégration réelle des services de paiement.

 Objectifs du projet

L'objectif principal est de concevoir et développer une solution numérique permettant de :

 consulter un catalogue de livres ;
 rechercher des ouvrages ;
 consulter les détails d'un livre ;
 consulter les offres disponibles auprès des vendeurs ;
 ajouter des articles au panier ;
 modifier les quantités et gérer le panier ;
 passer une commande ;
 gérer le processus de paiement ;
 proposer une application mobile Flutter ;
 gérer l'inscription et l'authentification ;
 consulter l'historique des commandes ;
 permettre aux vendeurs de gérer leurs offres ;
 fournir des fonctionnalités d'administration et de modération.

 Architecture du projet

Le projet est organisé autour de trois principaux composants :

                    ┌──────────────────────┐
                    │     Application Web  │
                    │    HTML / CSS / JS   │
                    └──────────┬───────────┘
                               │
                               │ JSON / HTTP
                               ▼
                    ┌──────────────────────┐
                    │      API REST PHP    │
                    │   Auth / Catalogue   │
                    │ Panier / Commandes   │
                    │      Paiement        │
                    └──────────┬───────────┘
                               │
                               │ PDO / SQL
                               ▼
                    ┌──────────────────────┐
                    │        MySQL         │
                    │    akouss_kalara     │
                    └──────────────────────┘

                    ┌──────────────────────┐
                    │ Application mobile  │
                    │     Flutter/Dart    │
                    └──────────┬───────────┘
                               │
                               │ HTTP / JSON
                               ▼
                         API REST PHP

 Technologies utilisées
Frontend Web
HTML5
CSS3
JavaScript
Fetch API
Interface responsive
Backend
PHP
API REST
JSON
PDO
Authentification par jeton Bearer
Sessions utilisateur côté API
Base de données
MySQL
Relations entre utilisateurs, livres, offres, paniers, commandes et paiements
Application mobile
Flutter
Dart
http
shared_preferences
Paiement

Architecture prévue pour :

MTN MoMo
Orange Money

Le projet dispose actuellement d'un mode de simulation de paiement permettant de tester les scénarios :

paiement réussi ;
paiement échoué.

L'intégration réelle nécessite les documents techniques, identifiants et paramètres officiels du fournisseur de paiement utilisé dans le cadre du projet (ARITED).

 Structure du projet
akouss-kalara-php/
│
├── api/
│   ├── admin/
│   ├── auth/
│   ├── catalogue/
│   ├── commandes/
│   ├── config/
│   ├── paiement/
│   ├── panier/
│   └── vendeur/
│
├── web/
│   ├── css/
│   ├── js/
│   ├── index.html
│   ├── catalogue.html
│   ├── livre.html
│   ├── panier.html
│   ├── commande.html
│   ├── connexion.html
│   ├── inscription.html
│   ├── mes-commandes.html
│   ├── vendeur-offres.html
│   ├── vendeur-publier.html
│   └── admin.html
│
├── akouss_kalara_mobile/
│   ├── lib/
│   │   ├── models/
│   │   ├── screens/
│   │   ├── services/
│   │   ├── widgets/
│   │   └── main.dart
│   ├── test/
│   └── pubspec.yaml
│
├── donnees_test.sql
└── README.md
 Fonctionnalités principales
 Authentification
Inscription utilisateur
Inscription vendeur
Connexion
Vérification de l'activation du compte
Gestion du jeton d'authentification
Déconnexion
Gestion de session côté application mobile
 Catalogue
Liste des livres
Recherche
Filtrage par catégorie
Consultation des détails
Consultation des offres disponibles
Affichage du prix et de la disponibilité
 Panier
Ajout d'une offre au panier
Modification des quantités
Suppression d'un article
Calcul automatique du total
Vérification du stock
 Commandes
Création d'une commande
Choix du mode de livraison
Décrémentation du stock
Historique des commandes
Consultation du statut de commande
Association avec le paiement
 Paiement

Le projet possède une architecture permettant de gérer différents fournisseurs de paiement.

Fournisseurs prévus :

MTN MoMo
Orange Money

Pour le développement et les tests, une API de simulation permet de reproduire :

Commande
   │
   ▼
Choix du fournisseur
   │
   ├── MTN MoMo
   │
   └── Orange Money
          │
          ▼
     Simulation
       /     \
      /       \
 Succès      Échec
   │            │
   ▼            ▼
Commande     Commande
  payée      en attente
 Application mobile Flutter

L'application mobile comprend notamment :

 Accueil
 Catalogue
 Recherche
 Détails d'un livre
 Panier
 Commandes
 Profil
 Connexion
 Inscription
 Simulation de paiement
Vérification du projet Flutter

L'analyse statique du projet est effectuée avec : flutter analyze

Les tests Flutter peuvent être exécutés avec : flutter test

 Installation
1. Cloner le dépôt
git clone https://github.com/Tiomo-Ivan/akouss-kalara-php.git
cd akouss-kalara-php
2. Préparer la base de données

Créer la base :

CREATE DATABASE akouss_kalara
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

Importer ensuite les données de démonstration :

mysql -u root -p akouss_kalara < donnees_test.sql

Les paramètres de connexion à la base de données doivent être configurés localement dans l'environnement de développement. Les mots de passe et autres secrets ne doivent pas être commités dans Git.

3. Configurer PHP

Vérifier la configuration du fichier :

api/config/database.php

Adapter les paramètres à l'environnement local.

4. Lancer le serveur Web

Avec XAMPP/Apache, placer ou conserver le projet dans le répertoire approprié, puis accéder à :

http://localhost/akouss-kalara-php/web/

L'API est disponible sous :

http://localhost/akouss-kalara-php/api/
 Installation de l'application Flutter

Se placer dans le projet mobile :

cd akouss_kalara_mobile

Installer les dépendances :

flutter pub get

Vérifier le projet :

flutter analyze

Exécuter les tests :

flutter test

Lancer l'application :

flutter run
 API

L'API utilise principalement des requêtes HTTP avec des données JSON.

Exemple de connexion :

POST /api/auth/connexion.php
Content-Type: application/json
Accept: application/json

Exemple de données :

{
  "email": "utilisateur@example.com",
  "mot_de_passe": "********"
}

Après authentification, les endpoints protégés utilisent un jeton :

Authorization: Bearer <TOKEN>
 Sécurité

Quelques principes appliqués dans le projet :

mots de passe stockés sous forme de hash ;
authentification par jeton ;
requêtes SQL préparées avec PDO ;
contrôle de propriété des paniers et commandes ;
vérification du stock avant création d'une commande ;
validation des données reçues par l'API ;
séparation des secrets de configuration du code versionné.

Les identifiants, mots de passe, tokens et clés d'API ne doivent jamais être publiés dans le dépôt Git.

 Tests et validation

Les fonctionnalités ont été testées progressivement au cours du développement.

Exemples de validations :

inscription ;
activation de compte ;
connexion ;
catalogue ;
recherche ;
détails des livres ;
offres ;
panier ;
création de commande ;
gestion du stock ;
historique des commandes ;
simulation de paiement MTN ;
simulation de paiement Orange ;
tests Flutter.

Le test Flutter principal vérifie notamment l'affichage de la navigation principale de l'application.

 État de l'intégration des paiements
Mode simulation

Disponible pour le développement :

MTN MoMo : succès / échec
Orange Money : succès / échec
Intégration réelle

L'architecture backend est préparée pour l'intégration des services réels.

Cependant, l'intégration de production reste dépendante de la réception des :

spécifications techniques officielles ;
identifiants API ;
clés d'accès ;
paramètres de l'environnement ;
URLs officielles ;
règles de callback ;
paramètres spécifiques au fournisseur de paiement.

Aucun identifiant réel n'est inclus dans ce dépôt.

 Méthodologie

Le développement du projet suit une approche Agile avec la méthodologie SCRUM.

Le projet est développé progressivement par sprints :

Analyse
   ↓
Conception
   ↓
Développement
   ↓
Tests
   ↓
Validation
   ↓
Amélioration


Cette approche permet d'ajouter et de tester progressivement les fonctionnalités.

Répartition générale

Ivan Tiomo

Interface Web
Application mobile Flutter
Intégration frontend/API
Tests et validation côté client

Risnel Talla

Backend PHP
Base de données MySQL
API REST
Gestion des données et logique serveur

Le développement est réalisé de manière collaborative avec Git et GitHub.
