<?php
/**
 * Test en ligne de commande : php avent/jeux/paires/test.php
 * Joue des parties contre avent_jeu_paires_action() / avent_jeu_paires_verifier() avec une horloge simulée.
 * Termine avec le code 1 si une attente n'est pas respectée.
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

/** Une partie simulée : $t est le temps (en secondes) depuis le « Commencer » du jeu. */
final class Partie
{
    public array $etat = [];
    public int $seq = 0;
    public function __construct(public array $config = [], public string $pseudo = 'alice', public int $jour = 1)
    {
        $GLOBALS['AVENT_PAIRES_HORLOGE'] = 1000.0;
    }
    public function appel(string $action, array $donnees, float $t, bool $nouvelleSeq = true): array
    {
        $GLOBALS['AVENT_PAIRES_HORLOGE'] = 1000.0 + $t;
        if ($action === 'retourner' && $nouvelleSeq && !isset($donnees['seq'])) {
            $donnees['seq'] = ++$this->seq;
        }
        return avent_jeu_paires_action($this->config, ['action' => $action, 'donnees' => $donnees],
            $this->etat, ['pseudo' => $this->pseudo, 'jour' => $this->jour, 'partie' => 1], 0);
    }
    public function demarrer(float $t = 0.0): array { return $this->appel('demarrer', [], $t); }
    public function retourner(int $i, float $t): array { return $this->appel('retourner', ['i' => $i], $t); }
    public function verifier(float $payloadTemps = 0): array
    {
        return avent_jeu_paires_verifier($this->config, ['temps_ms' => $payloadTemps, 'score' => 100], 0, ['etat' => $this->etat]);
    }
    /** Indices des paires (et du charbon) lus directement dans l'état serveur : réservé aux tests. */
    public function paires(): array
    {
        $par = [];
        $charbon = -1;
        foreach ($this->etat['cartes'] as $i => $v) {
            if ($v === -1) { $charbon = $i; } else { $par[$v][] = $i; }
        }
        return ['paires' => array_values($par), 'charbon' => $charbon];
    }
    /** Trouve toutes les paires à partir de $t, une carte toutes les $pas secondes. Renvoie le temps final. */
    public function toutTrouver(float $t, float $pas = 0.3): float
    {
        foreach ($this->paires()['paires'] as [$a, $b]) {
            $this->retourner($a, $t);
            $t += $pas;
            $this->retourner($b, $t);
            $t += $pas;
        }
        return $t;
    }
}

// --- Disposition ---------------------------------------------------------------------------------------------
echo "Disposition\n";
$p = new Partie();
$vue = $p->demarrer();
$verifie('demarrer : la vue ne contient ni disposition ni carte non trouvée', array_keys($vue) === ['lignes', 'colonnes', 'trouvees', 'charbons', 'ecoule_ms', 'fini', 'expire'] && $vue['trouvees'] === []);
$compte = array_count_values($p->etat['cartes']);
$verifie('25 cartes : 12 paires et un seul charbon', count($p->etat['cartes']) === 25 && ($compte[-1] ?? 0) === 1 && count($compte) === 13 && min(array_diff_key($compte, [-1 => 0])) === 2);
$q = new Partie();
$q->demarrer();
$verifie('même joueur, même jour : même disposition', $q->etat['cartes'] === $p->etat['cartes']);
$r = new Partie([], 'bob');
$r->demarrer();
$verifie('autre joueur : autre disposition', $r->etat['cartes'] !== $p->etat['cartes']);
$s = new Partie([], 'alice', 2);
$s->demarrer();
$verifie('autre jour : autre disposition', $s->etat['cartes'] !== $p->etat['cartes']);
$positions = [];
for ($k = 0; $k < 200; $k++) {
    $x = new Partie([], 'joueur' . $k);
    $x->demarrer();
    $positions[$x->paires()['charbon']] = true;
}
$verifie('le charbon tombe à des endroits variés (200 joueurs)', count($positions) >= 20);

