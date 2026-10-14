<?php
require_once __DIR__ . '/../includes/admin.php';

$admin = avent_exiger_admin();
$pdo   = avent_pdo();

if (avent_admin_post()) {
    $pseudo = (string) ($_POST['pseudo'] ?? '');
    $action = (string) ($_POST['action'] ?? '');

    $existe = $pdo->prepare('SELECT is_admin FROM utilisateurs WHERE pseudo = ?');
    $existe->execute([$pseudo]);
    $compte = $existe->fetch();

    if (!$compte) {
        avent_flash('Compte introuvable.', 'erreur');
    } elseif ($action === 'basculer_admin') {
        if ($pseudo === $admin['pseudo']) {
            avent_flash('Tu ne peux pas retirer ton propre rôle d\'administrateur.', 'erreur');
        } else {
            $pdo->prepare('UPDATE utilisateurs SET is_admin = 1 - is_admin WHERE pseudo = ?')->execute([$pseudo]);
            avent_flash('Rôle de « ' . $pseudo . ' » modifié.', 'ok');
        }
    } elseif ($action === 'basculer_exclu') {
        $deja = $pdo->prepare('SELECT 1 FROM avent_exclus WHERE pseudo = ?');
        $deja->execute([$pseudo]);
        if ($deja->fetchColumn()) {
            $pdo->prepare('DELETE FROM avent_exclus WHERE pseudo = ?')->execute([$pseudo]);
            avent_flash('« ' . $pseudo . ' » participe de nouveau au calendrier.', 'ok');
        } else {
            $pdo->prepare('INSERT INTO avent_exclus (pseudo) VALUES (?)')->execute([$pseudo]);
            avent_flash('« ' . $pseudo . ' » est exclu du calendrier (son compte de pronos n\'est pas touché).', 'ok');
        }
    }
    avent_rediriger('comptes.php');
}

$exclus = array_column($pdo->query('SELECT pseudo FROM avent_exclus')->fetchAll(), 'pseudo');
$comptes = $pdo->query('SELECT pseudo, email, date_inscription, is_admin FROM utilisateurs ORDER BY pseudo')->fetchAll();

avent_entete('Comptes', 'page-admin');
avent_admin_nav('comptes');
?>
<h1 class="page-titre">Comptes</h1>
<p>Les comptes sont ceux du site de pronos : ici, tu désignes les administrateurs du calendrier et tu peux exclure un joueur
    (il ne voit plus le calendrier et disparaît du classement, sans toucher à son compte de pronos).</p>

<div class="defile">
    <table class="tableau">
        <thead><tr><th>Pseudo</th><th>E-mail</th><th>Inscription</th><th>Rôle</th><th>Calendrier</th></tr></thead>
        <tbody>
        <?php foreach ($comptes as $c) :
            $estExclu = in_array($c['pseudo'], $exclus, true);
            ?>
            <tr>
                <td><strong><?= h($c['pseudo']) ?></strong></td>
                <td><?= h($c['email']) ?></td>
                <td><?= h(substr((string) $c['date_inscription'], 0, 10)) ?></td>
                <td>
                    <?= (int) $c['is_admin'] === 1 ? '<span class="tag tag-boss">Administrateur</span>' : 'Joueur' ?>
                    <form method="post" class="petit-formulaire">
                        <?= avent_champ_csrf() ?>
                        <input type="hidden" name="action" value="basculer_admin">
                        <input type="hidden" name="pseudo" value="<?= h($c['pseudo']) ?>">
                        <button type="submit"><?= (int) $c['is_admin'] === 1 ? 'Retirer' : 'Promouvoir' ?></button>
                    </form>
                </td>
                <td>
                    <?= $estExclu ? '<span class="tag tag-alerte">Exclu</span>' : '<span class="tag tag-ok">Participe</span>' ?>
                    <form method="post" class="petit-formulaire">
                        <?= avent_champ_csrf() ?>
                        <input type="hidden" name="action" value="basculer_exclu">
                        <input type="hidden" name="pseudo" value="<?= h($c['pseudo']) ?>">
                        <button type="submit"><?= $estExclu ? 'Réintégrer' : 'Exclure' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php avent_pied(); ?>
