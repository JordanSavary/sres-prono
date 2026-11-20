<?php
/**
 * Jeu « Escape Christmas » — module serveur.
 *
 * Le traîneau est verrouillé par un code à 4 chiffres. Le joueur résout 4 énigmes à 3 réponses (10 s chacune) ;
 * chaque bonne réponse révèle un chiffre, puis il saisit le code (3 essais, 20 s).
 *
 * Tout est tenu ici : tirage des énigmes (graine secrète propre au joueur et au jour), ordre des réponses,
 * chiffres, bonnes réponses et code ne quittent jamais le serveur ; chaque énigme n'est servie qu'après la
 * précédente résolue ; le serveur chronomètre lui-même chaque énigme, le code et la partie. Le navigateur
 * n'envoie que des actions (api/action.php) : aucun temps ni score annoncé par lui n'est lu.
 *
 * Contenu attendu (JSON saisi dans l'administration), tout est facultatif :
 *   {"parametres": {"nb_enigmes": 4, "duree_enigme": 10, "temps_plein": 4, "points_enigme": 20, "points_min_enigme": 8,
 *                   "penalite": 5, "points_code": [20, 13, 6], "duree_code": 20, "duree_globale": 110},
 *    "enigmes": [{"illustration": "⭐ ⭐ ⭐ = 9", "question": "…", "reponses": ["2", "3", "4"], "bonne": 1}, …]}
 * Sans « enigmes », la réserve par défaut (enigmes.php) est utilisée.
 *
 * Score d'une énigme : points_enigme jusqu'à temps_plein secondes, décroissance linéaire jusqu'à points_min_enigme à
 * duree_enigme, moins « penalite » par mauvaise réponse et par délai écoulé sur cette énigme, minimum 0.
 * Code : points_code[0] à la 1re tentative, points_code[1] à la 2e… (le nombre de tentatives = nombre de valeurs).
 * Délai dépassé (énigme) : l'énigme est rejouée avec un nouveau délai ; la pénalité s'applique. Passé duree_globale,
 * la partie s'arrête : le score est celui des points déjà acquis.
 */

const AVENT_ESC_VERSION     = 1;
const AVENT_ESC_TOLERANCE_S = 1.0;   // latence réseau tolérée sur une réponse d'énigme ou de code
const AVENT_ESC_GRACE_GLOBALE_S = 0.5;
const AVENT_ESC_AFFICHAGE_S = 2.0;   // temps pendant lequel le chiffre gagné est affiché (compté dans le délai du code)
const AVENT_ESC_EMOJIS      = [];

/** Horloge, remplaçable par les tests : $GLOBALS['AVENT_ESCAPE_HORLOGE'] = float. */
function avent_esc_now(): float
{
    return isset($GLOBALS['AVENT_ESCAPE_HORLOGE']) ? (float) $GLOBALS['AVENT_ESCAPE_HORLOGE'] : microtime(true);
}

/** Réserve par défaut (fichier côté serveur). */
function avent_esc_reserve_defaut(): array
{
    static $reserve = null;
    return $reserve ??= require __DIR__ . '/enigmes.php';
}

function avent_esc_enigme_ok($e): bool
{
    if (!is_array($e) || !is_string($e['question'] ?? null) || trim($e['question']) === '' || mb_strlen($e['question']) > 160) {
        return false;
    }
    if (isset($e['illustration']) && (!is_string($e['illustration']) || mb_strlen($e['illustration']) > 60)) {
        return false;
    }
    $r = $e['reponses'] ?? null;
    if (!is_array($r) || !array_is_list($r) || count($r) !== 3 || !is_int($e['bonne'] ?? null) || $e['bonne'] < 0 || $e['bonne'] > 2) {
        return false;
    }
    foreach ($r as $texte) {
        if (!is_string($texte) || trim($texte) === '' || mb_strlen($texte) > 40) {
            return false;
        }
    }
    return count(array_unique($r)) === 3;
}

