<?php
/**
 * Tests en ligne de commande : php tests/test.php
 * (pdo_sqlite requis ; sans php.ini :
 *  php -d extension_dir=<php>/ext -d extension=pdo_sqlite tests/test.php)
 * Termine avec le code 1 si une attente n'est pas respectée.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../includes/points.php';
require __DIR__ . '/../includes/validation.php';

$echecs = 0;
$verifie = static function (string $nom, bool $ok) use (&$echecs): void {
    echo ($ok ? '  ok    ' : '  ÉCHEC ') . $nom . "\n";
    if (!$ok)
        $echecs++;
};

/** Copie de l'ancien calcul (admin_actions.php) : référence de non-régression. */
function ancien_points($prono_a, $prono_b, $match)
{
    $score_a = $match['score_a'];
    $score_b = $match['score_b'];
    $points = 0;
    if ($prono_a == $score_a && $prono_b == $score_b) {
        $points = 5;
    } elseif (
        ($prono_a - $prono_b) == ($score_a - $score_b) &&
        (
            ($prono_a > $prono_b && $score_a > $score_b) ||
            ($prono_a < $prono_b && $score_a < $score_b) ||
            ($prono_a == $prono_b && $score_a == $score_b)
        )
    ) {
        $points = 3;
    } elseif (
        ($prono_a > $prono_b && $score_a > $score_b) ||
        ($prono_a < $prono_b && $score_a < $score_b) ||
        ($prono_a == $prono_b && $score_a == $score_b)
    ) {
        $points = 2;
    }
    if ($points > 0 && ($match['cote_a'] || $match['cote_nul'] || $match['cote_b'])) {
        if ($prono_a == $prono_b)
            $cote = $match['cote_nul'];
        elseif ($prono_a > $prono_b)
            $cote = $match['cote_a'];
        else
            $cote = $match['cote_b'];
        if ($cote) {
            if ($prono_a == $prono_b)
                $mul = 1.5;
            elseif ($cote < 1.75)
                $mul = 1.0;
            elseif ($cote <= 2.50)
                $mul = 1.5;
            else
                $mul = 2.0;
            $points = round($points * $mul);
        }
    }
    return $points;
}

/** Match joué avec cotes optionnelles. */
function match_joue($a, $b, $ca = null, $cn = null, $cb = null): array
{
    return ['score_a' => $a, 'score_b' => $b,
            'cote_a' => $ca, 'cote_nul' => $cn, 'cote_b' => $cb];
}

echo "points_pronostic\n";
$verifie('score exact', points_pronostic(2, 1, match_joue(2, 1)) === 5);
$verifie('bon écart', points_pronostic(3, 2, match_joue(2, 1)) === 3);
$verifie('nul non exact', points_pronostic(0, 0, match_joue(1, 1)) === 3);
$verifie('bon vainqueur', points_pronostic(3, 0, match_joue(2, 1)) === 2);
$verifie('mauvais', points_pronostic(0, 1, match_joue(2, 1)) === 0);
$verifie('nul ×1.5',
         points_pronostic(1, 1, match_joue(1, 1, 2, 3, 4)) === 8);
$verifie('favori ×1',
         points_pronostic(2, 1, match_joue(2, 1, 1.5, 3, 4)) === 5);
$verifie('équilibré ×1.5',
         points_pronostic(3, 0, match_joue(2, 1, 2.5, 3, 4)) === 3);
$verifie('outsider ×2',
         points_pronostic(0, 1, match_joue(0, 2, 1.2, 3, 4)) === 4);
$verifie('cote du côté pronostiqué absente',
         points_pronostic(0, 1, match_joue(0, 1, 1.2, 3, null)) === 5);
$verifie('0 point non multiplié',
         points_pronostic(0, 1, match_joue(2, 1, 1.2, 3, 4)) === 0);

$cotes = [[null, null, null], [1.5, 3.2, 5.0], [2.1, 3.0, 2.4],
          [3.0, '3.10', '1.60'], [0, 0, 2.0], ['2.50', null, '1.75']];
$ecarts = [];
foreach ($cotes as [$ca, $cn, $cb])
    for ($pa = 0; $pa <= 4; $pa++)
    for ($pb = 0; $pb <= 4; $pb++)
    for ($sa = 0; $sa <= 4; $sa++)
    for ($sb = 0; $sb <= 4; $sb++) {
        $m = match_joue($sa, $sb, $ca, $cn, $cb);
        if (points_pronostic($pa, $pb, $m) != ancien_points($pa, $pb, $m))
            $ecarts[] = "$pa-$pb / $sa-$sb";
    }
$verifie('identique à l\'ancien calcul (grille complète)', !$ecarts);

