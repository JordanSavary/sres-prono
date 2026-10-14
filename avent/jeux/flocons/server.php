<?php
/**
 * Jeu « Chasse aux flocons » (réflexe) — module serveur.
 *
 * Contenu attendu : {"cible":20,"rythme_ms":650}
 *   cible      : nombre de flocons à attraper pour atteindre 100 points
 *   rythme_ms  : délai entre l'apparition de deux flocons (300 à 2000)
 *
 * Ce jeu se joue entièrement dans le navigateur : le serveur ne peut pas rejouer la partie.
 * Il se contente de limiter le score à ce qui est plausible pour la durée écoulée.
 */

function avent_jeu_flocons_valider(array $config): ?string
{
    $cible  = $config['cible'] ?? null;
    $rythme = $config['rythme_ms'] ?? 650;
    if (!is_int($cible) || $cible < 3 || $cible > 200) {
        return '« cible » doit être un entier entre 3 et 200.';
    }
    if (!is_int($rythme) || $rythme < 300 || $rythme > 2000) {
        return '« rythme_ms » doit être un entier entre 300 et 2000.';
    }
    return null;
}

function avent_jeu_flocons_config_publique(array $config): array
{
    return [
        'cible'     => max(3, min(200, (int) ($config['cible'] ?? 20))),
        'rythme_ms' => max(300, min(2000, (int) ($config['rythme_ms'] ?? 650))),
    ];
}

function avent_jeu_flocons_verifier(array $config, array $payload, int $dureeMs): array
{
    $cible    = max(3, (int) ($config['cible'] ?? 20));
    $rythme   = max(300, (int) ($config['rythme_ms'] ?? 650));
    $attrapes = max(0, (int) ($payload['attrapes'] ?? 0));

    // Plausibilité : jamais plus de flocons que le jeu n'en fait apparaître (+ 1 d'avance),
    // et pas plus d'une capture toutes les 120 ms.
    $apparus  = intdiv($dureeMs, $rythme) + 1;
    $attrapes = min($attrapes, $apparus, intdiv($dureeMs, 120));

    $score = (int) min(100, round(100 * $attrapes / $cible));
    return ['score' => $score, 'reussite' => $attrapes >= $cible];
}