/** Paramètres effectifs (défauts + valeurs de l'administrateur, supposées validées par valider()). */
function avent_esc_params(array $config): array
{
    $s = is_array($config['parametres'] ?? null) ? $config['parametres'] : [];
    $entier = static fn(string $k, int $defaut, int $min, int $max): int =>
        isset($s[$k]) && is_int($s[$k]) ? max($min, min($max, $s[$k])) : $defaut;
    $p = [
        'nb_enigmes'        => $entier('nb_enigmes', 4, 3, 6),
        'duree_enigme'      => $entier('duree_enigme', 10, 5, 30),
        'points_enigme'     => $entier('points_enigme', 20, 1, 50),
        'penalite'          => $entier('penalite', 5, 0, 20),
        'duree_code'        => $entier('duree_code', 20, 5, 60),
        'duree_globale'     => $entier('duree_globale', 110, 30, 150),
    ];
    $p['temps_plein'] = $entier('temps_plein', 4, 1, $p['duree_enigme'] - 1);
    $p['points_min_enigme'] = $entier('points_min_enigme', 8, 0, $p['points_enigme']);
    $codes = $s['points_code'] ?? null;
    $p['points_code'] = [20, 13, 6];
    if (is_array($codes) && array_is_list($codes) && count($codes) >= 1 && count($codes) <= 5) {
        $ok = true;
        foreach ($codes as $v) {
            $ok = $ok && is_int($v) && $v >= 0 && $v <= 100;
        }
        if ($ok) {
            $p['points_code'] = $codes;
        }
    }
    $p['tentatives'] = count($p['points_code']);
    $reserve = is_array($config['enigmes'] ?? null) ? $config['enigmes'] : null;
    if ($reserve !== null) {
        foreach ($reserve as $e) {
            if (!avent_esc_enigme_ok($e)) {
                $reserve = null;
                break;
            }
        }
    }
    $p['reserve'] = $reserve !== null && count($reserve) >= $p['nb_enigmes'] ? array_values($reserve) : avent_esc_reserve_defaut();
    return $p;
}

/** Vérifie la configuration saisie par l'administrateur. Renvoie null si elle est valide. */
function avent_jeu_escape_valider(array $config): ?string
{
    $s = $config['parametres'] ?? [];
    if (!is_array($s)) {
        return '« parametres » doit être un objet.';
    }
    $bornes = ['nb_enigmes' => [3, 6], 'duree_enigme' => [5, 30], 'points_enigme' => [1, 50], 'penalite' => [0, 20], 'duree_code' => [5, 60], 'duree_globale' => [30, 150]];
    foreach ($bornes as $nom => [$min, $max]) {
        if (array_key_exists($nom, $s) && (!is_int($s[$nom]) || $s[$nom] < $min || $s[$nom] > $max)) {
            return "Paramètre « $nom » : entier attendu entre $min et $max.";
        }
    }
    $duree = $s['duree_enigme'] ?? 10;
    if (array_key_exists('temps_plein', $s) && (!is_int($s['temps_plein']) || $s['temps_plein'] < 1 || $s['temps_plein'] >= $duree)) {
        return "Paramètre « temps_plein » : entier entre 1 et duree_enigme - 1 ($duree).";
    }
    $max = $s['points_enigme'] ?? 20;
    if (array_key_exists('points_min_enigme', $s) && (!is_int($s['points_min_enigme']) || $s['points_min_enigme'] < 0 || $s['points_min_enigme'] > $max)) {
        return "Paramètre « points_min_enigme » : entier entre 0 et points_enigme ($max).";
    }
    if (array_key_exists('points_code', $s)) {
        $c = $s['points_code'];
        if (!is_array($c) || !array_is_list($c) || count($c) < 1 || count($c) > 5) {
            return '« points_code » : liste de 1 à 5 entiers (un par tentative).';
        }
        foreach ($c as $v) {
            if (!is_int($v) || $v < 0 || $v > 100) {
                return '« points_code » : entiers entre 0 et 100.';
            }
        }
    }
    if (array_key_exists('enigmes', $config)) {
        $liste = $config['enigmes'];
        $nb = $s['nb_enigmes'] ?? 4;
        if (!is_array($liste) || !array_is_list($liste) || count($liste) < $nb || count($liste) > 60) {
            return "« enigmes » : liste d'au moins $nb énigmes.";
        }
        foreach ($liste as $i => $e) {
            if (!avent_esc_enigme_ok($e)) {
                return 'Énigme n° ' . ($i + 1) . " : question, 3 réponses différentes (40 caractères max) et « bonne » (0 à 2) attendues.";
            }
        }
    }
    return null;
}

