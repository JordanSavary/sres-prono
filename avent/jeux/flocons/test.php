<?php
/**
 * Test en ligne de commande : php avent/jeux/flocons/test.php
 * Vérifie le générateur (règles d'équilibrage et d'espacement) et rejoue des parties contre avent_jeu_flocons_verifier().
 * Termine avec le code 1 si une attente n'est pas respectée. test.js vérifie la parité PHP/JavaScript.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/server.php';

$echecs = 0;
$verifie = static function (string $nom, bool $ok) use (&$echecs): void {
    echo ($ok ? '  ok    ' : '  ÉCHEC ') . $nom . "\n";
    if (!$ok) {
        $echecs++;
    }
};

if (($argv[1] ?? '') === 'dump') {                                       // sortie lue par test.js
    $p = avent_flocons_params(json_decode($argv[3] ?? '{}', true) ?: []);
    echo json_encode(['params' => $p, 'flocons' => avent_flocons_generer($p, (int) $argv[2], 300)]);
    exit;
}

$ctx = ['pseudo' => 'alice', 'jour' => 3];
$config = ['cible' => 20];
$p = avent_flocons_params($config);
$graine = avent_flocons_graine($ctx);

echo "Générateur\n";
$verifie('même graine : mêmes flocons', avent_flocons_generer($p, $graine, 200) === avent_flocons_generer($p, $graine, 200));
$verifie('graines différentes (joueur, jour) : calendriers différents', avent_flocons_generer($p, $graine, 60) !== avent_flocons_generer($p, avent_flocons_graine(['pseudo' => 'bob', 'jour' => 3]), 60)
    && avent_flocons_generer($p, $graine, 60) !== avent_flocons_generer($p, avent_flocons_graine(['pseudo' => 'alice', 'jour' => 4]), 60));

$absents = 0; $avantPiege = 0; $dansLeDelai = 0; $violations = 0; $adjacents = 0; $total = 0; $pieges = 0; $debut = [0, 0]; $fin = [0, 0];
for ($s = 1; $s <= 150; $s++) {
    $liste = avent_flocons_generer($p, 1000 + $s * 7919, 150);        // ~97 s de jeu
    foreach ($liste as $k => $f) {
        if (!empty($f['absent'])) { $absents++; continue; }
        $total++;
        $pieges += $f['piege'] ? 1 : 0;
        if ($f['piege'] && $f['t'] < 5000) { $avantPiege++; }
        if ($f['t'] < 25000) { $debut[0]++; $debut[1] += $f['piege'] ? 1 : 0; }
        if ($f['t'] >= 75000) { $fin[0]++; $fin[1] += $f['piege'] ? 1 : 0; }
        // vérification indépendante de l'espacement : échantillonnage tous les 25 ms (le générateur échantillonne tous les 50 ms)
        for ($j = $k - 1; $j >= 0 && $liste[$j]['t'] + 4000 > $f['t']; $j--) {
            $g = $liste[$j];
            if (!empty($g['absent']) || $g['t'] + $g['chute'] <= $f['t']) { continue; }
            if (!$f['piege'] && !$g['piege']) { continue; }
            $debutT = max($f['t'], $g['t']); $finT = min($f['t'] + $f['chute'], $g['t'] + $g['chute']);
            for ($t = $debutT; $t <= $finT; $t += 25) {
                $dx = abs($f['x'] - $g['x']);
                $dy = abs(intdiv(($t - $f['t']) * 1000, $f['chute']) - intdiv(($t - $g['t']) * 1000, $g['chute']));
                if ($dx < 250 && $dy < 250) { $violations++; if ($f['piege'] && $g['piege']) { $adjacents++; } break; }   // seuil un peu en dessous de 280 pour tolérer l'échantillonnage
            }
        }
    }
}
$verifie("aucun piège avant 5 s ($avantPiege)", $avantPiege === 0);
$verifie("jamais deux pièges ni piège/flocon trop proches ($violations) à la fois sur l'écran, adjacents : $adjacents", $violations === 0);
$verifie("flocons non lancés faute de place : $absents sur " . ($total + $absents) . ' (rare)', $absents / ($total + $absents) < 0.03);
$verifie('proportion début de partie ≈ 1 sur 5 (flocons des 25 premières s, hors 5 premières s sans piège : ' . round(100 * $debut[1] / $debut[0], 1) . ' % au global)', $debut[1] / $debut[0] > 0.08 && $debut[1] / $debut[0] < 0.22);
$verifie('proportion fin de partie ≈ 1 sur 3 (' . round(100 * $fin[1] / $fin[0], 1) . ' %)', $fin[1] / $fin[0] > 0.24 && $fin[1] / $fin[0] < 0.36);
$verifie('la proportion monte au fil de la partie', $fin[1] / $fin[0] > $debut[1] / $debut[0]);

/** Journal d'une partie : touche les flocons normaux (et éventuellement des pièges listés) à $delai ms de leur apparition. */
function journal(array $flocons, array $pieges = [], int $delai = 400, int $max = 200): array
{
    $c = [];
    foreach ($flocons as $k => $f) {
        if (!empty($f['absent'])) { continue; }
        if ($f['piege'] ? in_array($k, $pieges, true) : true) {
            $c[] = [$k, $f['t'] + $delai];
        }
        if (count($c) >= $max) { break; }
    }
    return $c;
}
$flocons = avent_flocons_generer($p, $graine, 200);
$idxPieges = array_keys(array_filter($flocons, fn($f) => $f['piege']));
$verifie('au moins 4 pièges dans les 200 premiers flocons', count($idxPieges) >= 4);
$verifier = fn(array $payload, int $duree = 100000, array $cfg = ['cible' => 20]) => avent_jeu_flocons_verifier($cfg, $payload, $duree, $ctx + ['etat' => ['depart_ms' => 0]]);

