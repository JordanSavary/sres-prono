<?php
/**
 * Gabarit commun des pages du calendrier (aucune ressource externe : tout est servi
 * depuis le domaine, comme l'exige la DSI).
 */

require_once __DIR__ . '/bootstrap.php';

function avent_entete(string $titre, string $classe = '', array $scripts = []): void
{
    $utilisateur = avent_utilisateur();
    $base        = avent_base();
    $racine      = avent_racine();
    $test        = avent_test();
    $page        = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $dansAdmin   = avent_dossier_script() === 'admin';
    $flash       = avent_flash();

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($titre) ?> – Mystère au Gîte des Flocons</title>
    <link rel="icon" type="image/png" href="<?= h($racine) ?>assets/favicon.png">
    <link rel="stylesheet" href="<?= h($base) ?>assets/avent.css?v=1">
</head>
<body class="<?= h($classe) ?>">
<div class="neige" aria-hidden="true"></div>
<header class="entete">
    <a class="marque" href="<?= h($base) ?>index.php">🎄 <span>Gîte des Flocons</span></a>
    <nav class="menu" aria-label="Navigation">
        <a href="<?= h($base) ?>index.php" <?= $page === 'index.php' && !$dansAdmin ? 'class="actif"' : '' ?>>Calendrier</a>
        <a href="<?= h($base) ?>classement.php" <?= $page === 'classement.php' ? 'class="actif"' : '' ?>>Classement</a>
        <a href="<?= h($racine) ?>dashboard.php">Pronos</a>
        <?php if ($utilisateur && $utilisateur['is_admin']) : ?>
            <a href="<?= h($base) ?>admin/index.php" <?= $dansAdmin ? 'class="actif"' : '' ?>>Admin</a>
        <?php endif; ?>
        <?php if ($utilisateur) : ?>
            <a href="<?= h($racine) ?>logout.php">Déconnexion</a>
        <?php endif; ?>
    </nav>
</header>
<?php if ($test['actif']) : ?>
    <div class="bandeau-test" role="status">
        Mode test actif :
        <?= $test['mode'] === 'tout' ? 'toutes les cases sont débloquées' : 'date simulée au ' . h($test['date']) ?>.
        Tes parties ne comptent ni dans le classement ni dans les statistiques.
        <a href="<?= h($base) ?>admin/index.php">Gérer</a>
    </div>
<?php endif; ?>
<?php if ($flash) : ?>
    <p class="flash flash-<?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></p>
<?php endif; ?>
<main class="contenu">
<?php
    $GLOBALS['avent_scripts'] = $scripts;
}

function avent_pied(): void
{
    $base = avent_base();
    ?>
</main>
<footer class="pied">Calendrier de l'avent · fait avec amour (et un peu de fondue) pour l'équipe</footer>
<script src="<?= h($base) ?>assets/avent.js?v=1"></script>
<?php foreach ($GLOBALS['avent_scripts'] ?? [] as $script) : ?>
<script src="<?= h($base . $script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php
}

/** Champ caché portant le jeton CSRF. */
function avent_champ_csrf(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(avent_csrf()) . '">';
}
