# Calendrier de l'avent — « Mystère au Gîte des Flocons »

Site de 24 mini-jeux (un par jour, du 1er au 24 décembre) greffé sur le site de pronos :
mêmes comptes, même session, même base MySQL. Tout vit dans ce dossier `avent/`.

## Installation (LWS)

1. **Base de données** : dans phpMyAdmin, sur la base du site de pronos, exécuter une seule fois
   `avent/sql/001_avent.sql`. Il ajoute la colonne `utilisateurs.is_admin` (le compte `admin` devient
   administrateur) et crée les tables `avent_*`. Compatible MySQL 8/9 et MariaDB 10.x.
2. **Fichiers** : déployer le dossier `avent/` ainsi que `login.php` (retour vers le calendrier après
   connexion) et `header.php` (lien « Avent » dans le menu). `includes/db.php` n'est pas modifié : ne pas
   écraser la configuration de production.
3. **Dossier d'images** : `avent/uploads/` doit être accessible en écriture par PHP. Son `.htaccess` interdit
   l'exécution de scripts ; si LWS répond « erreur 500 » sur les images, supprimer ce `.htaccess`
   (les fichiers sont de toute façon renommés selon leur type réel).
4. **Contenu** : se connecter avec un compte administrateur → menu *Admin* → *Planning*. Pour chaque jour,
   choisir le jeu, saisir son contenu (JSON), le message ou le GIF de révélation, éventuellement le jour boss.
5. **Test** : *Admin → Planning → Mode test* débloque toutes les cases ou simule une date. Les parties de test ne
   comptent ni dans le classement ni dans les statistiques. **À désactiver avant le 1er décembre.**
6. **Réglages** : *Admin → Réglages* (date de la case 1, rattrapage, essais, coefficient des jours boss).

## Règles implémentées

| Sujet | Règle |
| --- | --- |
| Ouverture | Case J ouverte à 00h00 (Europe/Paris, heure du serveur). Le contenu d'une case verrouillée n'est jamais envoyé au navigateur. |
| Rattrapage | Jouable du jour J au jour J+6 inclus (réglage « 7 jours »). Points complets. |
| Essais | 2 parties par joueur et par case, le meilleur score est gardé. Une partie lancée consomme l'essai. |
| Durée | 120 s maximum, chronomètre contrôlé par le serveur (5 s de tolérance réseau). |
| Points | Score du jeu (0–100) × coefficient du jour boss (réglable, 2 par défaut). |
| Jours boss | 5, 6, 12, 13, 19, 20 et 24 (modifiable dans l'administration). |
| Classement | Somme des meilleurs points par jour. Égalité : plus de cases réussies, puis temps cumulé le plus court. Réservé aux joueurs connectés. |
| Exclusion | Un joueur exclu (page *Comptes*) disparaît du calendrier et du classement, sans toucher à son compte de pronos. |

## Structure

```
avent/
  index.php            calendrier en chalet
  jour.php             page d'un jour (histoire, jeu, résultat, révélation)
  classement.php       classement
  api/start.php        démarre une partie, renvoie le contenu du jeu SANS les réponses
  api/finish.php       termine la partie : chronomètre, vérification par le module, points
  admin/               planning, édition d'un jour, statistiques, comptes, réglages
  includes/            bootstrap (règles, accès aux jeux, classement) et gabarit
  assets/              avent.css, avent.js (neige, fenêtres), jour.js (partie), admin.js
  jeux/<slug>/         un dossier par jeu (voir ci-dessous)
  sql/001_avent.sql    migration
  uploads/             images et GIF téléversés
```

## Ajouter un jeu (contrat)

Un jeu est un dossier `avent/jeux/<slug>/` (slug en minuscules, chiffres et `_`). Il apparaît automatiquement dans la
liste de l'administration. La plateforme gère tout le reste : comptes, essais, chronomètre, points, classement, révélation.

### `manifest.json`

```json
{
    "nom": "Nom affiché",
    "description": "Une phrase de consigne affichée avant la partie.",
    "verification": "serveur",
    "exemple": { "...": "contenu d'exemple, proposé par le bouton « Insérer l'exemple » de l'administration" }
}
```

### `server.php` (facultatif mais recommandé)

Trois fonctions, préfixées `avent_jeu_<slug>_` :

| Fonction | Rôle |
| --- | --- |
| `valider(array $config): ?string` | Vérifie le contenu saisi par l'administrateur. Renvoie `null` si valide, sinon un message d'erreur en français. |
| `config_publique(array $config): array` | Contenu envoyé au navigateur. **Retirer ici les réponses attendues.** Sans cette fonction, tout le contenu est envoyé. |
| `verifier(array $config, array $payload, int $dureeMs): array` | Corrige la partie à partir de ce que le jeu renvoie. Renvoie `['score' => 0..100, 'reussite' => bool]`. Sans cette fonction, le score est 0. |

Règle d'or : **tout ce qui peut être vérifié côté serveur doit l'être** (réponses d'un quiz, mot du pendu…). Pour un jeu
d'adresse ou de réflexe qui ne peut pas être rejoué par le serveur, `verifier` doit au moins plafonner le score à ce qui
est plausible pour `$dureeMs` (voir `jeux/flocons/server.php`).

### `game.js`

```js
window.AventJeux = window.AventJeux || {};
window.AventJeux.<slug> = {
    // conteneur : élément vide à remplir ; config : le résultat de config_publique()
    // api.terminer(payload) : à appeler quand le joueur a fini avant la fin du temps
    // api.restant() : millisecondes restantes
    demarrer: function (conteneur, config, api) { /* ... */ },
    // Appelé à la fin du temps : renvoie le payload à vérifier (état courant du joueur)
    arreter: function () { return { /* ... */ }; }
};
```

* Pas de chronomètre à écrire : la plateforme l'affiche et appelle `arreter()` à zéro.
* Tactile et souris : utiliser `pointerdown` ou `click`, prévoir un affichage fonctionnel à 360 px de large.
* Aucun son. Aucune ressource externe (polices, scripts, images) : tout doit être servi par le domaine.
* Écrire dans `conteneur` avec `textContent` (jamais `innerHTML` avec du contenu issu de la configuration).
* `game.css` (facultatif) est chargé automatiquement ; préfixer ses classes par le slug.

### Prompt type pour créer un nouveau jeu avec un outil de vibe coding

> Crée le jeu « <nom> » pour le calendrier de l'avent, dans `avent/jeux/<slug>/`. Respecte le contrat décrit dans
> `avent/README.md` (section « Ajouter un jeu ») : `manifest.json` avec un exemple de contenu, `server.php` avec
> `valider`, `config_publique` et `verifier` (vérification côté serveur autant que possible), `game.js` exposant
> `window.AventJeux.<slug>` avec `demarrer` et `arreter`, et `game.css` si besoin. Le jeu dure 2 minutes maximum, sans
> indice, sans son, sans ressource externe, jouable au doigt sur mobile (360 px). Ambiance : chalet de montagne à Noël,
> ton décalé. Règle de score : <décrire comment obtenir 0 à 100 points>.
> Inspire-toi de `jeux/quiz/` (vérification serveur) et `jeux/flocons/` (jeu de réflexe).

## Jeux fournis

* `quiz` — questions à choix multiple, corrigées côté serveur.
* `flocons` — jeu de réflexe : attraper des flocons avant qu'ils ne tombent (score plafonné côté serveur).
