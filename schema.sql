-- =============================================================================
-- AKOUSS KALARA — SCHÉMA MYSQL (pivot PHP, urgence délai dimanche)
-- Simplifié par rapport au schéma PostgreSQL initial :
-- - IDs en INT AUTO_INCREMENT au lieu d'UUID (plus rapide à manipuler en PHP)
-- - Structure conceptuelle identique (Livre/Offre séparés, prix figé, etc.)
-- À importer via phpMyAdmin (XAMPP) ou : mysql -u root -p akouss_kalara < schema.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS akouss_kalara CHARACTER SET utf8mb4;
USE akouss_kalara;

-- --- Comptes ---

CREATE TABLE utilisateurs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255) NOT NULL,      -- haché avec password_hash()
    nom_complet     VARCHAR(255) NOT NULL,
    telephone       VARCHAR(20),
    est_actif       TINYINT(1) NOT NULL DEFAULT 0,
    est_admin       TINYINT(1) NOT NULL DEFAULT 0,
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Jetons de session (remplace un vrai JWT, simple et rapide à mettre en oeuvre)
CREATE TABLE sessions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    jeton           VARCHAR(255) NOT NULL UNIQUE,
    date_creation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATETIME NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Jetons de vérification email / activation vendeur
CREATE TABLE jetons_verification (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    jeton           VARCHAR(255) NOT NULL UNIQUE,
    type            ENUM('activation_compte', 'activation_vendeur') NOT NULL DEFAULT 'activation_compte',
    date_creation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    est_utilise     TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE profils_vendeur (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id      INT NOT NULL UNIQUE,
    nom_boutique        VARCHAR(255) NOT NULL,
    description_boutique TEXT,
    statut              ENUM('non_verifie', 'actif', 'suspendu') NOT NULL DEFAULT 'non_verifie',
    numero_mobile_money VARCHAR(20),
    operateur_mobile_money ENUM('mtn_momo', 'orange_money'),
    date_creation       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_activation     DATETIME,
    motif_suspension    TEXT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT
);

-- --- Catalogue ---

CREATE TABLE categories (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nom     VARCHAR(150) NOT NULL UNIQUE,
    slug    VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE livres (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    titre               VARCHAR(500) NOT NULL,
    auteur              VARCHAR(255) NOT NULL,
    categorie_id        INT NOT NULL,
    description         TEXT,
    image_couverture    VARCHAR(500),
    date_creation       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE RESTRICT
);

CREATE TABLE offres (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    livre_id            INT NOT NULL,
    vendeur_id          INT NOT NULL,
    type                ENUM('physique', 'numerique') NOT NULL,
    prix                DECIMAL(10,2) NOT NULL,
    quantite_stock      INT,
    etat_article        ENUM('neuf', 'occasion'),
    fichier_numerique   VARCHAR(500),
    statut_droits       ENUM('domaine_public', 'droits_detenus'),
    justificatif_droits VARCHAR(500),
    statut_moderation   ENUM('en_attente', 'publiee', 'refusee') NOT NULL DEFAULT 'en_attente',
    date_creation       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE RESTRICT,
    FOREIGN KEY (vendeur_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT
);

-- --- Achats ---

CREATE TABLE paniers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL UNIQUE,
    date_maj        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE lignes_panier (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    panier_id   INT NOT NULL,
    offre_id    INT NOT NULL,
    quantite    INT NOT NULL DEFAULT 1,
    FOREIGN KEY (panier_id) REFERENCES paniers(id) ON DELETE CASCADE,
    FOREIGN KEY (offre_id) REFERENCES offres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ligne (panier_id, offre_id)
);

CREATE TABLE commandes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    statut          ENUM('en_attente', 'payee', 'expediee', 'livree', 'annulee') NOT NULL DEFAULT 'en_attente',
    montant_total   DECIMAL(10,2) NOT NULL,
    date_creation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT
);

CREATE TABLE lignes_commande (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    commande_id         INT NOT NULL,
    offre_id            INT NOT NULL,
    quantite            INT NOT NULL,
    prix_unitaire_fige  DECIMAL(10,2) NOT NULL,
    mode_livraison      ENUM('domicile', 'retrait', 'telechargement'),
    statut_ligne        ENUM('preparation', 'expediee', 'livree', 'disponible_telechargement') NOT NULL DEFAULT 'preparation',
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE RESTRICT,
    FOREIGN KEY (offre_id) REFERENCES offres(id) ON DELETE RESTRICT
);

-- --- Paiement ---

CREATE TABLE paiements (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    commande_id             INT NOT NULL UNIQUE,
    fournisseur             ENUM('mtn_momo', 'orange_money') NOT NULL,
    telephone_paiement      VARCHAR(20) NOT NULL,
    montant                 DECIMAL(10,2) NOT NULL,
    statut                  ENUM('en_attente', 'succes', 'echec') NOT NULL DEFAULT 'en_attente',
    reference_transaction   VARCHAR(255),   -- référence renvoyée par l'API MTN/Orange
    date_creation           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE RESTRICT
);
