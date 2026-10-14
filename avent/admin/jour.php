<?php
require_once __DIR__ . '/../includes/admin.php';

avent_exiger_admin();

$numero = (int) ($_GET['j'] ?? $_POST['jour'] ?? 0);
$jour   = avent_jour($numero);
if (!$jour) {
    avent_rediriger('index.php');
}

$jeux = avent_liste_jeux();
$nbParties = (int) avent_pdo()->query('SELECT COUNT(*) FROM avent_parties WHERE test = 0 AND jour = ' . $numero)->fetchColumn();
$dejaOuvert = avent_statut_jour($numero, null, true) !== 'verrouille';

$erreurs = [];
$avertissement = '';
$valeurs = $jour; // valeurs affichées dans le formulaire

if (avent_admin_post()) {
    $valeurs['titre']            = trim((string) ($_POST['titre'] ?? ''));
    $valeurs['intro']            = trim((string) ($_POST['intro'] ?? ''));
    $valeurs['jeu']              = trim((string) ($_POST['jeu'] ?? ''));
    $valeurs['config']           = trim((string) ($_POST['config'] ?? ''));
    $valeurs['est_boss']         = isset($_POST['est_boss']) ? 1 : 0;
    $valeurs['duree_max']        = (int) ($_POST['duree_max'] ?? 120);
    $valeurs['revelation_texte'] = trim((string) ($_POST['revelation_texte'] ?? ''));

    if (mb_strlen($valeurs['titre']) > 120) {
        $erreurs[] = 'Le titre ne doit pas dépasser 120 caractères.';
    }
    if ($valeurs['duree_max'] < 10 || $valeurs['duree_max'] > AVENT_DUREE_MAX) {
        $erreurs[] = 'La durée doit être comprise entre 10 et ' . AVENT_DUREE_MAX . ' secondes.';
    }

    // Jeu et contenu
    $configNormalisee = null;
    if ($valeurs['jeu'] !== '') {
        if (!isset($jeux[$valeurs['jeu']])) {
            $erreurs[] = 'Jeu inconnu.';
        } else {
            $decodee = json_decode($valeurs['config'], true);
            if (!is_array($decodee)) {
                $erreurs[] = 'Le contenu du jeu n\'est pas un JSON valide (' . json_last_error_msg() . ').';
            } else {
                $valider = avent_jeu_fonction($valeurs['jeu'], 'valider');
                $probleme = $valider ? $valider($decodee) : null;
                if ($probleme) {
                    $erreurs[] = 'Contenu du jeu : ' . $probleme;
                } else {
                    $configNormalisee = json_encode($decodee, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                }
            }
        }
    } else {
        $configNormalisee = '';
    }

    // Garde-fou : changer le jeu ou son contenu après des parties jouées modifie les conditions.
    $change = ($valeurs['jeu'] !== (string) $jour['jeu'])
        || ($configNormalisee !== null && avent_config_decodee($configNormalisee) != avent_config_decodee($jour['config']));
    if (!$erreurs && $change && $nbParties > 0 && $dejaOuvert && empty($_POST['confirmer'])) {
        $avertissement = "Cette case a déjà été jouée ($nbParties partie" . ($nbParties > 1 ? 's' : '')
            . "). Modifier le jeu ou son contenu change les conditions pour ceux qui ont déjà joué. "
            . 'Coche la confirmation pour enregistrer quand même.';
    }

    if (!$erreurs && !$avertissement) {
        try {
            $illustration = $jour['illustration'];
            $revelationImage = $jour['revelation_image'];

            $nouvelle = avent_enregistrer_image($_FILES['illustration'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'illustration');
            if ($nouvelle) {
                avent_supprimer_image($illustration);
                $illustration = $nouvelle;
            } elseif (!empty($_POST['supprimer_illustration'])) {
                avent_supprimer_image($illustration);
                $illustration = null;
            }

            $nouvelle = avent_enregistrer_image($_FILES['revelation_image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'revelation');
            if ($nouvelle) {
                avent_supprimer_image($revelationImage);
                $revelationImage = $nouvelle;
            } elseif (!empty($_POST['supprimer_revelation_image'])) {
                avent_supprimer_image($revelationImage);
                $revelationImage = null;
            }

            $maj = avent_pdo()->prepare(
                'UPDATE avent_jours SET titre = ?, intro = ?, jeu = ?, config = ?, est_boss = ?, duree_max = ?,
                        illustration = ?, revelation_texte = ?, revelation_image = ? WHERE jour = ?'
            );
            $maj->execute([
                $valeurs['titre'], $valeurs['intro'], $valeurs['jeu'] !== '' ? $valeurs['jeu'] : null,
                $configNormalisee, $valeurs['est_boss'], $valeurs['duree_max'],
                $illustration, $valeurs['revelation_texte'], $revelationImage, $numero,
            ]);
            avent_flash('Jour ' . $numero . ' enregistré.', 'ok');
            avent_rediriger('jour.php?j=' . $numero);
        } catch (RuntimeException $e) {
            $erreurs[] = $e->getMessage();
        }
    }
}

// Exemples de contenu, proposés par chaque module
$exemples = [];
foreach ($jeux as $slug => $manifeste) {
    if (isset($manifeste['exemple'])) {
        $exemples[$slug] = json_encode($manifeste['exemple'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

avent_entete('Modifier le jour ' . $numero, 'page-admin', ['assets/admin.js?v=1']);
avent_admin_nav('index');
?>
<h1 class="page-titre">Jour <?= $numero ?> · <?= h(avent_date_fr(avent_date_jour($numero))) ?></h1>

<?php foreach ($erreurs as $erreur) : ?>
    <p class="erreur-bloc"><?= h($erreur) ?></p>
<?php endforeach; ?>
<?php if ($avertissement) : ?>
    <p class="avertissement-bloc"><?= h($avertissement) ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="carte formulaire" id="formulaire-jour"
      data-exemples="<?= h(json_encode($exemples, JSON_UNESCAPED_UNICODE)) ?>">
    <?= avent_champ_csrf() ?>
    <input type="hidden" name="jour" value="<?= $numero ?>">

    <h2>Histoire</h2>
    <label for="titre">Titre de l'épisode</label>
    <input type="text" id="titre" name="titre" maxlength="120" value="<?= h($valeurs['titre']) ?>">

    <label for="intro">Texte d'introduction (affiché avant le jeu)</label>
    <textarea id="intro" name="intro"><?= h($valeurs['intro']) ?></textarea>

    <label for="illustration">Illustration (GIF, JPG, PNG ou WebP, 3 Mo max)</label>
    <?php if ($url = avent_url_image($jour['illustration'])) : ?>
        <img class="vignette" src="<?= h($url) ?>" alt="">
        <p class="case"><input type="checkbox" id="supprimer_illustration" name="supprimer_illustration" value="1">
            <label for="supprimer_illustration">Supprimer l'illustration actuelle</label></p>
    <?php endif; ?>
    <input type="file" id="illustration" name="illustration" accept="image/gif,image/jpeg,image/png,image/webp">

    <h2>Jeu</h2>
    <div class="ligne">
        <div>
            <label for="jeu">Jeu du jour</label>
            <select id="jeu" name="jeu">
                <option value="">— aucun —</option>
                <?php foreach ($jeux as $slug => $manifeste) : ?>
                    <option value="<?= h($slug) ?>" <?= $valeurs['jeu'] === $slug ? 'selected' : '' ?>><?= h($manifeste['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="duree_max">Durée maximale (secondes)</label>
            <input type="number" id="duree_max" name="duree_max" min="10" max="<?= AVENT_DUREE_MAX ?>" value="<?= (int) $valeurs['duree_max'] ?>">
        </div>
    </div>
    <p class="case"><input type="checkbox" id="est_boss" name="est_boss" value="1" <?= $valeurs['est_boss'] ? 'checked' : '' ?>>
        <label for="est_boss">Jour boss (points multipliés par le coefficient des réglages)</label></p>

    <label for="config">Contenu du jeu (JSON)</label>
    <textarea id="config" name="config" class="code" spellcheck="false"><?= h($valeurs['config']) ?></textarea>
    <p class="aide">Le format dépend du jeu choisi. <button type="button" id="inserer-exemple" class="petit-formulaire">Insérer l'exemple du jeu</button></p>

    <h2>Révélation (après la partie)</h2>
    <label for="revelation_texte">Message de l'équipe</label>
    <textarea id="revelation_texte" name="revelation_texte"><?= h($valeurs['revelation_texte']) ?></textarea>

    <label for="revelation_image">GIF ou image</label>
    <?php if ($url = avent_url_image($jour['revelation_image'])) : ?>
        <img class="vignette" src="<?= h($url) ?>" alt="">
        <p class="case"><input type="checkbox" id="supprimer_revelation_image" name="supprimer_revelation_image" value="1">
            <label for="supprimer_revelation_image">Supprimer l'image actuelle</label></p>
    <?php endif; ?>
    <input type="file" id="revelation_image" name="revelation_image" accept="image/gif,image/jpeg,image/png,image/webp">
    <p class="aide">Les fichiers sont hébergés sur le site (aucun lien externe, pour éviter les blocages du réseau de l'entreprise).</p>

    <?php if ($avertissement) : ?>
        <p class="case"><input type="checkbox" id="confirmer" name="confirmer" value="1">
            <label for="confirmer"><strong>Je confirme la modification malgré les parties déjà jouées.</strong></label></p>
    <?php endif; ?>

    <div class="actions">
        <button type="submit" class="bouton">Enregistrer</button>
        <a class="bouton bouton-secondaire" href="index.php">Retour au planning</a>
    </div>
</form>
<?php avent_pied(); ?>