/** Entier pseudo-aléatoire déterministe (graine secrète propre au joueur et au jour). */
function avent_esc_alea(string $graine, int $n, int $borne): int
{
    return hexdec(substr(hash_hmac('sha256', $graine . '|' . $n, function_exists('avent_secret') ? avent_secret() : 'secret-de-test'), 0, 8)) % $borne;
}

/** Tire les énigmes de la partie : ordre des énigmes, ordre des réponses, chiffre de chacune. */
function avent_esc_tirage(array $p, string $pseudo, int $jour): array
{
    $graine = 'escape|' . $jour . '|' . $pseudo;
    $indices = range(0, count($p['reserve']) - 1);
    $n = 0;
    for ($i = count($indices) - 1; $i > 0; $i--) {          // mélange de Fisher-Yates
        $j = avent_esc_alea($graine, $n++, $i + 1);
        [$indices[$i], $indices[$j]] = [$indices[$j], $indices[$i]];
    }
    $tirees = [];
    foreach (array_slice($indices, 0, $p['nb_enigmes']) as $idx) {
        $src = $p['reserve'][$idx];
        $ordre = [0, 1, 2];
        for ($i = 2; $i > 0; $i--) {
            $j = avent_esc_alea($graine, $n++, $i + 1);
            [$ordre[$i], $ordre[$j]] = [$ordre[$j], $ordre[$i]];
        }
        $tirees[] = [
            'illustration' => (string) ($src['illustration'] ?? ''),
            'question'     => $src['question'],
            'reponses'     => [$src['reponses'][$ordre[0]], $src['reponses'][$ordre[1]], $src['reponses'][$ordre[2]]],
            'bonne'        => array_search($src['bonne'], $ordre, true),
            'chiffre'      => avent_esc_alea($graine, $n++, 10),       // sans lien avec la réponse
        ];
    }
    return $tirees;
}

/** Points d'une énigme résolue en $t secondes (depuis le dernier démarrage de son délai), avec les pénalités. */
function avent_esc_points(float $t, int $fautes, array $p): int
{
    if ($t <= $p['temps_plein']) {
        $base = $p['points_enigme'];
    } elseif ($t >= $p['duree_enigme']) {
        $base = $p['points_min_enigme'];
    } else {
        $part = ($t - $p['temps_plein']) / ($p['duree_enigme'] - $p['temps_plein']);
        $base = (int) round($p['points_enigme'] - ($p['points_enigme'] - $p['points_min_enigme']) * $part);
    }
    return max(0, $base - $p['penalite'] * $fautes);
}

function avent_esc_score(array $etat): int
{
    return array_sum($etat['pts'] ?? []) + (int) ($etat['code_pts'] ?? 0);
}

/** Ce que le navigateur peut savoir d'une partie commencée (jamais réponses, chiffres ni code). */
function avent_esc_vue(array $etat, array $p): array
{
    $fin = $etat['tfin'] ?? avent_esc_now();
    return [
        'phase'      => $etat['phase'],
        'etape'      => count($etat['pts']) + 1,
        'nb'         => count($etat['enigmes']),
        'nb_chiffres' => count($etat['pts']),
        'score'      => avent_esc_score($etat),
        'ecoule_ms'  => (int) round(($fin - $etat['t0']) * 1000),
    ];
}

function avent_jeu_escape_config_publique(array $config, array $contexte = []): array
{
    $p = avent_esc_params($config);
    $reprise = null;
    if (!empty($contexte['partie']) && function_exists('avent_etat_lire')) {
        $etat = avent_etat_lire((int) $contexte['partie']);
        if (!empty($etat['t0'])) {
            $etat = avent_esc_global($etat, $p);
            $reprise = avent_esc_vue($etat, $p);
        }
    }
    return [
        'version'       => AVENT_ESC_VERSION,
        'nb_enigmes'    => $p['nb_enigmes'],
        'duree_enigme'  => $p['duree_enigme'],
        'duree_code'    => $p['duree_code'],
        'duree_globale' => $p['duree_globale'],
        'tentatives'    => $p['tentatives'],
        'points_enigme' => $p['points_enigme'],
        'temps_plein'   => $p['temps_plein'],
        'points_min_enigme' => $p['points_min_enigme'],
        'penalite'      => $p['penalite'],
        'affichage_ms'  => (int) (AVENT_ESC_AFFICHAGE_S * 1000),
        'reprise'       => $reprise,
    ];
}

