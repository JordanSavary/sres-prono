<?php
require_once __DIR__ . '/../includes/admin.php';

$admin = avent_exiger_admin();

// --- Mode test (propre à la session de l'administrateur) ------------------------------
if (avent_admin_post()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'test_activer') {
        $mode = ($_POST['mode'] ?? 'tout') === 'date' ? 'date' : 'tout';
        $date = (string) ($_POST['date'] ?? '');
        $valide = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($mode === 'date' && (!$valide || $valide->format('Y-m-d') !== $date)) {
            avent_flash('Date simulée invalide.', 'erreur');
        } else {
            $_SESSION['avent_test'] = ['actif' => true, 'mode' => $mode, 'date' => $mode === 'date' ? $date : null];
            avent_flash('Mode test activé : tes parties ne comptent pas.', 'ok');
        }
    } elseif ($action === 'test_desactiver') {
        unset($_SESSION['avent_test']);
        avent_flash('Mode test désactivé.', 'ok');
    } elseif ($action === 'tester_jour') {
        $numero = (int) ($_POST['jour'] ?? 0);
        if (avent_jour($numero)) {
            $_SESSION['avent_test'] = ['actif' => true, 'mode' => 'tout', 'date' => null];
            avent_rediriger('../jour.php?j=' . $numero);
        }
    }
    avent_rediriger('index.php');
}

$jours    = avent_jours();
$jeux     = avent_liste_jeux();
$parties  = [];
foreach (avent_pdo()->query('SELECT jour, COUNT(*) AS n FROM avent_parties WHERE test = 0 GROUP BY jour') as $ligne) {
    $parties[(int) $ligne['jour']] = (int) $ligne['n'];
}
$test = avent_test();
$maintenant = new DateTimeImmutable('now');
$etiquettes = [
    'verrouille' => 'Verrouillée', 'disponible' => 'Ouverte',
    'rattrapable' => 'Rattrapage', 'expire' => 'Expirée',
];

avent_entete('Administration', 'page-admin');
avent_admin_nav('index');
?>
<h1 class="page-titre">Planning</h1>

<section class="carte">
    <h2>Mode test</h2>
    <p>Pour jouer toutes les cases avant le lancement. Le mode ne concerne que ta session : les joueurs ne voient rien.
        Tes parties de test sont exclues du classement et des statistiques, et ne consomment pas d'essai.</p>
    <?php if ($test['actif']) : ?>
        <p><span class="tag tag-boss">Actif</span>
            <?= $test['mode'] === 'tout' ? 'Toutes les cases sont débloquées.' : 'Date simulée : ' . h($test['date']) . '.' ?></p>
        <form method="post" class="petit-formulaire">
            <?= avent_champ_csrf() ?>
            <input type="hidden" name="action" value="test_desactiver">
            <button type="submit">Désactiver le mode test</button>
        </form>
    <?php else : ?>
        <form method="post" class="formulaire">
            <?= avent_champ_csrf() ?>
            <input type="hidden" name="action" value="test_activer">
            <div class="ligne">
                <div>
                    <label for="mode">Mode</label>
                    <select name="mode" id="mode">
                        <option value="tout">Débloquer toutes les cases</option>
                        <option value="date">Simuler une date</option>
                    </select>
                </div>
                <div>
                    <label for="date">Date simulée</label>
                    <input type="date" name="date" id="date" value="<?= h(avent_debut()->format('Y-m-d')) ?>">
                </div>
            </div>
            <p><button type="submit" class="bouton">Activer le mode test</button></p>
        </form>
    <?php endif; ?>
    <?php if ($maintenant >= avent_debut()) : ?>
        <p class="avertissement-bloc">Le calendrier est ouvert aux joueurs : pense à désactiver le mode test après tes vérifications.</p>
    <?php endif; ?>
</section>

<div class="defile">
    <table class="tableau">
        <thead>
            <tr><th>Jour</th><th>Date</th><th>Titre</th><th>Jeu</th><th>Boss</th><th>Parties</th><th>État</th><th></th></tr>
        </thead>
        <tbody>
        <?php for ($n = 1; $n <= AVENT_NB_JOURS; $n++) :
            $j = $jours[$n] ?? null;
            if (!$j) { continue; }
            $statutReel = avent_statut_jour($n, $maintenant, true);
            $jeuNom = $j['jeu'] && isset($jeux[$j['jeu']]) ? $jeux[$j['jeu']]['nom'] : null;
            ?>
            <tr>
                <td><strong><?= $n ?></strong></td>
                <td><?= h(avent_date_fr(avent_date_jour($n))) ?></td>
                <td><?= h($j['titre']) ?></td>
                <td>
                    <?php if ($jeuNom) : ?>
                        <?= h($jeuNom) ?>
                    <?php elseif ($j['jeu']) : ?>
                        <span class="tag tag-alerte">Module « <?= h($j['jeu']) ?> » absent</span>
                    <?php else : ?>
                        <span class="tag tag-alerte">Aucun jeu</span>
                    <?php endif; ?>
                </td>
                <td><?= $j['est_boss'] ? '<span class="tag tag-boss">★ boss</span>' : '' ?></td>
                <td><?= $parties[$n] ?? 0 ?></td>
                <td><span class="tag <?= $statutReel === 'disponible' ? 'tag-ok' : '' ?>"><?= h($etiquettes[$statutReel] ?? $statutReel) ?></span></td>
                <td>
                    <a href="jour.php?j=<?= $n ?>">Modifier</a>
                    <?php if ($jeuNom) : ?>
                        <form method="post" class="petit-formulaire">
                            <?= avent_champ_csrf() ?>
                            <input type="hidden" name="action" value="tester_jour">
                            <input type="hidden" name="jour" value="<?= $n ?>">
                            <button type="submit">Tester</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endfor; ?>
        </tbody>
    </table>
</div>
<p class="legende">Les jeux disponibles sont les modules du dossier <code>avent/jeux/</code> : <?= $jeux ? h(implode(', ', array_column($jeux, 'nom'))) : 'aucun' ?>.</p>
<?php avent_pied(); ?>
