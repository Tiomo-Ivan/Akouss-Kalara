-- =============================================================================
-- DONNÉES DE TEST STANDARDISÉES — à exécuter APRÈS schema.sql
-- Toute l'équipe (Risnel, Ivan) doit importer ce fichier pour avoir
-- exactement les mêmes catégories et livres de démonstration.
--
-- Le compte "vendeur de démo" n'est PAS créé ici directement (un mot de
-- passe haché correctement ne peut être généré que par PHP via
-- password_hash(), pas écrit à la main dans du SQL). Suivez plutôt les
-- étapes du LISEZ-MOI.txt : inscrivez-vous normalement via le site, puis
-- exécutez la petite requête d'activation fournie plus bas.
--
-- Import via phpMyAdmin (onglet Importer) ou :
--   mysql -u root -p akouss_kalara < donnees_test.sql
-- =============================================================================
USE akouss_kalara;

INSERT INTO categories (nom, slug) VALUES
('Romans', 'romans'),
('Science', 'science'),
('Développement personnel', 'developpement-personnel'),
('Littérature générale', 'litterature-generale');

INSERT INTO livres (titre, auteur, categorie_id, description) VALUES
('Le Petit Prince', 'Antoine de Saint-Exupéry', (SELECT id FROM categories WHERE slug='litterature-generale'), 'Un conte poétique et philosophique.'),
('L''Alchimiste', 'Paulo Coelho', (SELECT id FROM categories WHERE slug='romans'), 'Un roman initiatique sur la quête de son destin.'),
('Sapiens', 'Yuval Noah Harari', (SELECT id FROM categories WHERE slug='science'), 'Une brève histoire de l''humanité.'),
('Atomic Habits', 'James Clear', (SELECT id FROM categories WHERE slug='developpement-personnel'), 'Petites habitudes, grands résultats.');

-- Les OFFRES ne sont pas créées ici : elles nécessitent un vendeur réel
-- (voir LISEZ-MOI.txt, étape "Créer et activer un compte vendeur de test").
-- Une fois votre compte vendeur activé, publiez ces 4 livres vous-même
-- depuis la page "Publier une offre" — 2 minutes, et garantit des données
-- cohérentes avec un vrai parcours utilisateur testé.
