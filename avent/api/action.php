<?php
/**
 * Appel supplémentaire pendant une partie : le navigateur envoie une action au module serveur du
 * jeu (avent_jeu_<slug>_action), qui tient l'état de la partie côté serveur (vies, pose validée…).
 * Le chronomètre, l'unicité de la partie et la durée maximale restent contrôlés ici.
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
$action      = (string) ($_POST['action'] ?? '');
$brut        = (string) ($_POST['donnees'] ?? '{}');
$donnees     = strlen($brut) <= 4096 ? json_decode($brut, true) : null;
if (!preg_match('/^[a-z_]{1,30}$/', $action) || !is_array($donnees)) {
    avent_json(['ok' => false, 'erreur' => 'Requête invalide.'], 400);
}

$pdo = avent_pdo();
avent_etat_table(); // DDL hors transaction
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
        avent_json(['ok' => false, 'erreur' => 'Cette partie est terminée.'], 409);
    }

    $numero = (int) $partie['jour'];
    $jour   = avent_jour($numero);
    $slug   = (string) ($jour['jeu'] ?? '');
    $config = avent_config_decodee($jour['config'] ?? '');
    $duree  = min(AVENT_DUREE_MAX, max(10, (int) ($jour['duree_max'] ?? AVENT_DUREE_MAX)));

    $debut    = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $partie['debut']) ?: new DateTimeImmutable('now');
    $ecouleMs = max(0, (int) round(((float) (new DateTimeImmutable('now'))->format('U.u') - (float) $debut->format('U.u')) * 1000));
    if ($ecouleMs > ($duree * 1000) + AVENT_GRACE_MS) {
        $pdo->rollBack();
        avent_json(['ok' => false, 'erreur' => 'Temps écoulé.'], 403);
    }

    $fonction = avent_jeu_fonction($slug, 'action');
    if (!$fonction) {
        $pdo->rollBack();
        avent_json(['ok' => false, 'erreur' => "Ce jeu n'accepte pas d'appel supplémentaire."], 404);
    }

    $etat = avent_etat_lire($partieId, true);
    $reponse = $fonction($config, ['action' => $action, 'donnees' => $donnees], $etat, [
        'pseudo' => $pseudo,
        'jour'   => $numero,
        'partie' => $partieId,
        'essai'  => $partie['essai'] !== null ? (int) $partie['essai'] : null,
        'test'   => (int) $partie['test'] === 1,
    ], $ecouleMs);
    avent_etat_ecrire($partieId, $etat);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

if (isset($reponse['erreur'])) {
    avent_json(['ok' => false] + $reponse, 400);
}
avent_json(['ok' => true] + $reponse);