// --- Partie parfaite -----------------------------------------------------------------------------------------
echo "\nParties\n";
$p = new Partie();
$p->demarrer();
$fin = $p->toutTrouver(2.0);
$rep = $p->etat['rep'];
$verifie('partie parfaite : fini, 12 paires, score 100 (temps ' . round($fin - 0.3 - 2.0, 1) . ' s)', $rep['fini'] === true && $rep['nb_paires'] === 12 && $rep['score'] === 100 && $p->verifier() === ['score' => 100, 'reussite' => true, 'paires' => 12, 'effectif_ms' => 8900]);

// Partie lente sans charbon : 80 s
$p = new Partie();
$p->demarrer();
[$a, $b] = $p->paires()['paires'][0];
$p->retourner($a, 1.0);
$p->retourner($b, 1.4);
$reste = array_slice($p->paires()['paires'], 1);
$t = 77.0;
foreach ($reste as [$a, $b]) { $p->retourner($a, $t); $t += 0.3; $p->retourner($b, $t); $t += 0.3; }
$attendu = (int) round(100 - 90 * (($t - 0.3 - 35) / 75));
$verifie("temps réel 80 s sans charbon : barème linéaire ($attendu points)", $p->etat['rep']['score'] === $attendu && $p->verifier()['score'] === $attendu);

// Charbons : 2 × +3 s
$p = new Partie();
$p->demarrer();
$charbon = $p->paires()['charbon'];
$p->retourner($charbon, 1.0);
$p->retourner($charbon, 2.0);
$fin = $p->toutTrouver(40.0);
$reel = $fin - 0.3;
$effectif = $reel + 6;
$attendu = (int) round(100 - 90 * (($effectif - 35) / 75));
$v = $p->verifier();
$verifie("deux charbons : temps effectif = réel + 6 s, score $attendu", $p->etat['charbons'] === 2 && $v['effectif_ms'] === (int) round($effectif * 1000) && $v['score'] === $attendu);

// Temps effectif au-delà de la durée max mais temps réel dans les limites : points_min
$p = new Partie();
$p->demarrer();
$charbon = $p->paires()['charbon'];
$t = 1.0;
for ($k = 0; $k < 6; $k++) { $p->retourner($charbon, $t); $t += 0.5; }
$fin = $p->toutTrouver(100.0);
$verifie('temps réel 100 s + 6 charbons (118 s effectifs) : 10 points (points_min)', $p->verifier()['score'] === 10);

// Charbon : annule la première carte, la suivante est donc une première carte
$p = new Partie();
$p->demarrer();
[$a, $b] = $p->paires()['paires'][0];
$c = $p->paires()['charbon'];
$r1 = $p->retourner($a, 1.0);
$r2 = $p->retourner($c, 1.5);
$r3 = $p->retourner($b, 2.0);
$verifie('charbon : annule la première carte (la carte suivante est à nouveau une première carte)', $r1['paire'] === null && ($r2['charbon'] ?? false) && $r3['paire'] === null && $p->etat['attente'] === $b);
$r4 = $p->retourner($a, 2.5);
$verifie('puis la paire se valide normalement', $r4['paire'] === true && $r4['nb_paires'] === 1);

// Deux cartes différentes : pas de paire, rien de verrouillé
$p = new Partie();
$p->demarrer();
$pa = $p->paires()['paires'];
$r1 = $p->retourner($pa[0][0], 1.0);
$r2 = $p->retourner($pa[1][0], 1.5);
$verifie('deux cartes différentes : pas de paire, rien de verrouillé', $r2['paire'] === false && $r2['premiere'] === $pa[0][0] && $p->etat['trouvees'] === []);