/** Clôt la partie si la durée globale est dépassée. Renvoie l'état (modifié ou non). */
function avent_esc_global(array $etat, array $p): array
{
    if ($etat['phase'] !== 'fini' && (avent_esc_now() - $etat['t0']) > $p['duree_globale'] + AVENT_ESC_GRACE_GLOBALE_S) {
        $etat['phase'] = 'fini';
        $etat['raison'] = 'global';
        $etat['tfin'] = $etat['t0'] + $p['duree_globale'];
        $etat['servie'] = false;
    }
    return $etat;
}

/** Description de l'énigme en cours pour le navigateur (sans bonne réponse ni chiffre). */
function avent_esc_enigme_vue(array $etat, array $p, float $now): array
{
    $k = count($etat['pts']);
    $e = $etat['enigmes'][$k];
    return [
        'numero'       => $k + 1,
        'illustration' => $e['illustration'],
        'question'     => $e['question'],
        'reponses'     => $e['reponses'],
        'essayees'     => $etat['essayees'],
        'restant_ms'   => max(0, (int) round(($etat['sert_a'] + $p['duree_enigme'] - $now) * 1000)),
    ];
}

/** Applique les délais écoulés sur l'énigme servie : chaque période entière écoulée est un « temps écoulé » pénalisé. */
function avent_esc_delais(array &$etat, array $p, float $now): int
{
    $ecoule = $now - $etat['sert_a'];
    if (!$etat['servie'] || $ecoule < $p['duree_enigme'] - 0.05) {
        return 0;
    }
    $n = max(1, (int) floor(($ecoule + 0.05) / $p['duree_enigme']));
    $etat['ecoules'] += $n;
    $etat['sert_a'] = $now;               // nouveau délai complet
    return $n;
}

/** Réponse commune à « enigme » : l'énigme en cours, ou le cadenas, ou la fin. */
function avent_esc_reponse_etat(array &$etat, array $p, float $now, array $en_plus = []): array
{
    $rep = $en_plus + ['score' => avent_esc_score($etat), 'phase' => $etat['phase'], 'ecoule_ms' => (int) round((($etat['tfin'] ?? $now) - $etat['t0']) * 1000)];
    if ($etat['phase'] === 'enigmes') {
        $rep['enigme'] = avent_esc_enigme_vue($etat, $p, $now);
    } elseif ($etat['phase'] === 'code') {
        $rep['code'] = [
            'restant_ms'             => max(0, (int) round(($etat['code_fin'] - $now) * 1000)),
            'tentatives_restantes'   => $p['tentatives'] - $etat['tentatives'],
        ];
    } else {
        $rep['termine'] = true;
        $rep['raison'] = $etat['raison'] ?? 'fin';
    }
    return $rep;
}

/**
 * Actions du jeu (api/action.php) :
 *  - demarrer : tire les énigmes et lance les chronos (idempotent) ; renvoie la vue (phase, étape, score…).
 *  - enigme : renvoie l'énigme en cours (la suivante seulement après résolution de la précédente), ou le cadenas, ou la fin.
 *    Redemander une énigme déjà servie ne remet pas son délai à zéro (les délais écoulés pendant une coupure sont pénalisés).
 *  - reponse {i, seq} : réponse à l'énigme servie. bon (+ chiffre, gain) / mauvais / expire (délai dépassé : énigme rejouée).
 *  - temps {seq} : le navigateur signale la fin du délai ; accepté seulement si le serveur confirme les 10 s écoulées.
 *  - code {code, seq} : saisie du code final (bon, mauvais, tentatives restantes).
 *  - temps_code {seq} : fin du délai de saisie du code, confirmée par le serveur.
 * « seq » (entier croissant) rend un renvoi après coupure réseau sans effet : la même réponse est rejouée.
 */
