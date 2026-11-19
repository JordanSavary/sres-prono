<?php
/**
 * Jeu « Les Lutins du Chalet » — module serveur (puzzle de type Queens / MeowDoku).
 *
 * Grille N×N découpée en N zones de couleur ; il faut placer N lutins : un par ligne, un par colonne,
 * un par zone, et jamais deux lutins sur des cases voisines (diagonales comprises). Chaque grille a
 * une solution unique, vérifiée par un solveur à retour sur trace.
 *
 * - La grille est la même pour tous les joueurs d'un même jour : elle est générée de façon
 *   déterministe à partir du numéro de case et d'un secret serveur (avent_secret()).
 * - La solution ne quitte jamais le serveur. Chaque pose est vérifiée par l'action « poser »
 *   (api/action.php), qui tient les vies et le chronomètre dans l'état de la partie.
 * - Le temps est mesuré par le serveur entre l'action « demarrer » et la dernière bonne pose ;
 *   le temps annoncé par le navigateur n'existe pas. verifier() ne lit que l'état serveur.
 *
 * Contenu attendu (JSON saisi dans l'administration), tout est facultatif :
 *   {"parametres": {"taille": 8, "vies": 3, "temps_parfait": 45, "temps_limite": 120, "score_min": 10, "variante": 0}}
 */

const AVENT_LUT_VERSION = 1;

/** [défaut, min, max] */
function avent_lut_bornes(): array
{
    return [
        'taille'        => [8, 6, 8],      // côté de la grille = nombre de zones = nombre de lutins
        'vies'          => [3, 1, 5],      // bonnets de Noël
        'temps_parfait' => [45, 10, 300],  // secondes : 100 points jusque-là
        'temps_limite'  => [120, 30, 600], // secondes : au-delà, 0 point
        'score_min'     => [10, 0, 99],    // points obtenus pile à temps_limite
        'variante'      => [0, 0, 999],    // change la grille du jour sans toucher au reste
    ];
}

function avent_lut_params(array $config): array
{
    $saisis = is_array($config['parametres'] ?? null) ? $config['parametres'] : [];
    $p = [];
    foreach (avent_lut_bornes() as $nom => [$defaut, $min, $max]) {
        $v = $saisis[$nom] ?? $defaut;
        $p[$nom] = is_int($v) ? max($min, min($max, $v)) : $defaut;
    }
    if ($p['temps_limite'] <= $p['temps_parfait']) {
        $p['temps_limite'] = $p['temps_parfait'] + 1;
    }
    return $p;
}

function avent_jeu_lutins_valider(array $config): ?string
{
    $saisis = $config['parametres'] ?? [];
    if (!is_array($saisis)) {
        return '« parametres » doit être un objet.';
    }
    foreach (avent_lut_bornes() as $nom => [$defaut, $min, $max]) {
        if (array_key_exists($nom, $saisis) && (!is_int($saisis[$nom]) || $saisis[$nom] < $min || $saisis[$nom] > $max)) {
            return "Paramètre « $nom » : entier attendu entre $min et $max (défaut $defaut).";
        }
    }
    $p = avent_lut_params($config);
    if (isset($saisis['temps_limite']) && $p['temps_limite'] !== $saisis['temps_limite']) {
        return '« temps_limite » doit être supérieur à « temps_parfait ».';
    }
    return null;
}

// ---------------------------------------------------------------------------
// Générateur pseudo-aléatoire (xorshift32)
// ---------------------------------------------------------------------------

final class AventLutRng
{
    private int $x;

    public function __construct(int $graine)
    {
        $graine &= 0xFFFFFFFF;
        $this->x = $graine === 0 ? 0x9E3779B9 : $graine;
    }

    public function rnd(int $n): int
    {
        $x = $this->x;
        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= $x >> 17;
        $x ^= ($x << 5) & 0xFFFFFFFF;
        $this->x = $x;
        return $x % $n;
    }

