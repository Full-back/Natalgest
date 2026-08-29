<?php

$PRENATAL_SECTIONS = [
    [
        'title' => 'Antécédents',
        'fields' => [
            ['key' => 'gestite', 'label' => 'Gestité', 'type' => 'number'],
            ['key' => 'parite', 'label' => 'Parité', 'type' => 'number'],
            ['key' => 'antecedentsObstetricaux', 'label' => 'Antécédents obstétricaux', 'type' => 'text'],
            ['key' => 'antecedentsMedicaux', 'label' => 'Antécédents médicaux', 'type' => 'text'],
            ['key' => 'antecedentsChirurgicaux', 'label' => 'Antécédents chirurgicaux', 'type' => 'text'],
            ['key' => 'antecedentsFamiliaux', 'label' => 'Antécédents familiaux', 'type' => 'text'],
            ['key' => 'allergies', 'label' => 'Allergies', 'type' => 'text'],
        ],
    ],
    [
        'title' => 'Examen clinique',
        'fields' => [
            ['key' => 'ageGestationnel', 'label' => 'Âge gestationnel (semaines)', 'type' => 'number'],
            ['key' => 'poids', 'label' => 'Poids (kg)', 'type' => 'number'],
            ['key' => 'temperature', 'label' => 'Température (°C)', 'type' => 'number'],
            ['key' => 'tension', 'label' => 'Tension artérielle', 'type' => 'text', 'placeholder' => '12/8'],
            ['key' => 'hauteurUterine', 'label' => 'Hauteur utérine (cm)', 'type' => 'number'],
            ['key' => 'bruitsCoeurFoetal', 'label' => 'Bruits du cœur fœtal (bpm)', 'type' => 'number'],
            ['key' => 'mouvementsFoetaux', 'label' => 'Mouvements fœtaux', 'type' => 'select', 'options' => ['Perçus', 'Non perçus']],
            ['key' => 'presentation', 'label' => 'Présentation fœtale', 'type' => 'select', 'options' => ['Céphalique', 'Siège', 'Transverse', 'Indéterminée']],
            ['key' => 'oedemes', 'label' => 'Œdèmes', 'type' => 'select', 'options' => ['Absents', 'Présents']],
        ],
    ],
    [
        'title' => 'Échographie',
        'fields' => [
            ['key' => 'typeEcho', 'label' => "Type d'échographie", 'type' => 'select', 'options' => ['T1 - datation', 'T2 - morphologique', 'T3 - croissance']],
            ['key' => 'termeEcho', 'label' => 'Terme échographique', 'type' => 'text'],
            ['key' => 'bip', 'label' => 'BIP (mm)', 'type' => 'number'],
            ['key' => 'longueurFemur', 'label' => 'Longueur fémorale (mm)', 'type' => 'number'],
            ['key' => 'perimetreAbdo', 'label' => 'Périmètre abdominal (mm)', 'type' => 'number'],
            ['key' => 'poidsFoetalEstime', 'label' => 'Poids fœtal estimé (g)', 'type' => 'number'],
            ['key' => 'positionPlacenta', 'label' => 'Position du placenta', 'type' => 'text'],
            ['key' => 'liquideAmniotique', 'label' => 'Liquide amniotique', 'type' => 'select', 'options' => ['Normal', 'Diminué', 'Augmenté']],
        ],
    ],
    [
        'title' => 'Biologie & sérologies',
        'fields' => [
            ['key' => 'groupeSanguin', 'label' => 'Groupe sanguin / Rhésus', 'type' => 'text', 'placeholder' => 'O+'],
            ['key' => 'raiRhesus', 'label' => 'RAI (si Rhésus négatif)', 'type' => 'select', 'options' => ['Négative', 'Positive', 'Non fait']],
            ['key' => 'hemoglobine', 'label' => 'Hémoglobine (g/dL)', 'type' => 'number'],
            ['key' => 'glycemie', 'label' => 'Glycémie / HGPO (g/L)', 'type' => 'number'],
            ['key' => 'proteinurie', 'label' => 'Protéinurie', 'type' => 'select', 'options' => ['Négative', 'Traces', 'Positive']],
            ['key' => 'ecbu', 'label' => 'ECBU', 'type' => 'select', 'options' => ['Normal', 'Infection détectée', 'Non fait']],
            ['key' => 'toxoplasmose', 'label' => 'Sérologie toxoplasmose', 'type' => 'select', 'options' => ['Immunisée', 'Non immunisée', 'Non fait']],
            ['key' => 'rubeole', 'label' => 'Sérologie rubéole', 'type' => 'select', 'options' => ['Immunisée', 'Non immunisée', 'Non fait']],
            ['key' => 'syphilis', 'label' => 'TPHA-VDRL (syphilis)', 'type' => 'select', 'options' => ['Négatif', 'Positif', 'Non fait']],
            ['key' => 'vih', 'label' => 'Sérologie VIH', 'type' => 'select', 'options' => ['Négatif', 'Positif', 'Non fait']],
            ['key' => 'hepatiteB', 'label' => 'AgHBs (hépatite B)', 'type' => 'select', 'options' => ['Négatif', 'Positif', 'Non fait']],
        ],
    ],
    [
        'title' => 'Vaccination & supplémentation',
        'fields' => [
            ['key' => 'vatDose', 'label' => 'Vaccin antitétanique (VAT)', 'type' => 'select', 'options' => ['VAT1', 'VAT2', 'VAT3', 'Rappel', 'À jour']],
            ['key' => 'dateVat', 'label' => 'Date du dernier VAT', 'type' => 'date'],
            ['key' => 'fer', 'label' => 'Supplémentation en fer/acide folique', 'type' => 'select', 'options' => ['Prescrite', 'En cours', 'Non prescrite']],
            ['key' => 'calcium', 'label' => 'Supplémentation en calcium', 'type' => 'select', 'options' => ['Prescrite', 'En cours', 'Non prescrite']],
        ],
    ],
    [
        'title' => 'Suivi complémentaire',
        'fields' => [
            ['key' => 'depistageT21', 'label' => 'Dépistage trisomie 21', 'type' => 'select', 'options' => ['Non concerné', 'Fait - risque faible', 'Fait - risque élevé', 'À faire']],
            ['key' => 'monitoringRcf', 'label' => 'Monitoring / RCF', 'type' => 'select', 'options' => ['Normal', 'Anormal', 'Non fait']],
            ['key' => 'consultationAnesthesie', 'label' => "Consultation d'anesthésie", 'type' => 'select', 'options' => ['Faite', 'À prévoir', 'Non concernée']],
            ['key' => 'preparationAccouchement', 'label' => "Préparation à l'accouchement", 'type' => 'select', 'options' => ['Débutée', 'Terminée', 'Non débutée']],
        ],
    ],
];

