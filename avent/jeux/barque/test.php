<?php
/**
 * Test en ligne de commande : php avent/jeux/barque/test.php
 * Rejoue des journaux types et affiche le score. Termine avec le code 1 si une attente n'est pas respectée.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/server.php';

/** Journal régulier : $parSeconde impulsions par seconde, la première au tick $depart. */
function journal(float $parSeconde, int $depart = 30, int $fin = AVENT_BARQUE_TICKS): array
{
    $j = [];
    for ($i = 0; ; $i++) {
        $t = $depart + intdiv((int) round($i * 60 * 1000 / $parSeconde), 1000);
        if ($t >= $fin) {
            break;
        }
        $j[] = $t;
    }
    return $j;
}

/** Journal « humain » : rythme moyen avec une gigue déterministe de ±25 %. */
function journalHumain(float $parSeconde, int $graine): array
{
    mt_srand($graine);
    $j = [];
    $t = 30;
    $base = 60 / $parSeconde;
    while ($t < AVENT_BARQUE_TICKS) {
        $j[] = $t;
        $t += max(AVENT_BARQUE_ECART, (int) round($base * (0.75 + mt_rand(0, 50) / 100)));
    }
    return $j;
}

$echecs = 0;
$verifie = static function (string $nom, bool $ok) use (&$echecs): void {
    echo ($ok ? '  ok   ' : '  ÉCHEC ') . $nom . "\n";
    if (!$ok) {
        $echecs++;
    }
};

echo "Paramètres : " . json_encode(avent_barque_params([])) . "\n";
echo "Chutes (s)  : " . implode(', ', array_map(static fn($t) => round($t / 60, 1), avent_barque_chutes(avent_barque_params([])))) . "\n\n";

echo str_pad('Journal', 38) . str_pad('Arrivée', 10) . str_pad('Lutins', 8) . "Score\n";
$cas = [
    'Parfait, 7 impulsions/s'              => journal(7),
    'Parfait, 7/s, départ immédiat'        => journal(7, 0),
    'Humain rapide, ~6,5/s (gigue ±25 %)'  => journalHumain(6.5, 1),
    'Moyen, 6/s'                           => journal(6),
    'Régulier mais lent, 5/s'              => journal(5),
    'Régulier et lent, 4,5/s'              => journal(4.5),
    'Lent et régulier, 4/s'                => journal(4),
    'Trop lent, 3,5/s'                     => journal(3.5),
    'Très lent, 2/s'                       => journal(2),
    'Aucune impulsion'                     => [],
    'Inhumain, 12/s (5 ticks d\'écart)'    => journal(12, 0),
];
$resultats = [];
foreach ($cas as $nom => $j) {
    $r = avent_jeu_barque_verifier([], ['impulsions' => $j], 70000, []);
    $resultats[$nom] = $r;
    echo str_pad($nom, 38)
        . str_pad(isset($r['arrivee']) ? number_format($r['arrivee'] / 60, 1, ',', '') . ' s' : (isset($r['coule']) ? 'coule' : 'rejeté'), 10)
        . str_pad((string) ($r['equipage'] ?? '-'), 8) . $r['score'] . "\n";
}

echo "\nContrôles :\n";
$verifie('7/s arrive en 25 s ou moins : 100 points', $resultats['Parfait, 7 impulsions/s']['score'] === 100);
$verifie('joueur régulier mais lent (4 à 5/s) arrive entre 35 et 50 s', ($a = $resultats['Lent et régulier, 4/s']['arrivee'] ?? 0) >= 2100 && $a <= 3000 && ($b = $resultats['Régulier mais lent, 5/s']['arrivee'] ?? 0) >= 2100 && $b <= 3000);
$verifie('3,5/s, 2/s et aucune impulsion : la barque coule, 0 point', $resultats['Trop lent, 3,5/s']['score'] === 0 && $resultats['Très lent, 2/s']['score'] === 0 && $resultats['Aucune impulsion']['score'] === 0);
$verifie('rythme inhumain à 5 ticks d\'écart accepté, plafonné à 100', $resultats['Inhumain, 12/s (5 ticks d\'écart)']['score'] === 100);

// Barème
$p = avent_barque_params([]);
$verifie('barème : 100 à 25 s, 10 à 60 s, ~55 à 42,5 s',
    avent_barque_score(1500, $p) === 100 && avent_barque_score(3600, $p) === 10 && avent_barque_score(2550, $p) === 55);

