<?php
$API_KEY  = 'a4acbb03986b4c70bd3afec8883c2c15';
$BASE_URL = 'https://api.football-data.org/v4';

$url = "{$BASE_URL}/competitions/WC/matches?season=2026";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Token: {$API_KEY}"]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

$equipes = [];
foreach ($data['matches'] as $m) {
    $home = $m['homeTeam']['name'];
    $away = $m['awayTeam']['name'];
    if ($home) $equipes[$home] = true;
    if ($away) $equipes[$away] = true;
}

ksort($equipes);
foreach (array_keys($equipes) as $nom) {
    echo $nom . "\n";
}