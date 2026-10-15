<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/decor.php';

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

/** Ordre des fenêtres sur la façade (mélangé, comme dans un vrai calendrier). */
const AVENT_ORDRE_FENETRES = [14, 3, 21, 8, 17, 1, 24, 10, 5, 19, 12, 6, 2, 16, 23, 9, 4, 20, 11, 22, 7, 15, 18, 13];

// Case à proposer en premier : la plus récente encore à jouer et non jouée
$jourACourir = null;
foreach ($jours as $n => $jour) {
    $st = avent_statut_jour($n, $maintenant);
    if (in_array($st, ['disponible', 'rattrapable'], true) && !isset($meilleures[$n]) && !empty($jour['jeu'])) {
        if ($jourACourir === null || $st === 'disponible' || $n > $jourACourir) {
            $jourACourir = $n;
        }
    }
}
$nbOuvertes = count($meilleures);
$pct = (int) round($nbOuvertes / AVENT_NB_JOURS * 100);

avent_entete('Calendrier', 'page-calendrier');
avent_decor_ciel();
?>
<section class="accroche">
    <h1>Mystère au <span>Gîte des Flocons</span></h1>
    <div class="tableau-bord">
        <div class="progression-carte">
            <div class="progression-ligne">
                <strong><?= $nbOuvertes ?> / <?= AVENT_NB_JOURS ?> volets ouverts</strong>
                <span>
                    <?php if ($maintenant < avent_debut()) : ?>
                        Ouverture le <?= h(avent_date_fr(avent_debut())) ?>
                    <?php elseif ($rang !== null) : ?>
                        <?= $rang ?><?= $rang === 1 ? 'er' : 'e' ?> sur <?= $nbJoueurs ?> · <?= $total ?> points
                    <?php else : ?>
                        Pas encore de points
                    <?php endif; ?>
                </span>
            </div>
            <div class="progression-piste"><div style="width:<?= $pct ?>%"></div></div>
        </div>
        <?php if ($jourACourir !== null) : ?>
            <a class="bouton" href="jour.php?j=<?= $jourACourir ?>">Jouer au jeu du jour</a>
        <?php endif; ?>
    </div>
</section>