    public function melanger(array $liste): array
    {
        for ($i = count($liste) - 1; $i > 0; $i--) {
            $j = $this->rnd($i + 1);
            [$liste[$i], $liste[$j]] = [$liste[$j], $liste[$i]];
        }
        return $liste;
    }
}

// ---------------------------------------------------------------------------
// Solveur et générateur
// ---------------------------------------------------------------------------

/**
 * Cherche les solutions (jusqu'à $limite). $zones[r][c] = numéro de zone 0..n-1.
 * Une solution = une colonne par ligne, colonnes et zones distinctes, deux lignes consécutives
 * jamais à moins de 2 colonnes d'écart (pas de contact, diagonales comprises).
 */
function avent_lut_solutions(array $zones, int $limite = 2): array
{
    $n = count($zones);
    $trouvees = [];
    $cols = [];
    $rec = static function (int $r, int $colonnes, int $zonesPrises) use (&$rec, &$trouvees, &$cols, $zones, $n, $limite): void {
        if (count($trouvees) >= $limite) {
            return;
        }
        if ($r === $n) {
            $trouvees[] = $cols;
            return;
        }
        for ($c = 0; $c < $n; $c++) {
            if (($colonnes >> $c) & 1 || ($r > 0 && abs($c - $cols[$r - 1]) < 2)) {
                continue;
            }
            $z = $zones[$r][$c];
            if (($zonesPrises >> $z) & 1) {
                continue;
            }
            $cols[$r] = $c;
            $rec($r + 1, $colonnes | (1 << $c), $zonesPrises | (1 << $z));
        }
    };
    $rec(0, 0, 0);
    return $trouvees;
}

function avent_lut_compter(array $zones, int $limite = 2): int
{
    return count(avent_lut_solutions($zones, $limite));
}

/** Vrai si la zone $k reste d'un seul tenant quand on en retire la case ($rx, $cx). */
function avent_lut_connexe_sans(array $zones, int $k, int $rx, int $cx): bool
{
    $n = count($zones);
    $cases = [];
    foreach ($zones as $r => $ligne) {
        foreach ($ligne as $c => $v) {
            if ($v === $k && !($r === $rx && $c === $cx)) {
                $cases[$r * $n + $c] = true;
            }
        }
    }
    if (!$cases) {
        return false;
    }
    $depart = array_key_first($cases);
    $vus = [$depart => true];
    $pile = [$depart];
    while ($pile) {
        $x = array_pop($pile);
        $r = intdiv($x, $n);
        $c = $x % $n;
        foreach ([[$r - 1, $c], [$r + 1, $c], [$r, $c - 1], [$r, $c + 1]] as [$vr, $vc]) {
            if ($vr >= 0 && $vc >= 0 && $vr < $n && $vc < $n) {
                $y = $vr * $n + $vc;
                if (isset($cases[$y]) && !isset($vus[$y])) {
                    $vus[$y] = true;
                    $pile[] = $y;
                }
            }
        }
    }
    return count($vus) === count($cases);
}

/**
 * Corrige une grille ayant plusieurs solutions : on retire à une seconde solution un lutin en
 * changeant la couleur d'une de ses cases (jamais une case de la solution prévue), jusqu'à
 * ce que la solution prévue soit la seule. Renvoie null si on n'y arrive pas.
 */
