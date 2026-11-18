<?php
/**
 * Jeu « Père Noël Turbo-Hotte » — module serveur.
 *
 * Principe : le serveur fournit une graine propre au joueur et au jour ; le niveau (cadeaux et
 * obstacles) est généré de façon déterministe à partir de cette graine, à l'identique en PHP et en
 * JavaScript (game.js). Le navigateur n'envoie que les entrées (les ticks où le doigt est posé ou
 * levé) ; verifier() REJOUE la partie ici, recalcule cadeaux, cœurs et durée de survie, et en déduit
 * le score. Aucun score envoyé par le navigateur n'est lu.
 *
 * Toute la simulation est en entiers (virgule fixe 1/64 px, division entière tronquée) : PHP et JS
 * donnent exactement le même résultat. SI VOUS MODIFIEZ UNE RÈGLE ICI, FAITES-LA AUSSI DANS game.js
 * (bloc « SIMULATION »), puis incrémentez AVENT_PNT_VERSION des deux côtés.
 *
 * Contenu attendu (JSON saisi dans l'administration), tout est facultatif :
 *   {"parametres": {"gravite": 26, "poussee": 34, "vitesse_depart": 150, "vitesse_fin": 270, "densite": 60}}
 */

const AVENT_PNT_VERSION = 1;
const AVENT_PNT_TICKS   = 5400;   // 90 s à 60 ticks par seconde
const AVENT_PNT_TPS     = 60;
const AVENT_PNT_SC      = 64;     // virgule fixe : 1 px = 64 unités
const AVENT_PNT_PX      = 80;     // abscisse écran du Père Noël (px)
const AVENT_PNT_SOL     = 272;    // ordonnée du sol (px)
const AVENT_PNT_HW      = 10;     // demi-largeur de la zone de contact du joueur (px)
const AVENT_PNT_HH      = 13;     // demi-hauteur (px)
const AVENT_PNT_YMIN    = 19 * AVENT_PNT_SC;
const AVENT_PNT_YMAX    = 259 * AVENT_PNT_SC;
const AVENT_PNT_VHAUT   = 352;    // vitesse verticale maximale vers le haut (unités/tick)
const AVENT_PNT_VBAS    = 416;    // et vers le bas
const AVENT_PNT_COEURS  = 3;
const AVENT_PNT_INVUL   = 120;    // 2 s d'invulnérabilité après un contact
const AVENT_PNT_CAP     = 40;     // cadeaux nécessaires pour les 70 points
const AVENT_PNT_MAX_ENTREES = 1500;
const AVENT_PNT_CROW_V  = 120;    // vitesse propre des corbeaux (unités/tick, vers la gauche)
const AVENT_PNT_ARC     = 36;     // hauteur de la cloche des boules de neige (px)

/** Valeurs par défaut et bornes des paramètres réglables : [défaut, min, max]. */
function avent_pnt_bornes(): array
{
    return [
        'gravite'        => [26, 8, 60],     // accélération vers le bas, doigt levé (unités/tick²)
        'poussee'        => [34, 10, 80],    // accélération vers le haut, doigt posé (unités/tick²)
        'vitesse_depart' => [150, 80, 400],  // défilement au tick 0 (unités/tick ; 64 = 1 px/tick)
        'vitesse_fin'    => [270, 80, 500],  // défilement au tick 5400
        'densite'        => [60, 10, 100],   // densité d'obstacles (%)
    ];
}

/** Paramètres effectifs : défauts, remplacés par ceux de l'administrateur s'ils sont valides. */
function avent_pnt_params(array $config): array
{
    $saisis = is_array($config['parametres'] ?? null) ? $config['parametres'] : [];
    $params = [];
    foreach (avent_pnt_bornes() as $nom => [$defaut, $min, $max]) {
        $v = $saisis[$nom] ?? $defaut;
        $params[$nom] = is_int($v) ? max($min, min($max, $v)) : $defaut;
    }
    if ($params['vitesse_fin'] < $params['vitesse_depart']) {
        $params['vitesse_fin'] = $params['vitesse_depart'];
    }
    return $params;
}

