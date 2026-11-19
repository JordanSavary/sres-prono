# Les Lutins du Chalet

Puzzle type Queens : grille N×N en N zones de couleur, un lutin par ligne, colonne et zone, jamais deux lutins voisins (diagonales comprises). Une seule solution par grille.

**Score** : 100 points jusqu'à `temps_parfait` s, puis décroissance linéaire jusqu'à `score_min` points à `temps_limite` s ; 0 au-delà, ou si les vies tombent à 0. Le temps est mesuré par le serveur entre l'action `demarrer` (appui sur « Commencer » dans le jeu) et la dernière bonne pose.

À régler pour ce jour : **durée de la partie = 120 s** (le chrono de la plateforme court depuis le clic sur « Commencer » de la page ; le jeu ne peut donc pas dépasser cette durée).

## Paramètres réglables (JSON du jour, tous facultatifs, entiers)

| Paramètre | Défaut | Plage | Effet |
|---|---|---|---|
| `taille` | 8 | 6–8 | Côté de la grille = nombre de zones = nombre de lutins. |
| `vies` | 3 | 1–5 | Bonnets de Noël : une mauvaise pose en coûte un. |
| `temps_parfait` | 45 | 10–300 | Secondes jusqu'auxquelles on a 100 points. |
| `temps_limite` | 120 | 30–600 | Secondes au-delà desquelles on a 0 point (> `temps_parfait`). |
| `score_min` | 10 | 0–99 | Points obtenus pile à `temps_limite`. |
| `variante` | 0 | 0–999 | Change la grille du jour sans toucher au reste. |

Facile : `{"parametres":{"taille":6,"vies":5}}` · Difficile : `{"parametres":{"vies":2,"temps_parfait":30}}`.

## Fonctionnement
- Grille unique par jour : générée à partir du numéro de case et du secret serveur (`avent_secret()`), régénérée jusqu'à ce que le solveur à retour sur trace en trouve une seule. Les zones doivent avoir de 3 à 16 cases.
- `config_publique` ne renvoie que les réglages ; la grille de zones part avec l'action `demarrer` (sinon on pourrait la résoudre avant d'appuyer sur « Commencer »). La solution ne quitte jamais le serveur.
- Chaque pose passe par l'action `poser` (`api/action.php`) : le serveur répond bon/mauvais, compte les vies et refuse toute pose après la dernière. L'état de la partie est dans la table `avent_etats` (créée automatiquement). Après un rechargement de page, vies, poses et chrono sont restitués.
- `verifier` ne lit que cet état : le navigateur ne peut rien annoncer (ni score, ni temps).
- Une seule partie par joueur et par case (`"essais_max": 1` dans le manifest).
