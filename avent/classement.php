<?php
require_once __DIR__ . '/includes/layout.php';

$utilisateur = avent_exiger_joueur();
$classement  = avent_classement();
$avatars     = avent_avatars();
$podium      = array_slice($classement, 0, 3);
$suite       = array_slice($classement, 3);

// Ordre d'affichage du podium : 2e, 1er, 3e
$ordrePodium = [];
foreach ([1, 0, 2] as $position) {
    if (isset($podium[$position])) {
        $ordrePodium[] = $podium[$position];
    }
}

avent_entete('Classement', 'page-classement');
?>
<h1 class="page-titre">Classement</h1>

<?php if (!$classement) : ?>
    <p class="carte vide">Personne n'a encore marqué de points. Sois le premier à ouvrir une fenêtre !</p>
<?php else : ?>
    <div class="podium">
        <?php foreach ($ordrePodium as $ligne) : ?>
            <div class="marche marche-<?= (int) $ligne['rang'] ?>">
                <img class="avatar" src="<?= h(avent_url_avatar($avatars[$ligne['pseudo']] ?? null)) ?>" alt="">
                <div class="pseudo"><?= h($ligne['pseudo']) ?></div>
                <div class="pts"><?= (int) $ligne['total'] ?> pts</div>
                <div class="socle"><?= (int) $ligne['rang'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="defile">
        <table class="liste-rangs">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Joueur</th>
                    <th class="num-col">Points</th>
                    <th class="num-col">Cases réussies</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classement as $ligne) : ?>
                    <tr class="<?= $ligne['pseudo'] === $utilisateur['pseudo'] ? 'moi' : '' ?>">
                        <td><?= (int) $ligne['rang'] ?></td>
                        <td>
                            <img src="<?= h(avent_url_avatar($avatars[$ligne['pseudo']] ?? null)) ?>" alt="">
                            <?= h($ligne['pseudo']) ?>
                        </td>
                        <td class="num-col"><?= (int) $ligne['total'] ?></td>
                        <td class="num-col"><?= (int) $ligne['nb_reussies'] ?> / <?= (int) $ligne['nb_jours'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="legende">Total = meilleur score de chaque case. À égalité : plus de cases réussies, puis temps cumulé le plus court.</p>
<?php endif; ?>
<?php avent_pied(); ?>
