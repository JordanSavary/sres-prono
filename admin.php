<?php
session_start();
include 'includes/db.php';
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Première compétition pour l'ajout de match
$premiere_competition = $pdo->query("SELECT id FROM competitions ORDER BY id ASC LIMIT 1")->fetch();
$comp_id_default = $premiere_competition['id'] ?? null;

// Tous les matchs avec noms des pays ET forme depuis la table pays
$matchs = $pdo->query("
    SELECT m.*, 
           pa.nom AS nom_equipe_a, pa.forme AS forme_a,
           pb.nom AS nom_equipe_b, pb.forme AS forme_b
    FROM matchs m
    LEFT JOIN pays pa ON m.equipe_a = pa.id
    LEFT JOIN pays pb ON m.equipe_b = pb.id
    ORDER BY m.date_match ASC
")->fetchAll();

// Liste des pays pour les selects
$pays = $pdo->query("SELECT DISTINCT p.id, p.nom FROM pays p JOIN matchs m ON p.id = m.equipe_a OR p.id = m.equipe_b ORDER BY p.nom")->fetchAll();

$joueurs_admin = $pdo->query("SELECT j.*, p.nom AS nom_pays FROM joueurs j LEFT JOIN pays p ON j.pays = p.id ORDER BY p.nom, j.nom")->fetchAll();

$jour_selectionne = $_GET['jour'] ?? date('Y-m-d');

$tous_matchs_par_jour = [];
foreach ($matchs as $match) {
    $jour = date('Y-m-d', strtotime($match['date_match']));
    $tous_matchs_par_jour[$jour][] = $match;
}

$jours_disponibles = array_keys($tous_matchs_par_jour);
sort($jours_disponibles);

$formatter = new \IntlDateFormatter(
    'fr_FR',
    \IntlDateFormatter::FULL,
    \IntlDateFormatter::NONE,
    null, null,
    'EEEE d MMMM y'
);

function render_match_card($match, $pays) {
    ob_start();
    ?>
    <div class="match-card-admin">
        <p class="phase"><?= htmlspecialchars($match['phase'] ?? '') ?></p>
        <form class="admin-match-form"
              data-id-match="<?= $match['id'] ?>"
              data-csrf="<?= $_SESSION['csrf_token'] ?>">

            <div class="match-prono-admin">
                <div class="equipe">
                    <img src="./flag/<?= $match['equipe_a'] ?? 'XXX' ?>.png"
                         class="avatar-small admin-flag"
                         id="flag-a-<?= $match['id'] ?>">
                    <p class="equipe-nom"><?= htmlspecialchars($match['nom_equipe_a'] ?? 'Inconnu') ?></p>
                    <select class="admin-field select-equipe" 
                            data-field="equipe_a"
                            data-flag-target="flag-a-<?= $match['id'] ?>"
                            data-current="<?= $match['equipe_a'] ?>"
                            style="display:none;">
                    </select>
                    <div class="admin-forme" data-field="forme_a" data-pays="<?= $match['equipe_a'] ?>">
                        <?php
                        $forme_a = str_pad($match['forme_a'] ?? '', 5, 'D');
                        foreach (str_split($forme_a) as $r): ?>
                            <span class="pastille-admin <?= $r === 'W' ? 'forme-w' : ($r === 'L' ? 'forme-l' : 'forme-d') ?>"
                                  data-etat="<?= $r ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" class="admin-field select-score" data-field="score_a"
                           inputmode="numeric" pattern="[0-9]*" maxlength="2"
                           value="<?= $match['score_a'] ?? '' ?>" placeholder="-">
                </div>

                <div class="equipe">
                    <img src="./flag/<?= $match['equipe_b'] ?? 'XXX' ?>.png"
                         class="avatar-small admin-flag"
                         id="flag-b-<?= $match['id'] ?>">
                    <p class="equipe-nom"><?= htmlspecialchars($match['nom_equipe_b'] ?? 'Inconnu') ?></p>
                    <select class="admin-field select-equipe"
                            data-field="equipe_b"
                            data-flag-target="flag-b-<?= $match['id'] ?>"
                            data-current="<?= $match['equipe_b'] ?>"
                            style="display:none;">
                    </select>
                    <div class="admin-forme" data-field="forme_b" data-pays="<?= $match['equipe_b'] ?>">
                        <?php
                        $forme_b = str_pad($match['forme_b'] ?? '', 5, 'D');
                        foreach (str_split($forme_b) as $r): ?>
                            <span class="pastille-admin <?= $r === 'W' ? 'forme-w' : ($r === 'L' ? 'forme-l' : 'forme-d') ?>"
                                  data-etat="<?= $r ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" class="admin-field select-score" data-field="score_b"
                           inputmode="numeric" pattern="[0-9]*" maxlength="2"
                           value="<?= $match['score_b'] ?? '' ?>" placeholder="-">
                </div>
            </div>

            <div class="admin-cotes">
                <input type="number" class="admin-field" data-field="cote_a"
                       step="0.1" min="0" max="100" placeholder="Cote A"
                       value="<?= $match['cote_a'] ?? '' ?>">
                <input type="number" class="admin-field" data-field="cote_nul"
                       step="0.1" min="0" max="100" placeholder="Cote Nul"
                       value="<?= $match['cote_nul'] ?? '' ?>">
                <input type="number" class="admin-field" data-field="cote_b"
                       step="0.1" min="0" max="100" placeholder="Cote B"
                       value="<?= $match['cote_b'] ?? '' ?>">
            </div>

            <input type="datetime-local" class="admin-field select-date" data-field="date_match"
                   value="<?= date('Y-m-d\TH:i', strtotime($match['date_match'])) ?>">
            <span class="statut-save"></span>
            <a href="admin_actions.php?action=delete_match&id=<?= $match['id'] ?>"
               class="btn-supprimer"
               onclick="return confirm('Supprimer ce match ?')">
                <i class="fa-solid fa-trash"></i>
            </a>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'head.php'; ?>
<body>
<?php include 'header.php'; ?>

<div class="glassy pronostic-section" style="display:flex; gap:1rem; flex-wrap:wrap; justify-content:center;">
    <form method="post" action="admin_actions.php">
        <input type="hidden" name="action" value="actualiser_points">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <button type="submit" class="btn-enregistrer">⚡ Actualiser les points</button>
    </form>
    <form method="post" action="fetch_scores.php">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <button type="submit" class="btn-enregistrer">🔄 Rafraîchir les scores</button>
    </form>
</div>

<div class="glassy pronostic-section">
    
    <div class="barre-jours">
        <?php foreach ($jours_disponibles as $jour): ?>
            <?php
            $date_obj = new DateTime($jour);
            $num_jour = $date_obj->format('d');
            $est_selectionne = $jour === $jour_selectionne;
            $est_aujourd_hui = $jour === date('Y-m-d');
            ?>
            <a href="admin.php?jour=<?= $jour ?>"
            class="btn-jour <?= $est_selectionne ? 'actif' : '' ?> <?= $est_aujourd_hui ? 'aujourd-hui' : '' ?>"
            data-date="<?= $jour ?>">
                <?= $num_jour ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Matchs du jour sélectionné -->
    <?php if (isset($tous_matchs_par_jour[$jour_selectionne])): ?>
        <div class="liste-matchs">
            <?php foreach ($tous_matchs_par_jour[$jour_selectionne] as $match): ?>
                <?= render_match_card($match, $pays) ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Aucun match ce jour.</p>
    <?php endif; ?>
</div>

<div class="glassy pronostic-section">
    <h3>🏆 Clôturer les pronostics tournoi</h3>
    <form method="post" action="admin_actions.php">
        <input type="hidden" name="action" value="actualiser_points_tournoi">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <label>Pays vainqueur :</label>
        <select name="pays_vainqueur" required>
            <?php foreach ($pays as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['id']) ?> - <?= htmlspecialchars($p['nom']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Meilleur buteur :</label>
        <select name="id_buteur" required>
            <?php foreach ($joueurs_admin as $j): ?>
                <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['nom']) ?> (<?= htmlspecialchars($j['nom_pays']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-enregistrer"
                onclick="return confirm('Attention, cette action attribue les points définitifs !')">
            🏁 Attribuer les points tournoi
        </button>
    </form>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'points_updated'): ?>
<script>
    alert("✅ Points mis à jour avec succès.");
    if (history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('success');
        history.replaceState(null, '', url);
    }
</script>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'scores_updated'): ?>
<script>
    alert("🔄 Scores rafraîchis avec succès.");
    if (history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('success');
        history.replaceState(null, '', url);
    }
</script>
<?php endif; ?>

<script>
function toggleScores(el) {
    const content = el.nextElementSibling;
    const chevron = el.querySelector('.chevron');
    const isHidden = content.style.display === 'none';
    content.style.display = isHidden ? 'block' : 'none';
    chevron.classList.toggle('open', isHidden);
}

const PAYS = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'nom' => $p['nom']], $pays)) ?>;