function avent_jeu_escape_action(array $config, array $requete, array &$etat, array $contexte, int $ecouleMs): array
{
    $p = avent_esc_params($config);
    $action = $requete['action'];
    $donnees = $requete['donnees'];
    $now = avent_esc_now();

    if ($action === 'demarrer') {
        if (empty($etat['t0'])) {
            $etat = [
                'enigmes'  => avent_esc_tirage($p, (string) ($contexte['pseudo'] ?? ''), (int) ($contexte['jour'] ?? 0)),   // ne quitte jamais le serveur
                'phase'    => 'enigmes',
                'pts'      => [],            // points des énigmes résolues
                'servie'   => false,
                'sert_a'   => 0.0,
                'fautes'   => 0,
                'ecoules'  => 0,
                'essayees' => [],
                'code'     => '',
                'tentatives' => 0,
                'code_pts' => 0,
                't0'       => $now,
                'seq'      => 0,
                'rep'      => null,
            ];
            $etat['code'] = implode('', array_column($etat['enigmes'], 'chiffre'));
        }
        $etat = avent_esc_global($etat, $p);
        return avent_esc_vue($etat, $p);
    }
    if (!in_array($action, ['enigme', 'reponse', 'temps', 'code', 'temps_code'], true)) {
        return ['erreur' => 'Action inconnue.'];
    }
    if (empty($etat['t0'])) {
        return ['erreur' => "La partie n'est pas commencée."];
    }

    // Renvoi réseau : même numéro de séquence, même réponse, aucun effet.
    $seq = $donnees['seq'] ?? null;
    if ($action !== 'enigme') {
        if (!is_int($seq) || $seq < 1) {
            return ['erreur' => 'Requête invalide.'];
        }
        if ($seq === $etat['seq'] && is_array($etat['rep'])) {
            return $etat['rep'];
        }
        if ($seq < $etat['seq']) {
            return ['erreur' => 'Requête périmée.'];
        }
    }

    $etat = avent_esc_global($etat, $p);
    if ($etat['phase'] === 'fini') {
        return avent_esc_reponse_etat($etat, $p, $now);
    }

    if ($action === 'enigme') {
        if ($etat['phase'] === 'enigmes') {
            if (!$etat['servie']) {
                $etat['servie'] = true;
                $etat['sert_a'] = $now;
                $etat['fautes'] = 0;
                $etat['ecoules'] = 0;
                $etat['essayees'] = [];
            } else {
                avent_esc_delais($etat, $p, $now);        // coupure : les délais écoulés sont pénalisés, jamais remis à zéro gratuitement
            }
        } elseif ($etat['phase'] === 'code') {
            avent_esc_code_delai($etat, $p, $now);
        }
        return avent_esc_reponse_etat($etat, $p, $now);
    }

    $rep = null;
    if ($action === 'reponse' || $action === 'temps') {
        if ($etat['phase'] !== 'enigmes' || !$etat['servie']) {
            return ['erreur' => "Aucune énigme n'est en cours."];
        }
    } else {
        if ($etat['phase'] !== 'code') {
            return ['erreur' => "Le cadenas n'est pas encore atteint."];
        }
    }

    if ($action === 'temps') {
        if ($now - $etat['sert_a'] < $p['duree_enigme'] - 0.05) {
            return ['erreur' => 'Trop tôt : le délai de cette énigme court encore.'];
        }
        $n = avent_esc_delais($etat, $p, $now);
        $rep = avent_esc_reponse_etat($etat, $p, $now, ['expire' => true, 'penalite' => $n * $p['penalite']]);
    } elseif ($action === 'reponse') {
        $i = $donnees['i'] ?? null;
        if (!is_int($i) || $i < 0 || $i > 2) {
            return ['erreur' => 'Réponse invalide.'];
        }
        $ecoule = $now - $etat['sert_a'];
        if ($ecoule > $p['duree_enigme'] + AVENT_ESC_TOLERANCE_S) {     // trop tard : compté comme un temps écoulé, énigme rejouée
            $n = avent_esc_delais($etat, $p, $now);
            $rep = avent_esc_reponse_etat($etat, $p, $now, ['expire' => true, 'penalite' => $n * $p['penalite']]);
        } else {
            $k = count($etat['pts']);
            $e = $etat['enigmes'][$k];
            if ($i === $e['bonne']) {
                $gain = avent_esc_points(min($ecoule, (float) $p['duree_enigme']), $etat['fautes'] + $etat['ecoules'], $p);
                $etat['pts'][] = $gain;
                $etat['servie'] = false;
                $info = ['bon' => true, 'chiffre' => $e['chiffre'], 'gain' => $gain];
                if (count($etat['pts']) === count($etat['enigmes'])) {
                    $etat['phase'] = 'code';
                    $etat['code_fin'] = $now + AVENT_ESC_AFFICHAGE_S + $p['duree_code'];
                }
                $rep = ['score' => avent_esc_score($etat), 'phase' => $etat['phase'], 'ecoule_ms' => (int) round(($now - $etat['t0']) * 1000)] + $info;
            } else {
                if (!in_array($i, $etat['essayees'], true)) {          // un même mauvais choix répété n'est pénalisé qu'une fois
                    $etat['essayees'][] = $i;
                    $etat['fautes']++;
                }
                $rep = ['bon' => false, 'penalite' => $p['penalite'], 'score' => avent_esc_score($etat), 'phase' => $etat['phase'],
                    'essayees' => $etat['essayees'], 'ecoule_ms' => (int) round(($now - $etat['t0']) * 1000)];
            }
        }
    } elseif ($action === 'temps_code') {
        if ($now < $etat['code_fin'] - 0.05) {
            return ['erreur' => 'Trop tôt : le délai de saisie court encore.'];
        }
        avent_esc_code_delai($etat, $p, $now);
        $rep = avent_esc_reponse_etat($etat, $p, $now, ['expire' => true]);
    } else {                                                           // code
        $saisi = $donnees['code'] ?? null;
        if (!is_string($saisi) || !preg_match('/^[0-9]{' . count($etat['enigmes']) . '}$/', $saisi)) {
            return ['erreur' => 'Code invalide : ' . count($etat['enigmes']) . ' chiffres attendus.'];
        }
        if ($now > $etat['code_fin'] + AVENT_ESC_TOLERANCE_S) {         // trop tard : plus de saisie
            avent_esc_code_delai($etat, $p, $now);
            $rep = avent_esc_reponse_etat($etat, $p, $now, ['expire' => true]);
        } else {
            $etat['tentatives']++;
            if (hash_equals($etat['code'], $saisi)) {
                $etat['code_pts'] = $p['points_code'][$etat['tentatives'] - 1];
                $etat['phase'] = 'fini';
                $etat['raison'] = 'code';
                $etat['tfin'] = $now;
                $rep = ['bon' => true, 'gain' => $etat['code_pts'], 'tentative' => $etat['tentatives']];
            } else {
                $restantes = $p['tentatives'] - $etat['tentatives'];
                if ($restantes <= 0) {
                    $etat['phase'] = 'fini';
                    $etat['raison'] = 'essais';
                    $etat['tfin'] = $now;
                }
                $rep = ['bon' => false, 'tentatives_restantes' => max(0, $restantes)];
            }
            $rep += ['score' => avent_esc_score($etat), 'phase' => $etat['phase'], 'termine' => $etat['phase'] === 'fini', 'raison' => $etat['raison'] ?? null,
                'ecoule_ms' => (int) round((($etat['tfin'] ?? $now) - $etat['t0']) * 1000)];
        }
    }

    $etat['seq'] = $seq;
    $etat['rep'] = $rep;
    return $rep;
}

