<?php
/**
 * Jeu « Cap sur Robinson island » — module serveur.
 *
 * Principe : le navigateur ne fait AUCUN appel pendant la partie (un seul appel « embarquer » juste
 * avant le départ, qui n'est jamais attendu : la partie démarre même s'il échoue). Il enregistre seulement les ticks
 * (60 par seconde) où une impulsion de rame a été prise en compte, et envoie ce journal à la fin.
 * verifier() REJOUE toute la partie ici : mêmes constantes, même calendrier de chute des lutins,
 * recalcul du tick d'arrivée, puis du score. Le temps ou le score annoncés par le navigateur ne sont
 * jamais lus.
 *
 * Toute la simulation est en entiers (division tronquée vers zéro) : PHP et JavaScript donnent
 * exactement le même résultat. SI VOUS MODIFIEZ UNE RÈGLE ICI, FAITES-LA AUSSI DANS game.js (bloc
 * « SIMULATION »), puis incrémentez AVENT_BARQUE_VERSION des deux côtés.
 *
 * Contenu attendu (JSON saisi dans l'administration), tout est facultatif :
 *   {"parametres": {"distance": 420000, "impulsion": 120, "frottement": 40, "courant": 1,
 *                   "taille_fin": 450, "temps_plein": 25, "points_min": 10}}
 *
 * À chaque tick t (0 à 3599), dans cet ordre :
 *   1. les lutins dont le tick de chute est <= t sont à l'eau ;
 *   2. si une impulsion est journalisée au tick t : v += impulsion * équipage / 8 ;
 *   3. frottement : v -= v * frottement / 1024 ; courant : v -= courant ;
 *   4. position += v (jamais en dessous du ponton de départ) ; si position >= distance, arrivée.
 */

const AVENT_BARQUE_VERSION = 1;
const AVENT_BARQUE_TICKS   = 3600;  // 60 s à 60 ticks par seconde : au-delà, la barque coule
const AVENT_BARQUE_TPS     = 60;
const AVENT_BARQUE_ECART   = 5;     // ticks minimum entre deux impulsions (5 ticks = 83 ms >= 80 ms)
const AVENT_BARQUE_MAX_IMP = 720;   // AVENT_BARQUE_TICKS / AVENT_BARQUE_ECART
const AVENT_BARQUE_LUTINS  = 8;
const AVENT_BARQUE_GRACE_MS = 5000; // tolérance sur la durée réelle (alignée sur AVENT_GRACE_MS)
/** Tailles (en ‰ de la taille de départ) que la barque franchit en rétrécissant : un lutin tombe à chacune. */
const AVENT_BARQUE_SEUILS  = [880, 780, 680, 590, 510, 440, 380];

/** Valeurs par défaut et bornes des paramètres réglables : [défaut, min, max]. */
function avent_barque_bornes(): array
{
    return [
        'distance'    => [420000, 100000, 1000000], // distance jusqu'à l'île (unités de position)
        'impulsion'   => [120, 40, 400],            // vitesse ajoutée par un coup de rame, équipage complet (unités/tick)
        'frottement'  => [40, 10, 120],             // freinage de l'eau : v perd v * frottement / 1024 à chaque tick
        'courant'     => [1, 0, 10],                // recul constant dû au courant (unités/tick²)
        'taille_fin'  => [450, 200, 800],           // taille de la barque à 60 s, en ‰ (1000 au départ)
        'temps_plein' => [25, 10, 40],              // secondes : 100 points jusque-là
        'points_min'  => [10, 0, 50],               // points si l'on arrive à 60 s pile
    ];
}

/** Paramètres effectifs : défauts, remplacés par ceux de l'administrateur s'ils sont valides. */
function avent_barque_params(array $config): array
{
    $saisis = is_array($config['parametres'] ?? null) ? $config['parametres'] : [];
    $params = [];
    foreach (avent_barque_bornes() as $nom => [$defaut, $min, $max]) {
        $v = $saisis[$nom] ?? $defaut;
        $params[$nom] = is_int($v) ? max($min, min($max, $v)) : $defaut;
    }
    return $params;
}

/** Vérifie la configuration saisie par l'administrateur. Renvoie null si elle est valide. */
function avent_jeu_barque_valider(array $config): ?string
{
    $saisis = $config['parametres'] ?? [];
    if (!is_array($saisis)) {
        return '« parametres » doit être un objet.';
    }
    foreach (avent_barque_bornes() as $nom => [$defaut, $min, $max]) {
        if (!array_key_exists($nom, $saisis)) {
            continue;
        }
        if (!is_int($saisis[$nom]) || $saisis[$nom] < $min || $saisis[$nom] > $max) {
            return "Paramètre « $nom » : entier attendu entre $min et $max (défaut $defaut).";
        }
    }
    return null;
}

/** Ticks de chute des lutins, dans l'ordre : la barque passe sous chaque seuil de taille (rétrécissement linéaire). */
function avent_barque_chutes(array $p): array
{
    $chutes = [];
    $perte = 1000 - $p['taille_fin'];
    foreach (AVENT_BARQUE_SEUILS as $seuil) {
        if ($seuil > $p['taille_fin']) {
            $chutes[] = intdiv((1000 - $seuil) * AVENT_BARQUE_TICKS + $perte - 1, $perte); // plafond
        }
    }
    return $chutes;
}

