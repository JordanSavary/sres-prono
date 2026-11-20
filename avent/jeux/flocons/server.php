<?php
/**
 * Jeu « Chasse aux flocons » (réflexe) — module serveur.
 *
 * Des flocons ❄ tombent ; il faut les toucher. Des flocons piégés 🔥 tombent aussi : en toucher un fait perdre
 * une vie (3 au départ), et la partie s'arrête à la dernière vie. Un flocon (normal ou piégé) manqué n'a aucun effet.
 *
 * Contenu attendu (JSON saisi dans l'administration), tout est facultatif sauf « cible » :
 *   {"cible": 20, "rythme_ms": 650, "vies": 3,
 *    "piege_debut": 200, "piege_fin": 333, "delai_sans_piege_ms": 5000, "distance_min": 280, "duree_ref_ms": 100000}
 *   cible                 flocons à attraper pour 100 points (3 à 200)
 *   rythme_ms             délai entre l'apparition de deux flocons (300 à 2000)
 *   vies                  nombre de vies (1 à 5, défaut 3)
 *   piege_debut/piege_fin proportion de flocons piégés (en ‰) au début et à la fin de duree_ref_ms (défauts 200 et 333)
 *   delai_sans_piege_ms   aucun piège avant ce délai (défaut 5000)
 *   distance_min          écart minimal (en ‰ de la largeur ET de la hauteur du terrain) entre un piège et tout autre
 *                         flocon visible en même temps (défaut 280, soit ~90 px à 360 px de large)
 *   duree_ref_ms          durée sur laquelle la proportion de pièges passe de piege_debut à piege_fin (défaut 100000)
 *
 * Principe de vérification (comme jeux/barque/) : tout le calendrier des flocons — apparition, durée de chute, position,
 * piégé ou non — est calculé à partir d'une graine propre au joueur et au jour (secret du serveur), par un générateur en
 * entiers que PHP et JavaScript calculent à l'identique. Le navigateur journalise ses touchers [numéro du flocon, temps en ms
 * depuis le départ] et envoie ce journal à la fin ; verifier() REJOUE la partie : un toucher compte seulement si le flocon
 * existe et est encore en train de tomber, un piège coûte une vie, la partie s'arrête à la dernière vie. Le score,
 * le nombre de vies ou le temps annoncés par le navigateur ne sont jamais lus.
 *
 * SI VOUS MODIFIEZ UNE RÈGLE DE GÉNÉRATION ICI, FAITES-LA AUSSI DANS game.js (bloc « GÉNÉRATEUR »), puis incrémentez
 * AVENT_FLOCONS_VERSION des deux côtés.
 */

const AVENT_FLOCONS_VERSION   = 2;
const AVENT_FLOCONS_MAX       = 520;    // flocons générés au plus (150 s à 300 ms)
const AVENT_FLOCONS_ECART_MS  = 100;    // deux touchers espacés de moins sont ignorés (le second)
const AVENT_FLOCONS_TOLERANCE = 250;    // ms de tolérance après la fin de chute d'un flocon (latence de l'animation)
const AVENT_FLOCONS_GRACE_MS  = 5000;   // tolérance sur la durée réelle (alignée sur AVENT_GRACE_MS)
const AVENT_FLOCONS_MAX_CLICS = 800;
const AVENT_FLOCONS_CHUTE_MAX = 4000;   // durée de chute maximale d'un flocon (2600 à 4000 ms)
const AVENT_FLOCONS_ESSAIS_X  = 24;     // tirages de position avant repli

/** Défauts et bornes : [défaut, min, max]. */
function avent_flocons_bornes(): array
{
    return [
        'cible'               => [20, 3, 200],
        'rythme_ms'           => [650, 300, 2000],
        'vies'                => [3, 1, 5],
        'piege_debut'         => [200, 0, 1000],
        'piege_fin'           => [333, 0, 1000],
        'delai_sans_piege_ms' => [5000, 0, 60000],
        'distance_min'        => [280, 100, 600],
        'duree_ref_ms'        => [100000, 10000, 150000],
    ];
}

