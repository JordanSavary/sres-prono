<?php
session_start();
include 'includes/db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur'])) {
    header('Location: login.php');
    exit();
}

// Récupérer le pseudo
$pseudo = $_SESSION['utilisateur'];

// ==========================
// RÉCUPÉRATION DES GROUPES
// ==========================
$stmt_groupes = $pdo->prepare("
    SELECT g.id, g.nom
    FROM groupes g
    JOIN groupes_membres gm ON g.id = gm.groupe_id
    WHERE gm.utilisateur = ?
    ORDER BY g.nom
");
$stmt_groupes->execute([$pseudo]);
$groupes = $stmt_groupes->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// GROUPE SÉLECTIONNÉ
// ==========================
$groupe_id = $_GET['groupe_id'] ?? null;

// Vérification sécurité : l'utilisateur doit appartenir au groupe
$groupe_ids_user = array_column($groupes, 'id');
if ($groupe_id && !in_array($groupe_id, $groupe_ids_user)) {
    die("Accès non autorisé à ce groupe.");
}

// ==========================
// RÉCUPÉRATION DU CLASSEMENT
// ==========================
if ($groupe_id) {
    $stmt = $pdo->prepare("
    SELECT u.pseudo, u.points, u.avatar
    FROM utilisateurs u
    JOIN groupes_membres gm 
        ON u.pseudo COLLATE utf8mb4_general_ci = gm.utilisateur
    WHERE gm.groupe_id = ?
    ORDER BY u.points DESC, u.pseudo ASC
");
    $stmt->execute([$groupe_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nom du groupe
    $groupes_assoc = array_column($groupes, 'nom', 'id');
    $titre = "Classement du groupe " . htmlspecialchars($groupes_assoc[$groupe_id]);

} else {
    $query = $pdo->query("
        SELECT pseudo, points, avatar
        FROM utilisateurs
        ORDER BY points DESC, pseudo ASC
    ");
    $users = $query->fetchAll(PDO::FETCH_ASSOC);

    $titre = "Classement général";
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
		
		<div class="tabs-groupes glassy ">
			<a href="classement.php" class="<?= !$groupe_id ? 'active' : '' ?>">🌍 Général</a>

			<?php foreach ($groupes as $g) : ?>
				<a href="classement.php?groupe_id=<?= $g['id'] ?>"
				   class="<?= ($groupe_id == $g['id']) ? 'active' : '' ?>">
					<?= htmlspecialchars($g['nom']) ?>
				</a>
			<?php endforeach; ?>
		</div>
    
        <section class="classement-container glassy">
			
			<h2><?= $titre ?></h2>

			<div class="podium">
				<?php if (!empty($users)) : ?>
					<?php for ($i = 0; $i < 3 && $i < count($users); $i++) : ?>
						<?php
						$avatar = !empty($users[$i]['avatar']) ? $users[$i]['avatar'] : 'avatars/default.png';
						?>
						<div class="podium-place podium-<?php echo $i + 1; ?>">
							<img src="<?= htmlspecialchars($avatar); ?>" alt="Avatar" class="avatar">
							<div class="place-number"><?= $i + 1; ?></div>
							<div class="pseudo"><?= htmlspecialchars($users[$i]['pseudo']); ?></div>
							<div class="points"><?= $users[$i]['points']; ?> pts</div>
						</div>
					<?php endfor; ?>
				<?php else : ?>
					<p>Aucun utilisateur dans ce groupe.</p>
				<?php endif; ?>
			</div>

		</section>

<section class="classement-container glassy">
    <div class="liste-classement">
        <h2>Les suivants</h2>

        <?php if (count($users) > 3) : ?>
            <ol start="4">
                <?php for ($i = 3; $i < count($users); $i++) : ?>
                    <?php
                    $avatar = !empty($users[$i]['avatar']) ? $users[$i]['avatar'] : 'avatars/default.png';
                    ?>
                    <li>
                        <img src="<?= htmlspecialchars($avatar); ?>" alt="Avatar" class="avatar-small">
                        <span class="pseudo"><?= htmlspecialchars($users[$i]['pseudo']); ?></span>
                        <span class="points"><?= $users[$i]['points']; ?> pts</span>
                    </li>
                <?php endfor; ?>
            </ol>
        <?php else : ?>
            <p>Pas assez de joueurs pour afficher plus de classement.</p>
        <?php endif; ?>

    </div>
</section>

</body>
<?php include 'script.php'; ?>
</html>
