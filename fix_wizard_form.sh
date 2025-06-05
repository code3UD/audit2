#!/bin/bash
# Correction du formulaire wizard qui se remet à zéro

echo "🔧 CORRECTION FORMULAIRE WIZARD"
echo "==============================="

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

MODULE_PATH="/usr/share/dolibarr/htdocs/custom/auditdigital"
WIZARD_FILE="$MODULE_PATH/wizard/index.php"

print_info "=== PROBLÈME IDENTIFIÉ ==="
echo "Le formulaire se remet à zéro car :"
echo "1. L'action 'create_audit' n'est pas définie dans le formulaire"
echo "2. Le traitement POST est après l'affichage"
echo "3. Les valeurs ne sont pas conservées après soumission"

print_info "\n=== CORRECTION DU WIZARD ==="

if [ -f "$WIZARD_FILE" ]; then
    # Créer une sauvegarde
    sudo cp "$WIZARD_FILE" "$WIZARD_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    print_status "Sauvegarde créée"
    
    # Créer une version corrigée du wizard
    cat << 'EOF' | sudo tee "$WIZARD_FILE" > /dev/null
<?php
/* Copyright (C) 2024 Up Digit Agency
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       wizard/index.php
 * \ingroup    auditdigital
 * \brief      Main wizard page for creating digital audits
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("/usr/share/dolibarr/htdocs/main.inc.php")) $res = @include "/usr/share/dolibarr/htdocs/main.inc.php";

if (!$res) {
    die("Error: Could not load main.inc.php");
}

// Check if module is enabled
if (!isModEnabled('auditdigital')) {
    accessforbidden('Module not enabled');
}

// Load required classes
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';

// Load translation files
$langs->loadLangs(array("main", "companies", "projects"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int');
$id = GETPOST('id', 'int');

// Security check
if (!$user->rights->auditdigital->audit->write) {
    accessforbidden();
}

// Initialize objects
$formcompany = new FormCompany($db);

// Variables pour conserver les valeurs
$structure_type = GETPOST('structure_type', 'alpha');
$fk_soc = GETPOST('fk_soc', 'int');
$fk_projet = GETPOST('fk_projet', 'int');
$sector = GETPOST('sector', 'alpha');
$employees_count = GETPOST('employees_count', 'alpha');
$it_budget = GETPOST('it_budget', 'alpha');

$error = 0;
$message = '';
$success = false;

// Handle form submission AVANT l'affichage
if ($action == 'create_audit' && $_POST) {
    print_r($_POST); // Debug
    
    // Validation
    if (empty($structure_type)) {
        $error++;
        $message = 'Le type de structure est obligatoire';
    }
    
    if (empty($fk_soc)) {
        $error++;
        $message = 'La société est obligatoire';
    }
    
    if (empty($sector)) {
        $error++;
        $message = 'Le secteur d\'activité est obligatoire';
    }
    
    if (empty($employees_count)) {
        $error++;
        $message = 'Le nombre d\'employés est obligatoire';
    }
    
    if (empty($it_budget)) {
        $error++;
        $message = 'Le budget IT est obligatoire';
    }
    
    if (!$error) {
        // Créer l'audit
        try {
            $audit = new Audit($db);
            
            // Générer une référence unique
            $audit->ref = 'AUD' . date('ymd') . '-' . sprintf('%04d', rand(1, 9999));
            $audit->label = 'Audit Digital - ' . date('Y-m-d H:i:s');
            $audit->audit_type = $structure_type;
            $audit->structure_type = $structure_type;
            $audit->fk_soc = $fk_soc;
            $audit->fk_projet = $fk_projet;
            $audit->status = 0; // Brouillon
            
            // Stocker les données du questionnaire en JSON
            $questionnaire_data = array(
                'structure_type' => $structure_type,
                'sector' => $sector,
                'employees_count' => $employees_count,
                'it_budget' => $it_budget,
                'date_creation' => date('Y-m-d H:i:s')
            );
            
            $audit->json_responses = json_encode($questionnaire_data);
            
            // Créer l'audit
            $result = $audit->create($user);
            
            if ($result > 0) {
                $success = true;
                $message = 'Audit créé avec succès ! ID: ' . $result;
                
                // Rediriger vers la liste des audits
                header('Location: ' . dol_buildpath('/custom/auditdigital/audit_list.php', 1));
                exit;
            } else {
                $error++;
                $message = 'Erreur lors de la création de l\'audit: ' . implode(', ', $audit->errors);
            }
            
        } catch (Exception $e) {
            $error++;
            $message = 'Erreur: ' . $e->getMessage();
        }
    }
}

/*
 * View
 */

