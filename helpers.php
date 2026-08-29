<?php

function calculerAge($dateNaissance) {
    if (!$dateNaissance) return '—';
    try {
        $dob = new DateTime($dateNaissance);
        $now = new DateTime();
        return $now->diff($dob)->y;
    } catch (Exception $e) {
        return '—';
    }
}

function badgeStatut($statut) {
    switch ($statut) {
        case 'attente':
            return ['label' => 'En attente', 'class' => 'badge-attente'];
        case 'en_consultation':
            return ['label' => 'En consultation', 'class' => 'badge-consultation'];
        case 'terminee':
            return ['label' => 'Terminée', 'class' => 'badge-terminee'];
        default:
            return ['label' => $statut, 'class' => ''];
    }
}

function etapeIndex($statut) {
    if ($statut === 'attente') return 1;
    if ($statut === 'en_consultation') return 2;
    if ($statut === 'terminee') return 3;
    return 0;
}

// Accueil  Sage-femme
function afficherParcours($statut, $compact = false) {
    $etapes = ["Enregistrement", "File d'attente", "Consultation", "Dossier clôturé"];
    $idx = etapeIndex($statut);
    $html = '<div class="parcours-stepper' . ($compact ? ' compact' : '') . '">';
    foreach ($etapes as $i => $label) {
        $actif = $i <= $idx ? 'actif' : '';
        $html .= '<span class="parcours-segment ' . $actif . '" title="' . htmlspecialchars($label) . '"></span>';
    }
    $html .= '</div>';
    return $html;
}

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Affiche un champ du formulaire 
function champHtml($field, $valeur, $readonly = false) {
    $key = e($field['key']);
    $label = e($field['label']);
    $type = $field['type'];
    $val = $valeur[$field['key']] ?? '';
    $html = '<div class="col-md-6 mb-3">';
    $html .= '<label class="form-label small text-muted">' . $label . '</label>';
    if ($type === 'select') {
        $html .= '<select name="champs[' . $key . ']" class="form-select form-select-sm">';
        $html .= '<option value="">—</option>';
        foreach ($field['options'] as $opt) {
            $sel = ($val === $opt) ? 'selected' : '';
            $html .= '<option value="' . e($opt) . '" ' . $sel . '>' . e($opt) . '</option>';
        }
        $html .= '</select>';
    } else {
        $placeholder = isset($field['placeholder']) ? e($field['placeholder']) : '';
        $ro = $readonly ? 'readonly' : '';
        $html .= '<input type="' . e($type) . '" name="champs[' . $key . ']" value="' . e($val) . '" placeholder="' . $placeholder . '" class="form-control form-control-sm" ' . $ro . '>';
    }
    $html .= '</div>';
    return $html;
}

function enregistrerAudit($pdo, $table, $enregistrementId, $action, $details = '', $patienteId = null) {
    $utilisateur = $_SESSION['user_nom'] ?? $_SESSION['user_name'] ?? 'Sage-femme';
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (table_cible, enregistrement_id, patiente_id, action, details, utilisateur)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$table, $enregistrementId, $patienteId, $action, $details, $utilisateur]);
}

function badgeAction($action) {
    switch ($action) {
        case 'creation':
            return ['label' => 'Création', 'class' => 'badge-consultation'];
        case 'modification':
            return ['label' => 'Modification', 'class' => 'badge-app'];
        case 'archivage':
            return ['label' => 'Archivage', 'class' => 'badge-terminee'];
        case 'desarchivage':
            return ['label' => 'Désarchivage', 'class' => 'badge-attente'];
        default:
            return ['label' => $action, 'class' => ''];
    }
}

function comptageVaccinsFaits($vaccinations) {
    if (!$vaccinations) return 0;
    $n = 0;
    foreach ($vaccinations as $v) {
        if (!empty($v['fait'])) $n++;
    }
    return $n;
}