// Rejets
$verifie('débit trop élevé (4 ticks d\'écart) rejeté', avent_jeu_barque_verifier([], ['impulsions' => [0, 4, 8]], 70000)['invalide'] ?? false);
$verifie('journal non croissant rejeté', avent_jeu_barque_verifier([], ['impulsions' => [10, 30, 20]], 70000)['invalide'] ?? false);
$verifie('doublon rejeté', avent_jeu_barque_verifier([], ['impulsions' => [10, 10]], 70000)['invalide'] ?? false);
$verifie('tick hors partie rejeté', avent_jeu_barque_verifier([], ['impulsions' => [3600]], 70000)['invalide'] ?? false);
$verifie('valeur non entière rejetée', avent_jeu_barque_verifier([], ['impulsions' => [10, '30']], 70000)['invalide'] ?? false);
$verifie('journal de taille anormale rejeté', avent_jeu_barque_verifier([], ['impulsions' => range(0, 3599, 1)], 70000)['invalide'] ?? false);
$verifie('journal absent rejeté', avent_jeu_barque_verifier([], [], 70000)['invalide'] ?? false);
$verifie('score envoyé par le client ignoré', avent_jeu_barque_verifier([], ['impulsions' => [], 'score' => 100, 'temps' => 1], 70000)['score'] === 0);

// Durée : un journal gagnant en ~24 s ne peut pas venir d'une partie démarrée il y a 10 s.
$gagnant = journal(7);
$verifie('journal gagnant, partie réellement longue : accepté', avent_jeu_barque_verifier([], ['impulsions' => $gagnant], 30000)['score'] === 100);
$verifie('journal gagnant, partie démarrée il y a 10 s : rejeté (triche de durée)', avent_jeu_barque_verifier([], ['impulsions' => $gagnant], 10000)['invalide'] ?? false);
$verifie('même journal, tolérance de 5 s : accepté à 19 s', avent_jeu_barque_verifier([], ['impulsions' => $gagnant], 19500)['score'] === 100);

// Départ enregistré par « embarquer » : la durée disponible se compte depuis ce moment
$etat = [];
$verifie('action embarquer : premier appel enregistre le départ', avent_jeu_barque_action([], ['action' => 'embarquer'], $etat, [], 12000) === ['depart' => true] && $etat['depart_ms'] === 12000);
$verifie('action embarquer : second appel sans effet', avent_jeu_barque_action([], ['action' => 'embarquer'], $etat, [], 30000) === ['depart' => false] && $etat['depart_ms'] === 12000);
$verifie('action inconnue refusée', isset(avent_jeu_barque_action([], ['action' => 'x'], $etat, [], 0)['erreur']));
$verifie('config_publique signale deja_parti après embarquer', avent_jeu_barque_config_publique([], ['etat' => $etat])['deja_parti'] === true);
$verifie('config_publique : pas de départ avant embarquer', avent_jeu_barque_config_publique([], ['etat' => []])['deja_parti'] === false);
$verifie('journal gagnant en 23 s, parti à 12 s, fin à 30 s : rejeté (la barque ne peut pas avoir fait 23 s en 18 s)', avent_jeu_barque_verifier([], ['impulsions' => $gagnant], 30000, ['etat' => $etat])['invalide'] ?? false);
$verifie('même journal, fin à 38 s (parti à 12 s) : accepté', avent_jeu_barque_verifier([], ['impulsions' => $gagnant], 38000, ['etat' => $etat])['score'] === 100);

// Configuration administrateur
$verifie('valider : paramètres corrects', avent_jeu_barque_valider(['parametres' => ['distance' => 300000]]) === null);
$verifie('valider : hors bornes refusé', avent_jeu_barque_valider(['parametres' => ['courant' => 99]]) !== null);
$verifie('valider : type refusé', avent_jeu_barque_valider(['parametres' => ['courant' => '2']]) !== null);
$verifie('config_publique : pas de score ni journal', array_keys(avent_jeu_barque_config_publique([], ['etat' => []])) === ['version', 'parametres', 'ticks', 'ecart', 'lutins', 'chutes', 'deja_parti']);

echo "\n" . ($echecs === 0 ? 'Tous les contrôles passent.' : "$echecs contrôle(s) en échec.") . "\n";
exit($echecs === 0 ? 0 : 1);
