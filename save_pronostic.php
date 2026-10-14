<?php
session_start();
include 'includes/db.php';
date_default_timezone_set('Europe/Paris');

// Déterminer si la requête vient de l'autosave (AJAX) ou d'un formulaire classique
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if ($is_ajax) {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'csrf_invalide']);
        exit;
    }
    die('Jeton CSRF invalide. Veuillez recharger la page.');
}

$id_utilisateur = $_SESSION['utilisateur'];
$id_match       = $_POST['id_match']  ?? null;
$prono_a        = $_POST['prono_a']   ?? null;
$prono_b        = $_POST['prono_b']   ?? null;

if (!$id_match || $prono_a === null || $prono_b === null) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'donnees_manquantes']);
        exit;
    }
    header('Location: dashboard.php?error=1');
    exit;
}

// Vérifier si le match a déjà commencé
$stmt = $pdo->prepare("SELECT date_match FROM matchs WHERE id = ?");
$stmt->execute([$id_match]);
$match = $stmt->fetch();

if (!$match || strtotime($match['date_match']) <= time()) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'match_started']);
        exit;
    }
    header('Location: dashboard.php?error=match_started');
    exit;
}

// Vérifier si le pronostic existe déjà
$stmt = $pdo->prepare("SELECT * FROM pronostics WHERE id_match = ? AND id_utilisateur = ?");
$stmt->execute([$id_match, $id_utilisateur]);
$pronostic = $stmt->fetch();

if ($pronostic) {
    $stmt = $pdo->prepare("UPDATE pronostics SET prono_a = ?, prono_b = ? WHERE id_match = ? AND id_utilisateur = ?");
    $stmt->execute([$prono_a, $prono_b, $id_match, $id_utilisateur]);
} else {
    $stmt = $pdo->prepare("INSERT INTO pronostics (id_match, id_utilisateur, prono_a, prono_b) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_match, $id_utilisateur, $prono_a, $prono_b]);
}

if ($is_ajax) {
    echo json_encode(['success' => true]);
    exit;
}

header('Location: dashboard.php?success=1');
exit;