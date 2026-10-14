<?php
session_start();
include 'includes/db.php';
date_default_timezone_set('Europe/Paris');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupérer le pseudo de l'utilisateur
$pseudo = $_SESSION['utilisateur'];
$stmt = $pdo->prepare("SELECT email, points, avatar FROM utilisateurs WHERE pseudo = ?");
$stmt->execute([$pseudo]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les groupes de l'utilisateur
$stmt_groupes = $pdo->prepare("
    SELECT g.id, g.nom, g.code, g.createur,
           COUNT(gm2.utilisateur) AS nb_membres
    FROM groupes g
    JOIN groupes_membres gm ON g.id = gm.groupe_id
    LEFT JOIN groupes_membres gm2 ON g.id = gm2.groupe_id
    WHERE gm.utilisateur = ?
    GROUP BY g.id
    ORDER BY g.nom
");
$stmt_groupes->execute([$pseudo]);
$mes_groupes = $stmt_groupes->fetchAll();

// Gestion du formulaire
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Jeton CSRF invalide. Veuillez recharger la page.');
    }
    if (isset($_POST['update_email'])) {
        $new_email = $_POST['email'] ?? '';
        if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $update = $pdo->prepare("UPDATE utilisateurs SET email = ? WHERE pseudo = ?");
            $update->execute([$new_email, $pseudo]);
            $message = "Email mis à jour avec succès.";
        } else {
            $message = "Email invalide.";
        }
    }

    if (isset($_POST['update_password'])) {
        $new_password = $_POST['password'] ?? '';
        if (strlen($new_password) >= 6) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE pseudo = ?");
            $update->execute([$hashed, $pseudo]);
            $message = "Mot de passe mis à jour.";
        } else {
            $message = "Le mot de passe doit contenir au moins 6 caractères.";
        }
    }

    if (isset($_POST['update_avatar'])) {
        $selected_avatar = $_POST['default_avatar'] ?? null;
        $file = $_FILES['avatar'] ?? null;

        if ($selected_avatar) {
            // L'utilisateur a choisi un avatar par défaut
            if (file_exists($selected_avatar)) {
                $update = $pdo->prepare("UPDATE utilisateurs SET avatar = ? WHERE pseudo = ?");
                $update->execute([$selected_avatar, $pseudo]);
                $message = "Avatar par défaut sélectionné.";
            } else {
                $message = "Avatar par défaut introuvable.";
            }

        } elseif ($file && $file['error'] === 0) {
            // L'utilisateur a uploadé un avatar personnalisé
            $mime = mime_content_type($file['tmp_name']);
            if (in_array($mime, ['image/jpeg', 'image/png'])) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatars/' . uniqid() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $filename);

                $update = $pdo->prepare("UPDATE utilisateurs SET avatar = ? WHERE pseudo = ?");
                $update->execute([$filename, $pseudo]);
                $message = "Avatar personnalisé mis à jour.";
            } else {
                $message = "Erreur : seuls les fichiers JPG et PNG sont acceptés.";
            }

        } else {
            $message = "Veuillez sélectionner un avatar ou en envoyer un.";
        }

        // Recharger l'utilisateur
        $stmt->execute([$pseudo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Recharger l'utilisateur
    $stmt->execute([$pseudo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
	
	// Recharger les groupes 
	$stmt_groupes = $pdo->prepare("
    SELECT g.id, g.nom, g.code, g.createur,
           COUNT(gm2.utilisateur) AS nb_membres
    FROM groupes g
    JOIN groupes_membres gm ON g.id = gm.groupe_id
    LEFT JOIN groupes_membres gm2 ON g.id = gm2.groupe_id
    WHERE gm.utilisateur = ?
    GROUP BY g.id
    ORDER BY g.nom
	");
	$stmt_groupes->execute([$pseudo]);
	$mes_groupes = $stmt_groupes->fetchAll();
	}
	
	// Date du premier match
	$premier_match = $pdo->query("SELECT MIN(date_match) AS premier FROM matchs")->fetch();
	$date_limite = $premier_match['premier'] ?? null;
	$tournoi_ouvert = $date_limite && strtotime($date_limite) > time();

	// Pronostic tournoi existant
	$stmt = $pdo->prepare("SELECT * FROM pronostics_tournoi WHERE id_utilisateur = ?");
	$stmt->execute([$pseudo]);
	$prono_tournoi = $stmt->fetch();

	$nom_buteur = '';
	$nom_pays_gagnant = '';
	if (!empty($prono_tournoi['meilleur_buteur'])) {
		$stmt_buteur = $pdo->prepare("SELECT j.nom, p.nom AS nom_pays FROM joueurs j LEFT JOIN pays p ON j.pays = p.id WHERE j.id = ?");
		$stmt_buteur->execute([$prono_tournoi['meilleur_buteur']]);
		$buteur = $stmt_buteur->fetch();
		$nom_buteur = $buteur ? $buteur['nom'] . ' (' . $buteur['nom_pays'] . ')' : '';
	}
	if (!empty($prono_tournoi['pays_gagnant'])) {
		$stmt_pays = $pdo->prepare("SELECT nom FROM pays WHERE id = ?");
		$stmt_pays->execute([$prono_tournoi['pays_gagnant']]);
		$pays_gagnant = $stmt_pays->fetch();
		$nom_pays_gagnant = $pays_gagnant['nom'] ?? $prono_tournoi['pays_gagnant'];
	}

	// Liste des joueurs
	$joueurs = $pdo->query("
		SELECT j.*, p.nom AS nom_pays 
		FROM joueurs j 
		LEFT JOIN pays p ON j.pays = p.id 
		ORDER BY p.nom, j.nom
	")->fetchAll();

	// Pays du meilleur buteur déjà sélectionné, pour préremplir le filtre pays
	$pays_buteur_selectionne = '';
	if (!empty($prono_tournoi['meilleur_buteur'])) {
		foreach ($joueurs as $j) {
			if ((string)$j['id'] === (string)$prono_tournoi['meilleur_buteur']) {
				$pays_buteur_selectionne = $j['pays'];
				break;
			}
		}
	}

	// Liste des pays participants (tous sauf XXX)
	$pays_participants = $pdo->query("
		SELECT DISTINCT p.id, p.nom 
		FROM pays p
		JOIN matchs m ON p.id = m.equipe_a OR p.id = m.equipe_b
		WHERE p.id != 'XXX'
		ORDER BY p.nom
	")->fetchAll();
	
?>

<!DOCTYPE html>
<html lang="fr">
<?php include 'head.php'; ?>
<body>
    <?php include 'header.php'; ?>
    <div class="mon-compte">
        <div class="mon-compte-content">
            <section class="compte-container glassy">
                <h1>Mon Compte</h1>

                <?php if ($user['avatar']) : ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="avatar-compte"></img>
                <?php else : ?>
                    <img src="avatars/default.png" alt="Avatar" width="100" class="avatar-compte"></img>
                <?php endif; ?>

                <h2><?= htmlspecialchars($pseudo) ?></h2>
                <p><?= $user['points'] ?> points</p>
            </section>
			<div class="mon-compte-grp-maj">	
				<section class="compte-container-update glassy">
					<h3>🏆 Pronostics de tournoi</h3>
					<?php if (!$tournoi_ouvert): ?>
						<p>Les pronostics de tournoi sont clôturés au début de la compétition.</p>
						<?php if ($prono_tournoi): ?>
							<p>Pays vainqueur : <strong><?= htmlspecialchars($nom_pays_gagnant) ?></strong></p>
							<p>Meilleur buteur : <strong><?= htmlspecialchars($nom_buteur) ?></strong></p>
						<?php endif; ?>
					<?php else: ?>
						<p><small>⏳ Modifiable jusqu'au début du premier match.</small></p>

						<form class="autosave-tournoi"
							  data-csrf="<?= $_SESSION['csrf_token'] ?>">

							<label>🥇 Pays vainqueur :</label>
							<select name="pays_gagnant">
								<option value="">-- Choisir --</option>
								<?php foreach ($pays_participants as $p): ?>
									<option value="<?= $p['id'] ?>"
											<?= ($prono_tournoi['pays_gagnant'] ?? '') === $p['id'] ? 'selected' : '' ?>>
										<?= htmlspecialchars($p['nom']) ?>
									</option>
								<?php endforeach; ?>
							</select>

							<label>🌍 Pays du meilleur buteur :</label>
							<select name="pays_buteur" id="pays_buteur">
								<option value="">-- Choisir un pays --</option>
								<?php foreach ($pays_participants as $p): ?>
									<option value="<?= $p['id'] ?>"
											<?= $pays_buteur_selectionne === $p['id'] ? 'selected' : '' ?>>
										<?= htmlspecialchars($p['nom']) ?>
									</option>
								<?php endforeach; ?>
							</select>

							<label>⚽ Meilleur buteur :</label>
							<select name="meilleur_buteur" id="meilleur_buteur" <?= empty($pays_buteur_selectionne) ? 'disabled' : '' ?>>
								<option value="">-- Choisir un joueur --</option>
								<?php foreach ($joueurs as $j): ?>
									<option value="<?= $j['id'] ?>"
											data-pays="<?= htmlspecialchars($j['pays']) ?>"
											<?= ($prono_tournoi['meilleur_buteur'] ?? '') == $j['id'] ? 'selected' : '' ?>>
										<?= htmlspecialchars($j['nom']) ?>
									</option>
								<?php endforeach; ?>
							</select>

							<span class="statut-save"></span>
						</form>
					<?php endif; ?>
				</section>
				
				<section class="compte-container-update glassy"> 
					<h3>Règles de calcul des points</h3>
					<table class="table-regles">
						<thead><tr><th>Résultat</th><th>Points</th></tr></thead>
						<tbody style="text-align: left;">
							<tr><td>Score exact (ex: 2–1 prédit, 2–1 réel)</td><td>5 pts</td></tr>
							<tr><td>Bon résultat et bonne différence de buts</td><td>3 pts</td></tr>
							<tr><td>Bon résultat (bonne tendance / vainqueur)</td><td>2 pts</td></tr>
							<tr><td>Mauvais résultat</td><td>0 pt</td></tr>
							<tr><td>Tournoi : Pays vainqueur</td><td>50 pts</td></tr>
							<tr><td>Tournoi : Meilleur buteur</td><td>50 pts</td></tr>

							<tr><td colspan="2"><strong>Multiplicateurs selon la cote (si pronostic correct)</strong></td></tr>
							<tr><td>Victoire d’un favori (cote &lt; 1.75)</td><td>×1</td></tr>
							<tr><td>Match équilibré (cote entre 1.75 et 2.50)</td><td>×1.5</td></tr>
							<tr><td>Victoire d’un outsider (cote &gt; 2.50)</td><td>×2</td></tr>
							<tr><td>Match nul pronostiqué</td><td>×1.5</td></tr>

							<tr><td colspan="2"><em>Exemple : score exact avec une cote à 2.60 → 5 pts × 2 = 10 pts</em></td></tr>
							<tr><td colspan="2">Pour les matchs éliminatoires, le résultat pris en compte est celui à la 90ème minute.</td></tr>
						</tbody>
					</table>
				</section>
				
				<section class="compte-container-update glassy">
					<h3>Mes groupes</h3>

					<?php if (isset($_GET['success'])) : ?>
						<?php if ($_GET['success'] === 'groupe_cree') : ?>
							<div class="success">
								Groupe créé ! Code d'invitation : 
								<strong><?= htmlspecialchars($_GET['code']) ?></strong>
								<br><small>Partagez ce code pour que vos amis rejoignent votre groupe.</small>
							</div>
						<?php elseif ($_GET['success'] === 'groupe_rejoint') : ?>
							<div class="success">
								Vous avez rejoint le groupe <strong><?= htmlspecialchars($_GET['nom']) ?></strong> !
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<?php if (isset($_GET['error'])) : ?>
						<?php $errors = [
							'nom_vide'          => 'Le nom du groupe ne peut pas être vide.',
							'code_vide'         => 'Veuillez saisir un code de groupe.',
							'groupe_introuvable'=> 'Aucun groupe trouvé avec ce code.',
							'deja_membre'       => 'Vous êtes déjà membre de ce groupe.',
						]; ?>
						<div class="error"><?= $errors[$_GET['error']] ?? 'Une erreur est survenue.' ?></div>
					<?php endif; ?>

					<?php if (!empty($mes_groupes)) : ?>
						<table class="table-regles">
							<thead>
								<tr>
									<th>Nom</th>
									<th>Code</th>
									<th>Membres</th>
									<th>Rôle</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($mes_groupes as $g) : ?>
									<tr>
										<td><?= htmlspecialchars($g['nom']) ?></td>
										<td><code><?= htmlspecialchars($g['code']) ?></code></td>
										<td><?= $g['nb_membres'] ?></td>
										<td><?= $g['createur'] === $pseudo ? '👑 Créateur' : 'Membre' ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p>Vous n'appartenez à aucun groupe pour l'instant.</p>
					<?php endif; ?>

					<!-- Créer un groupe -->
					<form method="POST" action="groupe_action.php">
						<div class="formulaire">
							<h4>Créer un groupe</h4>
							<input type="hidden" name="action" value="creer_groupe">
							<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
							<div class="formulaire-champs">
								<input type="text" name="nom_groupe" placeholder="Nom du groupe" required>
								<button type="submit" class="btn-enregistrer">Créer</button>
							</div>
						</div>
					</form>

					<!-- Rejoindre un groupe -->
					<form method="POST" action="groupe_action.php">
						<div class="formulaire">
							<h4>Rejoindre un groupe</h4>
							<input type="hidden" name="action" value="rejoindre_groupe">
							<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
							<div class="formulaire-champs">
								<input type="text" name="code_groupe" placeholder="Code du groupe (6 caractères)" maxlength="6" style="text-transform:uppercase">
								<button type="submit" class="btn-enregistrer">Rejoindre</button>
							</div>
						</div>
					</form>
				</section>
				<section class="compte-container-update glassy">      

					<form method="POST">
						<div class="formulaire">
							<h3>Modifier mon email</h3>
							<div class="formulaire-champs">
								<input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
								<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
								<button type="submit" name="update_email" class="btn-enregistrer">Enregistrer</button>
							</div>
						</div>
					</form>

					<form method="POST">
						<div class="formulaire">
							<h3>Modifier mon mot de passe</h3>
							<div class="formulaire-champs">
								<input type="password" name="password" placeholder="Nouveau mot de passe" required>
								<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
								<button type="submit" name="update_password" class="btn-enregistrer">Enregistrer</button>
							</div>
						</div>
					</form>

					<form method="POST" enctype="multipart/form-data">
						<div class="formulaire">
							<h3>Changer d'avatar</h3>
							<p>Choisir parmi les avatars proposés :</p>
							<div class="avatar-grid">
								<?php for ($i = 1; $i <= 12; $i++): 
									$filename = "avatars/default/user$i.png";
								?>
									<label class="avatar-choice">
										<input type="radio" name="default_avatar" value="<?= htmlspecialchars($filename) ?>">
										<img src="<?= $filename ?>" alt="Avatar user<?= $i ?>">
									</label>
								<?php endfor; ?>
							</div>

							<p>Ou importer un avatar personnalisé :</p>
							<div class="formulaire-champs">
								<input type="file" name="avatar" accept="image/jpeg,image/png">
								<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
								<button type="submit" name="update_avatar" class="btn-enregistrer">Enregistrer</button>
							</div>
						</div>
					</form>
				</section>
				
				
				
		
			</div>
        </div>                
    </div>
            
</body>
<?php include 'script.php'; ?>
</html>