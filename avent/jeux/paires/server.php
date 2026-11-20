<?php
/**
 * Jeu « Les Paires du Père Noël » — module serveur.
 *
 * Memory 5×5 : 12 paires d'emojis + une carte « charbon » sans paire. La disposition est tirée ici,
 * à partir d'une graine secrète propre au joueur et au jour, et ne quitte JAMAIS le serveur : chaque
 * retournement passe par l'action « retourner » (api/action.php), qui renvoie seulement la carte demandée.
 * Le serveur tient l'état (première carte en attente, paires trouvées, charbons, horodatages), mesure
 * lui-même le temps et ignore tout temps annoncé par le navigateur.
 *
 * Contenu attendu (JSON saisi dans l'administration), tout est facultatif :
 *   {"parametres": {"lignes": 5, "colonnes": 5, "duree_max": 110, "temps_parfait": 35,
 *                   "points_min": 10, "penalite": 3, "emojis": ["🎅", "🎄", …]}}
 *
 * Temps effectif = temps réel (du « Commencer » du jeu à la dernière paire) + penalite × charbons retournés.
 * Score : 100 jusqu'à temps_parfait, décroissance linéaire jusqu'à points_min à duree_max (le temps
 * effectif au-delà de duree_max donne points_min), 0 si le temps RÉEL dépasse duree_max avant la dernière paire.
 */

const AVENT_PAI_VERSION    = 1;
const AVENT_PAI_ECART_MS   = 250;   // délai minimum entre deux retournements
const AVENT_PAI_GRACE_MS   = 500;   // tolérance réseau sur la durée maximale
const AVENT_PAI_EMOJIS     = ['🎅', '🎄', '⭐', '🎁', '🔔', '⛄', '🦌', '🍪', '🧦', '🕯️', '❄️', '🛷'];

/** Horloge, remplaçable par les tests : $GLOBALS['AVENT_PAIRES_HORLOGE'] = float. */
function avent_pai_now(): float
{
    return isset($GLOBALS['AVENT_PAIRES_HORLOGE']) ? (float) $GLOBALS['AVENT_PAIRES_HORLOGE'] : microtime(true);
}

/** Paramètres effectifs (défauts + valeurs de l'administrateur, supposées validées par valider()). */
function avent_pai_params(array $config): array
{
    $s = is_array($config['parametres'] ?? null) ? $config['parametres'] : [];
    $entier = static fn(string $k, int $defaut, int $min, int $max): int =>
        isset($s[$k]) && is_int($s[$k]) ? max($min, min($max, $s[$k])) : $defaut;
    $p = [
        'lignes'        => $entier('lignes', 5, 3, 7),
        'colonnes'      => $entier('colonnes', 5, 3, 6),
        'duree_max'     => $entier('duree_max', 110, 30, 150),
        'points_min'    => $entier('points_min', 10, 0, 50),
        'penalite'      => $entier('penalite', 3, 0, 10),
        'emojis'        => AVENT_PAI_EMOJIS,
    ];
    $p['temps_parfait'] = $entier('temps_parfait', 35, 5, $p['duree_max'] - 1);
    if ($p['lignes'] * $p['colonnes'] % 2 === 0) {      // le charbon exige un nombre impair de cartes
        $p['lignes'] = 5;
        $p['colonnes'] = 5;
    }
    if (isset($s['emojis']) && is_array($s['emojis']) && avent_pai_emojis_ok($s['emojis'], intdiv($p['lignes'] * $p['colonnes'], 2))) {
        $p['emojis'] = array_values($s['emojis']);
    }
    $p['cartes'] = $p['lignes'] * $p['colonnes'];
    $p['paires'] = intdiv($p['cartes'], 2);
    $p['emojis'] = array_slice($p['emojis'], 0, $p['paires']);
    if (count($p['emojis']) < $p['paires']) {            // liste par défaut trop courte pour une grande grille
        $p['lignes'] = 5;
        $p['colonnes'] = 5;
        $p['cartes'] = 25;
        $p['paires'] = 12;
        $p['emojis'] = AVENT_PAI_EMOJIS;
    }
    return $p;
}

