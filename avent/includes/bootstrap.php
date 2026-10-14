<?php
/**
 * Socle du calendrier de l'avent.
 * - partage la session et la connexion MySQL du site de pronos ;
 * - règles de déblocage des cases (fuseau Europe/Paris, heure du serveur) ;
 * - accès aux jeux (modules dans avent/jeux/<slug>/) ;
 * - classement.
 *
 * Les pages API définissent AVENT_API avant d'inclure ce fichier pour obtenir
 * des réponses JSON (erreurs comprises).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Paris');

require_once __DIR__ . '/../../includes/db.php'; // fournit $pdo

const AVENT_NB_JOURS   = 24;
const AVENT_DUREE_MAX  = 120;   // secondes : plafond absolu d'un jeu
const AVENT_GRACE_MS   = 5000;  // tolérance réseau sur le chronomètre serveur
const AVENT_IMAGE_MAX  = 3 * 1024 * 1024;

// ---------------------------------------------------------------------------
// Utilitaires de base
// ---------------------------------------------------------------------------

function h(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

function avent_pdo(): PDO
{
    return $GLOBALS['pdo'];
}

function avent_json(array $donnees, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function avent_erreur_fatale(string $titre, string $detail, int $code = 500): never
{
    if (defined('AVENT_API')) {
        avent_json(['ok' => false, 'erreur' => $detail], $code);
    }
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . h($titre) . '</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#0f2236;color:#fff8ec;display:grid;'
        . 'place-items:center;min-height:100vh;margin:0;padding:16px;text-align:center}'
        . 'main{max-width:30rem}a{color:#e0b44c}</style></head><body><main>'
        . '<h1>' . h($titre) . '</h1><p>' . h($detail) . '</p>'
        . '<p><a href="' . h(avent_racine()) . 'index.php">Retour au site</a></p></main></body></html>';
    exit;
}

set_exception_handler(function (Throwable $e): void {
    error_log('[avent] ' . $e);
    $installation = $e instanceof PDOException && in_array((string) $e->getCode(), ['42S02', '42S22'], true);
    if ($installation) {
        avent_erreur_fatale(
            'Calendrier non installé',
            "La migration avent/sql/001_avent.sql n'a pas encore été exécutée sur la base de données."
        );
    }
    avent_erreur_fatale('Oups', 'Une erreur est survenue. Réessaie dans un instant.');
});

/** Chemin relatif depuis le script courant jusqu'à la racine du site de pronos. */
function avent_racine(): string
{
    return in_array(avent_dossier_script(), ['admin', 'api'], true) ? '../../' : '../';
}

/** Chemin relatif depuis le script courant jusqu'au dossier avent/. */
function avent_base(): string
{
    return in_array(avent_dossier_script(), ['admin', 'api'], true) ? '../' : '';
}

function avent_dossier_script(): string
{
    return basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
}