$POSTNATAL_MOTHER_SECTIONS = [
    [
        'title' => "Antécédents de l'accouchement",
        'fields' => [
            ['key' => 'modeAccouchement', 'label' => "Mode d'accouchement", 'type' => 'select', 'options' => ['Voie basse', 'Césarienne', 'Voie basse instrumentale']],
            ['key' => 'dateAccouchement', 'label' => "Date de l'accouchement", 'type' => 'date'],
            ['key' => 'complicationsAccouchement', 'label' => 'Complications à l’accouchement', 'type' => 'text'],
        ],
    ],
    [
        'title' => 'Examen de la mère',
        'fields' => [
            ['key' => 'etatMere', 'label' => 'État général de la mère', 'type' => 'text'],
            ['key' => 'tensionMere', 'label' => 'Tension artérielle', 'type' => 'text', 'placeholder' => '12/8'],
            ['key' => 'involutionUterine', 'label' => 'Involution utérine', 'type' => 'select', 'options' => ['Normale', 'Retardée']],
            ['key' => 'lochies', 'label' => 'Lochies', 'type' => 'select', 'options' => ['Normales', 'Abondantes', 'Malodorantes']],
            ['key' => 'cicatrice', 'label' => 'Cicatrice (épisiotomie/césarienne)', 'type' => 'select', 'options' => ['Non concernée', 'Cicatrisation normale', "Signes d'infection"]],
            ['key' => 'etatSeins', 'label' => 'État des seins', 'type' => 'text'],
            ['key' => 'etatPsychologique', 'label' => 'État psychologique (baby blues, humeur...)', 'type' => 'text'],
            ['key' => 'suitesCouches', 'label' => 'Suites de couches', 'type' => 'text'],
        ],
    ],
    [
        'title' => 'Suivi & planification',
        'fields' => [
            ['key' => 'allaitement', 'label' => 'Allaitement', 'type' => 'select', 'options' => ['Exclusif', 'Mixte', 'Artificiel']],
            ['key' => 'contraception', 'label' => 'Planification familiale / contraception', 'type' => 'text'],
        ],
    ],
    [
        'title' => 'Examens biologiques',
        'fields' => [
            ['key' => 'hemoglobineMere', 'label' => 'Hémoglobine mère (g/dL)', 'type' => 'number'],
            ['key' => 'bilanMere', 'label' => 'Autres examens biologiques', 'type' => 'text'],
        ],
    ],
];