function avent_lut_unifier(AventLutRng $rng, array $zones, array $sol): ?array
{
    $n = count($zones);
    for ($tour = 0; $tour < 60; $tour++) {
        $autres = array_values(array_filter(avent_lut_solutions($zones, 3), static fn (array $s): bool => $s !== $sol));
        if (!$autres) {
            return $zones;
        }
        $s2 = $autres[$rng->rnd(count($autres))];
        $zonesS2 = [];
        foreach ($s2 as $r => $c) {
            $zonesS2[$zones[$r][$c]][] = [$r, $c];
        }
        $coups = [];
        foreach ($s2 as $r => $c) {
            if ($sol[$r] === $c) {
                continue; // on ne touche pas aux cases de la solution prévue
            }
            $zx = $zones[$r][$c];
            foreach ([[$r - 1, $c], [$r + 1, $c], [$r, $c - 1], [$r, $c + 1]] as [$vr, $vc]) {
                if ($vr < 0 || $vc < 0 || $vr >= $n || $vc >= $n || $zones[$vr][$vc] === $zx) {
                    continue;
                }
                $zv = $zones[$vr][$vc];
                // la nouvelle zone doit déjà porter un autre lutin de s2, pour que s2 devienne invalide
                if (isset($zonesS2[$zv]) && $zonesS2[$zv][0] !== [$r, $c]) {
                    $coups[] = [$r, $c, $zv];
                }
            }
        }
        $coups = $rng->melanger($coups);
        $applique = false;
        foreach ($coups as [$r, $c, $zv]) {
            if (avent_lut_connexe_sans($zones, $zones[$r][$c], $r, $c)) {
                $zones[$r][$c] = $zv;
                $applique = true;
                break;
            }
        }
        if (!$applique) {
            return null;
        }
    }
    return null;
}

/** Une permutation valide (colonne par ligne, sans contact), tirée au hasard. */
function avent_lut_placer(AventLutRng $rng, int $n): array
{
    $sol = [];
    $rec = static function (int $r, int $colonnes) use (&$rec, &$sol, $rng, $n): bool {
        if ($r === $n) {
            return true;
        }
        foreach ($rng->melanger(range(0, $n - 1)) as $c) {
            if (($colonnes >> $c) & 1 || ($r > 0 && abs($c - $sol[$r - 1]) < 2)) {
                continue;
            }
            $sol[$r] = $c;
            if ($rec($r + 1, $colonnes | (1 << $c))) {
                return true;
            }
        }
        return false;
    };
    $rec(0, 0);
    return $sol;
}

/** Fait grandir N zones connexes autour des lutins posés, jusqu'à couvrir toute la grille. */
function avent_lut_zones(AventLutRng $rng, int $n, array $sol): array
{
    $z = array_fill(0, $n, array_fill(0, $n, -1));
    $taille = array_fill(0, $n, 0);
    $front = array_fill(0, $n, []);
    $voisins = static fn (int $r, int $c): array => array_filter(
        [[$r - 1, $c], [$r + 1, $c], [$r, $c - 1], [$r, $c + 1]],
        static fn (array $p): bool => $p[0] >= 0 && $p[0] < $n && $p[1] >= 0 && $p[1] < $n
    );
    $prendre = static function (int $zone, int $r, int $c) use (&$z, &$taille, &$front, $voisins): void {
        $z[$r][$c] = $zone;
        $taille[$zone]++;
        foreach ($voisins($r, $c) as [$vr, $vc]) {
            $front[$zone][] = [$vr, $vc];
        }
    };
    foreach ($sol as $r => $c) {
        $prendre($r, $r, $c);
    }
    for ($reste = $n * $n - $n; $reste > 0; $reste--) {
        // zones qui peuvent encore grandir
        $possibles = [];
        foreach ($front as $zone => $liste) {
            $front[$zone] = array_values(array_filter($liste, static fn (array $p): bool => $z[$p[0]][$p[1]] < 0));
            if ($front[$zone]) {
                $possibles[] = $zone;
            }
        }
        if ($rng->rnd(100) < 65) { // on favorise les petites zones pour garder des tailles voisines
            usort($possibles, static fn (int $a, int $b): int => [$taille[$a], $a] <=> [$taille[$b], $b]);
            $zone = $possibles[$rng->rnd(min(3, count($possibles)))];
        } else {
            $zone = $possibles[$rng->rnd(count($possibles))];
        }
        [$r, $c] = $front[$zone][$rng->rnd(count($front[$zone]))];
        $prendre($zone, $r, $c);
    }
    return $z;
}

/** Écarte les grilles aux zones minuscules (un lutin « offert ») ou gigantesques. */
function avent_lut_formes_ok(array $zones): bool
{
    $n = count($zones);
    $tailles = array_count_values(array_merge(...$zones));
    return count($tailles) === $n && min($tailles) >= max(3, $n - 5) && max($tailles) <= 2 * $n;
}

