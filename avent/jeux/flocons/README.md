# Chasse aux flocons

Des flocons ❄ tombent : il faut en toucher `cible`. Des flocons piégés 🔥 (flamme + anneau orange en pointillés) tombent aussi : en toucher un fait perdre une vie (3 au départ), à la dernière vie la partie s'arrête. Un flocon manqué (normal ou piégé) n'a aucun effet. Score inchangé : 100 × flocons attrapés / cible (plafonné à 100), sans malus pour les vies perdues.

## Vérification
* `config_publique` envoie une **graine** propre au joueur et au jour (dérivée du secret du serveur). Le calendrier de tous les flocons (apparition, durée de chute, position, piégé ou non) est calculé à partir d'elle par un générateur en entiers (xorshift32), **identique en PHP (`server.php`) et en JavaScript (`game.js`, bloc « GÉNÉRATEUR »)**. Si vous changez une règle de génération, changez-la des deux côtés et incrémentez `AVENT_FLOCONS_VERSION`.
* Le navigateur journalise ses touchers `[numéro du flocon, temps en ms]` et les envoie à la fin. `verifier` rejoue la partie : un toucher compte si le flocon existe et tombe encore (250 ms de tolérance), jamais deux fois le même, jamais deux touchers à moins de 100 ms ; un piège coûte une vie ; plus aucun toucher n'est pris en compte à la dernière vie ou une fois la cible atteinte. Score, vies et temps annoncés par le navigateur sont ignorés ; un journal mal formé, non chronologique ou plus long que la partie réelle donne 0.
* Anti-rechargement et une partie par jour : `essais_max: 1` et un appel `depart` au lancement (comme `jeux/barque/`). Page rechargée après le départ : le jeu affiche « Les flocons sont tombés sans toi » et la partie se termine avec 0.
* Limite connue : comme tout jeu de réflexe joué dans le navigateur, un script qui calcule le calendrier (public, dérivé de la graine) peut viser parfaitement. Le serveur garantit la cohérence (fenêtres, débit, vies), pas que ce soit un humain.

## Règles d'équilibrage et d'espacement (générateur)
* Aucun piège avant `delai_sans_piege_ms` ; ensuite la proportion de pièges monte linéairement de `piege_debut` à `piege_fin` (‰) sur `duree_ref_ms`.
* Un piège est à au moins `distance_min` (‰ de la largeur **et** de la hauteur du terrain, soit ~90 px à 360 px) de tout autre flocon visible en même temps, à chaque instant de la chute ; s'il n'y a pas de place, il devient un flocon normal. Un flocon normal n'est jamais placé à moins de `distance_min` d'un piège ; s'il n'y a vraiment aucune place, il n'est pas lancé (moins de 1 % des flocons). Les pièges font la même taille que les flocons (52 px).
* La proportion réellement obtenue est un peu en dessous de la proportion visée (pièges convertis faute de place) : ~16 % sur le début, ~29 % sur la fin avec les valeurs par défaut.

## Paramètres réglables (contenu de la case, JSON ; seule `cible` est obligatoire)
| Paramètre | Défaut | Bornes | Effet |
| --- | --- | --- | --- |
| `cible` | 20 | 3–200 | Flocons à attraper pour 100 points. |
| `rythme_ms` | 650 | 300–2000 | Délai entre deux flocons. |
| `vies` | 3 | 1–5 | Vies au départ. |
| `piege_debut` | 200 | 0–1000 | Proportion de pièges (‰) au départ (après le délai sans piège). `0` + `piege_fin: 0` = plus aucun piège. |
| `piege_fin` | 333 | 0–1000 | Proportion de pièges (‰) à `duree_ref_ms` et au-delà. |
| `delai_sans_piege_ms` | 5000 | 0–60000 | Aucun piège avant ce délai. |
| `distance_min` | 280 | 100–600 | Écart minimal piège / autres flocons (‰ de la largeur et de la hauteur). |
| `duree_ref_ms` | 100000 | 10000–150000 | Durée sur laquelle la proportion passe de `piege_debut` à `piege_fin`. |

Tests : `php avent/jeux/flocons/test.php` (générateur, parties, triche) et `node avent/jeux/flocons/test.js` (parité PHP/JS).