function avent_flocons_params(array $config): array
{
    $p = [];
    foreach (avent_flocons_bornes() as $nom => [$defaut, $min, $max]) {
        $v = $config[$nom] ?? $defaut;
        $p[$nom] = is_int($v) ? max($min, min($max, $v)) : $defaut;
    }
    return $p;
}

function avent_jeu_flocons_valider(array $config): ?string
{
    $cible = $config['cible'] ?? null;
    if (!is_int($cible) || $cible < 3 || $cible > 200) {
        return '« cible » doit être un entier entre 3 et 200.';
    }
    foreach (avent_flocons_bornes() as $nom => [$defaut, $min, $max]) {
        if (array_key_exists($nom, $config) && (!is_int($config[$nom]) || $config[$nom] < $min || $config[$nom] > $max)) {
            return "« $nom » doit être un entier entre $min et $max (défaut $defaut).";
        }
    }
    return null;
}

/** Graine propre au joueur et au jour (jamais nulle), dérivée du secret du serveur. */
function avent_flocons_graine(array $contexte): int
{
    $secret = function_exists('avent_secret') ? avent_secret() : 'secret-de-test';
    $g = hexdec(substr(hash_hmac('sha256', 'flocons|' . (int) ($contexte['jour'] ?? 0) . '|' . (string) ($contexte['pseudo'] ?? ''), $secret), 0, 8));
    return $g === 0 ? 1 : $g;
}

/** Départ enregistré par l'appel « depart » (ms depuis le début de la partie de la plateforme), ou null. */
function avent_flocons_depart(array $contexte): ?int
{
    $etat = $contexte['etat'] ?? null;
    if ($etat === null && isset($contexte['partie']) && function_exists('avent_etat_lire')) {
        $etat = avent_etat_lire((int) $contexte['partie']);
    }
    return is_array($etat) && isset($etat['depart_ms']) && is_int($etat['depart_ms']) ? $etat['depart_ms'] : null;
}

function avent_jeu_flocons_config_publique(array $config, array $contexte = []): array
{
    $p = avent_flocons_params($config);
    return [
        'version'   => AVENT_FLOCONS_VERSION,
        'graine'    => avent_flocons_graine($contexte),
        'deja_parti' => avent_flocons_depart($contexte) !== null,
    ] + $p;
}

/** Appel « depart » : enregistre le départ (une seule fois) pour que le rechargement ne permette pas de rejouer. */
function avent_jeu_flocons_action(array $config, array $requete, array &$etat, array $contexte, int $ecouleMs): array
{
    if (($requete['action'] ?? '') !== 'depart') {
        return ['erreur' => 'Action inconnue.'];
    }
    if (!isset($etat['depart_ms'])) {
        $etat['depart_ms'] = $ecouleMs;
        return ['depart' => true];
    }
    return ['depart' => false];
}

/* ---------------------------------------------------------------------------------------------------------------
 * GÉNÉRATEUR (identique dans game.js). Tout en entiers ; xorshift32 sans multiplication (pas de dépassement en PHP).
 * ------------------------------------------------------------------------------------------------------------- */

/** Prochain nombre pseudo-aléatoire de 32 bits ; $x est l'état du générateur (modifié). */
function avent_flocons_alea(int &$x): int
{
    $x ^= ($x << 13) & 0xFFFFFFFF;
    $x ^= $x >> 17;
    $x ^= ($x << 5) & 0xFFFFFFFF;
    return $x;
}

/** Deux flocons se « touchent-ils » à un instant de leur chute commune ? (écart < D en ‰ sur les deux axes) */
function avent_flocons_proches(array $a, array $b, int $d): bool
{
    if (abs($a['x'] - $b['x']) >= $d) {
        return false;
    }
    $debut = max($a['t'], $b['t']);
    $fin = min($a['t'] + $a['chute'], $b['t'] + $b['chute']);
    for ($t = $debut; $t <= $fin; $t += 50) {
        $ya = intdiv(($t - $a['t']) * 1000, $a['chute']);
        $yb = intdiv(($t - $b['t']) * 1000, $b['chute']);
        if (abs($ya - $yb) < $d) {
            return true;
        }
    }
    return false;
}