/** Délai de saisie du code dépassé : la partie se termine (0 point pour le code). */
function avent_esc_code_delai(array &$etat, array $p, float $now): void
{
    if ($etat['phase'] === 'code' && $now > $etat['code_fin'] - 0.05 + 0) {
        if ($now >= $etat['code_fin']) {
            $etat['phase'] = 'fini';
            $etat['raison'] = 'delai_code';
            $etat['tfin'] = $etat['code_fin'];
        }
    }
}

/**
 * Le score ne dépend que de l'état tenu par le serveur : points des énigmes résolues (temps de réponse mesuré par
 * le serveur, mauvaises réponses, délais écoulés) et points du code (rang de la tentative). Le payload du navigateur
 * est ignoré. Une partie interrompue (global dépassé, onglet fermé) garde les points déjà acquis.
 */
function avent_jeu_escape_verifier(array $config, array $payload, int $dureeMs, array $contexte = []): array
{
    $etat = $contexte['etat'] ?? [];
    if (empty($etat['t0'])) {
        return ['score' => 0, 'reussite' => false];
    }
    $score = avent_esc_score($etat);
    return [
        'score'     => $score,
        'reussite'  => ($etat['raison'] ?? '') === 'code',
        'enigmes'   => count($etat['pts'] ?? []),
        'code_pts'  => (int) ($etat['code_pts'] ?? 0),
    ];
}