function avent_pai_emojis_ok(array $liste, int $paires): bool
{
    if (!array_is_list($liste) || count($liste) < $paires || count($liste) > 40) {
        return false;
    }
    foreach ($liste as $e) {
        if (!is_string($e) || $e === '' || mb_strlen($e) > 12) {
            return false;
        }
    }
    return count(array_unique($liste)) === count($liste);
}

/** Vérifie la configuration saisie par l'administrateur. Renvoie null si elle est valide. */
function avent_jeu_paires_valider(array $config): ?string
{
    $s = $config['parametres'] ?? [];
    if (!is_array($s)) {
        return '« parametres » doit être un objet.';
    }
    $bornes = ['lignes' => [3, 7], 'colonnes' => [3, 6], 'duree_max' => [30, 150], 'points_min' => [0, 50], 'penalite' => [0, 10]];
    foreach ($bornes as $nom => [$min, $max]) {
        if (array_key_exists($nom, $s) && (!is_int($s[$nom]) || $s[$nom] < $min || $s[$nom] > $max)) {
            return "Paramètre « $nom » : entier attendu entre $min et $max.";
        }
    }
    $lignes = $s['lignes'] ?? 5;
    $colonnes = $s['colonnes'] ?? 5;
    if ($lignes * $colonnes % 2 === 0) {
        return 'lignes × colonnes doit être impair (une carte charbon sans paire occupe la dernière case).';
    }
    $duree = $s['duree_max'] ?? 110;
    if (array_key_exists('temps_parfait', $s) && (!is_int($s['temps_parfait']) || $s['temps_parfait'] < 5 || $s['temps_parfait'] >= $duree)) {
        return "Paramètre « temps_parfait » : entier entre 5 et duree_max - 1 ($duree).";
    }
    $paires = intdiv($lignes * $colonnes, 2);
    if (array_key_exists('emojis', $s)) {
        if (!is_array($s['emojis']) || !avent_pai_emojis_ok($s['emojis'], $paires)) {
            return "« emojis » : liste d'au moins $paires textes courts, tous différents.";
        }
    } elseif ($paires > count(AVENT_PAI_EMOJIS)) {
        return "Cette grille demande $paires paires : fournis une liste « emojis » d'au moins $paires éléments.";
    }
    return null;
}

/** Graine secrète propre au joueur, au jour et aux dimensions : flux d'entiers déterministe. */
function avent_pai_disposition(array $p, string $pseudo, int $jour): array
{
    $secret = function_exists('avent_secret') ? avent_secret() : 'secret-de-test';
    $cartes = [];
    for ($i = 0; $i < $p['paires']; $i++) {
        $cartes[] = $i;
        $cartes[] = $i;
    }
    $cartes[] = -1; // charbon
    for ($i = count($cartes) - 1; $i > 0; $i--) {
        $h = hash_hmac('sha256', 'paires|' . $jour . '|' . $pseudo . '|' . $p['cartes'] . '|' . $i, $secret);
        $j = hexdec(substr($h, 0, 8)) % ($i + 1);
        [$cartes[$i], $cartes[$j]] = [$cartes[$j], $cartes[$i]];
    }
    return $cartes;
}

/** Ce que le navigateur peut savoir d'une partie commencée : jamais les cartes non trouvées. */
function avent_pai_vue(array $etat, array $p): array
{
    $trouvees = [];
    foreach ($etat['trouvees'] as $i) {
        $trouvees[] = [$i, $p['emojis'][$etat['cartes'][$i]]];
    }
    $fin = $etat['tfin'] ?? avent_pai_now();
    return [
        'lignes'    => $p['lignes'],
        'colonnes'  => $p['colonnes'],
        'trouvees'  => $trouvees,
        'charbons'  => $etat['charbons'],
        'ecoule_ms' => (int) round(($fin - $etat['t0']) * 1000),
        'fini'      => $etat['fini'],
        'expire'    => $etat['expire'],
    ];
}