const CYCLE  = { 'D': 'W', 'W': 'L', 'L': 'D' };
const CLASSES = { 'D': 'forme-d', 'W': 'forme-w', 'L': 'forme-l' };

function remplirSelect(select) {
    if (select.options.length > 0) return;
    const current = select.dataset.current;
    PAYS.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = `${p.id} - ${p.nom}`;
        if (p.id === current) opt.selected = true;
        select.appendChild(opt);
    });
}

// Sauvegarde d'un match (scores, cotes, date, équipes)
function sauvegarder(form) {
    const statut = form.querySelector('.statut-save');
    const data = {
        action:     'update_match_ajax',
        id_match:   form.dataset.idMatch,
        csrf_token: form.dataset.csrf
    };

    form.querySelectorAll('.admin-field').forEach(f => {
        if (f.tagName !== 'SELECT' || f.options.length > 0) {
            data[f.dataset.field] = f.value;
        }
    });

    statut.textContent = '⏳';
    fetch('admin_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams(data)
    })
    .then(r => r.json())
    .then(data => {
        statut.textContent = data.success ? '✅' : '❌';
        setTimeout(() => statut.textContent = '', 2000);
    })
    .catch(() => { statut.textContent = '❌'; });
}

// Sauvegarde de la forme d'un pays dans la table pays
function sauvegarderForme(form, codePays, forme) {
    const statut = form.querySelector('.statut-save');
    statut.textContent = '⏳';

    fetch('admin_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action:     'update_forme_pays',
            csrf_token: form.dataset.csrf,
            pays:       codePays,
            forme:      forme
        })
    })
    .then(r => r.json())
    .then(data => {
        statut.textContent = data.success ? '✅' : '❌';
        setTimeout(() => statut.textContent = '', 2000);
    })
    .catch(() => { statut.textContent = '❌'; });
}

