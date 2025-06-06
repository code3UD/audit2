<?php
/* Copyright (C) 2024 Up Digit Agency
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       install_modern_features.php
 * \ingroup    auditdigital
 * \brief      Installation script for modern audit digital features
 */

// Load Dolibarr environment
$res = 0;

$paths_to_try = array(
    "../main.inc.php",
    "../../main.inc.php",
    "../../../main.inc.php",
    "../../../../main.inc.php",
    "../../../../../main.inc.php"
);

foreach ($paths_to_try as $path) {
    if (!$res && file_exists($path)) {
        $res = @include $path;
        if ($res) break;
    }
}

if (!$res) {
    die('Error: Cannot load Dolibarr main.inc.php');
}

// Security check
if (!$user->admin) {
    accessforbidden('Admin rights required');
}

// Get parameters
$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

if ($action == 'install') {
    $error = 0;
    $messages = array();
    
    // 1. Create comments table
    $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."auditdigital_comments (
        rowid           integer AUTO_INCREMENT PRIMARY KEY,
        fk_audit        integer NOT NULL,
        fk_question     integer,
        question_name   varchar(255),
        comment         text,
        attachments     text,
        fk_user         integer,
        date_creation   datetime NOT NULL,
        date_modification datetime,
        entity          integer DEFAULT 1 NOT NULL,
        import_key      varchar(14),
        
        INDEX idx_auditdigital_comments_fk_audit (fk_audit),
        INDEX idx_auditdigital_comments_fk_question (fk_question),
        INDEX idx_auditdigital_comments_fk_user (fk_user),
        INDEX idx_auditdigital_comments_entity (entity)
    ) ENGINE=innodb";
    
    $result = $db->query($sql);
    if ($result) {
        $messages[] = "✅ Table des commentaires créée avec succès";
    } else {
        $error++;
        $messages[] = "❌ Erreur lors de la création de la table des commentaires: " . $db->lasterror();
    }
    
    // 2. Add new fields to audit table if they don't exist
    $fields_to_add = array(
        'structure_type' => "varchar(50) DEFAULT 'tpe_pme'",
        'sector' => "varchar(100)",
        'employees_count' => "varchar(20)",
        'it_budget' => "varchar(20)",
        'digital_score' => "integer DEFAULT 0",
        'security_score' => "integer DEFAULT 0",
        'cloud_score' => "integer DEFAULT 0",
        'automation_score' => "integer DEFAULT 0",
        'global_score' => "integer DEFAULT 0",
        'roi_analysis' => "text",
        'roadmap_data' => "text",
        'executive_summary' => "text"
    );
    
    foreach ($fields_to_add as $field => $definition) {
        // Check if field exists
        $sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."auditdigital_audit LIKE '$field'";
        $result = $db->query($sql);
        
        if ($db->num_rows($result) == 0) {
            // Field doesn't exist, add it
            $sql = "ALTER TABLE ".MAIN_DB_PREFIX."auditdigital_audit ADD COLUMN $field $definition";
            $result = $db->query($sql);
            
            if ($result) {
                $messages[] = "✅ Champ '$field' ajouté à la table audit";
            } else {
                $error++;
                $messages[] = "❌ Erreur lors de l'ajout du champ '$field': " . $db->lasterror();
            }
        } else {
            $messages[] = "ℹ️ Champ '$field' déjà présent";
        }
    }
    
    // 3. Create directories for file uploads
    $upload_dirs = array(
        $conf->auditdigital->multidir_output[$conf->entity].'/comments',
        $conf->auditdigital->multidir_output[$conf->entity].'/exports',
        $conf->auditdigital->multidir_output[$conf->entity].'/charts'
    );
    
    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) {
            if (dol_mkdir($dir) >= 0) {
                $messages[] = "✅ Répertoire créé: $dir";
            } else {
                $error++;
                $messages[] = "❌ Erreur lors de la création du répertoire: $dir";
            }
        } else {
            $messages[] = "ℹ️ Répertoire déjà présent: $dir";
        }
    }
    
    // 4. Set configuration parameters
    $config_params = array(
        'AUDITDIGITAL_MODERN_UI_ENABLED' => '1',
        'AUDITDIGITAL_COMMENTS_ENABLED' => '1',
        'AUDITDIGITAL_CHARTS_ENABLED' => '1',
        'AUDITDIGITAL_ROI_CALCULATION_ENABLED' => '1',
        'AUDITDIGITAL_EXPORT_FORMATS' => 'json,csv,xml',
        'AUDITDIGITAL_AUTO_SAVE_INTERVAL' => '30',
        'AUDITDIGITAL_MAX_ATTACHMENT_SIZE' => '10485760', // 10MB
        'AUDITDIGITAL_ALLOWED_EXTENSIONS' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif'
    );
    
    foreach ($config_params as $param => $value) {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name, value, type, visible, entity) VALUES ";
        $sql .= "('".$db->escape($param)."', '".$db->escape($value)."', 'chaine', 0, ".$conf->entity.")";
        $sql .= " ON DUPLICATE KEY UPDATE value = '".$db->escape($value)."'";
        
        $result = $db->query($sql);
        if ($result) {
            $messages[] = "✅ Paramètre configuré: $param = $value";
        } else {
            $error++;
            $messages[] = "❌ Erreur lors de la configuration de $param: " . $db->lasterror();
        }
    }
    
    // 5. Insert sample data for demonstration
    if (GETPOST('install_demo_data', 'int')) {
        // Create a demo company if it doesn't exist
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE nom = 'Entreprise Démo AuditDigital' AND entity = ".$conf->entity;
        $result = $db->query($sql);
        
        if ($db->num_rows($result) == 0) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe (nom, address, zip, town, country_id, client, fournisseur, entity, date_creation) VALUES ";
            $sql .= "('Entreprise Démo AuditDigital', '123 Rue de la Technologie', '75001', 'Paris', 1, 1, 0, ".$conf->entity.", NOW())";
            
            $result = $db->query($sql);
            if ($result) {
                $demo_company_id = $db->last_insert_id(MAIN_DB_PREFIX."societe");
                $messages[] = "✅ Société de démonstration créée (ID: $demo_company_id)";
                
                // Create a demo audit
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."auditdigital_audit (ref, label, fk_soc, structure_type, sector, employees_count, it_budget, ";
                $sql .= "digital_score, security_score, cloud_score, automation_score, global_score, ";
                $sql .= "date_creation, fk_user_author, entity, status) VALUES ";
                $sql .= "('AUDIT-DEMO-".date('Ymd')."', 'Audit de Démonstration', $demo_company_id, 'tpe_pme', 'services', '11-50', '15k-50k', ";
                $sql .= "75, 45, 60, 35, 54, NOW(), ".$user->id.", ".$conf->entity.", 1)";
                
                $result = $db->query($sql);
                if ($result) {
                    $demo_audit_id = $db->last_insert_id(MAIN_DB_PREFIX."auditdigital_audit");
                    $messages[] = "✅ Audit de démonstration créé (ID: $demo_audit_id)";
                    
                    // Add demo comments
                    $demo_comments = array(
                        array('question' => 'digital_tools', 'comment' => 'L\'entreprise utilise déjà plusieurs outils digitaux mais pourrait améliorer l\'intégration entre eux.'),
                        array('question' => 'security_measures', 'comment' => 'La sécurité nécessite des améliorations urgentes, notamment au niveau des sauvegardes.'),
                        array('question' => 'cloud_usage', 'comment' => 'Migration vers le cloud recommandée pour améliorer la flexibilité et réduire les coûts.')
                    );
                    
                    foreach ($demo_comments as $comment_data) {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."auditdigital_comments (fk_audit, question_name, comment, fk_user, date_creation, entity) VALUES ";
                        $sql .= "($demo_audit_id, '".$db->escape($comment_data['question'])."', '".$db->escape($comment_data['comment'])."', ".$user->id.", NOW(), ".$conf->entity.")";
                        $db->query($sql);
                    }
                    
                    $messages[] = "✅ Commentaires de démonstration ajoutés";
                }
            }
        } else {
            $messages[] = "ℹ️ Société de démonstration déjà présente";
        }
    }
    
    // Summary
    if ($error == 0) {
        $messages[] = "🎉 Installation des fonctionnalités modernes terminée avec succès !";
    } else {
        $messages[] = "⚠️ Installation terminée avec $error erreur(s)";
    }
}