<section class="scene">
    <div class="monts monts-1" aria-hidden="true"></div>
    <div class="monts monts-2" aria-hidden="true"></div>
    <div class="neige-sol" aria-hidden="true"></div>
    <?php
    avent_decor_sapin('left:0%;width:150px;bottom:104px');
    avent_decor_sapin('left:8%;width:100px;bottom:84px');
    avent_decor_sapin('left:93%;width:104px;bottom:84px');
    avent_decor_sapin('left:97%;width:150px;bottom:104px');
    avent_decor_bonhomme();
    ?>
    <div class="chalet">
        <?php avent_decor_toit(); ?>
        <div class="rive" aria-hidden="true"></div>
        <div class="facade">
            <?php avent_decor_corniche(); ?>
            <div class="facade-corps">
                <div class="grille">
                    <?php foreach (AVENT_ORDRE_FENETRES as $i => $n) :
                        $jour     = $jours[$n] ?? ['est_boss' => 0, 'titre' => '', 'jeu' => null];
                        $statut   = avent_statut_jour($n, $maintenant);
                        $meilleur = $meilleures[$n] ?? null;
                        $utilises = $essais[$n] ?? 0;
                        $reste    = max(0, $essaisMax - $utilises);
                        $jouable  = in_array($statut, ['disponible', 'rattrapable'], true) && $reste > 0 && !empty($jour['jeu']);
                        $classes  = ['fenetre', 'v' . (($i + intdiv($i, 6)) % 4)];
                        $etat     = '';
                        $puce     = '';
                        $aide     = '';

                        if ($meilleur) {
                            $classes[] = 'statut-jouee';
                            $etat = (int) $meilleur['points'] . ' pts';
                            $aide = 'Jouée : ' . (int) $meilleur['points'] . ' points.';
                            if ($jouable) {
                                $classes[] = 'rejouable';
                                $aide .= ' Un essai de plus possible.';
                            }
                        } else {
                            $classes[] = 'statut-' . $statut;
                            if ($statut === 'verrouille') {
                                $aide = 'Ouverture le ' . avent_date_fr(avent_date_jour($n)) . '.';
                            } elseif ($statut === 'disponible') {
                                $puce = "Aujourd'hui";
                                $aide = 'Disponible aujourd\'hui.';
                            } elseif ($statut === 'rattrapable') {
                                $puce = 'Rattrapage';
                                $aide = 'Jouable jusqu\'au ' . avent_date_fr(avent_date_expiration($n)->modify('-1 day')) . '.';
                            } else {
                                $aide = 'Cette case n\'est plus jouable.';
                            }
                        }
                        if (!empty($jour['est_boss'])) {
                            $classes[] = 'boss';
                            $aide .= ' Jour boss : points majorés.';
                        }
                        $bloque = $statut === 'verrouille' && !$meilleur;
                        ?>
                        <div class="cellule">
                            <?php if ($puce !== '') : ?><span class="puce <?= $statut === 'rattrapable' ? 'puce-rattrapage' : '' ?>"><?= h($puce) ?></span><?php endif; ?>
                            <a class="<?= h(implode(' ', $classes)) ?>"
                               href="<?= $bloque ? '#' : 'jour.php?j=' . $n ?>"
                               data-jour="<?= $n ?>"
                               data-bloque="<?= $bloque ? '1' : '0' ?>"
                               data-message="<?= h(avent_date_fr(avent_date_jour($n))) ?>"
                               title="<?= h(trim($aide)) ?>"
                               aria-label="Case <?= $n ?> : <?= h(trim($aide)) ?>"
                               <?= $bloque ? 'aria-disabled="true"' : '' ?>>
                                <span class="vitre"><span class="etat"><?= h($etat) ?></span></span>
                                <span class="volets">
                                    <span class="volet volet-g"></span>
                                    <span class="volet volet-d"></span>
                                </span>
                                <?php if (!empty($jour['est_boss'])) : ?><span class="etoile" title="Jour boss">★</span><?php endif; ?>
                                <span class="num"><?= $n ?></span>
                            </a>
                            <?= avent_decor_balcon($i) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php avent_decor_porte(); ?>
            </div>
        </div>
        <div class="seuil" aria-hidden="true"></div>
        <?php avent_decor_cadeaux(); ?>
    </div>
</section>

<section class="regles">
    <div class="regles-contenu">
        <div class="legende">
            <span><i class="mini mini-ferme"></i> Volets fermés : pas encore disponible</span>
            <span><i class="mini mini-entrouvert"></i> Volets entrouverts : à jouer</span>
            <span><i class="mini mini-ouvert"></i> Volets grands ouverts : joué</span>
        </div>
        <h2>Comment ça marche ?</h2>
        <div class="etapes">
            <div class="etape">
                <span class="etape-num e1">1</span>
                <h3>Une fenêtre par jour</h3>
                <p>Chaque jour à minuit, une nouvelle fenêtre du gîte s'illumine. Une case manquée reste jouable pendant <?= avent_rattrapage_jours() ?> jours.</p>
            </div>
            <div class="etape">
                <span class="etape-num e2">2</span>
                <h3>Joue pour ouvrir</h3>
                <p>Un mini-jeu de 2 minutes maximum, <?= $essaisMax ?> essais par case, aucun indice. Le meilleur score est gardé.</p>
            </div>
            <div class="etape">
                <span class="etape-num e3">3</span>
                <h3>Grimpe au classement</h3>
                <p>Tes points s'additionnent jusqu'au 24. Les jours boss <span aria-hidden="true">★</span> rapportent ×<?= h(rtrim(rtrim(number_format(avent_coef_boss(), 1, ',', ''), '0'), ',')) ?> points.</p>
            </div>
        </div>
    </div>
</section>
<p id="toast" class="toast" role="status" aria-live="polite" hidden></p>
<?php avent_pied(); ?>
