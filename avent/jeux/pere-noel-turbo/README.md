# Père Noël Turbo-Hotte

Score 0–100 : 70 pts de cadeaux (proportionnel, plafonné à 40 cadeaux) + 30 pts de survie (proportionnel sur 90 s). Réussite à partir de 50.

À régler pour ce jour : **durée de la partie = 120 s** (90 s de jeu + écran de départ).

## Paramètres réglables (JSON du jour, tous facultatifs, entiers)

| Paramètre | Défaut | Plage | Effet |
|---|---|---|---|
| `gravite` | 26 | 8–60 | Chute (doigt levé). Plus haut = plus nerveux. |
| `poussee` | 34 | 10–80 | Montée (doigt posé). Plus haut = plus réactif. |
| `vitesse_depart` | 150 | 80–400 | Défilement au début (64 = 1 px/tick, 150 ≈ 2,3 px/tick). |
| `vitesse_fin` | 270 | 80–500 | Défilement à 90 s (≥ départ). Courbe linéaire. |
| `densite` | 60 | 10–100 | Plus haut = plus d'obstacles et cadeaux plus rapprochés. |

Facile : `{"parametres":{"gravite":20,"densite":35}}` · Difficile : `{"parametres":{"densite":90,"vitesse_fin":380}}`.

## Fonctionnement anti-triche
Graine = HMAC(secret serveur, joueur|jour) → niveau identique pour les deux essais d'un joueur ce jour-là. Le navigateur envoie seulement `{entrees:[ticks de changement d'état du doigt], ticks}` ; `verifier` rejoue la simulation entière (60 ticks/s, entiers) et ignore tout score client. Rejets : > 1500 changements, ticks non croissants/hors bornes, durée impossible vs temps écoulé serveur. Usage unique : mécanisme de partie de la plateforme.

⚠ `server.php` et le bloc SIMULATION de `game.js` doivent rester identiques ; incrémenter `AVENT_PNT_VERSION` aux deux endroits en cas de changement de règle.
