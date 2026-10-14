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

// Exemple : Affichage des compétitions et matchs disponibles
$competitions = $pdo->query("SELECT * FROM competitions ORDER BY nom")->fetchAll();

$sql = "SELECT 
            m.id, m.competition_id, m.date_match, m.score_a, m.score_b, m.phase, m.cote_a, m.cote_nul, m.cote_b,
            pa.nom AS nom_equipe_a, pa.forme AS forme_a,
            pb.nom AS nom_equipe_b, pb.forme AS forme_b,
            m.equipe_a, m.equipe_b,
            p.prono_a, p.prono_b, p.points
        FROM matchs m
        LEFT JOIN pays pa ON m.equipe_a = pa.id
        LEFT JOIN pays pb ON m.equipe_b = pb.id
        LEFT JOIN pronostics p 
            ON m.id = p.id_match AND p.id_utilisateur = ?
        ORDER BY m.date_match ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$pseudo]);
$matchs = $stmt->fetchAll();

$formatter = new \IntlDateFormatter(
    'fr_FR', 
    \IntlDateFormatter::FULL, 
    \IntlDateFormatter::NONE,
    null,
    null,
    'EEEE d MMMM y'
);

?>

<!DOCTYPE html>
<html lang="fr">
<?php include 'head.php'; 

?>