/** Configuration envoyée au navigateur : le client rejoue exactement la même simulation. */
function avent_jeu_barque_config_publique(array $config, array $contexte = []): array
{
    return [
        'version'    => AVENT_BARQUE_VERSION,
        'parametres' => avent_barque_params($config),
        'ticks'      => AVENT_BARQUE_TICKS,
        'ecart'      => AVENT_BARQUE_ECART,
        'lutins'     => AVENT_BARQUE_LUTINS,
        'chutes'     => avent_barque_chutes(avent_barque_params($config)),
        'deja_parti' => avent_barque_depart($contexte) !== null,
    ];
}

/** Moment du départ (ms depuis le début de la partie de la plateforme), ou null si la barque n'est pas encore partie. */
function avent_barque_depart(array $contexte): ?int
{
    $etat = $contexte['etat'] ?? null;
    if ($etat === null && isset($contexte['partie']) && function_exists('avent_etat_lire')) {
        $etat = avent_etat_lire((int) $contexte['partie']);
    }
    return is_array($etat) && isset($etat['depart_ms']) && is_int($etat['depart_ms']) ? $etat['depart_ms'] : null;
}

/** Appel « embarquer » : enregistre le départ (une seule fois). */
function avent_jeu_barque_action(array $config, array $requete, array &$etat, array $contexte, int $ecouleMs): array
{
    if (($requete['action'] ?? '') !== 'embarquer') {
        return ['erreur' => 'Action inconnue.'];
    }
    if (!isset($etat['depart_ms'])) {
        $etat['depart_ms'] = $ecouleMs;
        return ['depart' => true];
    }
    return ['depart' => false];
}

/**
 * Rejoue la partie. $impulsions : ticks strictement croissants (déjà validés).
 * Renvoie ['arrivee' => ticks écoulés à l'arrivée ou null, 'equipage' => lutins restants, 'position' => …].
 */
function avent_barque_simuler(array $p, array $impulsions): array
{
    $chutes = avent_barque_chutes($p);
    $marque = array_flip($impulsions);
    $pos = 0;
    $v = 0;
    $equipage = AVENT_BARQUE_LUTINS;
    $iChute = 0;
    for ($t = 0; $t < AVENT_BARQUE_TICKS; $t++) {
        while ($iChute < count($chutes) && $chutes[$iChute] <= $t) {
            $equipage--;
            $iChute++;
        }
        if (isset($marque[$t])) {
            $v += intdiv($p['impulsion'] * $equipage, AVENT_BARQUE_LUTINS);
        }
        $v -= intdiv($v * $p['frottement'], 1024);
        $v -= $p['courant'];
        $pos += $v;
        if ($pos < 0) {
            $pos = 0;
            if ($v < 0) {
                $v = 0;
            }
        }
        if ($pos >= $p['distance']) {
            return ['arrivee' => $t + 1, 'equipage' => $equipage, 'position' => $pos];
        }
    }
    return ['arrivee' => null, 'equipage' => $equipage, 'position' => $pos];
}

/** Barème : 100 points jusqu'à temps_plein, décroissance linéaire jusqu'à points_min à 60 s. */
function avent_barque_score(int $arrivee, array $p): int
{
    $plein = $p['temps_plein'] * AVENT_BARQUE_TPS;
    if ($arrivee <= $plein) {
        return 100;
    }
    $largeur = AVENT_BARQUE_TICKS - $plein;
    $perte = (100 - $p['points_min']) * ($arrivee - $plein);
    $points = 100 - intdiv(2 * $perte + $largeur, 2 * $largeur); // arrondi à l'entier le plus proche
    return max($p['points_min'], min(100, $points));
}

/**
 * Corrige la partie. Payload attendu : {"impulsions": [ticks…]} — rien d'autre n'est lu.
 */
function avent_jeu_barque_verifier(array $config, array $payload, int $dureeMs, array $contexte = []): array
{
    $nul = ['score' => 0, 'reussite' => false, 'invalide' => true];

    $imp = $payload['impulsions'] ?? null;
    if (!is_array($imp) || !array_is_list($imp) || count($imp) > AVENT_BARQUE_MAX_IMP) {
        return $nul;
    }
    $precedent = null;
    foreach ($imp as $t) {
        if (!is_int($t) || $t < 0 || $t >= AVENT_BARQUE_TICKS) {
            return $nul;
        }
        if ($precedent !== null && $t - $precedent < AVENT_BARQUE_ECART) {
            return $nul; // trop rapproché (ou pas strictement croissant) : débit impossible à la main
        }
        $precedent = $t;
    }

    $p = avent_barque_params($config);
    $res = avent_barque_simuler($p, $imp);
    if ($res['arrivee'] === null) {
        return ['score' => 0, 'reussite' => false, 'coule' => true, 'equipage' => $res['equipage']];
    }
    // Durée impossible : la partie ne peut pas avoir duré plus de ticks que de temps réel écoulé depuis le départ
    // (enregistré par « embarquer » s'il a abouti, sinon depuis le début de la partie de la plateforme).
    $depart = avent_barque_depart($contexte);
    $disponibleMs = $dureeMs - ($depart ?? 0);
    if ($res['arrivee'] * 1000 > ($disponibleMs + AVENT_BARQUE_GRACE_MS) * AVENT_BARQUE_TPS) {
        return $nul;
    }
    $score = avent_barque_score($res['arrivee'], $p);
    return [
        'score'    => $score,
        'reussite' => true,
        'arrivee'  => $res['arrivee'],
        'equipage' => $res['equipage'],
    ];
}
