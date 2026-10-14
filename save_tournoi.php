<?php
	session_start();
	include 'includes/db.php';
	header('Content-Type: application/json');

	if (!isset($_SESSION['utilisateur'])) {
		echo json_encode(['success' => false, 'error' => 'non_connecte']);
		exit();
	}

	if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
		echo json_encode(['success' => false, 'error' => 'csrf_invalide']);
		exit();
	}

	// Vérifier que le tournoi est encore ouvert
	$premier_match = $pdo->query("SELECT MIN(date_match) AS premier FROM matchs")->fetch();
	if (!$premier_match || strtotime($premier_match['premier']) <= time()) {
		echo json_encode(['success' => false, 'error' => 'tournoi_ferme']);
		exit();
	}

	$pseudo         = $_SESSION['utilisateur'];
	$pays_gagnant   = $_POST['pays_gagnant']    ?: null;
	$meilleur_buteur = $_POST['meilleur_buteur'] ?: null;

	$stmt = $pdo->prepare("SELECT id_utilisateur FROM pronostics_tournoi WHERE id_utilisateur = ?");
	$stmt->execute([$pseudo]);

	if ($stmt->fetch()) {
		$stmt = $pdo->prepare("
			UPDATE pronostics_tournoi 
			SET pays_gagnant = ?, meilleur_buteur = ? 
			WHERE id_utilisateur = ?
		");
		$stmt->execute([$pays_gagnant, $meilleur_buteur, $pseudo]);
	} else {
		$stmt = $pdo->prepare("
			INSERT INTO pronostics_tournoi (id_utilisateur, pays_gagnant, meilleur_buteur) 
			VALUES (?, ?, ?)
		");
		$stmt->execute([$pseudo, $pays_gagnant, $meilleur_buteur]);
	}

	echo json_encode(['success' => true]);
	exit();

?>