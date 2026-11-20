# Les Paires du Père Noël

Memory 5×5 : 12 paires d'emojis de Noël et une carte « charbon » sans paire. Retourner le charbon annule la première carte éventuellement retournée et ajoute une pénalité au temps effectif.

**Durée à régler pour la case : 130 à 150 s.** Le jeu dure 110 s au maximum, mais le chrono de la plateforme démarre avant (au clic sur « Commencer » de la page), alors que celui du jeu ne démarre qu'à son propre bouton « Commencer ». J'ai donc relevé le plafond `AVENT_DUREE_MAX` de la plateforme de 120 à 150 s (`includes/bootstrap.php`).

## Principe de vérification

* `demarrer` crée la partie : le serveur tire la disposition (graine secrète propre au joueur, au jour et aux dimensions), la garde dans l'état de la partie (`avent_etats`) et lance le chrono. La disposition n'est jamais envoyée : `config_publique` ne contient que les paramètres publics, et la reprise après rechargement ne révèle que les paires déjà trouvées.
* Chaque retournement est un appel `retourner {i, seq}` (mécanisme `api/action.php`). Le serveur renvoie l'emoji de la carte demandée (ou `charbon`) et, pour une deuxième carte, si c'est une paire.
* Refusés : index non entier ou hors grille, carte déjà verrouillée, deuxième sélection identique à la première, retournement après la durée maximale (tolérance réseau de 0,5 s), deux retournements espacés de moins de 250 ms.
* `seq` (entier croissant) rend un renvoi après coupure réseau sans effet : le serveur rejoue sa dernière réponse.
* Le temps est mesuré par le serveur, de `demarrer` à la dernière paire. Rechargement : la partie reprend avec le temps déjà écoulé ; la carte en attente est annulée. Le payload envoyé par le navigateur est ignoré.
* Côté navigateur : le retournement commence au `pointerdown`, l'emoji apparaît à la réponse. Pendant la requête et pendant les 700 ms d'une mauvaise paire, les touchers sont ignorés (pas de troisième carte).

## Paramètres réglables

Dans le contenu de la case (JSON), tous facultatifs :

| Paramètre | Défaut | Bornes | Effet |
| --- | --- | --- | --- |
| `lignes`, `colonnes` | 5, 5 | 3–7, 3–6 | Taille de la grille. `lignes × colonnes` doit être impair (le charbon occupe la case sans paire). |
| `emojis` | 🎅 🎄 ⭐ 🎁 🔔 ⛄ 🦌 🍪 🧦 🕯️ ❄️ 🛷 | au moins (cartes − 1) / 2, tous différents, 12 caractères max | Les premiers emojis de la liste sont utilisés. Une grille de plus de 12 paires exige une liste fournie. |
| `duree_max` | 110 | 30–150 | Secondes de jeu. Au-delà, aucun retournement n'est accepté (0 point). |
| `temps_parfait` | 35 | 5 à `duree_max` − 1 | 100 points jusqu'à ce temps effectif. |
| `points_min` | 10 | 0–50 | Points à `duree_max` de temps effectif. |
| `penalite` | 3 | 0–10 | Secondes ajoutées par charbon retourné. |

Temps effectif = temps réel + `penalite` × charbons. Score : 100 jusqu'à `temps_parfait`, puis linéaire jusqu'à `points_min` à `duree_max`. Si le temps effectif dépasse `duree_max` alors que le temps réel est dans la limite (beaucoup de charbons), le score est `points_min`. Si le temps réel dépasse `duree_max` avant la dernière paire : 0.

## Test

```
php avent/jeux/paires/test.php
```

Joue des parties contre le module avec une horloge simulée : disposition (secrète, variée, propre au joueur et au jour), partie parfaite, partie lente, charbons, dépassement des 110 s, appels invalides (index hors grille, carte verrouillée, retournements trop rapides, seq périmée), rechargement et client qui triche sur son temps, validation des paramètres.
