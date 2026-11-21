<?php
// Calcul des points : pronostics de match, bonus tournoi, total par utilisateur.

/** Signe du résultat : 1 victoire A, -1 victoire B, 0 nul. */
function signe_score($a, $b): int
{
    return $a <=> $b;
}

/** Points d'un pronostic pour un match joué (score exact 5, écart 3, vainqueur 2), avec multiplicateur de cote. */
function points_pronostic($prono_a, $prono_b, array $match): int
{
    $score_a = $match['score_a'];
    $score_b = $match['score_b'];
    $bon_signe = signe_score($prono_a, $prono_b)
        === signe_score($score_a, $score_b);

    $points = 0;
    if ($prono_a == $score_a && $prono_b == $score_b) {
        $points = 5;
    } elseif ($bon_signe
              && ($prono_a - $prono_b) == ($score_a - $score_b)) {
        $points = 3;
    } elseif ($bon_signe) {
        $points = 2;
    }

    // Multiplicateur selon la cote — uniquement si points > 0 et cotes renseignées
    if ($points === 0
        || !($match['cote_a'] || $match['cote_nul'] || $match['cote_b']))
        return $points;

    if ($prono_a == $prono_b)
        $cote = $match['cote_nul'];
    elseif ($prono_a > $prono_b)
        $cote = $match['cote_a'];
    else
        $cote = $match['cote_b'];
    if (!$cote)
        return $points;

    if ($prono_a == $prono_b)
        $mul = 1.5; // nul prédit
    elseif ($cote < 1.75)
        $mul = 1.0; // grand favori
    elseif ($cote <= 2.50)
        $mul = 1.5; // équilibré
    else
        $mul = 2.0; // outsider

    return (int) round($points * $mul);
}

/** Recalcule pronostics.points pour tous les matchs dont le score est connu. */
function recalculer_points_pronostics(PDO $pdo): void
{
    $sql = "SELECT id, score_a, score_b, cote_a, cote_nul, cote_b
            FROM matchs";
    $matchs = [];
    foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $m)
        $matchs[$m['id']] = $m;

    $pronos = $pdo->query("SELECT * FROM pronostics")
                  ->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare("UPDATE pronostics SET points = ?
                          WHERE id_utilisateur = ? AND id_match = ?");

    foreach ($pronos as $p) {
        $m = $matchs[$p['id_match']] ?? null;
        if (!$m || $m['score_a'] === null || $m['score_b'] === null)
            continue;

        $pts = points_pronostic($p['prono_a'], $p['prono_b'], $m);
        $upd->execute([$pts, $p['id_utilisateur'], $p['id_match']]);
    }
}

/** Fixe les bonus tournoi (50 pts vainqueur, 50 pts meilleur buteur) de chaque pronostic tournoi. */
function attribuer_points_tournoi(PDO $pdo,
                                  $pays_vainqueur,
                                  $id_buteur): void
{
    $sql = "UPDATE pronostics_tournoi SET
              points_vainqueur =
                CASE WHEN pays_gagnant = ? THEN 50 ELSE 0 END,
              points_buteur =
                CASE WHEN meilleur_buteur = ? THEN 50 ELSE 0 END";
    $pdo->prepare($sql)->execute([$pays_vainqueur, $id_buteur]);
}

/**
 * Seul endroit où utilisateurs.points est calculé :
 * somme des points de match + bonus tournoi. Idempotent.
 */
function recalculer_totaux(PDO $pdo): void
{
    $pdo->exec("
        UPDATE utilisateurs SET points =
          COALESCE((SELECT SUM(p.points) FROM pronostics p
                    WHERE p.id_utilisateur = utilisateurs.pseudo), 0)
          + COALESCE((SELECT COALESCE(t.points_vainqueur, 0)
                             + COALESCE(t.points_buteur, 0)
                      FROM pronostics_tournoi t
                      WHERE t.id_utilisateur = utilisateurs.pseudo), 0)
    ");
}