function avent_jeu_paires_config_publique(array $config, array $contexte = []): array
{
    $p = avent_pai_params($config);
    $reprise = null;
    if (!empty($contexte['partie']) && function_exists('avent_etat_lire')) {
        $etat = avent_etat_lire((int) $contexte['partie']);
        if (!empty($etat['t0'])) {
            $etat = avent_pai_expirer($etat, $p);
            $reprise = avent_pai_vue($etat, $p);
        }
    }
    return [
        'version'       => AVENT_PAI_VERSION,
        'lignes'        => $p['lignes'],
        'colonnes'      => $p['colonnes'],
        'duree_max'     => $p['duree_max'],
        'temps_parfait' => $p['temps_parfait'],
        'points_min'    => $p['points_min'],
        'penalite'      => $p['penalite'],
        'reprise'       => $reprise,
    ];
}

/** Temps écoulé : marque la partie expirée si la durée maximale est passée (sans l'écrire : lecture seule). */
function avent_pai_expirer(array $etat, array $p): array
{
    if (!$etat['fini'] && !$etat['expire'] && (avent_pai_now() - $etat['t0']) * 1000 > $p['duree_max'] * 1000 + AVENT_PAI_GRACE_MS) {
        $etat['expire'] = true;
        $etat['tfin'] = $etat['t0'] + $p['duree_max'];
    }
    return $etat;
}

/** Score à partir du temps effectif en secondes (réel + pénalités). */
function avent_pai_score(float $effectifS, array $p): int
{
    if ($effectifS <= $p['temps_parfait']) {
        return 100;
    }
    if ($effectifS >= $p['duree_max']) {
        return $p['points_min'];
    }
    $part = ($effectifS - $p['temps_parfait']) / ($p['duree_max'] - $p['temps_parfait']);
    return max($p['points_min'], min(100, (int) round(100 - (100 - $p['points_min']) * $part)));
}

/**
 * Actions du jeu (api/action.php) :
 *  - demarrer : tire la disposition, lance le chrono serveur (idempotent). Rechargement : reprise avec le temps écoulé ;
 *    la carte éventuellement en attente est annulée.
 *  - retourner {i, seq} : retourne la carte i. Réponses : carte (emoji ou charbon), paire (null pour une première carte),
 *    charbons, ecoule_ms, fini (+ temps_ms, effectif_ms, score) ou expire. « seq » (entier croissant) rend un renvoi
 *    après coupure réseau sans effet : la même réponse est rejouée.
 */
