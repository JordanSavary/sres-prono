# Cap sur Robinson island

Jeu de rythme : huit lutins ramènent la barque vers l'île, qui rétrécit pendant la traversée et perd un lutin à chaque seuil de taille franchi. Tapote (ou Espace) en cadence ; le courant ramène la barque en arrière si on s'arrête.

**Durée à régler pour la case : 90 à 120 s** (réglage « durée max » de la case). La partie dure 60 s de jeu au maximum, plus la lecture des consignes.

## Principe de vérification

* Aucun appel pendant la partie, sauf `embarquer` juste avant le départ (jamais attendu : la partie démarre même s'il échoue). Il sert à deux choses : si la page est rechargée après le départ, la barque ne repart pas (0 point), et la durée rejouée est comparée au temps réel écoulé depuis ce départ.
* Le navigateur envoie seulement `{"impulsions": [ticks…]}` : les ticks (60 par seconde) où une impulsion a été prise en compte. `verifier` rejoue la partie (`server.php`, miroir exact de `game.js`), recalcule le tick d'arrivée et le score. Le temps ou le score annoncés par le navigateur ne sont jamais lus.
* Rejeté (0 point) : journal non entier, non croissant, hors partie, plus de 720 impulsions, écart inférieur à 5 ticks (83 ms), ou arrivée plus rapide que le temps réellement écoulé (tolérance 5 s).
* Limite connue : un robot qui tape à 7 coups par seconde de façon parfaite obtient 100 points, comme un humain très régulier. Aucune simulation côté serveur ne peut l'empêcher.

## Simulation (pas fixe de 1/60 s, entiers uniquement)

À chaque tick, dans cet ordre :

1. les lutins dont le tick de chute est atteint sont à l'eau ;
2. si une impulsion est journalisée : `v += impulsion × équipage / 8` ;
3. `v -= v × frottement / 1024` (division tronquée), puis `v -= courant` ;
4. `position += v` (jamais en dessous du ponton) ; arrivée si `position ≥ distance`.

À 60 s (3600 ticks) sans arrivée, la barque coule : 0 point.

## Paramètres réglables

Dans le contenu de la case (JSON), tous facultatifs, entiers dans les bornes indiquées. Valeurs par défaut calibrées pour 7 impulsions/s ≈ 23 s.

| Paramètre | Défaut | Bornes | Effet |
| --- | --- | --- | --- |
| `distance` | 420000 | 100000–1000000 | Distance jusqu'à l'île. Plus grand : traversée plus longue. |
| `impulsion` | 120 | 40–400 | Vitesse ajoutée par un coup de rame, équipage complet. |
| `frottement` | 40 | 10–120 | Freinage de l'eau (÷1024 par tick). Plus grand : la barque s'arrête plus vite. |
| `courant` | 1 | 0–10 | Recul constant. À 3 ou plus, il faut un rythme très soutenu pour avancer. |
| `taille_fin` | 450 | 200–800 | Taille de la barque à 60 s, en ‰. Détermine combien de lutins tombent (seuils fixes 880, 780, 680, 590, 510, 440, 380 ‰). |
| `temps_plein` | 25 | 10–40 | Secondes jusqu'auxquelles on a 100 points. |
| `points_min` | 10 | 0–50 | Points si l'on arrive à 60 s pile. |

Le calendrier de rétrécissement est linéaire : la taille passe de 1000 ‰ à `taille_fin` en 3600 ticks, et le lutin n° k tombe au tick où la taille franchit le seuil n° k. Avec les valeurs par défaut : à 13,1 s, 24,0 s, 34,9 s, 44,7 s et 53,5 s.

Barème : 100 points jusqu'à `temps_plein`, puis décroissance linéaire (arrondie) jusqu'à `points_min` à 60 s ; 0 si la barque coule.

## Tests

```
php avent/jeux/barque/test.php   # rejoue des journaux types, contrôle rejets et barème
node avent/jeux/barque/test.js   # parité JavaScript/PHP sur 450 journaux (nécessite php)
```

Résultats avec les paramètres par défaut :

| Journal | Arrivée | Score |
| --- | --- | --- |
| Parfait, 7/s | 23,2 s | 100 |
| Humain rapide, ~6,5/s avec gigue | 24,7 s | 100 |
| Moyen, 6/s | 28,2 s | 92 |
| Régulier mais lent, 5/s | 35,1 s | 74 |
| Régulier et lent, 4,5/s | 41,0 s | 59 |
| Lent, 4/s | 49,4 s | 37 |
| 3,5/s ou moins, aucune impulsion | la barque coule | 0 |
| Inhumain, 12/s (5 ticks d'écart) | 12,7 s | 100 |

Si l'on modifie une règle : la changer dans `server.php` et dans `game.js`, incrémenter `AVENT_BARQUE_VERSION`, puis relancer les deux tests.
