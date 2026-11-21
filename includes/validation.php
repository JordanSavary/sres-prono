<?php
// Validation des saisies utilisateur (avatars, pronostics).

/** Extension à utiliser pour un avatar téléversé, d'après son type réel ; null si refusé. */
function extension_avatar($mime): ?string
{
    return ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
}

/** Vrai si le chemin désigne un avatar par défaut (avatars/default/userN.png). */
function avatar_defaut_valide($chemin): bool
{
    return is_string($chemin)
        && preg_match('#^avatars/default/user[0-9]+\.png$#D', $chemin) === 1;
}

/** Score pronostiqué en entier 0–20, ou null si invalide. */
function score_prono($valeur): ?int
{
    $opt = ['options' => ['min_range' => 0, 'max_range' => 20]];
    $v = filter_var($valeur, FILTER_VALIDATE_INT, $opt);
    return $v === false ? null : $v;
}