/**
 * Génère les flocons 0 à $n - 1 : tableau de ['t' => apparition ms, 'chute' => durée ms, 'x' => position ‰ de la largeur, 'piege' => bool].
 * Règles : pas de piège avant delai_sans_piege_ms ; proportion de pièges linéaire de piege_debut à piege_fin sur duree_ref_ms ;
 * un piège est à au moins distance_min (‰) de tout autre flocon visible en même temps (sinon il redevient un flocon normal) ;
 * un flocon normal ne se place jamais à moins de distance_min d'un piège visible en même temps (s'il n'y a pas de place, il n'est pas lancé : « absent »).
 */
function avent_flocons_generer(array $p, int $graine, int $n): array
{
    $x = $graine === 0 ? 1 : $graine & 0xFFFFFFFF;
    for ($i = 0; $i < 4; $i++) {                 // quelques tirages à blanc : graines proches, suites différentes
        avent_flocons_alea($x);
    }
    $liste = [];
    for ($k = 0; $k < $n; $k++) {
        $t = $k * $p['rythme_ms'];
        $chute = 2600 + avent_flocons_alea($x) % 1401;
        $piege = false;
        if ($t >= $p['delai_sans_piege_ms']) {
            $part = min(1000, intdiv($t * 1000, $p['duree_ref_ms']));
            $proportion = $p['piege_debut'] + intdiv(($p['piege_fin'] - $p['piege_debut']) * $part, 1000);
            $piege = avent_flocons_alea($x) % 1000 < $proportion;
        }
        // flocons encore visibles quand celui-ci apparaît (les plus anciens ont déjà fini de tomber)
        $voisins = [];
        for ($j = $k - 1; $j >= 0 && $liste[$j]['t'] + AVENT_FLOCONS_CHUTE_MAX > $t; $j--) {
            if (empty($liste[$j]['absent']) && $liste[$j]['t'] + $liste[$j]['chute'] > $t) {         // un flocon plus ancien peut tomber plus lentement qu'un plus récent
                $voisins[] = $liste[$j];
            }
        }
        $choisi = null;
        for ($essai = 0; $essai < AVENT_FLOCONS_ESSAIS_X && $choisi === null; $essai++) {
            $cx = 40 + avent_flocons_alea($x) % 821;                       // 4 % à 86 % de la largeur
            $f = ['t' => $t, 'chute' => $chute, 'x' => $cx, 'piege' => $piege];
            $ok = true;
            foreach ($voisins as $v) {
                if (($piege || $v['piege']) && avent_flocons_proches($f, $v, $p['distance_min'])) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $choisi = $f;
            }
        }
        if ($choisi === null && $piege) {                                  // pas de place pour le piège : flocon normal
            $piege = false;
            for ($essai = 0; $essai < AVENT_FLOCONS_ESSAIS_X && $choisi === null; $essai++) {
                $cx = 40 + avent_flocons_alea($x) % 821;
                $f = ['t' => $t, 'chute' => $chute, 'x' => $cx, 'piege' => false];
                $ok = true;
                foreach ($voisins as $v) {
                    if ($v['piege'] && avent_flocons_proches($f, $v, $p['distance_min'])) {
                        $ok = false;
                        break;
                    }
                }
                if ($ok) {
                    $choisi = $f;
                }
            }
        }
        if ($choisi === null) {                                            // repli : balayage de la largeur, depuis un départ tiré
            $depart = avent_flocons_alea($x) % 41;
            for ($i = 0; $i < 41 && $choisi === null; $i++) {
                $cx = 40 + (($depart + $i) % 41) * 20;
                $f = ['t' => $t, 'chute' => $chute, 'x' => $cx, 'piege' => false];
                $ok = true;
                foreach ($voisins as $v) {
                    if ($v['piege'] && avent_flocons_proches($f, $v, $p['distance_min'])) {
                        $ok = false;
                        break;
                    }
                }
                if ($ok) {
                    $choisi = $f;
                }
            }
            // Aucune place sans frôler un piège : ce flocon n'apparaît pas (rare ; jamais de flocon collé à un piège).
            $choisi ??= ['t' => $t, 'chute' => $chute, 'x' => 0, 'piege' => false, 'absent' => true];
        }
        $liste[] = $choisi;
    }
    return $liste;
}

