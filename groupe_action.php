<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['utilisateur'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifier_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Jeton CSRF invalide. Veuillez recharger la page.');
    }
}

$pseudo = $_SESSION['utilisateur'];
$action = $_POST['action'] ?? null;

// === CRÉER UN GROUPE ===
if ($action === 'creer_groupe') {
    verifier_csrf();

    $nom = trim($_POST['nom_groupe'] ?? '');
    if (empty($nom)) {
        header('Location: compte.php?error=nom_vide');
        exit();
    }

    // Générer un code unique de 6 caractères
    do {
        $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $stmt = $pdo->prepare("SELECT id FROM groupes WHERE code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetch()); // Recommencer si le code existe déjà

    // Insérer le groupe
    $stmt = $pdo->prepare("INSERT INTO groupes (nom, code, createur) VALUES (?, ?, ?)");
    $stmt->execute([$nom, $code, $pseudo]);
    $groupe_id = $pdo->lastInsertId();

    // Ajouter le créateur comme membre
    $stmt = $pdo->prepare("INSERT INTO groupes_membres (groupe_id, utilisateur) VALUES (?, ?)");
    $stmt->execute([$groupe_id, $pseudo]);

    header('Location: compte.php?success=groupe_cree&code=' . $code);
    exit();
}

// === REJOINDRE UN GROUPE ===
if ($action === 'rejoindre_groupe') {
    verifier_csrf();

    $code = strtoupper(trim($_POST['code_groupe'] ?? ''));
    if (empty($code)) {
        header('Location: compte.php?error=code_vide');
        exit();
    }

    // Vérifier que le groupe existe
    $stmt = $pdo->prepare("SELECT id, nom FROM groupes WHERE code = ?");
    $stmt->execute([$code]);
    $groupe = $stmt->fetch();

    if (!$groupe) {
        header('Location: compte.php?error=groupe_introuvable');
        exit();
    }

    // Vérifier si déjà membre
    $stmt = $pdo->prepare("SELECT * FROM groupes_membres WHERE groupe_id = ? AND utilisateur = ?");
    $stmt->execute([$groupe['id'], $pseudo]);
    if ($stmt->fetch()) {
        header('Location: compte.php?error=deja_membre');
        exit();
    }

    // Ajouter comme membre
    $stmt = $pdo->prepare("INSERT INTO groupes_membres (groupe_id, utilisateur) VALUES (?, ?)");
    $stmt->execute([$groupe['id'], $pseudo]);

    header('Location: compte.php?success=groupe_rejoint&nom=' . urlencode($groupe['nom']));
    exit();
}

header('Location: compte.php');
exit();