document.querySelectorAll('.admin-match-form').forEach(form => {

    // Au clic sur le nom du pays, afficher le select
    form.querySelectorAll('.equipe-nom').forEach(nom => {
        nom.style.cursor = 'pointer';
        nom.title = 'Cliquer pour modifier';
        nom.addEventListener('click', () => {
            const equipe = nom.nextElementSibling;
            remplirSelect(equipe);
            nom.style.display = 'none';
            equipe.style.display = 'block';
            equipe.focus();
        });
    });

    // Mise à jour du drapeau + texte au changement d'équipe
    form.querySelectorAll('.select-equipe').forEach(select => {
        select.addEventListener('change', () => {
            const flag = document.getElementById(select.dataset.flagTarget);
            if (flag) flag.src = `./flag/${select.value}.png`;
            const nom = select.previousElementSibling;
            nom.textContent = select.options[select.selectedIndex].text.split(' - ')[1];
            select.style.display = 'none';
            nom.style.display = 'block';
            sauvegarder(form);
        });
    });

    // Cycle des pastilles de forme → sauvegarde dans table pays
    form.querySelectorAll('.pastille-admin').forEach(pastille => {
        pastille.style.cursor = 'pointer';
        pastille.addEventListener('click', () => {
            const etatActuel = pastille.dataset.etat;
            const nouvelEtat = CYCLE[etatActuel];
            pastille.classList.remove(CLASSES[etatActuel]);
            pastille.classList.add(CLASSES[nouvelEtat]);
            pastille.dataset.etat = nouvelEtat;

            const divForme = pastille.closest('.admin-forme');
            const codePays = divForme.dataset.pays;
            const forme    = [...divForme.querySelectorAll('.pastille-admin')]
                                .map(p => p.dataset.etat).join('');

            sauvegarderForme(form, codePays, forme);
        });
    });

    // Autosave sur les champs normaux (scores, cotes, date)
    form.querySelectorAll('.admin-field').forEach(field => {
        field.addEventListener('change', () => sauvegarder(form));
    });
});
</script>

</body>
<?php include 'script.php'; ?>
</html>