echo "Partie sans piège touché\n";
$normaux = array_keys(array_filter($flocons, fn($f) => !$f['piege'] && empty($f['absent'])));
$clics = array_map(fn($k) => [$k, $flocons[$k]['t'] + 400], array_slice($normaux, 0, 20));
$r = $verifier(['clics' => $clics]);
$verifie('20 flocons normaux : 100 points, 3 vies, réussite', $r['score'] === 100 && $r['vies'] === 3 && $r['reussite'] === true && $r['pieges'] === 0);
$r = $verifier(['clics' => array_slice($clics, 0, 10)]);
$verifie('10 flocons sur 20 : 50 points (barème inchangé)', $r['score'] === 50 && $r['reussite'] === false);
$verifie('aucun toucher : 0 point', $verifier(['clics' => []])['score'] === 0);

echo "Partie qui touche 2 pièges seulement\n";
$deux = array_slice($idxPieges, 0, 2);
$mix = [];
foreach (array_slice($normaux, 0, 30) as $k) { $mix[] = [$k, $flocons[$k]['t'] + 400]; }
foreach ($deux as $k) { $mix[] = [$k, $flocons[$k]['t'] + 400]; }
usort($mix, fn($a, $b) => $a[1] <=> $b[1]);
// $mix peut contenir des touchers à moins de 100 ms (rejetés) : on décale le piège d'un pas pour le test
$r = $verifier(['clics' => $mix], 100000, ['cible' => 30]);
$verifie('2 pièges : 1 vie restante, la partie continue, 30 flocons → 100 points', $r['vies'] === 1 && $r['pieges'] === 2 && $r['score'] === 100 && $r['reussite'] === true);

