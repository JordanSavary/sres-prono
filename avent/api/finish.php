<?php
/**
 * Termine une partie : contrôle le chronomètre côté serveur, fait vérifier la réponse par le
 * module du jeu, calcule les points et renvoie la révélation du jour.
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
$partieId    = (int) ($_POST['partie'] ?? 0);
$brut        = (string) ($_POST['payload'] ?? '{}');
$payload     = strlen($brut) <= 65536 ? json_decode($brut, true) : null;
if (!is_array($payload)) {
    $payload = [];
}

$pdo = avent_pdo();
$pdo->beginTransaction();
try {
    $requete = $pdo->prepare('SELECT * FROM avent_parties WHERE id = ? AND pseudo = ? FOR UPDATE');
    $requete->execute([$partieId, $pseudo]);
    $partie = $requete->fetch();

    if (!$partie) {
        $pdo->rollBack();
        avent_json(['ok' => false, 'erreur' => 'Partie introuvable.'], 404);
    }
    if ($partie['statut'] !== 'en_cours') {
        $pdo->rollBack();
        avent_json(['ok' => false, 'erreur' => 'Cette partie est déjà terminée.'], 409);
    }

    $numero = (int) $partie['jour'];
    $jour   = avent_jour($numero);
    $slug   = (string) ($jour['jeu'] ?? '');
    $config = avent_config_decodee($jour['config'] ?? '');
    $duree  = min(AVENT_DUREE_MAX, max(10, (int) ($jour['duree_max'] ?? AVENT_DUREE_MAX)));

    $fin      = new DateTimeImmutable('now');
    $debut    = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $partie['debut']) ?: $fin;
    $ecouleMs = max(0, (int) round(((float) $fin->format('U.u') - (float) $debut->format('U.u')) * 1000));

    $score    = 0;
    $reussite = false;
    $horsDelai = $ecouleMs > ($duree * 1000) + AVENT_GRACE_MS;

    if (!$horsDelai) {
        $verifier = avent_jeu_fonction($slug, 'verifier');
        if ($verifier) {
            $resultat = $verifier($config, $payload, $ecouleMs);
            $score    = max(0, min(100, (int) round((float) ($resultat['score'] ?? 0))));
            $reussite = !empty($resultat['reussite']);
        }
    }

    $coef   = !empty($jour['est_boss']) ? avent_coef_boss() : 1.0;
    $points = (int) round($score * $coef);

    $maj = $pdo->prepare(
        'UPDATE avent_parties SET statut = \'termine\', fin = ?, duree_ms = ?, score = ?, points = ?, reussite = ?
         WHERE id = ? AND statut = \'en_cours\''
    );
    $maj->execute([
        $fin->format('Y-m-d H:i:s.v'), min($ecouleMs, 4294967295), $score, $points, $reussite ? 1 : 0, $partieId,
    ]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

$test = (int) $partie['test'] === 1;
$meilleur = $points;
$essaisRestants = null;
if (!$test) {
    $requete = $pdo->prepare(
        'SELECT MAX(points) FROM avent_parties WHERE pseudo = ? AND jour = ? AND test = 0 AND statut = \'termine\''
    );
    $requete->execute([$pseudo, $numero]);
    $meilleur = (int) $requete->fetchColumn();

    $requete = $pdo->prepare(
        'SELECT COUNT(*) FROM avent_parties WHERE pseudo = ? AND jour = ? AND test = 0 AND essai IS NOT NULL'
    );
    $requete->execute([$pseudo, $numero]);
    $essaisRestants = max(0, avent_essais_max() - (int) $requete->fetchColumn());
}

$statut = avent_statut_jour($numero);
$peutRejouer = in_array($statut, ['disponible', 'rattrapable'], true) && ($test || $essaisRestants > 0);

avent_json([
    'ok'              => true,
    'hors_delai'      => $horsDelai,
    'score'           => $score,
    'points'          => $points,
    'reussite'        => $reussite,
    'duree_ms'        => $ecouleMs,
    'meilleur_points' => $meilleur,
    'essais_restants' => $essaisRestants,
    'peut_rejouer'    => $peutRejouer,
    'test'            => $test,
    'revelation'      => [
        'texte' => (string) ($jour['revelation_texte'] ?? ''),
        'image' => avent_image_valide($jour['revelation_image'] ?? null), // relatif à avent/, comme la page du jour
    ],
]);