/**
 * Grille du jour : renvoie ['zones' => [[…]], 'solution' => [colonne par ligne], 'essais' => n].
 * Régénère jusqu'à obtenir une solution unique.
 */
function avent_lut_generer(int $graine, int $n): array
{
    $rng = new AventLutRng($graine);
    for ($essai = 1; $essai <= 20000; $essai++) {
        $sol = avent_lut_placer($rng, $n);
        $zones = avent_lut_zones($rng, $n, $sol);
        $zones = avent_lut_unifier($rng, $zones, $sol);
        if ($zones !== null && avent_lut_formes_ok($zones) && avent_lut_compter($zones, 2) === 1) {
            // Les couleurs sont attribuées au hasard (le numéro de zone n'indique rien).
            $couleurs = $rng->melanger(range(0, $n - 1));
            foreach ($zones as $r => $ligne) {
                foreach ($ligne as $c => $zone) {
                    $zones[$r][$c] = $couleurs[$zone];
                }
            }
            return ['zones' => $zones, 'solution' => $sol, 'essais' => $essai];
        }
    }
    throw new RuntimeException('Génération de la grille impossible.');
}

function avent_lut_graine(array $params, int $jour): int
{
    $secret = function_exists('avent_secret') ? avent_secret() : 'secret-de-test';
    $hex = hash_hmac('sha256', 'lutins|' . $jour . '|' . $params['taille'] . '|' . $params['variante'], $secret);
    return hexdec(substr($hex, 0, 8)) & 0xFFFFFFFF;
}

// ---------------------------------------------------------------------------
// Plateforme : config publique, actions, vérification
// ---------------------------------------------------------------------------

/** Ce que le navigateur peut savoir d'une partie commencée (jamais la solution). */
function avent_lut_vue(array $etat): array
{
    $n = $etat['n'];
    $bons = [];
    foreach ($etat['bons'] as $k) {
        $bons[] = [intdiv($k, $n), $k % $n];
    }
    $refus = [];
    foreach ($etat['mauvais'] as $k) {
        $refus[] = [intdiv($k, $n), $k % $n];
    }
    $vue = [
        'zones'     => $etat['zones'],
        'bons'      => $bons,
        'refus'     => $refus,
        'vies'      => $etat['vies'],
        'fini'      => $etat['fini'],
        'perdu'     => $etat['perdu'],
        'ecoule_ms' => (int) round(((isset($etat['tfin']) ? $etat['tfin'] : microtime(true)) - $etat['t0']) * 1000),
    ];
    if ($etat['fini']) {
        $vue['temps_ms'] = (int) round(($etat['tfin'] - $etat['t0']) * 1000);
    }
    return $vue;
}

/**
 * Envoyé au démarrage de la partie : uniquement les réglages. La grille ne part qu'avec l'action
 * « demarrer » (sinon on pourrait la résoudre avant d'appuyer sur « Commencer »). Après un
 * rechargement de page, l'état de la partie en cours est restitué dans « reprise ».
 */
function avent_jeu_lutins_config_publique(array $config, array $contexte = []): array
{
    $p = avent_lut_params($config);
    $reprise = null;
    if (!empty($contexte['partie']) && function_exists('avent_etat_lire')) {
        $etat = avent_etat_lire((int) $contexte['partie']);
        if (!empty($etat['t0'])) {
            $reprise = avent_lut_vue($etat);
        }
    }
    return [
        'version'  => AVENT_LUT_VERSION,
        'taille'   => $p['taille'],
        'vies'     => $p['vies'],
        'reprise'  => $reprise,
    ];
}

/**
 * Actions du jeu (api/action.php) :
 *  - demarrer : lance le chrono serveur et renvoie la grille de zones (idempotent) ;
 *  - poser {r, c} : vérifie un lutin. Réponse : bon, vies, fini, perdu (+ temps_ms et score si terminé).
 */
