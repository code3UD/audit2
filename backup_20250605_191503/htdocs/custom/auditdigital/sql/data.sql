-- Copyright (C) 2024 Up Digit Agency
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- Données initiales pour la bibliothèque de solutions

-- Solutions de maturité numérique
INSERT INTO llx_auditdigital_solutions (ref, label, category, sub_category, solution_type, target_audience, price_range, implementation_time, priority, roi_percentage, roi_months, json_features, json_benefits, json_requirements, description, date_creation) VALUES
('SOL-WEB-001', 'Création site web vitrine moderne', 'maturite_numerique', 'presence_digitale', 'website', 'tpe,pme,collectivite', '5k', 15, 10, 25, 12, 
'["Design responsive mobile-first", "SEO optimisé", "Formulaire de contact sécurisé", "Intégration Google Analytics", "Certificat SSL inclus"]',
'["Visibilité accrue sur internet", "Génération de leads qualifiés", "Image professionnelle renforcée", "Accessible 24/7", "Réduction des appels entrants"]',
'["Nom de domaine", "Contenu texte et images", "Logo haute résolution"]',
'Site web vitrine professionnel avec design moderne et optimisé pour le référencement naturel', NOW()),

('SOL-CLOUD-002', 'Migration Google Workspace', 'cloud', 'collaboration', 'cloud_service', 'tpe,pme', '10k', 20, 9, 30, 18,
'["Migration emails existants", "Formation utilisateurs", "Configuration sécurité avancée", "Intégration calendriers partagés", "Stockage cloud 30GB/utilisateur"]',
'["Collaboration temps réel", "Travail à distance facilité", "Réduction coûts infrastructure", "Sauvegardes automatiques", "Support Google inclus"]',
'["Liste des utilisateurs", "Accès boîtes mail actuelles", "Connexion internet fiable"]',
'Migration complète vers Google Workspace avec formation et configuration sécurisée', NOW()),

('SOL-CYBER-003', 'Firewall UTM nouvelle génération', 'cybersecurite', 'protection', 'security_hardware', 'pme,collectivite', '15k', 10, 10, 0, 0,
'["Protection anti-ransomware", "Filtrage web intelligent", "VPN site-to-site", "Inspection SSL/TLS", "Reporting détaillé"]',
'["Protection contre 99% des menaces", "Conformité réglementaire", "Réduction risques data breach", "Monitoring temps réel", "Support 24/7"]',
'["Audit réseau existant", "Plan d\'adressage IP", "Fenêtre de maintenance"]',
'Firewall UTM professionnel pour protection avancée contre les cybermenaces', NOW()),

('SOL-AUTO-004', 'Automatisation workflows Zapier', 'automatisation', 'processus', 'automation_tool', 'tpe,pme', '5k', 10, 8, 40, 6,
'["Connexion 5000+ applications", "Workflows personnalisés", "Déclencheurs multiples", "Formation équipe incluse", "Support premium 3 mois"]',
'["Gain de temps 5h/semaine", "Réduction erreurs manuelles", "Processus standardisés", "Données synchronisées", "Scalabilité illimitée"]',
'["Liste des processus à automatiser", "Accès aux applications", "Référent projet interne"]',
'Automatisation des processus métier avec Zapier pour gagner en efficacité', NOW()),

('SOL-COLL-005', 'Portail citoyen interactif', 'maturite_numerique', 'service_public', 'web_portal', 'collectivite', '20k', 30, 10, 20, 24,
'["Démarches en ligne", "Paiement sécurisé", "Espace personnel citoyen", "Notifications push", "Accessibilité RGAA"]',
'["Réduction affluence guichet", "Service 24/7", "Satisfaction citoyens", "Traçabilité demandes", "Économies papier"]',
'["Liste des démarches", "API état civil", "Formation agents"]',
'Portail citoyen moderne pour dématérialiser les démarches administratives', NOW());