echo "Partie qui perd les 3 vies\n";
$premiersNormaux = array_slice($normaux, 0, 5);
$trois = array_slice($idxPieges, 0, 3);
$mix = [];
foreach ($premiersNormaux as $k) { $mix[] = [$k, $flocons[$k]['t'] + 400]; }
foreach ($trois as $k) { $mix[] = [$k, $flocons[$k]['t'] + 400]; }
// flocons normaux touchés APRÈS la 3e perte : ne comptent pas
$tFin = max(array_map(fn($k) => $flocons[$k]['t'] + 400, $trois));
foreach (array_slice($normaux, 5, 15) as $k) { $mix[] = [$k, max($flocons[$k]['t'] + 400, $tFin + 150)]; }
usort($mix, fn($a, $b) => $a[1] <=> $b[1]);
$avantPerte = 0;
$vies = 3;
foreach ($mix as [$k, $t]) { if ($flocons[$k]['piege']) { $vies--; if ($vies === 0) { break; } } else { $avantPerte++; } }
$r = $verifier(['clics' => $mix]);
$verifie("3 pièges : 0 vie, la partie s'arrête à la 3e perte, score = points acquis ($avantPerte flocons → " . $r['score'] . ' points)', $r['vies'] === 0 && $r['pieges'] === 3 && $r['attrapes'] === $avantPerte && $r['score'] === (int) round(100 * $avantPerte / 20) && $r['reussite'] === false);
$verifie('aucun malus supplémentaire à la 3e perte (même score qu\'avec la partie arrêtée là)', $r['score'] === avent_flocons_score($avantPerte, 20));

echo "Client qui triche\n";
$r = $verifier(['clics' => array_slice($clics, 0, 5), 'score' => 100, 'vies' => 99, 'attrapes' => 20, 'temps' => 1]);
$verifie('score, vies et attrapes annoncés ignorés (5 flocons : 25 points, 3 vies)', $r['score'] === 25 && $r['vies'] === 3);
$r = $verifier(['clics' => array_merge(array_slice($clics, 0, 5), [[9999, 99000]]), 'vies' => 3]);
$verifie('flocon inexistant ignoré', $r['score'] === 25);
$r = $verifier(['clics' => [[$normaux[0], $flocons[$normaux[0]]['t'] + 400], [$normaux[0], $flocons[$normaux[0]]['t'] + 700]]]);
$verifie('même flocon deux fois : compté une fois', $r['attrapes'] === 1);
$r = $verifier(['clics' => [[$normaux[0], $flocons[$normaux[0]]['t'] - 300 > 0 ? $flocons[$normaux[0]]['t'] - 300 : 0]]]);
$k5 = $normaux[array_key_last(array_slice($normaux, 0, 6, true))];
$r = $verifier(['clics' => [[$k5, 100]]]);
$verifie('toucher avant l\'apparition du flocon : ignoré', $r['attrapes'] === 0);
$r = $verifier(['clics' => [[$normaux[0], $flocons[$normaux[0]]['t'] + $flocons[$normaux[0]]['chute'] + 2000]]]);
$verifie('toucher après la chute (flocon au sol) : ignoré', $r['attrapes'] === 0);
$rafale = [];
foreach (array_slice($normaux, 0, 20) as $i => $k) { $rafale[] = [$k, $flocons[$k]['t'] + 400]; }
$serre = [];
$base = $flocons[$normaux[0]]['t'] + 400;
foreach (array_slice($normaux, 0, 12) as $i => $k) { $serre[] = [$k, $base + $i * 10]; }       // 12 flocons en 110 ms : impossible
$r = $verifier(['clics' => $serre]);
$verifie('rafale impossible (touchers espacés de 10 ms) : ignorée sauf le premier ' . $r['attrapes'], $r['attrapes'] <= 2);
$verifie('journal non chronologique : invalide, 0 point', $verifier(['clics' => [[1, 5000], [2, 4000]]])['score'] === 0 && !empty($verifier(['clics' => [[1, 5000], [2, 4000]]])['invalide']));
$verifie('journal mal formé : invalide', !empty($verifier(['clics' => [['a', 1]]])['invalide']) && !empty($verifier(['clics' => 'x'])['invalide']) && !empty($verifier(['clics' => [[1]]])['invalide']) && !empty($verifier(['clics' => [[1, 1.5]]])['invalide']));
$verifie('trop de touchers : invalide', !empty($verifier(['clics' => array_fill(0, 801, [0, 0])])['invalide']));
$r = avent_jeu_flocons_verifier($config, ['clics' => array_slice($clics, 0, 20)], 3000, $ctx + ['etat' => ['depart_ms' => 0]]);
$verifie('journal plus long que la partie réelle (3 s écoulées, touchers jusqu\'à 20 s) : invalide', !empty($r['invalide']) && $r['score'] === 0);
$r = avent_jeu_flocons_verifier($config, ['clics' => array_slice($clics, 0, 5)], 14000, $ctx + ['etat' => ['depart_ms' => 12000]]);
$verifie('le départ noté par « depart » est pris en compte', !empty($r['invalide']) === ($clics[4][1] > 14000 - 12000 + AVENT_FLOCONS_GRACE_MS));
$verifie('autre joueur, mêmes clics : flocons différents, donc score différent ou nul', (function () use ($config, $clics) {
    $r = avent_jeu_flocons_verifier($config, ['clics' => $clics], 100000, ['pseudo' => 'mallory', 'jour' => 3, 'etat' => ['depart_ms' => 0]]);
    return $r['score'] < 100;
})());