/*
 * View
 */

$title = 'Installation des Fonctionnalités Modernes - AuditDigital';
llxHeader('', $title, '', '', 0, 0, array('/custom/auditdigital/css/auditdigital-modern.css'));

?>

<style>
.install-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.install-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 30px;
}

.feature-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.feature-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #0066cc;
}

.install-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.messages {
    background: #e9ecef;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    max-height: 400px;
    overflow-y: auto;
}

.message {
    margin: 5px 0;
    padding: 5px 0;
    border-bottom: 1px solid #dee2e6;
}

.checkbox-group {
    margin: 15px 0;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}
</style>

<div class="install-container">
    <div class="install-header">
        <h1>🚀 Installation des Fonctionnalités Modernes</h1>
        <p>Mise à niveau du module AuditDigital vers l'interface nouvelle génération</p>
    </div>
    
    <?php if ($action != 'install') { ?>
    
    <h2>📋 Fonctionnalités qui seront installées :</h2>
    
    <div class="feature-list">
        <div class="feature-card">
            <h3>🎨 Interface Moderne</h3>
            <ul>
                <li>Cards cliquables avec animations</li>
                <li>Stepper visuel interactif</li>
                <li>Design glassmorphism</li>
                <li>Interface responsive</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <h3>💬 Système de Commentaires</h3>
            <ul>
                <li>Commentaires par question</li>
                <li>Pièces jointes multiples</li>
                <li>Historique des modifications</li>
                <li>Gestion des permissions</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <h3>📊 Graphiques Interactifs</h3>
            <ul>
                <li>Chart.js intégré</li>
                <li>Graphiques radar et barres</li>
                <li>Visualisation temps réel</li>
                <li>Export des graphiques</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <h3>🎯 Fonctionnalités Métier</h3>
            <ul>
                <li>Calcul ROI automatique</li>
                <li>Roadmap d'implémentation</li>
                <li>Synthèse exécutive</li>
                <li>Export multi-format</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <h3>📄 PDF Moderne</h3>
            <ul>
                <li>Design professionnel</li>
                <li>Graphiques intégrés</li>
                <li>Jauges visuelles</li>
                <li>Mise en page optimisée</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <h3>⚙️ Améliorations Techniques</h3>
            <ul>
                <li>Auto-save intelligent</li>
                <li>Validation côté client</li>
                <li>Gestion d'erreurs avancée</li>
                <li>Performance optimisée</li>
            </ul>
        </div>
    </div>
    
    <div class="install-form">
        <h3>🔧 Options d'Installation</h3>
        
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="install">
            
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="install_demo_data" value="1" checked>
                    <span>Installer les données de démonstration (société et audit d'exemple)</span>
                </label>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn-modern">
                    <i class="fas fa-download"></i> Lancer l'Installation
                </button>
                <a href="/custom/auditdigital/demo_modern.php" class="btn-modern btn-secondary-modern">
                    <i class="fas fa-eye"></i> Voir la Démonstration
                </a>
            </div>
        </form>
    </div>
    
    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <h4>⚠️ Prérequis :</h4>
        <ul>
            <li>Dolibarr 13.0+ recommandé</li>
            <li>PHP 7.4+ requis</li>
            <li>Module AuditDigital activé</li>
            <li>Droits administrateur</li>
        </ul>
    </div>
    
    <?php } else { ?>
    
    <h2>📋 Résultats de l'Installation</h2>
    
    <div class="messages">
        <?php foreach ($messages as $message) { ?>
        <div class="message"><?php echo $message; ?></div>
        <?php } ?>
    </div>
    
    <div style="margin-top: 30px; text-align: center;">
        <a href="/custom/auditdigital/demo_modern.php" class="btn-modern">
            <i class="fas fa-rocket"></i> Découvrir les Nouvelles Fonctionnalités
        </a>
        <a href="/custom/auditdigital/wizard/modern.php" class="btn-modern btn-success-modern">
            <i class="fas fa-magic"></i> Tester le Wizard Moderne
        </a>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn-modern btn-secondary-modern">
            <i class="fas fa-redo"></i> Relancer l'Installation
        </a>
    </div>
    
    <?php } ?>
    
    <div style="margin-top: 40px; padding: 20px; background: #e7f3ff; border-radius: 8px;">
        <h4>📚 Documentation :</h4>
        <ul>
            <li><strong>Guide Utilisateur :</strong> /custom/auditdigital/docs/GUIDE_UTILISATEUR.md</li>
            <li><strong>Documentation Technique :</strong> /custom/auditdigital/docs/DOCUMENTATION_TECHNIQUE.md</li>
            <li><strong>API Reference :</strong> Classes Audit, Questionnaire avec nouvelles méthodes</li>
            <li><strong>Support :</strong> contact@updigit.fr</li>
        </ul>
    </div>
</div>

<?php

llxFooter();

?>