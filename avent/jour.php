<?php
require_once __DIR__ . '/includes/layout.php';

$utilisateur = avent_exiger_joueur();
$pseudo      = $utilisateur['pseudo'];
$numero      = (int) ($_GET['j'] ?? 0);
$jour        = avent_jour($numero);

if (!$jour) {
    avent_rediriger('index.php');
}

$statut     = avent_statut_jour($numero);
$meilleures = avent_meilleures_parties($pseudo);
$meilleur   = $meilleures[$numero] ?? null;
$test       = avent_test()['actif'];

if ($statut === 'verrouille' && !$meilleur) {
    avent_flash('Pas encore ! Cette case ouvre le ' . avent_date_fr(avent_date_jour($numero)) . '.', 'info');
    avent_rediriger('index.php');
}

$jeu       = $jour['jeu'] ? avent_jeu($jour['jeu']) : null;
$utilises  = avent_essais_utilises($pseudo)[$numero] ?? 0;
$essaisMax = avent_essais_max();
$reste     = max(0, $essaisMax - $utilises);
$duree     = min(AVENT_DUREE_MAX, max(10, (int) $jour['duree_max']));
$coef      = !empty($jour['est_boss']) ? avent_coef_boss() : 1.0;
$ouvert    = in_array($statut, ['disponible', 'rattrapable'], true);
$peutJouer = $jeu && $ouvert && ($test || $reste > 0);
$imageIllustration = avent_url_image($jour['illustration']);
$imageRevelation   = avent_url_image($jour['revelation_image']);
$revelationVisible = $meilleur || $test;

if (!$jeu) {
    $raison = "Le jeu de ce jour n'est pas encore prêt. Reviens un peu plus tard !";
} elseif (!$ouvert) {
    $raison = "Cette case a expiré : elle n'est plus jouable.";
} elseif (!$test && $reste <= 0) {
    $raison = 'Tu as utilisé tes ' . $essaisMax . ' essais pour cette case.';
} else {
    $raison = '';
}

avent_entete('Jour ' . $numero, 'page-jour', ['assets/jour.js?v=1']);
?>
<article class="jour"
         id="jeu-page"
         data-jour="<?= $numero ?>"
         data-csrf="<?= h(avent_csrf()) ?>"
         data-duree="<?= $duree ?>">
    <header class="jour-titre">
        <p class="jour-date">Jour <?= $numero ?> · <?= h(avent_date_fr(avent_date_jour($numero))) ?>
            <?php if (!empty($jour['est_boss'])) : ?><span class="badge-boss">★ Jour boss</span><?php endif; ?>
            <?php if ($statut === 'rattrapable') : ?><span class="badge-rattrapage">Rattrapage</span><?php endif; ?>
        </p>
        <h1><?= h($jour['titre'] ?: 'Jour ' . $numero) ?></h1>
    </header>

    <?php if ($imageIllustration) : ?>
        <img class="illustration" src="<?= h($imageIllustration) ?>" alt="">
    <?php endif; ?>
    <?php if (!empty($jour['intro'])) : ?>
        <p class="histoire"><?= nl2br(h($jour['intro'])) ?></p>
    <?php endif; ?>

    <section class="carte" id="avant">
        <?php if ($jeu) : ?>
            <h2><?= h($jeu['nom']) ?></h2>
            <?php if (!empty($jeu['description'])) : ?><p><?= h($jeu['description']) ?></p><?php endif; ?>
            <ul class="infos">
                <li>⏱ <?= $duree ?> secondes maximum</li>
                <li>🎯 <?= $test ? 'Essais illimités (mode test)' : $reste . ' essai' . ($reste > 1 ? 's' : '') . ' restant' . ($reste > 1 ? 's' : '') . ' sur ' . $essaisMax ?></li>
                <?php if ($coef > 1) : ?><li>★ Points ×<?= h(rtrim(rtrim(number_format($coef, 1, ',', ''), '0'), ',')) ?></li><?php endif; ?>
                <li>🚫 Pas d'indice</li>
            </ul>
        <?php endif; ?>

        <?php if ($meilleur) : ?>
            <p class="meilleur">Ton meilleur score : <strong><?= (int) $meilleur['points'] ?> points</strong>
                (score <?= (int) $meilleur['score'] ?>/100)</p>
        <?php endif; ?>

        <?php if ($peutJouer) : ?>
            <button type="button" class="bouton" id="bouton-commencer">
                <?= $meilleur ? 'Retenter ma chance' : 'Commencer' ?>
            </button>
            <p class="avertissement">Dès que tu appuies, le chronomètre démarre et l'essai est consommé.</p>
        <?php else : ?>
            <p class="information"><?= h($raison) ?></p>
        <?php endif; ?>
    </section>

    <section class="carte arene" id="arene" hidden>
        <div class="chrono" aria-live="off">
            <div class="chrono-barre"><span id="chrono-rempli"></span></div>
            <span id="chrono-texte">--</span>
        </div>
        <div id="jeu-conteneur"></div>
    </section>

    <section class="carte resultat" id="resultat" hidden>
        <h2 id="resultat-titre"></h2>
        <p class="score-final"><span id="resultat-points">0</span> <small>points</small></p>
        <p id="resultat-detail"></p>
        <div id="resultat-revelation" class="revelation" hidden></div>
        <div class="actions">
            <button type="button" class="bouton" id="bouton-rejouer" hidden>Retenter ma chance</button>
            <a class="bouton bouton-secondaire" href="classement.php">Voir le classement</a>
            <a class="bouton bouton-secondaire" href="index.php">Retour au calendrier</a>
        </div>
    </section>

    <?php if ($revelationVisible && (!empty($jour['revelation_texte']) || $imageRevelation)) : ?>
        <section class="carte revelation" id="revelation-fixe">
            <h2>Le mot de l'équipe</h2>
            <?php if ($imageRevelation) : ?><img src="<?= h($imageRevelation) ?>" alt=""><?php endif; ?>
            <?php if (!empty($jour['revelation_texte'])) : ?><p><?= nl2br(h($jour['revelation_texte'])) ?></p><?php endif; ?>
        </section>
    <?php endif; ?>

    <p class="retour"><a href="index.php">← Retour au calendrier</a></p>
</article>
<?php avent_pied(); ?>