/** Vérifie la configuration saisie par l'administrateur. Renvoie null si elle est valide. */
function avent_jeu_pere_noel_turbo_valider(array $config): ?string
{
    $saisis = $config['parametres'] ?? [];
    if (!is_array($saisis)) {
        return '« parametres » doit être un objet.';
    }
    foreach (avent_pnt_bornes() as $nom => [$defaut, $min, $max]) {
        if (!array_key_exists($nom, $saisis)) {
            continue;
        }
        if (!is_int($saisis[$nom]) || $saisis[$nom] < $min || $saisis[$nom] > $max) {
            return "Paramètre « $nom » : entier attendu entre $min et $max (défaut $defaut).";
        }
    }
    $p = avent_pnt_params($config);
    if (isset($saisis['vitesse_depart'], $saisis['vitesse_fin']) && $saisis['vitesse_fin'] < $saisis['vitesse_depart']) {
        return '« vitesse_fin » doit être supérieure ou égale à « vitesse_depart ».';
    }
    return null;
}

/** Graine du joueur pour ce jour : 32 bits dérivés d'un secret serveur (stable pour ses deux essais). */
function avent_pnt_graine(array $contexte): int
{
    $secret = function_exists('avent_secret') ? avent_secret() : 'secret-de-test';
    $hex = hash_hmac('sha256', (string) ($contexte['pseudo'] ?? '') . '|' . (int) ($contexte['jour'] ?? 0) . '|pnt', $secret);
    $graine = hexdec(substr($hex, 0, 8)) & 0xFFFFFFFF;
    return $graine === 0 ? 1 : $graine;
}

/** Configuration envoyée au navigateur : paramètres effectifs et graine. Aucune position d'objet. */
function avent_jeu_pere_noel_turbo_config_publique(array $config, array $contexte = []): array
{
    return [
        'version'    => AVENT_PNT_VERSION,
        'graine'     => avent_pnt_graine($contexte),
        'parametres' => avent_pnt_params($config),
    ];
}

// ---------------------------------------------------------------------------
// Générateur pseudo-aléatoire (xorshift32), identique en JavaScript
// ---------------------------------------------------------------------------

final class AventPntRng
{
    private int $x;

    public function __construct(int $graine)
    {
        $graine &= 0xFFFFFFFF;
        $this->x = $graine === 0 ? 0x9E3779B9 : $graine;
    }

    public function suivant(): int
    {
        $x = $this->x;
        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= $x >> 17;
        $x ^= ($x << 5) & 0xFFFFFFFF;
        $this->x = $x;
        return $x;
    }

    /** Entier de 0 à $n - 1. */
    public function rnd(int $n): int
    {
        return $this->suivant() % $n;
    }
}

// ---------------------------------------------------------------------------
// Défilement et niveau
// ---------------------------------------------------------------------------

/** Tables de défilement : unités cumulées et pixels entiers, pour les ticks 0 à TICKS + 1. */
function avent_pnt_defilement(array $p): array
{
    $unites = [0];
    $px = [0];
    $vd = $p['vitesse_depart'];
    $vf = $p['vitesse_fin'];
    for ($t = 0; $t <= AVENT_PNT_TICKS; $t++) {
        $vitesse = $vd + intdiv(($vf - $vd) * $t, AVENT_PNT_TICKS);
        $unites[$t + 1] = $unites[$t] + $vitesse;
        $px[$t + 1] = intdiv($unites[$t + 1], AVENT_PNT_SC);
    }
    return ['unites' => $unites, 'px' => $px];
}

/** Plus petit tick T (0 à TICKS + 1) dont le défilement en px atteint $v ; TICKS + 2 si aucun. */
function avent_pnt_premier_tick(array $px, int $v): int
{
    $bas = 0;
    $haut = AVENT_PNT_TICKS + 2;
    while ($bas < $haut) {
        $milieu = intdiv($bas + $haut, 2);
        if ($px[$milieu] >= $v) {
            $haut = $milieu;
        } else {
            $bas = $milieu + 1;
        }
    }
    return $bas;
}

/** Onde triangulaire 0..60 d'une phase 0..119. */
function avent_pnt_tri(int $p): int
{
    return $p < 60 ? $p : 120 - $p;
}

/**
 * Génère le niveau complet : liste d'objets triés par tick d'entrée en jeu.
 * Types : g cadeau, i stalactite, a guirlande, c corbeau, s boule de neige.
 */
