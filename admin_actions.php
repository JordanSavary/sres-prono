<?php
session_start();
include 'includes/db.php';

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

    $stmt = $pdo->query("SELECT id, score_a, score_b, cote_a, cote_nul, cote_b FROM matchs");
    $matchs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $match) {
        $matchs[$match['id']] = $match;
    }

    $stmt = $pdo->query("SELECT * FROM pronostics");
    $pronostics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare("UPDATE pronostics SET points = ? WHERE id_utilisateur = ? AND id_match = ?");

    foreach ($pronostics as $prono) {
        $id_match       = $prono['id_match'];
        $id_utilisateur = $prono['id_utilisateur'];
        $prono_a        = $prono['prono_a'];
        $prono_b        = $prono['prono_b'];

        if (!isset($matchs[$id_match])) continue;

        $match = $matchs[$id_match];
        if ($match['score_a'] === null || $match['score_b'] === null) continue;

        $score_a = $match['score_a'];
        $score_b = $match['score_b'];

        $points = 0;
        if ($prono_a == $score_a && $prono_b == $score_b) {
            $points = 5;
        } elseif (
            ($prono_a - $prono_b) == ($score_a - $score_b) &&
            (
                ($prono_a > $prono_b && $score_a > $score_b) ||
                ($prono_a < $prono_b && $score_a < $score_b) ||
                ($prono_a == $prono_b && $score_a == $score_b)
            )
        ) {
            $points = 3;
        } elseif (
            ($prono_a > $prono_b && $score_a > $score_b) ||
            ($prono_a < $prono_b && $score_a < $score_b) ||
            ($prono_a == $prono_b && $score_a == $score_b)
        ) {
            $points = 2;
        }
		
		// Multiplicateur selon la cote — uniquement si points > 0 et cotes renseignées
        if ($points > 0 && ($match['cote_a'] || $match['cote_nul'] || $match['cote_b'])) {

            // Déterminer quelle cote correspond au pronostic
            if ($prono_a == $prono_b) {
                // Pronostic nul
                $cote_pronostiquee = $match['cote_nul'];
            } elseif ($prono_a > $prono_b) {
                // Pronostic victoire équipe A
                $cote_pronostiquee = $match['cote_a'];
            } else {
                // Pronostic victoire équipe B
                $cote_pronostiquee = $match['cote_b'];
            }

            // Calcul du multiplicateur
            if ($cote_pronostiquee) {
                if ($prono_a == $prono_b) {
                    // Nul prédit → toujours ×1.5
                    $multiplicateur = 1.5;
                } elseif ($cote_pronostiquee < 1.75) {
                    $multiplicateur = 1.0; // grand favori
                } elseif ($cote_pronostiquee <= 2.50) {
                    $multiplicateur = 1.5; // équilibré
                } else {
                    $multiplicateur = 2.0; // outsider
                }

                $points = round($points * $multiplicateur);
            }
        }

        $update->execute([$points, $id_utilisateur, $id_match]);
    }

    $stmt = $pdo->query("SELECT id_utilisateur, SUM(points) as total FROM pronostics GROUP BY id_utilisateur");
    $totaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update_user = $pdo->prepare("UPDATE utilisateurs SET points = ? WHERE pseudo = ?");
    foreach ($totaux as $row) {
        $update_user->execute([$row['total'], $row['id_utilisateur']]);
    }

    header('Location: admin.php?success=points_updated');
    exit;
}

if ($action === 'actualiser_points_tournoi') {
    verifier_csrf();

    $pays_vainqueur  = trim($_POST['pays_vainqueur']);
    $id_buteur       = trim($_POST['id_buteur']);

    // Récupérer tous les pronostics tournoi
    $stmt = $pdo->query("SELECT * FROM pronostics_tournoi");
    $pronos = $stmt->fetchAll();

    $update = $pdo->prepare("
        UPDATE pronostics_tournoi 
        SET points_vainqueur = ?, points_buteur = ? 
        WHERE id_utilisateur = ?
    ");

    foreach ($pronos as $prono) {
        $pts_vainqueur = ($prono['pays_gagnant'] == $pays_vainqueur) ? 50 : 0;
        $pts_buteur    = ($prono['meilleur_buteur'] == $id_buteur)    ? 50 : 0;
        $update->execute([$pts_vainqueur, $pts_buteur, $prono['id_utilisateur']]);
    }

    // Ajouter ces points au total des utilisateurs
    $stmt = $pdo->query("SELECT id_utilisateur, points_vainqueur, points_buteur FROM pronostics_tournoi");
    $update_user = $pdo->prepare("UPDATE utilisateurs SET points = points + ? WHERE pseudo = ?");
    foreach ($stmt->fetchAll() as $row) {
        $bonus = $row['points_vainqueur'] + $row['points_buteur'];
        if ($bonus > 0) {
            $update_user->execute([$bonus, $row['id_utilisateur']]);
        }
    }

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
    $id             = $_GET['id'] ?? null;
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