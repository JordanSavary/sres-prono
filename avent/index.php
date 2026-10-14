<?php
require_once __DIR__ . '/includes/layout.php';

$utilisateur = avent_exiger_joueur();
$pseudo      = $utilisateur['pseudo'];
$maintenant  = avent_maintenant();

$jours      = avent_jours();
$meilleures = avent_meilleures_parties($pseudo);
$essais     = avent_essais_utilises($pseudo);
$essaisMax  = avent_essais_max();

// Position et total du joueur
$total = 0;
$rang  = null;
$nbJoueurs = 0;
foreach (avent_classement() as $ligne) {
    $nbJoueurs++;
    if ($ligne['pseudo'] === $pseudo) {
        $total = (int) $ligne['total'];
        $rang  = (int) $ligne['rang'];
    }
}

function avent_sapin(string $classe): string
{
    return '<svg class="sapin ' . $classe . '" viewBox="0 0 120 200" aria-hidden="true">'
        . '<rect x="52" y="170" width="16" height="30" fill="#5a3a22"/>'
        . '<polygon points="60,6 100,70 20,70" fill="#1f5d3a"/>'
        . '<polygon points="60,40 112,115 8,115" fill="#256b43"/>'
        . '<polygon points="60,82 118,172 2,172" fill="#2c7a4d"/>'
        . '<circle cx="60" cy="8" r="6" fill="#e0b44c"/>'
        . '<circle cx="42" cy="60" r="4" fill="#b3202a"/><circle cx="78" cy="52" r="4" fill="#e0b44c"/>'
        . '<circle cx="34" cy="104" r="4" fill="#e0b44c"/><circle cx="84" cy="98" r="4" fill="#b3202a"/>'
        . '<circle cx="50" cy="148" r="4" fill="#b3202a"/><circle cx="92" cy="146" r="4" fill="#e0b44c"/>'
        . '</svg>';
}

avent_entete('Calendrier', 'page-calendrier');
?>
<section class="accroche">
    <h1>Mystère au Gîte des Flocons</h1>
    <?php if ($maintenant < avent_debut()) : ?>
        <p>Les cadeaux sont encore au chaud : l'enquête commence le <strong><?= h(avent_date_fr(avent_debut())) ?></strong>.</p>
    <?php else : ?>
        <p>Chaque jour, une fenêtre s'ouvre sur un indice. Réussis le mini-jeu du jour pour faire avancer l'enquête avec Barnabé.</p>
    <?php endif; ?>
    <p class="mes-points">
        <?php if ($rang !== null) : ?>
            Tu es <strong><?= $rang ?><?= $rang === 1 ? 'er' : 'e' ?></strong> sur <?= $nbJoueurs ?>
            avec <strong><?= $total ?> points</strong>.
        <?php else : ?>
            Tu n'as pas encore de points : ouvre la première fenêtre disponible !
        <?php endif; ?>
        <a href="classement.php">Voir le classement</a>
    </p>
</section>

<div class="scene">
    <?= avent_sapin('sapin-g') ?>
    <div class="chalet">
        <div class="toit" aria-hidden="true"></div>
        <div class="facade">
            <?php for ($n = 1; $n <= AVENT_NB_JOURS; $n++) :
                $jour     = $jours[$n] ?? ['est_boss' => 0, 'titre' => '', 'jeu' => null];
                $statut   = avent_statut_jour($n, $maintenant);
                $meilleur = $meilleures[$n] ?? null;
                $utilises = $essais[$n] ?? 0;
                $reste    = max(0, $essaisMax - $utilises);
                $jouable  = in_array($statut, ['disponible', 'rattrapable'], true) && $reste > 0 && !empty($jour['jeu']);
                $classes  = ['fenetre'];
                $etat     = '';
                $aide     = '';

                if ($meilleur) {
                    $classes[] = 'statut-jouee';
                    $etat = '✓ ' . (int) $meilleur['points'];
                    $aide = 'Jouée : ' . (int) $meilleur['points'] . ' points.';
                    if ($jouable) {
                        $classes[] = 'rejouable';
                        $aide .= ' Un essai de plus possible.';
                    }
                } else {
                    $classes[] = 'statut-' . $statut;
                    if ($statut === 'verrouille') {
                        $etat = '🔒';
                        $aide = 'Ouverture le ' . avent_date_fr(avent_date_jour($n)) . '.';
                    } elseif ($statut === 'disponible') {
                        $etat = 'À jouer';
                        $aide = 'Disponible aujourd\'hui.';
                    } elseif ($statut === 'rattrapable') {
                        $etat = 'Rattrapage';
                        $aide = 'Jouable jusqu\'au ' . avent_date_fr(avent_date_expiration($n)->modify('-1 day')) . '.';
                    } else {
                        $etat = 'Expirée';
                        $aide = 'Cette case n\'est plus jouable.';
                    }
                }
                if (!empty($jour['est_boss'])) {
                    $classes[] = 'boss';
                    $aide .= ' Jour boss : points majorés.';
                }
                $bloque = $statut === 'verrouille' && !$meilleur;
                ?>
                <a class="<?= h(implode(' ', $classes)) ?>"
                   href="<?= $bloque ? '#' : 'jour.php?j=' . $n ?>"
                   data-jour="<?= $n ?>"
                   data-bloque="<?= $bloque ? '1' : '0' ?>"
                   data-message="<?= h(avent_date_fr(avent_date_jour($n))) ?>"
                   title="<?= h(trim($aide)) ?>"
                   aria-label="Case <?= $n ?> : <?= h(trim($aide)) ?>"
                   <?= $bloque ? 'aria-disabled="true"' : '' ?>>
                    <span class="volet volet-g"></span>
                    <span class="volet volet-d"></span>
                    <span class="num"><?= $n ?><?= !empty($jour['est_boss']) ? '<i class="etoile">★</i>' : '' ?></span>
                    <span class="etat"><?= h($etat) ?></span>
                </a>
            <?php endfor; ?>
        </div>
        <div class="porte" aria-hidden="true"></div>
    </div>
    <?= avent_sapin('sapin-d') ?>
</div>
<div class="sol" aria-hidden="true"></div>

<p class="legende">
    <span class="pastille p-dispo"></span> à jouer
    <span class="pastille p-rattrape"></span> rattrapage (7 jours)
    <span class="pastille p-jouee"></span> jouée
    <span class="pastille p-boss"></span> jour boss
</p>
<p id="toast" class="toast" role="status" aria-live="polite" hidden></p>
<?php avent_pied(); ?>