// Temps dépassé
$p = new Partie();
$p->demarrer();
$pa = $p->paires()['paires'];
for ($k = 0; $k < 11; $k++) { $p->retourner($pa[$k][0], 1.0 + 2 * $k); $p->retourner($pa[$k][1], 1.5 + 2 * $k); }
$r = $p->retourner($pa[11][0], 111.0);
$verifie('retournement après 110 s : refusé (termine), partie expirée', ($r['termine'] ?? false) && $r['expire'] === true);
$p->retourner($pa[11][1], 111.4);
$verifie('11 paires seulement : verifier donne 0 point', $p->verifier()['score'] === 0 && $p->verifier()['paires'] === 11);
$p = new Partie();
$p->demarrer();
$verifie('partie jamais terminée (aucune paire) : 0 point', $p->verifier()['score'] === 0);
$p = new Partie();
$p->demarrer();
$pa = $p->paires()['paires'];
for ($k = 0; $k < 11; $k++) { $p->retourner($pa[$k][0], 1.0 + 9 * $k); $p->retourner($pa[$k][1], 1.5 + 9 * $k); }
$p->retourner($pa[11][0], 109.8);
$r = $p->retourner($pa[11][1], 110.2);
$verifie('dernière paire à 110,2 s (dans la tolérance réseau de 0,5 s) : acceptée, 10 points', $r['fini'] === true && $p->verifier()['score'] === 10);

// --- Appels invalides ----------------------------------------------------------------------------------------
echo "\nAppels invalides\n";
$p = new Partie();
$verifie('retourner avant demarrer : erreur', isset($p->retourner(0, 1.0)['erreur']));
$p->demarrer();
$verifie('index négatif refusé', isset($p->retourner(-1, 1.0)['erreur']));
$verifie('index hors grille refusé', isset($p->retourner(25, 1.5)['erreur']));
$verifie('index non entier refusé', isset($p->appel('retourner', ['i' => '3'], 2.0)['erreur']) && isset($p->appel('retourner', ['i' => 3.0], 2.0)['erreur']));
$verifie('seq absente refusée', isset(avent_jeu_paires_action([], ['action' => 'retourner', 'donnees' => ['i' => 3]], $p->etat, ['pseudo' => 'alice', 'jour' => 1], 0)['erreur']));
$pa = $p->paires()['paires'];
$p->retourner($pa[0][0], 3.0);
$verifie('deuxième sélection identique à la première refusée', isset($p->retourner($pa[0][0], 3.5)['erreur']));
$verifie('retournement trop rapide (100 ms après le précédent) refusé', isset($p->retourner($pa[0][1], 3.1)['erreur']));
$verifie('même retournement à 300 ms : accepté', ($p->retourner($pa[0][1], 3.3)['paire'] ?? null) === true);
$verifie('carte déjà verrouillée refusée', isset($p->retourner($pa[0][0], 4.0)['erreur']));
$verifie('action inconnue refusée', isset($p->appel('triche', [], 5.0)['erreur']));
// renvoi réseau : même seq → même réponse, sans effet
$rep1 = $p->retourner($pa[1][0], 5.0);
$etatAvant = $p->etat;
$rep2 = $p->appel('retourner', ['i' => $pa[1][0], 'seq' => $p->seq], 5.05);
$verifie('renvoi du dernier retournement (même seq) : même réponse, état inchangé', $rep2 === $rep1 && $p->etat === $etatAvant);
$verifie('seq périmée refusée', isset($p->appel('retourner', ['i' => $pa[2][0], 'seq' => 1], 6.0)['erreur']));
$verifie('une erreur ne modifie pas l\'état (pas de pénalité, pas de carte en attente)', $p->etat['charbons'] === 0 && $p->etat['attente'] === $pa[1][0]);

