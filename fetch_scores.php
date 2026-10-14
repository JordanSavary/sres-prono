<?php
include 'includes/db.php';
date_default_timezone_set('Europe/Paris');

$API_KEY  = 'a4acbb03986b4c70bd3afec8883c2c15';
$BASE_URL = 'https://api.football-data.org/v4';

$url = "{$BASE_URL}/competitions/WC/matches?season=2026&status=FINISHED";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Auth-Token: {$API_KEY}"
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!isset($data['matches'])) {
    die("Erreur API : " . json_encode($data));
}

$mapping = [
    'Algeria'               => 'DZA',
    'Argentina'             => 'ARG',
    'Australia'             => 'AUS',
    'Austria'               => 'AUT',
    'Belgium'               => 'BEL',
    'Bosnia-Herzegovina'    => 'BIH',
    'Brazil'                => 'BRA',
    'Canada'                => 'CAN',
    'Cape Verde Islands'    => 'CPV',
    'Colombia'              => 'COL',
    'DR Congo'              => 'COD',
    'Congo DR'              => 'COD',
    'Croatia'               => 'HRV',
    'Curaçao'               => 'CUW',
    'Czechia'               => 'CZE',
    'Ecuador'               => 'ECU',
    'Egypt'                 => 'EGY',
    'England'               => 'ENG',
    'France'                => 'FRA',
    'Germany'               => 'DEU',
    'Ghana'                 => 'GHA',
    'Haiti'                 => 'HTI',
    'Iran'                  => 'IRN',
    'Iraq'                  => 'IRQ',
    'Ivory Coast'           => 'CIV',
    'Japan'                 => 'JPN',
    'Jordan'                => 'JOR',
    'Mexico'                => 'MEX',
    'Morocco'               => 'MAR',
    'Netherlands'           => 'NLD',
    'New Zealand'           => 'NZL',
    'Norway'                => 'NOR',
    'Panama'                => 'PAN',
    'Paraguay'              => 'PRY',
    'Portugal'              => 'PRT',
    'Qatar'                 => 'QAT',
    'Saudi Arabia'          => 'SAU',
    'Scotland'              => 'SCO',
    'Senegal'               => 'SEN',
    'South Africa'          => 'ZAF',
    'South Korea'           => 'KOR',
    'Spain'                 => 'ESP',
    'Sweden'                => 'SWE',
    'Switzerland'           => 'CHE',
    'Tunisia'               => 'TUN',
    'Turkey'                => 'TUR',
    'United States'         => 'USA',
    'Uruguay'               => 'URY',
    'Uzbekistan'            => 'UZB',
];

// Une seule requête qui cherche sur les deux dates possibles
$stmt_find = $pdo->prepare("
    SELECT id FROM matchs 
    WHERE DATE(date_match) IN (?, ?) 
    AND equipe_a = ? AND equipe_b = ?
    AND (score_a IS NULL OR score_b IS NULL)
");

$stmt_update = $pdo->prepare("
    UPDATE matchs SET score_a = ?, score_b = ? WHERE id = ?
");

$maj = 0;
$ignores = 0;
$non_trouves = [];

foreach ($data['matches'] as $m) {
    $score_home = $m['score']['fullTime']['home'];
    $score_away = $m['score']['fullTime']['away'];

    if ($score_home === null || $score_away === null) {
        $ignores++;
        continue;
    }

    $home = $m['homeTeam']['name'];
    $away = $m['awayTeam']['name'];

    $date_utc   = (new DateTime($m['utcDate']))->format('Y-m-d');
    $date_paris = (new DateTime($m['utcDate'], new DateTimeZone('UTC')))
                    ->setTimezone(new DateTimeZone('Europe/Paris'))
                    ->format('Y-m-d');

    $code_home = $mapping[$home] ?? null;
    $code_away = $mapping[$away] ?? null;

    if (!$code_home || !$code_away) {
        $non_trouves[] = "$home vs $away";
        continue;
    }

    // Cherche sur date UTC et date Paris en une seule requête
    $stmt_find->execute([$date_utc, $date_paris, $code_home, $code_away]);
    $match = $stmt_find->fetch();

    if ($match) {
        $stmt_update->execute([$score_home, $score_away, $match['id']]);
        $maj++;
    }
}

echo "✅ {$maj} score(s) mis à jour.\n";
if ($ignores > 0) {
    echo "⏭️ {$ignores} match(s) ignorés (score non disponible malgré statut FINISHED).\n";
}
if (!empty($non_trouves)) {
    echo "⚠️ Équipes non mappées :\n";
    foreach (array_unique($non_trouves) as $m) echo "  - {$m}\n";
}

header('Location: admin.php?success=scores_updated');
exit;