function avent_jeu_paires_action(array $config, array $requete, array &$etat, array $contexte, int $ecouleMs): array
{
    $p = avent_pai_params($config);
    $action = $requete['action'];
    $donnees = $requete['donnees'];
    $now = avent_pai_now();

    if ($action === 'demarrer') {
        if (empty($etat['t0'])) {
            $etat = [
                'cartes'   => avent_pai_disposition($p, (string) ($contexte['pseudo'] ?? ''), (int) ($contexte['jour'] ?? 0)), // ne quitte jamais le serveur
                'trouvees' => [],
                'attente'  => null,
                'charbons' => 0,
                'fini'     => false,
                'expire'   => false,
                't0'       => $now,
                'dernier'  => 0.0,
                'seq'      => 0,
                'rep'      => null,
            ];
        } else {
            $etat['attente'] = null;
        }
        $etat = avent_pai_expirer($etat, $p);
        return avent_pai_vue($etat, $p);
    }

    if ($action !== 'retourner') {
        return ['erreur' => 'Action inconnue.'];
    }
    if (empty($etat['t0'])) {
        return ['erreur' => "La partie n'est pas commencée."];
    }
    $i = $donnees['i'] ?? null;
    $seq = $donnees['seq'] ?? null;
    if (!is_int($i) || !is_int($seq) || $seq < 1) {
        return ['erreur' => 'Requête invalide.'];
    }
    if ($seq === $etat['seq'] && is_array($etat['rep'])) {
        return $etat['rep'];                           // renvoi du dernier retournement : même réponse, aucun effet
    }
    if ($seq < $etat['seq']) {
        return ['erreur' => 'Requête périmée.'];
    }
    $etat = avent_pai_expirer($etat, $p);
    if ($etat['fini'] || $etat['expire']) {
        return avent_pai_vue($etat, $p) + ['termine' => true];
    }
    if ($i < 0 || $i >= $p['cartes']) {
        return ['erreur' => 'Carte invalide.'];
    }
    if (in_array($i, $etat['trouvees'], true)) {
        return ['erreur' => 'Carte déjà verrouillée.'];
    }
    if ($etat['attente'] === $i) {
        return ['erreur' => 'Choisis une autre carte.'];
    }
    if ($etat['dernier'] > 0 && ($now - $etat['dernier']) * 1000 < AVENT_PAI_ECART_MS) {
        return ['erreur' => 'Doucement : trop de cartes retournées trop vite.'];
    }
    if (($now - $etat['t0']) * 1000 > $p['duree_max'] * 1000 + AVENT_PAI_GRACE_MS) {
        $etat['expire'] = true;
        $etat['tfin'] = $etat['t0'] + $p['duree_max'];
        return avent_pai_vue($etat, $p) + ['termine' => true];
    }

    $etat['dernier'] = $now;
    $etat['seq'] = $seq;
    $valeur = $etat['cartes'][$i];
    $rep = ['i' => $i];

    if ($valeur === -1) {                               // charbon : annule la première carte, coûte du temps
        $etat['charbons']++;
        $etat['attente'] = null;
        $rep += ['charbon' => true, 'paire' => null];
    } elseif ($etat['attente'] === null) {              // première carte
        $etat['attente'] = $i;
        $rep += ['carte' => $p['emojis'][$valeur], 'paire' => null];
    } else {                                            // deuxième carte
        $premiere = $etat['attente'];
        $etat['attente'] = null;
        $paire = $etat['cartes'][$premiere] === $valeur;
        if ($paire) {
            $etat['trouvees'][] = $premiere;
            $etat['trouvees'][] = $i;
        }
        $rep += ['carte' => $p['emojis'][$valeur], 'paire' => $paire, 'premiere' => $premiere];
        if ($paire && count($etat['trouvees']) === 2 * $p['paires']) {
            $etat['fini'] = true;
            $etat['tfin'] = $now;
        }
    }

    $rep += [
        'charbons'  => $etat['charbons'],
        'nb_paires' => intdiv(count($etat['trouvees']), 2),
        'ecoule_ms' => (int) round((($etat['tfin'] ?? $now) - $etat['t0']) * 1000),
        'fini'      => $etat['fini'],
    ];
    if ($etat['fini']) {
        $reel = $etat['tfin'] - $etat['t0'];
        $effectif = $reel + $p['penalite'] * $etat['charbons'];
        $rep += ['temps_ms' => (int) round($reel * 1000), 'effectif_ms' => (int) round($effectif * 1000), 'score' => avent_pai_score($effectif, $p)];
    }
    $etat['rep'] = $rep;
    return $rep;
}

/**
 * Le score ne dépend que de l'état tenu par le serveur : dernière paire trouvée dans la durée maximale,
 * temps réel mesuré par le serveur, charbons retournés. Le payload du navigateur est ignoré.
 */
function avent_jeu_paires_verifier(array $config, array $payload, int $dureeMs, array $contexte = []): array
{
    $etat = $contexte['etat'] ?? [];
    $p = avent_pai_params($config);
    if (empty($etat['t0']) || empty($etat['fini']) || !isset($etat['tfin']) || count($etat['trouvees'] ?? []) !== 2 * $p['paires']) {
        return ['score' => 0, 'reussite' => false, 'paires' => intdiv(count($etat['trouvees'] ?? []), 2)];
    }
    $reel = $etat['tfin'] - $etat['t0'];
    if ($reel * 1000 > $p['duree_max'] * 1000 + AVENT_PAI_GRACE_MS) {
        return ['score' => 0, 'reussite' => false, 'paires' => $p['paires']];
    }
    $effectif = $reel + $p['penalite'] * ($etat['charbons'] ?? 0);
    $score = avent_pai_score($effectif, $p);
    return ['score' => $score, 'reussite' => $score > 0, 'paires' => $p['paires'], 'effectif_ms' => (int) round($effectif * 1000)];
}
