<?php
/**
 * Démarre une partie : vérifie l'ouverture de la case et le nombre d'essais, enregistre
 * l'heure de début (le chronomètre fait foi côté serveur) et renvoie le contenu du jeu
 * sans les réponses attendues.
 */
define('AVENT_API', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    avent_json(['ok' => false, 'erreur' => 'Méthode non autorisée.'], 405);
}
if (!avent_csrf_valide()) {
    avent_json(['ok' => false, 'erreur' => 'Jeton de sécurité invalide. Recharge la page.'], 403);
}

$utilisateur = avent_exiger_joueur();
$pseudo      = $utilisateur['pseudo'];
$numero      = (int) ($_POST['jour'] ?? 0);
$jour        = avent_jour($numero);

if (!$jour) {
    avent_json(['ok' => false, 'erreur' => 'Case inconnue.'], 404);
}

$statut = avent_statut_jour($numero);
if ($statut === 'verrouille') {
    avent_json(['ok' => false, 'erreur' => "Cette case n'est pas encore ouverte."], 403);
}
if ($statut === 'expire') {
    avent_json(['ok' => false, 'erreur' => "Cette case a expiré."], 403);
}

$slug = (string) $jour['jeu'];
$jeu  = $slug !== '' ? avent_jeu($slug) : null;
if (!$jeu) {
    avent_json(['ok' => false, 'erreur' => "Le jeu de ce jour n'est pas encore prêt."], 409);
}

$config   = avent_config_decodee($jour['config']);
$duree    = min(AVENT_DUREE_MAX, max(10, (int) $jour['duree_max']));
$test     = avent_test()['actif'] ? 1 : 0;
$pdo      = avent_pdo();
$limiteMs = ($duree * 1000) + AVENT_GRACE_MS;

// Parties « en cours » trop anciennes : abandonnées (l'essai reste consommé).
$maintenant = new DateTimeImmutable('now');
$perimees = $pdo->prepare(
    'UPDATE avent_parties SET statut = \'abandon\', fin = ?
     WHERE pseudo = ? AND jour = ? AND test = ? AND statut = \'en_cours\' AND debut < ?'
);
$perimees->execute([
    $maintenant->format('Y-m-d H:i:s.v'),
    $pseudo, $numero, $test,
    $maintenant->modify('-' . $limiteMs . ' milliseconds')->format('Y-m-d H:i:s.v'),
]);

// Une partie encore valide est reprise (rechargement de la page) : le temps continue de courir.
$enCours = $pdo->prepare(
    'SELECT id, essai, debut FROM avent_parties
     WHERE pseudo = ? AND jour = ? AND test = ? AND statut = \'en_cours\' ORDER BY id DESC LIMIT 1'
);
$enCours->execute([$pseudo, $numero, $test]);
$partie = $enCours->fetch();

if (!$partie) {
    $essai = null;
    if (!$test) {
        $compte = $pdo->prepare(
            'SELECT COUNT(*) FROM avent_parties WHERE pseudo = ? AND jour = ? AND test = 0 AND essai IS NOT NULL'
        );
        $compte->execute([$pseudo, $numero]);
        $utilises = (int) $compte->fetchColumn();
        if ($utilises >= avent_essais_max()) {
            avent_json(['ok' => false, 'erreur' => "Tu as utilisé tous tes essais pour cette case."], 403);
        }
        $essai = $utilises + 1;
    }

    $debut = avent_maintenant_precis();
    try {
        $insertion = $pdo->prepare(
            'INSERT INTO avent_parties (pseudo, jour, essai, test, statut, debut) VALUES (?, ?, ?, ?, \'en_cours\', ?)'
        );
        $insertion->execute([$pseudo, $numero, $essai, $test, $debut]);
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') { // deux démarrages simultanés sur le même essai
            avent_json(['ok' => false, 'erreur' => 'Une partie est déjà en cours. Recharge la page.'], 409);
        }
        throw $e;
    }
    $partie = ['id' => (int) $pdo->lastInsertId(), 'essai' => $essai, 'debut' => $debut];
}

$debutPartie = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $partie['debut']) ?: $maintenant;
$ecouleMs    = (int) round(($maintenant->format('U.u') - $debutPartie->format('U.u')) * 1000);
$restantMs   = max(0, ($duree * 1000) - $ecouleMs);

avent_json([
    'ok'     => true,
    'partie' => [
        'id'         => (int) $partie['id'],
        'essai'      => $partie['essai'] !== null ? (int) $partie['essai'] : null,
        'duree_ms'   => $duree * 1000,
        'restant_ms' => $restantMs,
        'test'       => (bool) $test,
    ],
    'jeu' => [
        'slug'   => $slug,
        'nom'    => $jeu['nom'],
        'script' => 'jeux/' . $slug . '/game.js',
        'style'  => is_file(dirname(__DIR__) . '/jeux/' . $slug . '/game.css') ? 'jeux/' . $slug . '/game.css' : null,
    ],
    'config' => avent_config_publique($slug, $config),
]);