function avent_pnt_niveau(array $p, int $graine, array $px): array
{
    $rng = new AventPntRng($graine);
    $objets = [];
    $id = 0;
    $ajouter = static function (array $o) use (&$objets, &$id): void {
        $o['id'] = $id++;
        $objets[] = $o;
    };
    // Objet fixe dans le monde : actif quand son abscisse monde est proche du joueur.
    $fixe = static function (array $o, int $xmin, int $xmax) use ($px, $ajouter): void {
        $t0 = avent_pnt_premier_tick($px, $xmin - AVENT_PNT_PX - 20);
        $t1 = avent_pnt_premier_tick($px, $xmax - AVENT_PNT_PX + 21) - 1;
        if ($t0 > AVENT_PNT_TICKS) {
            return;
        }
        $o['t0'] = $t0;
        $o['t1'] = $t1;
        $ajouter($o);
    };
    $cadeau = static function (int $xw, int $y) use ($fixe): void {
        $y = max(28, min(250, $y));
        $fixe(['k' => 'g', 'xw' => $xw, 'y' => $y, 'pris' => 0], $xw, $xw);
    };
    $stalactite = static function (int $xw, int $longueur) use ($fixe): void {
        $fixe(['k' => 'i', 'xw' => $xw, 'len' => $longueur], $xw, $xw);
    };
    $guirlande = static function (int $x1, int $y1, int $x2, int $y2, int $phase) use ($fixe): void {
        $fixe(['k' => 'a', 'x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2, 'ph' => $phase], min($x1, $x2), max($x1, $x2));
    };
    $corbeau = static function (int $ta, int $y0, int $phase) use ($ajouter): void {
        $ajouter(['k' => 'c', 'ta' => $ta, 'y0' => $y0, 'ph' => $phase, 't0' => max(0, $ta - 14), 't1' => $ta + 14]);
    };
    $boule = static function (int $ts, int $dt, int $yt) use ($ajouter): void {
        $ta = $ts + $dt;
        $ajouter(['k' => 's', 'ts' => $ts, 'dt' => $dt, 'yt' => $yt, 'ta' => $ta, 't0' => max(0, $ta - 10), 't1' => $ta + 10]);
    };

    $onde = [0, 14, 24, 28, 24, 14, 0, -14, -24, -28, -24, -14];
    $arbre = ['...X...', '..XXX..', '.XXXXX.', 'XXXXXXX', '...X...'];
    $coeur = ['.XX.XX.', 'XXXXXXX', 'XXXXXXX', '.XXXXX.', '..XXX..', '...X...'];

    $densite = $p['densite'];
    $probaObstacle = 20 + intdiv($densite * 6, 10);
    $ta = 240; // quatre secondes de calme au départ

    while ($ta < AVENT_PNT_TICKS - 60) {
        $ancre = $px[$ta] + AVENT_PNT_PX;
        $largeur = 0;

        if ($rng->rnd(100) < $probaObstacle) {
            if ($ta < 900) {
                $m = $rng->rnd(3);
                $h = $m === 0 ? 10 : ($m === 1 ? 65 : 75);
            } else {
                $h = $rng->rnd(100);
            }

            if ($h < 25) { // rangée de stalactites
                $n = 1 + $rng->rnd(3);
                $derniere = 0;
                for ($i = 0; $i < $n; $i++) {
                    $dx = $i * 70 + $rng->rnd(20);
                    $derniere = 60 + $rng->rnd(111);
                    $stalactite($ancre + $dx, $derniere);
                }
                $yc = min(AVENT_PNT_SOL - 30, $derniere + 55);
                $gx = $ancre + ($n - 1) * 70 - 24;
                for ($i = 0; $i < 4; $i++) {
                    $cadeau($gx + 24 * $i, $yc);
                }
                $largeur = $n * 70;
            } elseif ($h < 45) { // portillon : guirlande verticale
                $x = $ancre + $rng->rnd(30);
                $y1 = 50 + $rng->rnd(81);
                $y2 = min(268, $y1 + 100 + $rng->rnd(40));
                $guirlande($x, $y1, $x, $y2, $rng->rnd(96));
                for ($i = 0; $i < 5; $i++) {
                    $cadeau($x - 48 + 24 * $i, $y1 - 34);
                }
                $largeur = 60;
            } elseif ($h < 60) { // guirlande en diagonale
                $dx = 70 + $rng->rnd(41);
                $dy = 90 + $rng->rnd(61);
                $ya = 40 + $rng->rnd(80);
                if ($rng->rnd(2) === 0) {
                    $y1 = $ya;
                    $y2 = min(268, $ya + $dy);
                } else {
                    $y1 = min(268, $ya + $dy);
                    $y2 = $ya;
                }
                $guirlande($ancre, $y1, $ancre + $dx, $y2, $rng->rnd(96));
                for ($i = 0; $i < 3; $i++) {
                    $cadeau($ancre + $dx + 30 + 24 * $i, intdiv($y1 + $y2, 2));
                }
                $largeur = $dx + 40;
            } elseif ($h < 70) { // guirlande horizontale
                $longueur = 100 + $rng->rnd(71);
                $y = 60 + $rng->rnd(171);
                $guirlande($ancre, $y, $ancre + $longueur, $y, $rng->rnd(96));
                $yg = $y >= 90 ? $y - 32 : $y + 32;
                for ($i = 0; $i < 4; $i++) {
                    $cadeau($ancre + intdiv($longueur * ($i + 1), 5), $yg);
                }
                $largeur = $longueur;
            } elseif ($h < 85) { // corbeaux grincheux
                $n = 1 + $rng->rnd(3);
                for ($i = 0; $i < $n; $i++) {
                    $corbeau($ta + 22 * $i, 50 + $rng->rnd(191), $rng->rnd(120));
                }
                $largeur = 40 * $n + 60;
            } else { // boules de neige
                $n = 2 + $rng->rnd(3);
                for ($i = 0; $i < $n; $i++) {
                    $dt = 60 + $rng->rnd(21);
                    $boule($ta + 16 * $i - $dt, $dt, 40 + $rng->rnd(211));
                }
                $largeur = 30 * $n + 80;
            }
        } else {
            $g = $rng->rnd(100);
            if ($g < 30) { // ligne
                $n = 4 + $rng->rnd(4);
                $y = 60 + $rng->rnd(171);
                for ($i = 0; $i < $n; $i++) {
                    $cadeau($ancre + 24 * $i, $y);
                }
                $largeur = 24 * $n;
            } elseif ($g < 60) { // vague
                $n = 6 + $rng->rnd(4);
                $c = 100 + $rng->rnd(101);
                for ($i = 0; $i < $n; $i++) {
                    $cadeau($ancre + 22 * $i, $c + $onde[$i % 12]);
                }
                $largeur = 22 * $n;
            } elseif ($g < 75) { // diagonale
                $n = 5 + $rng->rnd(3);
                $y0 = 50 + $rng->rnd(100);
                $montant = $rng->rnd(2);
                $pas = 14 + $rng->rnd(6);
                for ($i = 0; $i < $n; $i++) {
                    $cadeau($ancre + 24 * $i, $montant === 1 ? $y0 + $i * $pas : $y0 + ($n - 1 - $i) * $pas);
                }
                $largeur = 24 * $n;
            } elseif ($g < 88) { // sapin
                $c = 40 + $rng->rnd(111);
                foreach ($arbre as $r => $ligne) {
                    for ($col = 0; $col < 7; $col++) {
                        if ($ligne[$col] === 'X') {
                            $cadeau($ancre + 20 * $col, $c + 20 * $r);
                        }
                    }
                }
                $largeur = 140;
            } else { // cœur
                $c = 40 + $rng->rnd(111);
                foreach ($coeur as $r => $ligne) {
                    for ($col = 0; $col < 7; $col++) {
                        if ($ligne[$col] === 'X') {
                            $cadeau($ancre + 20 * $col, $c + 20 * $r);
                        }
                    }
                }
                $largeur = 140;
            }
        }

        $ecart = 260 - intdiv($densite * 9, 10) + $rng->rnd(120);
        $suivant = avent_pnt_premier_tick($px, $ancre + $largeur + $ecart - AVENT_PNT_PX);
        $ta = $suivant <= $ta ? $ta + 1 : $suivant;
    }

    usort($objets, static function (array $a, array $b): int {
        return [$a['t0'], $a['id']] <=> [$b['t0'], $b['id']];
    });
    return $objets;
}

// ---------------------------------------------------------------------------
// Simulation
// ---------------------------------------------------------------------------

/** Vrai si le point (px, py) est à moins de 14 px du segment [(ax, ay), (bx, by)] (entiers). */
function avent_pnt_pres_du_segment(int $pxx, int $pyy, int $ax, int $ay, int $bx, int $by): bool
{
    $r2 = 14 * 14;
    $dx = $bx - $ax;
    $dy = $by - $ay;
    $den = $dx * $dx + $dy * $dy;
    $wx = $pxx - $ax;
    $wy = $pyy - $ay;
    $scal = $wx * $dx + $wy * $dy;
    if ($scal <= 0) {
        return $wx * $wx + $wy * $wy <= $r2;
    }
    if ($scal >= $den) {
        $ux = $pxx - $bx;
        $uy = $pyy - $by;
        return $ux * $ux + $uy * $uy <= $r2;
    }
    $croix = $wx * $dy - $wy * $dx;
    return $croix * $croix <= $r2 * $den;
}

/** Garniture clignotante : éteinte 0-39, avertissement 40-55, allumée 56-95 (période 96 ticks). */
function avent_pnt_garniture_allumee(int $tick, int $phase): bool
{
    return (($tick + $phase) % 96) >= 56;
}

/**
 * Rejoue une partie. $entrees : ticks où l'état du doigt change (liste croissante, doigt levé au départ).
 * $ticksMax : nombre de ticks que le client dit avoir joués.
 * Renvoie ['ticks', 'cadeaux', 'coeurs', 'pertes', 'mort'].
 */
function avent_pnt_simuler(array $p, int $graine, array $entrees, int $ticksMax): array
{
    $tables = avent_pnt_defilement($p);
    $px = $tables['px'];
    $objets = avent_pnt_niveau($p, $graine, $px);
    $nb = count($objets);

    $y = 130 * AVENT_PNT_SC;
    $vy = 0;
    $coeurs = AVENT_PNT_COEURS;
    $invul = 0;
    $cadeaux = 0;
    $presse = false;
    $idx = 0;
    $nbEntrees = count($entrees);
    $prochain = 0;
    $actifs = [];
    $joues = 0;
    $mort = false;
    $ticksMax = min($ticksMax, AVENT_PNT_TICKS);

    for ($t = 0; $t < $ticksMax; $t++) {
        while ($idx < $nbEntrees && $entrees[$idx] <= $t) {
            $presse = !$presse;
            $idx++;
        }

        // Physique
        if ($presse) {
            $vy -= $p['poussee'];
        } else {
            $vy += $p['gravite'];
        }
        if ($vy < -AVENT_PNT_VHAUT) {
            $vy = -AVENT_PNT_VHAUT;
        }
        if ($vy > AVENT_PNT_VBAS) {
            $vy = AVENT_PNT_VBAS;
        }
        $y += $vy;
        if ($y < AVENT_PNT_YMIN) {
            $y = AVENT_PNT_YMIN;
            if ($vy < 0) {
                $vy = 0;
            }
        }
        if ($y > AVENT_PNT_YMAX) {
            $y = AVENT_PNT_YMAX;
            if ($vy > 0) {
                $vy = 0;
            }
        }

        // Collisions, à l'instant T = t + 1
        $T = $t + 1;
        $joues = $T;
        $spx = $px[$T];
        $ppy = intdiv($y, AVENT_PNT_SC);
        while ($prochain < $nb && $objets[$prochain]['t0'] <= $T) {
            $actifs[] = $prochain;
            $prochain++;
        }
        $touche = false;
        $reste = [];
        foreach ($actifs as $i) {
            if ($objets[$i]['t1'] < $T) {
                continue;
            }
            $reste[] = $i;
            $o = $objets[$i];
            switch ($o['k']) {
                case 'g':
                    if ($o['pris'] === 0) {
                        $sx = $o['xw'] - $spx;
                        if (abs($sx - AVENT_PNT_PX) <= 7 + AVENT_PNT_HW && abs($o['y'] - $ppy) <= 7 + AVENT_PNT_HH) {
                            $objets[$i]['pris'] = 1;
                            $cadeaux++;
                        }
                    }
                    break;
                case 'i':
                    $sx = $o['xw'] - $spx;
                    if (abs($sx - AVENT_PNT_PX) <= 6 + AVENT_PNT_HW && $ppy - AVENT_PNT_HH <= $o['len'] - 5) {
                        $touche = true;
                    }
                    break;
                case 'a':
                    if (avent_pnt_garniture_allumee($T, $o['ph'])
                        && avent_pnt_pres_du_segment(AVENT_PNT_PX, $ppy, $o['x1'] - $spx, $o['y1'], $o['x2'] - $spx, $o['y2'])) {
                        $touche = true;
                    }
                    break;
                case 'c':
                    $cx = AVENT_PNT_PX + ($px[$o['ta']] - $spx) - intdiv(AVENT_PNT_CROW_V * ($T - $o['ta']), AVENT_PNT_SC);
                    $cy = $o['y0'] + avent_pnt_tri((($T + $o['ph']) * 4) % 120) - 30;
                    if (abs($cx - AVENT_PNT_PX) <= 11 + AVENT_PNT_HW && abs($cy - $ppy) <= 8 + AVENT_PNT_HH) {
                        $touche = true;
                    }
                    break;
                case 's':
                    $u = $T - $o['ts'];
                    $bx = (360 + 16) - intdiv(((360 + 16) - AVENT_PNT_PX) * $u, $o['dt']);
                    $by = 260 + intdiv(($o['yt'] - 260) * $u, $o['dt']) - intdiv(4 * AVENT_PNT_ARC * $u * ($o['dt'] - $u), $o['dt'] * $o['dt']);
                    if (abs($bx - AVENT_PNT_PX) <= 7 + AVENT_PNT_HW && abs($by - $ppy) <= 7 + AVENT_PNT_HH) {
                        $touche = true;
                    }
                    break;
            }
        }
        $actifs = $reste;

        if ($invul > 0) {
            $invul--;
        } elseif ($touche) {
            $coeurs--;
            $invul = AVENT_PNT_INVUL;
            if ($coeurs <= 0) {
                $mort = true;
                break;
            }
        }
    }

    return [
        'ticks'   => $joues,
        'cadeaux' => $cadeaux,
        'coeurs'  => max(0, $coeurs),
        'pertes'  => AVENT_PNT_COEURS - max(0, $coeurs),
        'mort'    => $mort,
    ];
}

/** Score 0..100 : 70 points de cadeaux (plafonnés à 40), 30 points de survie (sur 90 s). Entiers. */
function avent_pnt_score(int $cadeaux, int $ticks): int
{
    $cadeaux = min($cadeaux, AVENT_PNT_CAP);
    $ticks = min($ticks, AVENT_PNT_TICKS);
    return intdiv(315 * $cadeaux + $ticks + 90, 180);
}

/**
 * Rejoue la partie à partir des entrées et en déduit le score. Le score éventuellement envoyé par
 * le navigateur est ignoré. payload = {"entrees":[12, 40, …], "ticks": 3120}.
 */
function avent_jeu_pere_noel_turbo_verifier(array $config, array $payload, int $dureeMs, array $contexte = []): array
{
    $nul = ['score' => 0, 'reussite' => false, 'invalide' => true];

    $entrees = $payload['entrees'] ?? null;
    $ticks = $payload['ticks'] ?? null;
    if (!is_array($entrees) || !is_int($ticks) || $ticks < 0 || $ticks > AVENT_PNT_TICKS) {
        return $nul;
    }
    if (count($entrees) > AVENT_PNT_MAX_ENTREES || !array_is_list($entrees)) {
        return $nul;
    }
    $precedent = -1;
    foreach ($entrees as $e) {
        if (!is_int($e) || $e <= $precedent || $e >= AVENT_PNT_TICKS) {
            return $nul; // pas strictement croissant, hors bornes, ou pas un entier
        }
        $precedent = $e;
    }
    // Durée impossible : on ne peut pas avoir joué plus de ticks que le temps écoulé depuis le départ.
    if ($ticks * 1000 > ($dureeMs + AVENT_GRACE_MS_PNT) * AVENT_PNT_TPS) {
        return $nul;
    }

    $resultat = avent_pnt_simuler(avent_pnt_params($config), avent_pnt_graine($contexte), $entrees, $ticks);
    $score = avent_pnt_score($resultat['cadeaux'], $resultat['ticks']);
    return [
        'score'    => $score,
        'reussite' => $score >= 50,
        'cadeaux'  => $resultat['cadeaux'],
        'ticks'    => $resultat['ticks'],
        'pertes'   => $resultat['pertes'],
    ];
}

// Tolérance réseau sur la durée (alignée sur AVENT_GRACE_MS de la plateforme, 5 s).
const AVENT_GRACE_MS_PNT = 5000;