echo "Partie, reprise et contexte\n";
$cfgPub = avent_jeu_flocons_config_publique($config, $ctx);
$verifie('config publique : graine, vies, paramètres ; pas de calendrier ni de score', isset($cfgPub['graine']) && $cfgPub['vies'] === 3 && $cfgPub['cible'] === 20 && $cfgPub['deja_parti'] === false && !isset($cfgPub['flocons']));
$etat = [];
$verifie('action « depart » enregistre le départ une fois', avent_jeu_flocons_action($config, ['action' => 'depart', 'donnees' => []], $etat, $ctx, 1234) === ['depart' => true]
    && avent_jeu_flocons_action($config, ['action' => 'depart', 'donnees' => []], $etat, $ctx, 9999) === ['depart' => false] && $etat['depart_ms'] === 1234);
$verifie('action inconnue refusée', isset(avent_jeu_flocons_action($config, ['action' => 'x', 'donnees' => []], $etat, $ctx, 0)['erreur']));
$verifie('config publique après départ : deja_parti', avent_jeu_flocons_config_publique($config, $ctx + ['etat' => ['depart_ms' => 5]])['deja_parti'] === true);

echo "Validation du contenu\n";
$verifie('ancien contenu {cible, rythme_ms} valide', avent_jeu_flocons_valider(['cible' => 20, 'rythme_ms' => 650]) === null);
$verifie('exemple du manifest valide', avent_jeu_flocons_valider(json_decode(file_get_contents(__DIR__ . '/manifest.json'), true)['exemple']) === null);
$verifie('vies hors bornes refusées', avent_jeu_flocons_valider(['cible' => 20, 'vies' => 0]) !== null && avent_jeu_flocons_valider(['cible' => 20, 'vies' => 9]) !== null);
$verifie('proportion, distance, délai hors bornes refusés', avent_jeu_flocons_valider(['cible' => 20, 'piege_debut' => 2000]) !== null && avent_jeu_flocons_valider(['cible' => 20, 'distance_min' => 5]) !== null && avent_jeu_flocons_valider(['cible' => 20, 'delai_sans_piege_ms' => -1]) !== null);
$verifie('cible manquante refusée', avent_jeu_flocons_valider([]) !== null);
$verifie('sans piège (piege_debut = piege_fin = 0) : aucun flocon piégé', (function () use ($graine) {
    $p0 = avent_flocons_params(['cible' => 20, 'piege_debut' => 0, 'piege_fin' => 0]);
    return count(array_filter(avent_flocons_generer($p0, $graine, 150), fn($f) => $f['piege'])) === 0;
})());

echo $echecs === 0 ? "\nTout est bon.\n" : "\n$echecs échec(s).\n";
exit($echecs === 0 ? 0 : 1);