function avent_rediriger(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function avent_flash(?string $message = null, string $type = 'info'): ?array
{
    if ($message !== null) {
        $_SESSION['avent_flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $flash = $_SESSION['avent_flash'] ?? null;
    unset($_SESSION['avent_flash']);
    return $flash;
}

// ---------------------------------------------------------------------------
// CSRF (même jeton de session que le site de pronos)
// ---------------------------------------------------------------------------

function avent_csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function avent_csrf_valide(): bool
{
    $envoye = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($envoye)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $envoye);
}

function avent_exiger_csrf(): void
{
    if (!avent_csrf_valide()) {
        avent_erreur_fatale('Requête refusée', 'Jeton de sécurité invalide. Recharge la page et réessaie.', 403);
    }
}

// ---------------------------------------------------------------------------
// Réglages
// ---------------------------------------------------------------------------

function avent_reglages(bool $recharger = false): array
{
    static $cache = null;
    if ($cache === null || $recharger) {
        $cache = [];
        foreach (avent_pdo()->query('SELECT cle, valeur FROM avent_reglages') as $ligne) {
            $cache[$ligne['cle']] = $ligne['valeur'];
        }
    }
    return $cache;
}

function avent_reglage(string $cle, string $defaut = ''): string
{
    return avent_reglages()[$cle] ?? $defaut;
}

function avent_essais_max(): int
{
    return max(1, (int) avent_reglage('essais_max', '2'));
}

function avent_coef_boss(): float
{
    return max(1.0, (float) avent_reglage('coef_boss', '2'));
}

function avent_rattrapage_jours(): int
{
    return max(1, (int) avent_reglage('rattrapage_jours', '7'));
}

// ---------------------------------------------------------------------------
// Utilisateur courant
// ---------------------------------------------------------------------------

function avent_utilisateur(): ?array
{
    static $utilisateur = false;
    if ($utilisateur !== false) {
        return $utilisateur;
    }
    $pseudo = $_SESSION['utilisateur'] ?? null;
    if (!is_string($pseudo) || $pseudo === '') {
        return $utilisateur = null;
    }
    $requete = avent_pdo()->prepare('SELECT pseudo, avatar, is_admin FROM utilisateurs WHERE pseudo = ?');
    $requete->execute([$pseudo]);
    $ligne = $requete->fetch();
    if (!$ligne) {
        return $utilisateur = null;
    }
    $exclu = avent_pdo()->prepare('SELECT 1 FROM avent_exclus WHERE pseudo = ?');
    $exclu->execute([$ligne['pseudo']]);
    $ligne['is_admin'] = (int) $ligne['is_admin'] === 1;
    $ligne['exclu']    = (bool) $exclu->fetchColumn();
    return $utilisateur = $ligne;
}

/** Joueur connecté et non exclu ; redirige vers la connexion sinon. */
function avent_exiger_joueur(): array
{
    $utilisateur = avent_utilisateur();
    if (!$utilisateur) {
        if (defined('AVENT_API')) {
            avent_json(['ok' => false, 'erreur' => 'Session expirée : reconnecte-toi.'], 401);
        }
        $chemin = $_SERVER['SCRIPT_NAME'] ?? '';
        $position = strpos($chemin, '/avent/');
        $cible = $position === false ? 'avent/index.php' : substr($chemin, $position + 1);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $cible .= '?' . $_SERVER['QUERY_STRING'];
        }
        avent_rediriger(avent_racine() . 'login.php?redirect=' . rawurlencode($cible));
    }
    if ($utilisateur['exclu'] && !$utilisateur['is_admin']) {
        avent_erreur_fatale('Accès retiré', "Tu ne participes pas à ce calendrier. Contacte l'organisateur.", 403);
    }
    return $utilisateur;
}

function avent_exiger_admin(): array
{
    $utilisateur = avent_exiger_joueur();
    if (!$utilisateur['is_admin']) {
        avent_erreur_fatale('Accès réservé', "Cette page est réservée aux administrateurs du calendrier.", 403);
    }
    return $utilisateur;
}

// ---------------------------------------------------------------------------
// Mode test (administrateurs uniquement, propre à la session)
// ---------------------------------------------------------------------------

function avent_test(): array
{
    $utilisateur = avent_utilisateur();
    $test = $_SESSION['avent_test'] ?? null;
    if (!$utilisateur || !$utilisateur['is_admin'] || !is_array($test) || empty($test['actif'])) {
        return ['actif' => false, 'mode' => null, 'date' => null];
    }
    return [
        'actif' => true,
        'mode'  => ($test['mode'] ?? 'tout') === 'date' ? 'date' : 'tout',
        'date'  => $test['date'] ?? null,
    ];
}

// ---------------------------------------------------------------------------
// Dates et déblocage des cases
// ---------------------------------------------------------------------------

function avent_maintenant(): DateTimeImmutable
{
    $test = avent_test();
    if ($test['actif'] && $test['mode'] === 'date' && $test['date']) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $test['date']);
        if ($date) {
            return $date->modify('+12 hours'); // midi de la date simulée
        }
    }
    return new DateTimeImmutable('now');
}

function avent_debut(): DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', avent_reglage('debut', '2026-12-01'));
    return $date ?: new DateTimeImmutable('2026-12-01 00:00:00');
}

function avent_date_jour(int $jour): DateTimeImmutable
{
    return avent_debut()->modify('+' . ($jour - 1) . ' days');
}

/** Date (exclue) à partir de laquelle la case ne peut plus être jouée. */
function avent_date_expiration(int $jour): DateTimeImmutable
{
    return avent_date_jour($jour)->modify('+' . avent_rattrapage_jours() . ' days');
}

/**
 * verrouille   : pas encore ouverte
 * disponible   : jour J (ou mode test « tout débloquer »)
 * rattrapable  : du jour J+1 au jour J+6 inclus (7 jours glissants avec le réglage par défaut)
 * expire       : plus jouable
 */
function avent_statut_jour(int $jour, ?DateTimeImmutable $maintenant = null, bool $reel = false): string
{
    $test = avent_test();
    if (!$reel && $test['actif'] && $test['mode'] === 'tout') {
        return 'disponible';
    }
    $maintenant ??= avent_maintenant();
    $ouverture = avent_date_jour($jour);
    if ($maintenant < $ouverture) {
        return 'verrouille';
    }
    if ($maintenant >= avent_date_expiration($jour)) {
        return 'expire';
    }
    return $maintenant < $ouverture->modify('+1 day') ? 'disponible' : 'rattrapable';
}

