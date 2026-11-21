<?php
session_start();
include 'includes/db.php';
require_once 'includes/points.php';

// Déterminer si la requête vient de l'autosave (AJAX) ou d'un formulaire classique
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if ($is_ajax) {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fonction helper pour vérifier le CSRF uniquement en POST
function verifier_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Jeton CSRF invalide. Veuillez recharger la page.');
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (in_array($action, ['add_competition', 'edit_competition', 'edit_match'])) {
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Admin - Pronos Sport</title>
        <link rel="stylesheet" href="styles/style.css">
    </head>
    <body>
        <?php include 'header.php'; ?>
        <div class="glassy">
<?php
}

// === ACTUALISER POINTS ===
if ($action === 'actualiser_points') {
    verifier_csrf();
    recalculer_points_pronostics($pdo);
    recalculer_totaux($pdo);

    header('Location: admin.php?success=points_updated');
    exit;
}

if ($action === 'actualiser_points_tournoi') {
    verifier_csrf();

    $pays_vainqueur  = trim($_POST['pays_vainqueur']);
    $id_buteur       = trim($_POST['id_buteur']);

    attribuer_points_tournoi($pdo, $pays_vainqueur, $id_buteur);
    recalculer_totaux($pdo);

    header('Location: admin.php?success=points_tournoi_updated');
    exit();
}

// === MATCHS ===
if ($action === 'add_match') {
    verifier_csrf();

    $competition_id = $_POST['competition_id'];
    $date_match     = $_POST['date_match'];
    $equipe_a       = trim($_POST['equipe_a']);
    $equipe_b       = trim($_POST['equipe_b']);

    if ($competition_id && $date_match && $equipe_a && $equipe_b) {
        $stmt = $pdo->prepare("INSERT INTO matchs (competition_id, date_match, equipe_a, equipe_b) VALUES (?, ?, ?, ?)");
        $stmt->execute([$competition_id, $date_match, $equipe_a, $equipe_b]);
    }
    header('Location: admin.php?competition_id=' . $competition_id);
    exit();
}

if ($action === 'delete_match') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: admin.php');
        exit();
    }
    verifier_csrf();

    $id = $_POST['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM matchs WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: admin.php');
    exit();
}

if ($action === 'update_forme_pays') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'csrf_invalide']);
        exit();
    }

    $pays  = $_POST['pays']  ?? null;
    $forme = $_POST['forme'] ?? null;

    if (!$pays || !$forme || strlen($forme) !== 5) {
        echo json_encode(['success' => false, 'error' => 'donnees_invalides']);
        exit();
    }

    // Valider que la forme ne contient que W, D, L
    if (!preg_match('/^[WDL]{5}$/', $forme)) {
        echo json_encode(['success' => false, 'error' => 'forme_invalide']);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE pays SET forme = ? WHERE id = ?");
    $stmt->execute([$forme, $pays]);

    echo json_encode(['success' => true]);
    exit();
}

if ($action === 'update_match_ajax') {
    verifier_csrf();

    $id = $_POST['id_match'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false]);
        exit;
    }

    $fields = [];
    $values = [];

    foreach (['equipe_a', 'equipe_b', 'score_a', 'score_b', 'date_match', 
          'cote_a', 'cote_nul', 'cote_b'] as $field) {
        if (isset($_POST[$field])) {
			
            $fields[] = "$field = ?";
            $values[] = $_POST[$field] !== '' ? $_POST[$field] : null;
        }
    }

    if ($fields) {
        $values[] = $id;
        $sql = "UPDATE matchs SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }

    echo json_encode(['success' => true]);
    exit;
}

// Aucune action reconnue
header('Location: admin.php');
exit();