function avent_jeu_lutins_action(array $config, array $requete, array &$etat, array $contexte, int $ecouleMs): array
{
    $p = avent_lut_params($config);
    $action = $requete['action'];
    $donnees = $requete['donnees'];

    if ($action === 'demarrer') {
        if (empty($etat['t0'])) {
            $grille = avent_lut_generer(avent_lut_graine($p, (int) ($contexte['jour'] ?? 0)), $p['taille']);
            $etat = [
                'n'       => $p['taille'],
                'zones'   => $grille['zones'],
                'sol'     => $grille['solution'],   // ne quitte jamais le serveur
                'vies'    => $p['vies'],
                'bons'    => [],
                'mauvais' => [],
                'fini'    => false,
                'perdu'   => false,
                't0'      => microtime(true),
            ];
        }
        return avent_lut_vue($etat);
    }

    if ($action === 'poser') {
        if (empty($etat['t0'])) {
            return ['erreur' => "La partie n'est pas commencée."];
        }
        $r = $donnees['r'] ?? null;
        $c = $donnees['c'] ?? null;
        $n = $etat['n'];
        if (!is_int($r) || !is_int($c) || $r < 0 || $c < 0 || $r >= $n || $c >= $n) {
            return ['erreur' => 'Case invalide.'];
        }
        if ($etat['fini'] || $etat['perdu']) {
            return ['erreur' => 'La partie est terminée.'];
        }
        $k = $r * $n + $c;
        if (in_array($k, $etat['bons'], true)) {
            $bon = true;                       // renvoi (double appui, nouvel essai réseau) : sans effet
        } elseif (in_array($k, $etat['mauvais'], true)) {
            $bon = false;                      // déjà refusée : ne coûte pas une seconde vie
        } elseif ($etat['sol'][$r] === $c) {
            $bon = true;
            $etat['bons'][] = $k;
            if (count($etat['bons']) === $n) {
                $etat['fini'] = true;
                $etat['tfin'] = microtime(true);
            }
        } else {
            $bon = false;
            $etat['mauvais'][] = $k;
            $etat['vies']--;
            if ($etat['vies'] <= 0) {
                $etat['vies'] = 0;
                $etat['perdu'] = true;
                $etat['tfin'] = microtime(true);
            }
        }
        $reponse = ['bon' => $bon, 'vies' => $etat['vies'], 'fini' => $etat['fini'], 'perdu' => $etat['perdu']];
        if ($etat['fini']) {
            $reponse['temps_ms'] = (int) round(($etat['tfin'] - $etat['t0']) * 1000);
            $reponse['score'] = avent_lut_score(($etat['tfin'] - $etat['t0']), $p);
        }
        return $reponse;
    }

    return ['erreur' => 'Action inconnue.'];
}

/** 100 jusqu'à temps_parfait, puis décroissance linéaire jusqu'à score_min à temps_limite, 0 au-delà. */
function avent_lut_score(float $secondes, array $p): int
{
    if ($secondes <= $p['temps_parfait']) {
        return 100;
    }
    if ($secondes > $p['temps_limite']) {
        return 0;
    }
    $part = ($secondes - $p['temps_parfait']) / ($p['temps_limite'] - $p['temps_parfait']);
    return (int) round(100 - (100 - $p['score_min']) * $part);
}

/**
 * Le score ne dépend que de l'état tenu par le serveur : grille complète, vies restantes, temps
 * mesuré par le serveur. Le payload envoyé par le navigateur est ignoré.
 */
function avent_jeu_lutins_verifier(array $config, array $payload, int $dureeMs, array $contexte = []): array
{
    $etat = $contexte['etat'] ?? [];
    $p = avent_lut_params($config);
    if (empty($etat['t0']) || empty($etat['fini']) || !empty($etat['perdu']) || ($etat['vies'] ?? 0) <= 0
        || count($etat['bons'] ?? []) !== ($etat['n'] ?? -1) || !isset($etat['tfin'])) {
        return ['score' => 0, 'reussite' => false];
    }
    $score = avent_lut_score($etat['tfin'] - $etat['t0'], $p);
    return ['score' => $score, 'reussite' => $score > 0];
}