function avent_mois_fr(int $mois): string
{
    return ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août',
            'septembre', 'octobre', 'novembre', 'décembre'][$mois] ?? '';
}

function avent_date_fr(DateTimeImmutable $date): string
{
    return (int) $date->format('j') . ' ' . avent_mois_fr((int) $date->format('n'));
}

// ---------------------------------------------------------------------------
// Jours et parties
// ---------------------------------------------------------------------------

function avent_jour(int $numero): ?array
{
    if ($numero < 1 || $numero > AVENT_NB_JOURS) {
        return null;
    }
    $requete = avent_pdo()->prepare('SELECT * FROM avent_jours WHERE jour = ?');
    $requete->execute([$numero]);
    $ligne = $requete->fetch();
    return $ligne ?: null;
}

function avent_jours(): array
{
    $jours = [];
    foreach (avent_pdo()->query('SELECT * FROM avent_jours ORDER BY jour') as $ligne) {
        $jours[(int) $ligne['jour']] = $ligne;
    }
    return $jours;
}

/** Meilleure partie terminée du joueur pour chaque jour (hors parties de test). */
function avent_meilleures_parties(string $pseudo): array
{
    $requete = avent_pdo()->prepare(
        'SELECT jour, points, score, reussite, duree_ms FROM (
             SELECT jour, points, score, reussite, duree_ms,
                    ROW_NUMBER() OVER (PARTITION BY jour ORDER BY points DESC, duree_ms ASC) AS rn
             FROM avent_parties
             WHERE pseudo = ? AND test = 0 AND statut = \'termine\'
         ) t WHERE rn = 1'
    );
    $requete->execute([$pseudo]);
    $resultat = [];
    foreach ($requete as $ligne) {
        $resultat[(int) $ligne['jour']] = $ligne;
    }
    return $resultat;
}

/** Nombre d'essais consommés par jour (une partie lancée compte, même abandonnée). */
function avent_essais_utilises(string $pseudo): array
{
    $requete = avent_pdo()->prepare(
        'SELECT jour, COUNT(*) AS n FROM avent_parties
         WHERE pseudo = ? AND test = 0 AND essai IS NOT NULL GROUP BY jour'
    );
    $requete->execute([$pseudo]);
    $resultat = [];
    foreach ($requete as $ligne) {
        $resultat[(int) $ligne['jour']] = (int) $ligne['n'];
    }
    return $resultat;
}

function avent_maintenant_precis(): string
{
    return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s.v');
}

// ---------------------------------------------------------------------------
// Classement
// ---------------------------------------------------------------------------

/**
 * Total = somme des meilleurs scores de chaque jour. Égalité : plus de cases réussies,
 * puis temps cumulé le plus court. Les parties de test et les joueurs exclus sont ignorés.
 */
function avent_classement(): array
{
    $lignes = avent_pdo()->query(
        'SELECT pseudo, SUM(points) AS total, SUM(reussite) AS nb_reussies,
                COUNT(*) AS nb_jours, SUM(duree_ms) AS temps_ms
         FROM (
             SELECT pseudo, jour, points, reussite, duree_ms,
                    ROW_NUMBER() OVER (PARTITION BY pseudo, jour ORDER BY points DESC, duree_ms ASC) AS rn
             FROM avent_parties
             WHERE test = 0 AND statut = \'termine\'
         ) t
         WHERE rn = 1 AND pseudo NOT IN (SELECT pseudo FROM avent_exclus)
         GROUP BY pseudo
         ORDER BY total DESC, nb_reussies DESC, temps_ms ASC, pseudo ASC'
    )->fetchAll();

    $rang = 0;
    $precedent = null;
    foreach ($lignes as $position => &$ligne) {
        $cle = $ligne['total'] . '|' . $ligne['nb_reussies'] . '|' . $ligne['temps_ms'];
        if ($cle !== $precedent) {
            $rang = $position + 1;
            $precedent = $cle;
        }
        $ligne['rang'] = $rang;
    }
    unset($ligne);
    return $lignes;
}

/** Avatars du site de pronos, indexés par pseudo (requête séparée : collations différentes). */
function avent_avatars(): array
{
    $avatars = [];
    foreach (avent_pdo()->query('SELECT pseudo, avatar FROM utilisateurs') as $ligne) {
        $avatars[$ligne['pseudo']] = $ligne['avatar'];
    }
    return $avatars;
}

