<?php
/** Aides communes aux pages d'administration. */

require_once __DIR__ . '/layout.php';

/** Barre de navigation de l'administration. */
function avent_admin_nav(string $actif): void
{
    $liens = [
        'index'     => ['Planning et test', 'index.php'],
        'stats'     => ['Statistiques', 'stats.php'],
        'comptes'   => ['Comptes', 'comptes.php'],
        'reglages'  => ['Réglages', 'reglages.php'],
    ];
    echo '<nav class="admin-nav" aria-label="Administration">';
    foreach ($liens as $cle => [$libelle, $url]) {
        echo '<a href="' . h($url) . '"' . ($cle === $actif ? ' class="actif"' : '') . '>' . h($libelle) . '</a>';
    }
    echo '</nav>';
}

/** Valide une requête POST d'administration (méthode + CSRF) ; renvoie true si c'est un POST valide. */
function avent_admin_post(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    avent_exiger_csrf();
    return true;
}