echo "totaux en base\n";
$pdo = new PDO('sqlite::memory:', null, null,
               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec("
    CREATE TABLE utilisateurs (pseudo TEXT PRIMARY KEY,
                               points INTEGER DEFAULT 0);
    CREATE TABLE matchs (id INTEGER PRIMARY KEY, score_a INTEGER,
                         score_b INTEGER, cote_a REAL, cote_nul REAL,
                         cote_b REAL);
    CREATE TABLE pronostics (id_match INTEGER, id_utilisateur TEXT,
                             prono_a INTEGER, prono_b INTEGER,
                             points INTEGER DEFAULT 0);
    CREATE TABLE pronostics_tournoi (id_utilisateur TEXT,
                                     pays_gagnant TEXT,
                                     meilleur_buteur INTEGER,
                                     points_vainqueur INTEGER,
                                     points_buteur INTEGER);
    INSERT INTO utilisateurs (pseudo, points)
        VALUES ('alice', 0), ('bob', 0), ('carole', 999);
    INSERT INTO matchs VALUES (1, 2, 1, NULL, NULL, NULL),
                              (2, NULL, NULL, NULL, NULL, NULL),
                              (3, 0, 0, NULL, NULL, NULL);
    INSERT INTO pronostics (id_match, id_utilisateur, prono_a, prono_b)
        VALUES (1, 'alice', 2, 1), (3, 'alice', 1, 1),
               (1, 'bob', 0, 1), (2, 'bob', 1, 0);
    INSERT INTO pronostics_tournoi
            (id_utilisateur, pays_gagnant, meilleur_buteur)
        VALUES ('alice', 'FRA', 7), ('bob', 'FRA', 9),
               ('carole', 'ESP', 7);
");

/** Points totaux par pseudo. */
$totaux = static fn(): array => $pdo
    ->query("SELECT pseudo, points FROM utilisateurs ORDER BY pseudo")
    ->fetchAll(PDO::FETCH_KEY_PAIR);

recalculer_points_pronostics($pdo);
recalculer_totaux($pdo);
$verifie('points de match seuls',
         $totaux() == ['alice' => 8, 'bob' => 0, 'carole' => 0]);

attribuer_points_tournoi($pdo, 'FRA', '7');
recalculer_totaux($pdo);
$attendu = ['alice' => 108, 'bob' => 50, 'carole' => 50];
$verifie('bonus tournoi ajouté', $totaux() == $attendu);

attribuer_points_tournoi($pdo, 'FRA', '7');
recalculer_totaux($pdo);
$verifie('bonus tournoi non doublé si relancé', $totaux() == $attendu);

recalculer_points_pronostics($pdo);
recalculer_totaux($pdo);
$verifie('bonus conservé après actualisation des matchs',
         $totaux() == $attendu);

$pdo->exec("UPDATE matchs SET score_a = 1, score_b = 0 WHERE id = 2");
recalculer_points_pronostics($pdo);
recalculer_totaux($pdo);
$verifie('nouveau score pris en compte avec le bonus',
         $totaux() == ['alice' => 108, 'bob' => 55, 'carole' => 50]);

attribuer_points_tournoi($pdo, 'ESP', '');
recalculer_totaux($pdo);
$verifie('correction du vainqueur, buteur vide',
         $totaux() == ['alice' => 8, 'bob' => 5, 'carole' => 50]);

echo "validation\n";
$verifie('extension jpeg', extension_avatar('image/jpeg') === 'jpg');
$verifie('extension png', extension_avatar('image/png') === 'png');
$verifie('type refusé', extension_avatar('text/x-php') === null);
$verifie('gif refusé', extension_avatar('image/gif') === null);

foreach (['avatars/default/user1.png', 'avatars/default/user12.png']
         as $c)
    $verifie("avatar par défaut accepté : $c", avatar_defaut_valide($c));
foreach (['avatars/68384d2b88db7.png', 'avatars/default.png',
          'avatars/default/user1.php', 'avatars/default/userX.png',
          '../includes/db.php', 'avatars/default/../../compte.php',
          'avatars/default/user1.png/../../x.png',
          '/avatars/default/user1.png', "avatars/default/user1.png\n",
          'https://exemple.com/avatars/default/user1.png', '', null,
          ['avatars/default/user1.png']] as $c)
    $verifie('avatar par défaut refusé : ' . json_encode($c),
             !avatar_defaut_valide($c));

foreach (['0' => 0, '20' => 20, '7' => 7, 3 => 3] as $v => $n)
    $verifie("score accepté : $v", score_prono((string) $v) === $n);
foreach (['-1', '21', '1.5', 'abc', '', '1e1', null, ['1']] as $v)
    $verifie('score refusé : ' . json_encode($v),
             score_prono($v) === null);

echo $echecs ? "\n$echecs échec(s)\n" : "\nTous les tests passent.\n";
exit($echecs ? 1 : 0);