function avent_url_avatar(?string $avatar): string
{
    $avatar = (string) $avatar;
    if ($avatar === '' || !preg_match('#^avatars/[A-Za-z0-9_./-]+$#', $avatar) || str_contains($avatar, '..')) {
        $avatar = 'avatars/default.png';
    }
    return avent_racine() . $avatar;
}

function avent_formater_duree(int $millisecondes): string
{
    $secondes = intdiv($millisecondes, 1000);
    return intdiv($secondes, 60) . ' min ' . str_pad((string) ($secondes % 60), 2, '0', STR_PAD_LEFT) . ' s';
}

// ---------------------------------------------------------------------------
// Jeux (modules dans avent/jeux/<slug>/)
// ---------------------------------------------------------------------------

function avent_slug_valide(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9_]{1,50}$/', $slug);
}

function avent_liste_jeux(): array
{
    $jeux = [];
    foreach (glob(dirname(__DIR__) . '/jeux/*', GLOB_ONLYDIR) ?: [] as $dossier) {
        $slug = basename($dossier);
        $fichier = $dossier . '/manifest.json';
        if (!avent_slug_valide($slug) || !is_file($fichier)) {
            continue;
        }
        $manifeste = json_decode((string) file_get_contents($fichier), true);
        if (is_array($manifeste) && !empty($manifeste['nom'])) {
            $manifeste['slug'] = $slug;
            $jeux[$slug] = $manifeste;
        }
    }
    ksort($jeux);
    return $jeux;
}

function avent_jeu(string $slug): ?array
{
    return avent_liste_jeux()[$slug] ?? null;
}

/** Fonction fournie par le module serveur du jeu (avent_jeu_<slug>_<nom>), ou null. */
function avent_jeu_fonction(string $slug, string $nom): ?string
{
    if (!avent_slug_valide($slug)) {
        return null;
    }
    $fichier = dirname(__DIR__) . '/jeux/' . $slug . '/server.php';
    if (!is_file($fichier)) {
        return null;
    }
    require_once $fichier;
    $fonction = 'avent_jeu_' . $slug . '_' . $nom;
    return function_exists($fonction) ? $fonction : null;
}

function avent_config_decodee(?string $json): array
{
    $config = json_decode((string) $json, true);
    return is_array($config) ? $config : [];
}

/** Configuration envoyée au navigateur : le module retire les réponses attendues. */
function avent_config_publique(string $slug, array $config): array
{
    $fonction = avent_jeu_fonction($slug, 'config_publique');
    return $fonction ? $fonction($config) : $config;
}

// ---------------------------------------------------------------------------
// Images téléversées (illustrations, GIF de révélation)
// ---------------------------------------------------------------------------

function avent_enregistrer_image(array $fichier, string $prefixe): ?string
{
    if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Le fichier n'a pas pu être envoyé (erreur " . (int) $fichier['error'] . ').');
    }
    if ($fichier['size'] > AVENT_IMAGE_MAX) {
        throw new RuntimeException('Image trop lourde (3 Mo maximum).');
    }
    $types = ['image/gif' => 'gif', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($fichier['tmp_name']);
    if (!isset($types[$mime]) || @getimagesize($fichier['tmp_name']) === false) {
        throw new RuntimeException('Format non accepté : GIF, JPG, PNG ou WebP uniquement.');
    }
    // L'extension vient du type réel du fichier, jamais du nom envoyé par le navigateur.
    $nom = preg_replace('/[^a-z0-9]/', '', strtolower($prefixe)) . '-' . bin2hex(random_bytes(6)) . '.' . $types[$mime];
    if (!move_uploaded_file($fichier['tmp_name'], dirname(__DIR__) . '/uploads/' . $nom)) {
        throw new RuntimeException("Impossible d'enregistrer l'image sur le serveur.");
    }
    return 'uploads/' . $nom;
}

function avent_supprimer_image(?string $chemin): void
{
    if ($chemin && preg_match('#^uploads/[a-z0-9._-]+$#', $chemin)) {
        $fichier = dirname(__DIR__) . '/' . $chemin;
        if (is_file($fichier)) {
            @unlink($fichier);
        }
    }
}

/** Chemin d'image relatif au dossier avent/ (celui des pages joueur et des réponses JSON), ou null. */
function avent_image_valide(?string $chemin): ?string
{
    return ($chemin && preg_match('#^uploads/[a-z0-9._-]+$#', $chemin)) ? $chemin : null;
}

/** URL de l'image relative au script courant (pages joueur : « uploads/… », administration : « ../uploads/… »). */
function avent_url_image(?string $chemin): ?string
{
    $valide = avent_image_valide($chemin);
    return $valide ? avent_base() . $valide : null;
}
