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
 * \file        class/questionnaire.class.php
 * \ingroup     auditdigital
 * \brief       This file contains the Questionnaire class for managing audit questionnaires
 */

/**
 * Class for Questionnaire management
 */
class Questionnaire
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var array Questionnaire structure
     */
    public $questionnaire;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
        $this->initQuestionnaire();
    }

    /**
     * Initialize questionnaire structure
     *
     * @return void
     */
    private function initQuestionnaire()
    {
        $this->questionnaire = array(
            'step1_general' => array(
                'title' => 'Informations générales',
                'description' => 'Informations de base sur votre structure',
                'questions' => array(
                    'structure_type' => array(
                        'type' => 'radio',
                        'label' => 'Type de structure',
                        'required' => true,
                        'options' => array(
                            'tpe_pme' => 'TPE/PME',
                            'collectivite' => 'Collectivité territoriale'
                        )
                    ),
                    'sector' => array(
                        'type' => 'select',
                        'label' => 'Secteur d\'activité',
                        'required' => true,
                        'options' => array(
                            'commerce' => 'Commerce',
                            'industrie' => 'Industrie',
                            'services' => 'Services',
                            'sante' => 'Santé',
                            'education' => 'Éducation',
                            'administration' => 'Administration publique',
                            'autre' => 'Autre'
                        )
                    ),
                    'employees_count' => array(
                        'type' => 'select',
                        'label' => 'Nombre d\'employés',
                        'required' => true,
                        'options' => array(
                            '1-10' => '1 à 10',
                            '11-50' => '11 à 50',
                            '51-250' => '51 à 250',
                            '251-500' => '251 à 500',
                            '500+' => 'Plus de 500'
                        )
                    ),
                    'it_budget' => array(
                        'type' => 'select',
                        'label' => 'Budget IT annuel',
                        'required' => true,
                        'options' => array(
                            '0-5k' => 'Moins de 5 000€',
                            '5k-15k' => '5 000€ à 15 000€',
                            '15k-50k' => '15 000€ à 50 000€',
                            '50k-100k' => '50 000€ à 100 000€',
                            '100k+' => 'Plus de 100 000€'
                        )
                    ),
                    'main_objectives' => array(
                        'type' => 'checkbox',
                        'label' => 'Objectifs principaux',
                        'required' => true,
                        'options' => array(
                            'efficiency' => 'Améliorer l\'efficacité',
                            'security' => 'Renforcer la sécurité',
                            'costs' => 'Réduire les coûts',
                            'innovation' => 'Favoriser l\'innovation',
                            'compliance' => 'Assurer la conformité'
                        )
                    )
                )
            ),
            'step2_maturite' => array(
                'title' => 'Maturité numérique',
                'description' => 'Évaluation de votre niveau de digitalisation',
                'questions' => array(
                    'website_presence' => array(
                        'type' => 'radio',
                        'label' => 'Présence web',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Aucun site web',
                            1 => 'Site vitrine basique',
                            2 => 'Site moderne et responsive'
                        )
                    ),
                    'social_media' => array(
                        'type' => 'radio',
                        'label' => 'Présence sur les réseaux sociaux',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Aucune présence',
                            1 => 'Présence basique',
                            2 => 'Stratégie active et engagée'
                        )
                    ),
                    'collaborative_tools' => array(
                        'type' => 'radio',
                        'label' => 'Outils collaboratifs utilisés',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Email uniquement',
                            1 => 'Quelques outils (chat, partage fichiers)',
                            2 => 'Suite complète (Office 365, Google Workspace)'
                        )
                    ),
                    'process_digitalization' => array(
                        'type' => 'radio',
                        'label' => 'Niveau de digitalisation des processus',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Processus majoritairement manuels',
                            1 => 'Quelques processus digitalisés',
                            2 => 'Majorité des processus digitalisés'
                        )
                    ),
                    'team_training' => array(
                        'type' => 'radio',
                        'label' => 'Formation des équipes au numérique',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Aucune formation',
                            1 => 'Formations ponctuelles',
                            2 => 'Plan de formation structuré'
                        )
                    )
                )
            ),
            'step3_cybersecurite' => array(
                'title' => 'Cybersécurité',
                'description' => 'Évaluation de votre niveau de sécurité informatique',
                'questions' => array(
                    'password_policy' => array(
                        'type' => 'radio',
                        'label' => 'Politique de mots de passe',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Aucune politique',
                            1 => 'Politique basique',
                            2 => 'Politique stricte avec 2FA'
                        )
                    ),
                    'backup_strategy' => array(
                        'type' => 'radio',
                        'label' => 'Stratégie de sauvegarde',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Aucune sauvegarde régulière',
                            1 => 'Sauvegardes manuelles occasionnelles',
                            2 => 'Sauvegardes automatiques et testées'
                        )
                    ),
                    'antivirus_firewall' => array(
                        'type' => 'radio',
                        'label' => 'Protection antivirus/firewall',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Protection basique ou inexistante',
                            1 => 'Antivirus sur les postes',
                            2 => 'Solution complète (antivirus + firewall + EDR)'
                        )
                    ),
                    'security_training' => array(
                        'type' => 'radio',
                        'label' => 'Formation cybersécurité des équipes',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Aucune sensibilisation',
                            1 => 'Sensibilisation ponctuelle',
                            2 => 'Formation régulière et tests'
                        )
                    ),
                    'gdpr_compliance' => array(
                        'type' => 'radio',
                        'label' => 'Conformité RGPD',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Non conforme',
                            1 => 'Partiellement conforme',
                            2 => 'Totalement conforme avec DPO'
                        )
                    )
                )
            ),
            'step4_cloud' => array(
                'title' => 'Cloud et infrastructure',
                'description' => 'Évaluation de votre infrastructure et usage du cloud',
                'questions' => array(
                    'current_hosting' => array(
                        'type' => 'radio',
                        'label' => 'Hébergement actuel',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Serveurs physiques sur site',
                            1 => 'Hébergement mutualisé',
                            2 => 'Cloud privé ou hybride'
                        )
                    ),
                    'cloud_services' => array(
                        'type' => 'radio',
                        'label' => 'Services cloud utilisés',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Aucun service cloud',
                            1 => 'Quelques services (email, stockage)',
                            2 => 'Infrastructure complète en cloud'
                        )
                    ),
                    'storage_needs' => array(
                        'type' => 'radio',
                        'label' => 'Besoins en stockage',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Stockage local uniquement',
                            1 => 'Stockage cloud basique',
                            2 => 'Solution de stockage avancée avec sync'
                        )
                    ),
                    'remote_work' => array(
                        'type' => 'radio',
                        'label' => 'Capacité de télétravail',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Impossible ou très limité',
                            1 => 'Possible avec des contraintes',
                            2 => 'Télétravail fluide et sécurisé'
                        )
                    ),
                    'network_performance' => array(
                        'type' => 'radio',
                        'label' => 'Performance réseau',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Connexion lente ou instable',
                            1 => 'Connexion correcte',
                            2 => 'Très haut débit avec redondance'
                        )
                    )
                )
            ),
            'step5_automatisation' => array(
                'title' => 'Automatisation',
                'description' => 'Évaluation de votre niveau d\'automatisation des processus',
                'questions' => array(
                    'manual_processes' => array(
                        'type' => 'checkbox',
                        'label' => 'Processus manuels identifiés',
                        'required' => true,
                        'options' => array(
                            'invoicing' => 'Facturation',
                            'reporting' => 'Reporting',
                            'data_entry' => 'Saisie de données',
                            'communication' => 'Communication client',
                            'inventory' => 'Gestion des stocks'
                        )
                    ),
                    'automation_tools' => array(
                        'type' => 'radio',
                        'label' => 'Outils d\'automatisation existants',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Aucun outil',
                            1 => 'Quelques automatisations basiques',
                            2 => 'Plateforme d\'automatisation avancée'
                        )
                    ),
                    'integration_needs' => array(
                        'type' => 'checkbox',
                        'label' => 'Intégrations souhaitées',
                        'required' => true,
                        'options' => array(
                            'crm_erp' => 'CRM ↔ ERP',
                            'email_crm' => 'Email ↔ CRM',
                            'website_crm' => 'Site web ↔ CRM',
                            'accounting' => 'Comptabilité ↔ Autres outils',
                            'ecommerce' => 'E-commerce ↔ Gestion'
                        )
                    ),
                    'time_savings' => array(
                        'type' => 'radio',
                        'label' => 'Gains de temps recherchés',
                        'required' => true,
                        'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                        'options' => array(
                            0 => 'Moins de 5h/semaine',
                            1 => '5 à 15h/semaine',
                            2 => 'Plus de 15h/semaine'
                        )
                    ),
                    'automation_budget' => array(
                        'type' => 'select',
                        'label' => 'Budget automatisation',
                        'required' => true,
                        'options' => array(
                            '0-2k' => 'Moins de 2 000€',
                            '2k-5k' => '2 000€ à 5 000€',
                            '5k-10k' => '5 000€ à 10 000€',
                            '10k+' => 'Plus de 10 000€'
                        )
                    )
                )
            )
        );
    }

    /**
     * Get questionnaire structure
     *
     * @return array Questionnaire structure
     */
    public function getQuestionnaire()
    {
        return $this->questionnaire;
    }

    /**
     * Get specific step
     *
     * @param string $step Step identifier
     * @return array|null Step data or null if not found
     */
    public function getStep($step)
    {
        return isset($this->questionnaire[$step]) ? $this->questionnaire[$step] : null;
    }

    /**
     * Get all steps
     *
     * @return array Array of step identifiers
     */
    public function getSteps()
    {
        return array_keys($this->questionnaire);
    }

    /**
     * Validate step responses
     *
     * @param string $step Step identifier
     * @param array $responses User responses
     * @return array Array with validation result and errors
     */
    public function validateStep($step, $responses)
    {
        $stepData = $this->getStep($step);
        if (!$stepData) {
            return array('valid' => false, 'errors' => array('Invalid step'));
        }

        $errors = array();
        
        foreach ($stepData['questions'] as $questionId => $question) {
            if ($question['required'] && (!isset($responses[$questionId]) || empty($responses[$questionId]))) {
                $errors[] = 'Le champ "'.$question['label'].'" est obligatoire';
            }

            // Validate question type specific rules
            if (isset($responses[$questionId])) {
                switch ($question['type']) {
                    case 'radio':
                    case 'select':
                        if (!array_key_exists($responses[$questionId], $question['options'])) {
                            $errors[] = 'Valeur invalide pour "'.$question['label'].'"';
                        }
                        break;
                    case 'checkbox':
                        if (!is_array($responses[$questionId])) {
                            $errors[] = 'Format invalide pour "'.$question['label'].'"';
                        } else {
                            foreach ($responses[$questionId] as $value) {
                                if (!array_key_exists($value, $question['options'])) {
                                    $errors[] = 'Valeur invalide pour "'.$question['label'].'"';
                                    break;
                                }
                            }
                        }
                        break;
                }
            }
        }

        return array('valid' => empty($errors), 'errors' => $errors);
    }

    /**
     * Calculate score for a step
     *
     * @param string $step Step identifier
     * @param array $responses User responses
     * @return int Score for the step
     */
    public function calculateStepScore($step, $responses)
    {
        $stepData = $this->getStep($step);
        if (!$stepData) {
            return 0;
        }

        $totalScore = 0;
        $maxScore = 0;

        foreach ($stepData['questions'] as $questionId => $question) {
            if (isset($question['score_mapping']) && isset($responses[$questionId])) {
                $response = $responses[$questionId];
                if (isset($question['score_mapping'][$response])) {
                    $totalScore += $question['score_mapping'][$response];
                }
                $maxScore += max($question['score_mapping']);
            }
        }

        return $maxScore > 0 ? round(($totalScore / $maxScore) * 100) : 0;
    }

    /**
     * Calculate global score from all responses
     *
     * @param array $allResponses All user responses
     * @return array Array with detailed scores
     */
    public function calculateGlobalScore($allResponses)
    {
        $scores = array(
            'maturite' => 0,
            'cybersecurite' => 0,
            'cloud' => 0,
            'automatisation' => 0,
            'global' => 0
        );

        $stepScoreMapping = array(
            'step2_maturite' => 'maturite',
            'step3_cybersecurite' => 'cybersecurite',
            'step4_cloud' => 'cloud',
            'step5_automatisation' => 'automatisation'
        );

        $totalScore = 0;
        $scoreCount = 0;

        foreach ($stepScoreMapping as $step => $scoreKey) {
            if (isset($allResponses[$step])) {
                $stepScore = $this->calculateStepScore($step, $allResponses[$step]);
                $scores[$scoreKey] = $stepScore;
                $totalScore += $stepScore;
                $scoreCount++;
            }
        }

        if ($scoreCount > 0) {
            $scores['global'] = round($totalScore / $scoreCount);
        }

        return $scores;
    }

    /**
     * Get questionnaire adapted for structure type
     *
     * @param string $structureType Structure type (tpe_pme or collectivite)
     * @return array Adapted questionnaire
     */
    public function getAdaptedQuestionnaire($structureType)
    {
        $questionnaire = $this->questionnaire;

        // Adapt questions based on structure type
        if ($structureType === 'collectivite') {
            // Add specific questions for collectivities
            $questionnaire['step3_cybersecurite']['questions']['rgpd_dpo'] = array(
                'type' => 'radio',
                'label' => 'Désignation d\'un DPO',
                'required' => true,
                'score_mapping' => array(0 => 1, 1 => 5),
                'options' => array(
                    0 => 'Pas de DPO désigné',
                    1 => 'DPO désigné et formé'
                )
            );

            $questionnaire['step4_cloud']['questions']['sovereignty'] = array(
                'type' => 'radio',
                'label' => 'Souveraineté des données',
                'required' => true,
                'score_mapping' => array(0 => 1, 1 => 3, 2 => 5),
                'options' => array(
                    0 => 'Données hébergées à l\'étranger',
                    1 => 'Hébergement européen',
                    2 => 'Hébergement français certifié'
                )
            );
        }

        return $questionnaire;
    }

    /**
     * Generate recommendations based on scores
     *
     * @param array $scores Calculated scores
     * @param string $structureType Structure type
     * @return array Array of recommendations
     */
    public function generateRecommendations($scores, $structureType = 'tpe_pme')
    {
        $recommendations = array();

        // Define thresholds
        $lowThreshold = 40;
        $mediumThreshold = 70;

        foreach ($scores as $category => $score) {
            if ($category === 'global') continue;

            $categoryRecommendations = array();

            if ($score < $lowThreshold) {
                $categoryRecommendations['priority'] = 'high';
                $categoryRecommendations['message'] = 'Niveau critique - Action immédiate requise';
            } elseif ($score < $mediumThreshold) {
                $categoryRecommendations['priority'] = 'medium';
                $categoryRecommendations['message'] = 'Niveau moyen - Améliorations recommandées';
            } else {
                $categoryRecommendations['priority'] = 'low';
                $categoryRecommendations['message'] = 'Bon niveau - Optimisations possibles';
            }

            $categoryRecommendations['score'] = $score;
            $recommendations[$category] = $categoryRecommendations;
        }

        return $recommendations;
    }
}