/**
 * Rejoue les touchers. $clics : liste de [numéro, temps ms] déjà validée (temps croissants).
 * Renvoie ['attrapes', 'vies', 'pieges' (touchés), 'fini' (cible atteinte ou plus de vie), 'perdu' (plus de vie)].
 */
function avent_flocons_rejouer(array $p, array $flocons, array $clics): array
{
    $attrapes = 0;
    $vies = $p['vies'];
    $pieges = 0;
    $vus = [];
    $dernier = null;
    $fini = false;
    foreach ($clics as [$k, $t]) {
        if ($dernier !== null && $t - $dernier < AVENT_FLOCONS_ECART_MS) {
            continue;                                                      // débit impossible à la main : ignoré
        }
        if ($k >= count($flocons) || isset($vus[$k])) {
            continue;
        }
        $f = $flocons[$k];
        if (!empty($f['absent'])) {
            continue;                                                      // ce flocon n'a pas été lancé
        }
        if ($t < $f['t'] || $t > $f['t'] + $f['chute'] + AVENT_FLOCONS_TOLERANCE) {
            continue;                                                      // flocon pas encore apparu, ou déjà au sol
        }
        $vus[$k] = true;
        $dernier = $t;
        if ($f['piege']) {
            $pieges++;
            $vies--;
            if ($vies <= 0) {
                $fini = true;
                break;
            }
        } else {
            $attrapes++;
            if ($attrapes >= $p['cible']) {
                $fini = true;
                break;
            }
        }
    }
    return ['attrapes' => $attrapes, 'vies' => max(0, $vies), 'pieges' => $pieges, 'fini' => $fini, 'perdu' => $vies <= 0];
}

/** Score : inchangé (100 × flocons attrapés / cible, plafonné à 100) ; la perte des vies n'ajoute aucun malus. */
function avent_flocons_score(int $attrapes, int $cible): int
{
    return (int) min(100, round(100 * $attrapes / $cible));
}

function avent_jeu_flocons_verifier(array $config, array $payload, int $dureeMs, array $contexte = []): array
{
    $nul = ['score' => 0, 'reussite' => false, 'invalide' => true];
    $clics = $payload['clics'] ?? [];
    if (!is_array($clics) || !array_is_list($clics) || count($clics) > AVENT_FLOCONS_MAX_CLICS) {
        return $nul;
    }
    $precedent = 0;
    foreach ($clics as $c) {
        if (!is_array($c) || count($c) !== 2 || !array_is_list($c) || !is_int($c[0]) || !is_int($c[1]) || $c[0] < 0 || $c[1] < 0 || $c[1] < $precedent) {
            return $nul;                                                   // journal mal formé ou non chronologique
        }
        $precedent = $c[1];
    }
    // Durée impossible : aucun toucher après la fin de la partie (depuis le départ noté par « depart », sinon depuis le début de la plateforme).
    $depart = avent_flocons_depart($contexte) ?? 0;
    if ($clics && $precedent > $dureeMs - $depart + AVENT_FLOCONS_GRACE_MS) {
        return $nul;
    }
    $p = avent_flocons_params($config);
    $n = min(AVENT_FLOCONS_MAX, intdiv(max(0, $precedent), $p['rythme_ms']) + 2);
    $flocons = avent_flocons_generer($p, avent_flocons_graine($contexte), $n);
    $res = avent_flocons_rejouer($p, $flocons, $clics);
    return [
        'score'    => avent_flocons_score($res['attrapes'], $p['cible']),
        'reussite' => $res['attrapes'] >= $p['cible'],
        'attrapes' => $res['attrapes'],
        'vies'     => $res['vies'],
        'pieges'   => $res['pieges'],
    ];
}
