<?php
/**
 * Réserve d'énigmes par défaut (utilisée quand le contenu de la case n'en fournit pas).
 * Fichier lu uniquement par server.php : les réponses ne quittent jamais le serveur.
 * Une énigme : illustration (emojis), question, 3 réponses, « bonne » = index (0 à 2) de la bonne réponse.
 * Les 4 premières sont celles du jeu d'origine.
 */
return [
    ['illustration' => '⭐ ⭐ ⭐ = 9', 'question' => 'Trois étoiles valent 9. Combien vaut une étoile ?', 'reponses' => ['2', '3', '4'], 'bonne' => 1],
    ['illustration' => '🦌🦌🦌🦌🦌🦌🦌🦌 − 🎁🎁🎁🎁🎁🎁', 'question' => '8 rennes traditionnels moins 6 cadeaux : combien reste-t-il ?', 'reponses' => ['1', '2', '3'], 'bonne' => 1],
    ['illustration' => '🔴 🟢 🔴 🟢 🔴 ❓', 'question' => 'Quelle couleur vient ensuite ?', 'reponses' => ['Rouge', 'Vert', 'Or'], 'bonne' => 1],
    ['illustration' => '4 < ❓ < 6', 'question' => 'Je suis plus grand que 4 et plus petit que 6. Qui suis-je ?', 'reponses' => ['4', '5', '6'], 'bonne' => 1],
    ['illustration' => '⭐ 🎄 ⭐ ⭐ 🎁 ⭐', 'question' => 'Combien d\'étoiles vois-tu ?', 'reponses' => ['3', '4', '5'], 'bonne' => 1],
    ['illustration' => '🎄 ⛄ 🏖️ 🎁', 'question' => 'Quel est l\'intrus (rien à voir avec l\'hiver) ?', 'reponses' => ['🎄', '🏖️', '⛄'], 'bonne' => 1],
    ['illustration' => '2 · 4 · 6 · 8 · ❓', 'question' => 'Quel nombre vient ensuite ?', 'reponses' => ['9', '10', '12'], 'bonne' => 1],
    ['illustration' => '🍪🍪🍪🍪🍪🍪🍪🍪🍪🍪🍪🍪', 'question' => '12 biscuits sur l\'assiette, le Père Noël en mange 5. Combien en reste-t-il ?', 'reponses' => ['6', '7', '8'], 'bonne' => 1],
    ['illustration' => '🦌 > 🐈 > 🐢', 'question' => 'Le renne court plus vite que le chat, qui court plus vite que la tortue. Qui est le plus lent ?', 'reponses' => ['Le renne', 'Le chat', 'La tortue'], 'bonne' => 2],
    ['illustration' => '❄️❄️❄️❄️❄️❄️❄️', 'question' => 'Combien de flocons ?', 'reponses' => ['6', '7', '8'], 'bonne' => 1],
    ['illustration' => '⭐ · ⭐⭐ · ⭐⭐⭐ · ❓', 'question' => 'Combien d\'étoiles dans la case suivante ?', 'reponses' => ['3', '4', '5'], 'bonne' => 1],
    ['illustration' => '🎅 × 4 sacs de 🎁🎁🎁🎁🎁', 'question' => 'Le Père Noël porte 4 sacs de 5 cadeaux. Combien de cadeaux en tout ?', 'reponses' => ['9', '20', '25'], 'bonne' => 1],
    ['illustration' => '📅 25 décembre : Noël', 'question' => 'Noël est le 25 décembre. Quel jour est la veille ?', 'reponses' => ['23', '24', '26'], 'bonne' => 1],
    ['illustration' => '🦌 🦌', 'question' => 'Combien de pattes ont 2 rennes en tout ?', 'reponses' => ['4', '8', '6'], 'bonne' => 1],
    ['illustration' => '2 · 4 · 7 · 8', 'question' => 'Quel nombre est l\'intrus ?', 'reponses' => ['2', '7', '8'], 'bonne' => 1],
    ['illustration' => '🥛 🍪 🍫 🔧', 'question' => 'Lequel n\'est pas un goûter pour le Père Noël ?', 'reponses' => ['🥛', '🔧', '🍫'], 'bonne' => 1],
];