// --- Rechargement et triche sur le temps ---------------------------------------------------------------------
echo "\nRechargement et triche\n";
$p = new Partie();
$p->demarrer();
$pa = $p->paires()['paires'];
$p->retourner($pa[0][0], 10.0);
$p->retourner($pa[0][1], 10.5);
$p->retourner($pa[1][0], 11.0);
$vue = $p->demarrer(30.0);
$verifie('rechargement : même partie, chrono non remis à zéro (30 s), paire trouvée restituée', $vue['ecoule_ms'] === 30000 && count($vue['trouvees']) === 2 && $p->etat['attente'] === null);
$verifie('rechargement : seules les cartes trouvées sont révélées', array_column($vue['trouvees'], 0) === [$pa[0][0], $pa[0][1]]);
$vue2 = $p->demarrer(200.0);
$verifie('rechargement après la durée max : expirée', $vue2['expire'] === true && $vue2['ecoule_ms'] === 110000);
$cfg = avent_jeu_paires_config_publique([], []);
$verifie('config_publique : paramètres publics seulement, pas de disposition', array_keys($cfg) === ['version', 'lignes', 'colonnes', 'duree_max', 'temps_parfait', 'points_min', 'penalite', 'reprise'] && $cfg['reprise'] === null);

$p = new Partie();
$p->demarrer();
$p->toutTrouver(200.0);   // tout après 110 s : refusé
$verifie('client qui joue après 110 s : rien n\'est enregistré, 0 point', $p->verifier()['score'] === 0 && $p->etat['trouvees'] === []);

$p = new Partie();
$p->demarrer();
$p->toutTrouver(60.0);
$v = avent_jeu_paires_verifier([], ['temps_ms' => 1000, 'effectif_ms' => 1000, 'score' => 100, 'paires' => 12, 'charbons' => 0], 1000, ['etat' => $p->etat]);
$attendu = (int) round(100 - 90 * ((60 + 24 * 0.3 - 0.3 - 35) / 75));
$verifie("client qui annonce 1 s et 100 points : seul le temps serveur compte ($attendu points)", $v['score'] === $attendu);

// --- Paramètres ----------------------------------------------------------------------------------------------
echo "\nParamètres\n";
$verifie('valider : configuration vide', avent_jeu_paires_valider([]) === null);
$verifie('valider : exemple du manifest', avent_jeu_paires_valider(json_decode(file_get_contents(__DIR__ . '/manifest.json'), true)['exemple']) === null);
$verifie('valider : grille paire refusée', avent_jeu_paires_valider(['parametres' => ['lignes' => 4, 'colonnes' => 6]]) !== null);
$verifie('valider : grille 3×5 (7 paires) acceptée', avent_jeu_paires_valider(['parametres' => ['lignes' => 3, 'colonnes' => 5]]) === null);
$verifie('valider : grille trop grande pour la liste par défaut refusée', avent_jeu_paires_valider(['parametres' => ['lignes' => 7, 'colonnes' => 5]]) !== null);
$verifie('valider : grille 7×5 avec 17 emojis acceptée', avent_jeu_paires_valider(['parametres' => ['lignes' => 7, 'colonnes' => 5, 'emojis' => array_merge(AVENT_PAI_EMOJIS, ['🍷', '🥂', '🍫', '🧣', '🎿'])]]) === null);
$verifie('valider : emojis en double refusés', avent_jeu_paires_valider(['parametres' => ['emojis' => array_fill(0, 12, '🎅')]]) !== null);
$verifie('valider : hors bornes et types refusés', avent_jeu_paires_valider(['parametres' => ['penalite' => 99]]) !== null && avent_jeu_paires_valider(['parametres' => ['duree_max' => '110']]) !== null && avent_jeu_paires_valider(['parametres' => ['temps_parfait' => 200]]) !== null);
$petite = new Partie(['parametres' => ['lignes' => 3, 'colonnes' => 3, 'penalite' => 5, 'temps_parfait' => 10, 'duree_max' => 40]]);
$petite->demarrer();
$petite->toutTrouver(20.0);
$verifie('grille 3×3 (4 paires), barème personnalisé : 100 pts jusqu\'à 10 s, 10 pts à 40 s', count($petite->etat['cartes']) === 9 && $petite->etat['fini'] && $petite->verifier()['reussite']);

echo "\n" . ($echecs === 0 ? 'Tous les contrôles passent.' : "$echecs contrôle(s) en échec.") . "\n";
exit($echecs === 0 ? 0 : 1);
