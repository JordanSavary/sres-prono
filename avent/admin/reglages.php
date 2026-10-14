<?php
require_once __DIR__ . '/../includes/admin.php';

avent_exiger_admin();

$erreurs = [];
$valeurs = [
    'debut'            => avent_reglage('debut', '2026-12-01'),
    'coef_boss'        => avent_reglage('coef_boss', '2'),
    'essais_max'       => avent_reglage('essais_max', '2'),
    'rattrapage_jours' => avent_reglage('rattrapage_jours', '7'),
];

if (avent_admin_post()) {
    foreach ($valeurs as $cle => $_) {
        $valeurs[$cle] = trim((string) ($_POST[$cle] ?? ''));
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $valeurs['debut']);
    if (!$date || $date->format('Y-m-d') !== $valeurs['debut']) {
        $erreurs[] = 'La date de la case n°1 est invalide.';
    }
    $coef = str_replace(',', '.', $valeurs['coef_boss']);
    if (!is_numeric($coef) || (float) $coef < 1 || (float) $coef > 10) {
        $erreurs[] = 'Le coefficient des jours boss doit être compris entre 1 et 10.';
    } else {
        $valeurs['coef_boss'] = (string) (float) $coef;
    }
    if (!ctype_digit($valeurs['essais_max']) || (int) $valeurs['essais_max'] < 1 || (int) $valeurs['essais_max'] > 5) {
        $erreurs[] = 'Le nombre d\'essais doit être compris entre 1 et 5.';
    }
    if (!ctype_digit($valeurs['rattrapage_jours']) || (int) $valeurs['rattrapage_jours'] < 1 || (int) $valeurs['rattrapage_jours'] > 30) {
        $erreurs[] = 'La durée de rattrapage doit être comprise entre 1 et 30 jours.';
    }

    if (!$erreurs) {
        $requete = avent_pdo()->prepare(
            'INSERT INTO avent_reglages (cle, valeur) VALUES (?, ?) ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)'
        );
        foreach ($valeurs as $cle => $valeur) {
            $requete->execute([$cle, $valeur]);
        }
        avent_flash('Réglages enregistrés.', 'ok');
        avent_rediriger('reglages.php');
    }
}

avent_entete('Réglages', 'page-admin');
avent_admin_nav('reglages');
?>
<h1 class="page-titre">Réglages</h1>

<?php foreach ($erreurs as $erreur) : ?>
    <p class="erreur-bloc"><?= h($erreur) ?></p>
<?php endforeach; ?>

<form method="post" class="carte formulaire">
    <?= avent_champ_csrf() ?>

    <label for="debut">Date de la case n°1</label>
    <input type="date" id="debut" name="debut" value="<?= h($valeurs['debut']) ?>">
    <p class="aide">Les 24 cases s'ouvrent ensuite à raison d'une par jour, à minuit (heure de Paris).</p>

    <label for="rattrapage_jours">Durée de rattrapage (jours)</label>
    <input type="number" id="rattrapage_jours" name="rattrapage_jours" min="1" max="30" value="<?= h($valeurs['rattrapage_jours']) ?>">
    <p class="aide">Avec 7 jours, la case du 1er décembre reste jouable jusqu'au 7 décembre inclus.</p>

    <label for="essais_max">Parties par joueur et par case</label>
    <input type="number" id="essais_max" name="essais_max" min="1" max="5" value="<?= h($valeurs['essais_max']) ?>">
    <p class="aide">Le meilleur score est conservé.</p>

    <label for="coef_boss">Coefficient des jours boss</label>
    <input type="text" id="coef_boss" name="coef_boss" inputmode="decimal" value="<?= h($valeurs['coef_boss']) ?>">
    <p class="aide">Les points d'une partie un jour boss sont multipliés par ce nombre (ex. 2 ou 1,5).</p>

    <?php if (new DateTimeImmutable('now') >= avent_debut()) : ?>
        <p class="avertissement-bloc">Le calendrier est déjà ouvert : modifier ces réglages change les règles en cours de partie.</p>
    <?php endif; ?>

    <div class="actions">
        <button type="submit" class="bouton">Enregistrer</button>
    </div>
</form>
<?php avent_pied(); ?>
