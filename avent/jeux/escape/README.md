# Escape Christmas

4 énigmes à 3 réponses (10 s chacune), un chiffre gagné par bonne réponse (affiché 2 s, puis masqué), puis un code à 4 chiffres sur un cadenas (20 s, 3 essais). Score 0–100.

**Durée à régler pour la case : 130 à 150 s** (le jeu dure 110 s au maximum, mais le chrono de la plateforme démarre avant le bouton « Démarrer la mission »).

## Vérification
* `demarrer` : le serveur tire 4 énigmes dans la réserve (graine secrète par joueur et par jour), mélange les réponses, attribue un chiffre 0–9 à chacune. Tout reste dans l'état serveur (`avent_etats`) ; `config_publique` ne contient que les paramètres.
* `enigme` : sert l'énigme en cours (la suivante seulement après résolution de la précédente) et lance son délai de 10 s côté serveur. Redemander ne remet pas le délai à zéro.
* `reponse {i, seq}` : `bon` (+ chiffre, gain) ou `mauvais` ; jamais la bonne réponse. Réponse après 10 s + 1 s de tolérance : temps écoulé, énigme rejouée avec nouveau délai et pénalité. `temps` : le navigateur signale la fin du délai, le serveur le confirme.
* `code {code, seq}` / `temps_code` : 3 essais, 20 s (après les 2 s d'affichage du dernier chiffre).
* Au-delà de la durée globale (110 s) toute action est refusée ; le score est celui des points acquis. `verifier` ne lit que l'état serveur (le payload du navigateur est ignoré).
* Rechargement : la partie reprend à l'étape en cours, délais serveur déjà écoulés (un délai dépassé pendant la coupure est pénalisé). Les chiffres déjà gagnés ne sont pas ré-affichés.
* `.htaccess` interdit l'accès web à `manifest.json`, `enigmes.php`, `server.php`, `test.php` (la réserve contient les bonnes réponses) — non testé sous Apache.

Tests : `php avent/jeux/escape/test.php`.

## Paramètres réglables (contenu de la case, JSON, tous facultatifs)
| Paramètre | Défaut | Bornes |
| --- | --- | --- |
| `nb_enigmes` | 4 | 3–6 |
| `duree_enigme` (s) | 10 | 5–30 |
| `temps_plein` (s, 20 pts jusqu'à…) | 4 | 1 – duree_enigme−1 |
| `points_enigme` | 20 | 1–50 |
| `points_min_enigme` (à `duree_enigme`) | 8 | 0 – points_enigme |
| `penalite` (par erreur ou temps écoulé) | 5 | 0–20 |
| `points_code` (un par tentative ; sa longueur = nombre d'essais) | [20, 13, 6] | 1–5 valeurs, 0–100 |
| `duree_code` (s) | 20 | 5–60 |
| `duree_globale` (s) | 110 | 30–150 |

`enigmes` : liste facultative (au moins `nb_enigmes`) d'objets `{illustration, question, reponses[3 différentes], bonne (0–2)}` ; sans elle, la réserve de 16 énigmes (réponses toujours des chiffres 0–9) (`enigmes.php`) est utilisée.