$POSTNATAL_CHILD_SECTIONS = [
    [
        'title' => 'Identité à la naissance',
        'fields' => [
            ['key' => 'poidsNaissance', 'label' => 'Poids de naissance (g)', 'type' => 'number'],
            ['key' => 'tailleNaissance', 'label' => 'Taille de naissance (cm)', 'type' => 'number'],
            ['key' => 'perimetreCranienNaissance', 'label' => 'Périmètre crânien à la naissance (cm)', 'type' => 'number'],
            ['key' => 'scoreApgar', 'label' => "Score d'Apgar", 'type' => 'text', 'placeholder' => '9/10'],
        ],
    ],
    [
        'title' => "Examen clinique de l'enfant",
        'fields' => [
            ['key' => 'poidsActuel', 'label' => 'Poids actuel (g)', 'type' => 'number'],
            ['key' => 'temperatureEnfant', 'label' => 'Température (°C)', 'type' => 'number'],
            ['key' => 'coloration', 'label' => 'Coloration / ictère', 'type' => 'select', 'options' => ['Normale', 'Ictère modéré', 'Ictère sévère']],
            ['key' => 'etatNouveauNe', 'label' => 'État général du nouveau-né', 'type' => 'text'],
            ['key' => 'cordon', 'label' => 'Cordon ombilical', 'type' => 'select', 'options' => ['En voie de chute', 'Tombé', "Signes d'infection"]],
            ['key' => 'fontanelle', 'label' => 'Fontanelle', 'type' => 'select', 'options' => ['Normale', 'Bombée', 'Déprimée']],
            ['key' => 'reflexes', 'label' => 'Réflexes archaïques', 'type' => 'select', 'options' => ['Présents', 'Absents/faibles']],
        ],
    ],
    [
        'title' => 'Alimentation',
        'fields' => [
            ['key' => 'typeAllaitementEnfant', 'label' => "Type d'allaitement", 'type' => 'select', 'options' => ['Exclusif', 'Mixte', 'Artificiel']],
            ['key' => 'prisePoids', 'label' => 'Prise de poids depuis la naissance', 'type' => 'text'],
        ],
    ],
    [
        'title' => 'Examens & dépistages',
        'fields' => [
            ['key' => 'depistageNeonatal', 'label' => 'Dépistage néonatal (Guthrie)', 'type' => 'select', 'options' => ['Fait - normal', 'Fait - anormal', 'Non fait']],
            ['key' => 'bilirubine', 'label' => 'Bilirubine (si ictère)', 'type' => 'text'],
            ['key' => 'testAudition', 'label' => 'Test auditif', 'type' => 'select', 'options' => ['Fait - normal', 'Fait - anormal', 'Non fait']],
            ['key' => 'depistageDrepanocytose', 'label' => 'Dépistage drépanocytose', 'type' => 'select', 'options' => ['Fait - normal', 'Fait - anormal', 'Non fait']],
        ],
    ],
    [
        'title' => "Prescriptions pour l'enfant",
        'fields' => [
            ['key' => 'vitamineD', 'label' => 'Vitamine D', 'type' => 'select', 'options' => ['Prescrite', 'En cours', 'Non prescrite']],
            ['key' => 'ferEnfant', 'label' => 'Fer (si indiqué)', 'type' => 'select', 'options' => ['Prescrit', 'En cours', 'Non prescrit']],
            ['key' => 'autresProduits', 'label' => 'Autres produits prescrits', 'type' => 'text'],
        ],
    ],
];

$VACCINES_ENFANT = [
    ['key' => 'bcg', 'label' => 'BCG', 'age' => 'Naissance'],
    ['key' => 'polio0', 'label' => 'Polio 0 (VPO)', 'age' => 'Naissance'],
    ['key' => 'penta1', 'label' => 'Penta 1 + VPO1 + Pneumo 1', 'age' => '6 semaines'],
    ['key' => 'penta2', 'label' => 'Penta 2 + VPO2 + Pneumo 2', 'age' => '10 semaines'],
    ['key' => 'penta3', 'label' => 'Penta 3 + VPO3 + Pneumo 3 + VPI', 'age' => '14 semaines'],
    ['key' => 'vitA6', 'label' => 'Vitamine A', 'age' => '6 mois'],
    ['key' => 'rr1', 'label' => 'Rougeole-Rubéole (VAR) 1', 'age' => '9 mois'],
    ['key' => 'fj', 'label' => 'Fièvre jaune (VAA)', 'age' => '9 mois'],
    ['key' => 'rr2', 'label' => 'Rougeole-Rubéole (VAR) 2', 'age' => '15-18 mois'],
];