<body>
    <?php include 'header.php'; ?>

    <main class="glassy pronostic-section">
        <h2>Mes Pronostics</h2>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'match_started') : ?>
            <div class="error">Le match a déjà commencé, vous ne pouvez plus enregistrer de pronostic.</div>
        <?php endif; ?>

        <?php

        $matchs_presents = [];
        $matchs_passes = [];

        $now = time();
        $deux_jours = 24 * 60 * 60;

        foreach ($matchs as $match) {
            $date_match = strtotime($match['date_match']);
            if ($date_match < $now && ($now - $date_match) > $deux_jours) {
                $matchs_passes[] = $match;
            } else {
                $matchs_presents[] = $match;
            }
        }

        function regrouper_par_jour($matchs) {
            $groupes = [];
            foreach ($matchs as $match) {
                $jour = date('l j F Y', strtotime($match['date_match']));
                $groupes[$jour][] = $match;
            }
            return $groupes;
        }
		
		function render_forme($forme) {
			if (!$forme) return '';
			$html = '<div class="forme-pastilles">';
			foreach (str_split($forme) as $r) {
				$classe = match($r) {
					'W' => 'forme-w',
					'D' => 'forme-d',
					'L' => 'forme-l',
					default => 'forme-inconnu'
				};
				$html .= "<span class='pastille {$classe}'></span>";
			}
			$html .= '</div>';
			return $html;
		}
		
		function classe_points($points) {
			if ($points === null) return 'points-neutre';
			if ($points == 0) return 'points-nul';
			if ($points <= 3) return 'points-moyen';
			return 'points-bien';
		}

        $matchs_presents_par_jour = regrouper_par_jour($matchs_presents);
        $matchs_passes_par_jour = regrouper_par_jour($matchs_passes);

        if (!empty($matchs_passes_par_jour)) : ?>
            <div id="section-passes" style="display:none;">
                <?php foreach ($matchs_passes_par_jour as $jour => $matchs_du_jour) : ?>
                    <h3 class="jour"><?php echo ucfirst($formatter->format(new DateTime($matchs_du_jour[0]['date_match']))); ?></h3>
                    <div class="liste-matchs">
                        <?php foreach ($matchs_du_jour as $match) : ?>
                            <div class="match-card">
                                <div class="match-prono-form">
                                    <div class="match-prono">
                                        <div class="equipe">
                                            <img src="./flag/<?= $match['equipe_a'] ?? 'XXX' ?>.png" alt="<?= htmlspecialchars($match['nom_equipe_a'] ?? 'Inconnu') ?>" class="avatar-small">
											<p><?= htmlspecialchars($match['nom_equipe_a'] ?? 'Inconnu') ?></p>
                                        </div>
										<div class="score-match">
											<span class='score_final'><?= $match['score_a'] ?></span>
										</div>
										<div class="score-match">
											<span class='score_final'><?= $match['score_b'] ?></span>
										</div>
                                        <div class="equipe">
                                            <img src="./flag/<?= $match['equipe_b'] ?? 'XXX' ?>.png" alt="<?= htmlspecialchars($match['nom_equipe_b'] ?? 'Inconnu') ?>" class="avatar-small">
											<p><?= htmlspecialchars($match['nom_equipe_b'] ?? 'Inconnu') ?></p>
                                        </div>
                                    </div>
                                </div>
								<div class="bas-match">
                                        <span class="match-joue <?= classe_points($match['points']) ?>"><?= $match['prono_a'] ?> - <?= $match['prono_b'] ?> / <?= $match['points'] ?> <?= ($match['points'] == 1) ? 'point' : 'points' ?></span>
                                    </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <p><button type="button" id="toggle-passes" class="btn-enregistrer">Afficher plus</button></p>
        <?php endif; ?>

        <?php foreach ($matchs_presents_par_jour as $jour => $matchs_du_jour) : ?>
            <h3 class="jour"><?php echo ucfirst($formatter->format(new DateTime($matchs_du_jour[0]['date_match']))); ?></h3>
            <div class="liste-matchs">
                <?php foreach ($matchs_du_jour as $match) : ?>
                    <div class="match-card">
                        <p class="phase"><?= $match['phase'] ?> - <?= date('H:i', strtotime($match['date_match'])) ?></p>
                        <?php if (strtotime($match['date_match']) < time()) : ?>
                            <div class="match-prono-form">
                                <div class="match-prono">
                                    <div class="equipe">
                                        <img src="./flag/<?= $match['equipe_a'] ?? 'XXX' ?>.png" alt="<?= htmlspecialchars($match['nom_equipe_a'] ?? 'Inconnu') ?>" class="avatar-small">
										<p><?= htmlspecialchars($match['nom_equipe_a'] ?? 'Inconnu') ?></p>
                                    </div>
									<div class="scores-et-prono-et-cotes">
										<div class="scores-et-prono">
											<div class="score-match">
												<span class='score_final'><?= $match['score_a'] ?></span>
											</div>
											<div class="score-match">
												<span class='score_final'><?= $match['score_b'] ?></span>
											</div>
										</div>
									</div>
									<div class="equipe">
                                        <img src="./flag/<?= $match['equipe_b'] ?? 'XXX' ?>.png" alt="<?= htmlspecialchars($match['nom_equipe_b'] ?? 'Inconnu') ?>" class="avatar-small">
										<p><?= htmlspecialchars($match['nom_equipe_b'] ?? 'Inconnu') ?></p>
                                    </div>
                                </div>
                            </div>
							<div class="bas-match">
                                    <span class="match-joue <?= classe_points($match['points']) ?>"><?= $match['prono_a'] ?> - <?= $match['prono_b'] ?> / <?= $match['points'] ?> <?= ($match['points'] == 1) ? 'point' : 'points' ?></span>
                                </div>
                        <?php else : ?>
							<form class="match-prono-form autosave-form"
								  data-id-match="<?= $match['id'] ?>"
								  data-csrf="<?= $_SESSION['csrf_token'] ?>">
								<div class="match-prono">
									<div class="equipe">
										<img src="./flag/<?= $match['equipe_a'] ?? 'XXX' ?>.png" alt="<?= htmlspecialchars($match['nom_equipe_a'] ?? 'Inconnu') ?>" class="avatar-small">
										<p><?= htmlspecialchars($match['nom_equipe_a'] ?? 'Inconnu') ?></p>
										<?= render_forme($match['forme_a']) ?>
									</div>
									<div class="scores-et-prono-et-cotes">
										<div class="scores-et-prono">
											<input type="number" name="prono_a" min="0" max="20" value="<?= $match['prono_a'] ?? '' ?>" required>
											<input type="number" name="prono_b" min="0" max="20" value="<?= $match['prono_b'] ?? '' ?>" required>
										</div>
									<?php if ($match['cote_a'] || $match['cote_nul'] || $match['cote_b']): ?>
										<div class="cotes">
											<span><?= $match['cote_a'] ?></span>
											<span><?= $match['cote_nul'] ?></span>
											<span><?= $match['cote_b'] ?></span>
										</div>
									<?php endif; ?>
									</div>

									<div class="equipe">
										<img src="./flag/<?= $match['equipe_b'] ?? 'XXX' ?>.png" alt="<?= htmlspecialchars($match['nom_equipe_b'] ?? 'Inconnu') ?>" class="avatar-small">
										<p><?= htmlspecialchars($match['nom_equipe_b'] ?? 'Inconnu') ?></p>
										<?= render_forme($match['forme_b']) ?>
									</div>
								</div>
								<div class="bas-match">
									<span class="statut-save"></span>
								</div>
							</form>
						<?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

    </main>
                    
</body>
<?php include 'script.php'; ?>
</html>