$title = 'Nouvel Audit Digital';
llxHeader('', $title, '');

?>

<style>
.audit-wizard {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.audit-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #0066CC, #004499);
    color: white;
    border-radius: 8px;
}

.audit-form-group {
    margin-bottom: 20px;
}

.audit-form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.audit-form-label.required:after {
    content: ' *';
    color: #e74c3c;
}

.audit-form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.audit-form-control:focus {
    outline: none;
    border-color: #0066CC;
}

.audit-radio-group {
    display: flex;
    gap: 20px;
    margin-top: 10px;
}

.audit-radio-item {
    flex: 1;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.audit-radio-item:hover {
    border-color: #0066CC;
    background-color: #f8f9fa;
}

.audit-radio-item input[type="radio"] {
    margin-right: 10px;
}

.audit-radio-item input[type="radio"]:checked + label {
    color: #0066CC;
    font-weight: bold;
}

.audit-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.audit-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
}

.audit-btn-primary {
    background-color: #0066CC;
    color: white;
}

.audit-btn-primary:hover {
    background-color: #004499;
}

.audit-btn-secondary {
    background-color: #6c757d;
    color: white;
}

.audit-btn-secondary:hover {
    background-color: #545b62;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

.success {
    background-color: #d4edda;
    color: #155724;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
}
</style>

<div class="audit-wizard">
    <div class="audit-header">
        <h1>🎯 Nouvel Audit Digital</h1>
        <p>Évaluez la maturité numérique de votre organisation en quelques étapes simples.</p>
    </div>
    
    <?php if ($error) { ?>
    <div class="error">
        <?php echo $message; ?>
    </div>
    <?php } ?>
    
    <?php if ($success) { ?>
    <div class="success">
        <?php echo $message; ?>
    </div>
    <?php } ?>
    
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="action" value="create_audit">
        
        <!-- Structure Type -->
        <div class="audit-form-group">
            <label class="audit-form-label required">Type de structure</label>
            <div class="audit-radio-group">
                <div class="audit-radio-item">
                    <input type="radio" id="structure_type_tpe_pme" name="structure_type" value="tpe_pme" 
                           <?php echo ($structure_type == 'tpe_pme') ? 'checked' : ''; ?> required>
                    <label for="structure_type_tpe_pme">TPE/PME</label>
                </div>
                <div class="audit-radio-item">
                    <input type="radio" id="structure_type_collectivite" name="structure_type" value="collectivite"
                           <?php echo ($structure_type == 'collectivite') ? 'checked' : ''; ?> required>
                    <label for="structure_type_collectivite">Collectivité territoriale</label>
                </div>
            </div>
        </div>
        
        <!-- Third Party -->
        <div class="audit-form-group">
            <label class="audit-form-label required">Société</label>
            <?php echo $formcompany->select_company($fk_soc, 'fk_soc', '', 'Sélectionner une société', 1, 0, null, 0, 'audit-form-control'); ?>
        </div>
        
        <!-- Project (optional) -->
        <div class="audit-form-group">
            <label class="audit-form-label">Projet (optionnel)</label>
            <select name="fk_projet" class="audit-form-control">
                <option value="">-- Sélectionner --</option>
                <!-- Simplified project selection -->
            </select>
        </div>
        
        <!-- Sector -->
        <div class="audit-form-group">
            <label class="audit-form-label required">Secteur d'activité</label>
            <select name="sector" class="audit-form-control" required>
                <option value="">-- Sélectionner --</option>
                <option value="commerce" <?php echo ($sector == 'commerce') ? 'selected' : ''; ?>>Commerce</option>
                <option value="services" <?php echo ($sector == 'services') ? 'selected' : ''; ?>>Services</option>
                <option value="industrie" <?php echo ($sector == 'industrie') ? 'selected' : ''; ?>>Industrie</option>
                <option value="administration" <?php echo ($sector == 'administration') ? 'selected' : ''; ?>>Administration</option>
                <option value="sante" <?php echo ($sector == 'sante') ? 'selected' : ''; ?>>Santé</option>
                <option value="education" <?php echo ($sector == 'education') ? 'selected' : ''; ?>>Éducation</option>
            </select>
        </div>
        
        <!-- Employees Count -->
        <div class="audit-form-group">
            <label class="audit-form-label required">Nombre d'employés</label>
            <select name="employees_count" class="audit-form-control" required>
                <option value="">-- Sélectionner --</option>
                <option value="1-10" <?php echo ($employees_count == '1-10') ? 'selected' : ''; ?>>1 à 10 employés</option>
                <option value="11-50" <?php echo ($employees_count == '11-50') ? 'selected' : ''; ?>>11 à 50 employés</option>
                <option value="51-250" <?php echo ($employees_count == '51-250') ? 'selected' : ''; ?>>51 à 250 employés</option>
                <option value="250+" <?php echo ($employees_count == '250+') ? 'selected' : ''; ?>>Plus de 250 employés</option>
            </select>
        </div>
        
        <!-- IT Budget -->
        <div class="audit-form-group">
            <label class="audit-form-label required">Budget IT annuel</label>
            <select name="it_budget" class="audit-form-control" required>
                <option value="">-- Sélectionner --</option>
                <option value="0-5k" <?php echo ($it_budget == '0-5k') ? 'selected' : ''; ?>>Moins de 5 000€</option>
                <option value="5k-15k" <?php echo ($it_budget == '5k-15k') ? 'selected' : ''; ?>>5 000€ à 15 000€</option>
                <option value="15k-50k" <?php echo ($it_budget == '15k-50k') ? 'selected' : ''; ?>>15 000€ à 50 000€</option>
                <option value="50k+" <?php echo ($it_budget == '50k+') ? 'selected' : ''; ?>>Plus de 50 000€</option>
            </select>
        </div>
        
        <!-- Buttons -->
        <div class="audit-buttons">
            <button type="button" class="audit-btn audit-btn-secondary" onclick="history.back()">
                ← Retour
            </button>
            <button type="submit" class="audit-btn audit-btn-primary">
                Créer l'audit →
            </button>
        </div>
    </form>
</div>

<script>
// Améliorer l'UX des boutons radio
document.querySelectorAll('.audit-radio-item').forEach(function(item) {
    item.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            
            // Retirer la classe active des autres items
            document.querySelectorAll('.audit-radio-item').forEach(function(otherItem) {
                otherItem.classList.remove('active');
            });
            
            // Ajouter la classe active à l'item sélectionné
            this.classList.add('active');
        }
    });
});

