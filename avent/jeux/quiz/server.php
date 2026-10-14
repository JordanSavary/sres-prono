<?php
/**
 * Jeu « Quiz » — module serveur.
 *
 * Contenu attendu (JSON saisi dans l'administration) :
 *   {"questions":[{"question":"…","options":["a","b","c","d"],"reponse":2}, …]}
 * `reponse` est le numéro (à partir de 1) de la bonne option.
 *
 * Les bonnes réponses ne quittent jamais le serveur : config_publique() les retire.
 */

/** Vérifie la configuration saisie par l'administrateur. Renvoie null si elle est valide. */
function avent_jeu_quiz_valider(array $config): ?string
{
    $questions = $config['questions'] ?? null;
    if (!is_array($questions) || count($questions) < 1 || count($questions) > 30) {
        return 'Le quiz doit contenir entre 1 et 30 questions.';
    }
    foreach (array_values($questions) as $i => $question) {
        $numero = $i + 1;
        if (!is_array($question) || trim((string) ($question['question'] ?? '')) === '') {
            return "Question $numero : l'énoncé est vide.";
        }
        $options = $question['options'] ?? null;
        if (!is_array($options) || count($options) < 2 || count($options) > 6) {
            return "Question $numero : il faut entre 2 et 6 options.";
        }
        foreach ($options as $option) {
            if (!is_string($option) || trim($option) === '') {
                return "Question $numero : une option est vide.";
            }
        }
        $reponse = $question['reponse'] ?? null;
        if (!is_int($reponse) || $reponse < 1 || $reponse > count($options)) {
            return "Question $numero : « reponse » doit être le numéro de la bonne option (à partir de 1).";
        }
    }
    return null;
}

/** Configuration envoyée au navigateur : sans les bonnes réponses. */
function avent_jeu_quiz_config_publique(array $config): array
{
    $publiques = [];
    foreach ($config['questions'] ?? [] as $question) {
        $publiques[] = [
            'question' => (string) ($question['question'] ?? ''),
            'options'  => array_values(array_map('strval', $question['options'] ?? [])),
        ];
    }
    return ['questions' => $publiques];
}

/**
 * Corrige les réponses envoyées : payload = {"reponses":[2, 1, null, …]}.
 * Score = pourcentage de bonnes réponses sur l'ensemble des questions ; réussite à partir de 50 %.
 */
function avent_jeu_quiz_verifier(array $config, array $payload, int $dureeMs): array
{
    $questions = array_values($config['questions'] ?? []);
    $total     = count($questions);
    if ($total === 0) {
        return ['score' => 0, 'reussite' => false];
    }
    $reponses = is_array($payload['reponses'] ?? null) ? array_values($payload['reponses']) : [];
    $bonnes   = 0;
    foreach ($questions as $i => $question) {
        if (isset($reponses[$i]) && (int) $reponses[$i] === (int) $question['reponse']) {
            $bonnes++;
        }
    }
    $score = (int) round(100 * $bonnes / $total);
    return ['score' => $score, 'reussite' => $score >= 50];
}
