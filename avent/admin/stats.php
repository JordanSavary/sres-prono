<?php
require_once __DIR__ . '/../includes/admin.php';

avent_exiger_admin();

$pdo = avent_pdo();

// Parties terminées, hors tests et hors joueurs exclus.
$parties = $pdo->query(
    'SELECT pseudo, jour, essai, score, points, reussite, duree_ms, fin
     FROM avent_parties
     WHERE test = 0 AND statut = \'termine\' AND pseudo NOT IN (SELECT pseudo FROM avent_exclus)
     ORDER BY fin'
)->fetchAll();

$parJour    = [];
$parJoueur  = [];
foreach ($parties as $p) {
    $jour = (int) $p['jour'];
    $parJour[$jour][] = $p;
    $parJoueur[$p['pseudo']][] = $p;
}

$nbInscrits = (int) $pdo->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
$nbActifs   = count($parJoueur);
$jours      = avent_jours();

$lignes = [];
$maxJoueurs = 1;
for ($n = 1; $n <= AVENT_NB_JOURS; $n++) {
    $liste = $parJour[$n] ?? [];
    $joueurs = count(array_unique(array_column($liste, 'pseudo')));
    $reussies = array_sum(array_map(fn ($p) => (int) $p['reussite'], $liste));
    $finJour = avent_date_jour($n)->modify('+1 day');
    $rattrapage = 0;
    foreach ($liste as $p) {
        $fin = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $p['fin']);
        if ($fin && $fin >= $finJour) {
            $rattrapage++;
        }
    }
    $lignes[$n] = [
        'joueurs'    => $joueurs,
        'parties'    => count($liste),
        'taux'       => $liste ? round(100 * $reussies / count($liste)) : null,
        'score'      => $liste ? round(array_sum(array_column($liste, 'score')) / count($liste)) : null,
        'temps'      => $liste ? (int) round(array_sum(array_column($liste, 'duree_ms')) / count($liste)) : null,
        'rattrapage' => $rattrapage,
    ];
    $maxJoueurs = max($maxJoueurs, $joueurs);
}

// Résumé par joueur
$resume = [];
foreach ($parJoueur as $pseudo => $liste) {
    $meilleurs = [];
    foreach ($liste as $p) {
        $j = (int) $p['jour'];
        if (!isset($meilleurs[$j]) || $p['points'] > $meilleurs[$j]) {
            $meilleurs[$j] = (int) $p['points'];
        }
    }
    $resume[$pseudo] = [
        'cases'  => count($meilleurs),
        'total'  => array_sum($meilleurs),
        'derniere' => end($liste)['fin'],
        'parties' => $liste,
    ];
}
uasort($resume, fn ($a, $b) => $b['total'] <=> $a['total']);

avent_entete('Statistiques', 'page-admin');
avent_admin_nav('stats');
?>
<h1 class="page-titre">Statistiques</h1>

<section class="carte">
    <p><strong><?= $nbInscrits ?></strong> comptes sur le site · <strong><?= $nbActifs ?></strong> joueur<?= $nbActifs > 1 ? 's' : '' ?>
        ayant terminé au moins une partie · <strong><?= count($parties) ?></strong> parties terminées.</p>
    <p class="aide">Les parties de test et les joueurs exclus ne sont pas comptés.</p>
</section>

<h2>Par jour</h2>
<div class="defile">
    <table class="tableau">
        <thead>
            <tr><th>Jour</th><th>Joueurs</th><th>Participation</th><th>Parties</th><th>Réussite</th><th>Score moyen</th><th>Temps moyen</th><th>En rattrapage</th></tr>
        </thead>
        <tbody>
        <?php foreach ($lignes as $n => $l) : ?>
            <tr>
                <td><strong><?= $n ?></strong> <?= !empty($jours[$n]['est_boss']) ? '<span class="tag tag-boss">★</span>' : '' ?></td>
                <td><?= $l['joueurs'] ?></td>
                <td><span class="barre-stat" style="width: <?= (int) round(140 * $l['joueurs'] / $maxJoueurs) ?>px"></span></td>
                <td><?= $l['parties'] ?></td>
                <td><?= $l['taux'] === null ? '–' : $l['taux'] . ' %' ?></td>
                <td><?= $l['score'] === null ? '–' : $l['score'] . ' / 100' ?></td>
                <td><?= $l['temps'] === null ? '–' : h(avent_formater_duree($l['temps'])) ?></td>
                <td><?= $l['rattrapage'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2>Par joueur</h2>
<?php if (!$resume) : ?>
    <p class="carte vide">Aucune partie terminée pour le moment.</p>
<?php else : ?>
    <div class="defile">
        <table class="tableau">
            <thead><tr><th>Joueur</th><th>Cases jouées</th><th>Points</th><th>Dernière partie</th></tr></thead>
            <tbody>
            <?php foreach ($resume as $pseudo => $r) : ?>
                <tr>
                    <td>
                        <details>
                            <summary><?= h($pseudo) ?></summary>
                            <ul>
                            <?php foreach ($r['parties'] as $p) : ?>
                                <li>Jour <?= (int) $p['jour'] ?>, essai <?= (int) $p['essai'] ?> :
                                    <?= (int) $p['score'] ?>/100 (<?= (int) $p['points'] ?> pts) le <?= h(substr($p['fin'], 0, 16)) ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </details>
                    </td>
                    <td><?= $r['cases'] ?> / <?= AVENT_NB_JOURS ?></td>
                    <td><?= $r['total'] ?></td>
                    <td><?= h(substr($r['derniere'], 0, 16)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php avent_pied(); ?>