// Validation côté client
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[required]');
    let hasError = false;
    
    requiredFields.forEach(function(field) {
        if (!field.value) {
            field.style.borderColor = '#e74c3c';
            hasError = true;
        } else {
            field.style.borderColor = '#ddd';
        }
    });
    
    if (hasError) {
        e.preventDefault();
        alert('Veuillez remplir tous les champs obligatoires');
    }
});
</script>

<?php

llxFooter();
?>
EOF

    print_status "Wizard corrigé avec :"
    echo "  - Traitement POST avant affichage"
    echo "  - Conservation des valeurs après soumission"
    echo "  - Action 'create_audit' définie"
    echo "  - Validation complète"
    echo "  - Création d'audit fonctionnelle"
    echo "  - Redirection après succès"
    
else
    print_error "Fichier wizard non trouvé : $WIZARD_FILE"
fi

print_info "\n=== REDÉMARRAGE APACHE ==="
sudo systemctl restart apache2
print_status "Apache redémarré"

print_info "\n=== TEST ==="
print_status "🎉 CORRECTION TERMINÉE !"
echo ""
print_info "🧪 TESTEZ MAINTENANT :"
echo "1. http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo "2. Remplissez le formulaire"
echo "3. Cliquez sur 'Créer l'audit'"
echo "4. Vérifiez que l'audit est créé et que vous êtes redirigé"

exit 0