/* Page d'un jour : démarrage de la partie, chronomètre, dialogue avec les modules de jeu.
 *
 * Contrat d'un jeu (voir avent/README.md) :
 *   window.AventJeux.<slug> = {
 *     demarrer(conteneur, config, api) { ... },   // api.terminer(payload) pour finir avant la fin du temps
 *     arreter() { return payload; }               // appelé à la fin du temps : renvoie l'état à vérifier
 *   };
 * Le chronomètre affiché est indicatif : le serveur fait foi.
 */
(function () {
    'use strict';

    var page = document.getElementById('jeu-page');
    if (!page) { return; }

    var jour = page.dataset.jour;
    var csrf = page.dataset.csrf;
    var boutonCommencer = document.getElementById('bouton-commencer');
    var boutonRejouer = document.getElementById('bouton-rejouer');
    var avant = document.getElementById('avant');
    var arene = document.getElementById('arene');
    var resultat = document.getElementById('resultat');
    var conteneur = document.getElementById('jeu-conteneur');
    var barre = document.getElementById('chrono-rempli');
    var texteChrono = document.getElementById('chrono-texte');
    var blocChrono = arene.querySelector('.chrono');

    var etat = null; // { partie, jeu, fin, minuteur, termine }

    function post(url, donnees) {
        var corps = new URLSearchParams(donnees);
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: corps.toString()
        }).then(function (reponse) {
            return reponse.json().catch(function () {
                return { ok: false, erreur: 'Réponse inattendue du serveur.' };
            });
        });
    }

    function afficherErreur(message) {
        var info = avant.querySelector('.information');
        if (!info) {
            info = document.createElement('p');
            info.className = 'information';
            avant.appendChild(info);
        }
        info.textContent = message;
        avant.hidden = false;
        arene.hidden = true;
        if (boutonCommencer) { boutonCommencer.disabled = false; }
    }

    function chargerRessource(type, url) {
        return new Promise(function (resolve, reject) {
            if (type === 'style') {
                var lien = document.createElement('link');
                lien.rel = 'stylesheet';
                lien.href = url;
                lien.onload = resolve;
                lien.onerror = resolve; // un style manquant ne bloque pas la partie
                document.head.appendChild(lien);
                return;
            }
            var script = document.createElement('script');
            script.src = url;
            script.onload = resolve;
            script.onerror = function () { reject(new Error('Impossible de charger le jeu.')); };
            document.head.appendChild(script);
        });
    }

    function demarrer() {
        if (boutonCommencer) { boutonCommencer.disabled = true; }
        if (boutonRejouer) { boutonRejouer.disabled = true; }

        post('api/start.php', { jour: jour }).then(function (reponse) {
            if (!reponse.ok) { throw new Error(reponse.erreur || 'Impossible de démarrer.'); }
            var chargements = [chargerRessource('script', reponse.jeu.script + '?v=' + Date.now())];
            if (reponse.jeu.style) { chargements.push(chargerRessource('style', reponse.jeu.style)); }
            return Promise.all(chargements).then(function () { return reponse; });
        }).then(function (reponse) {
            var module = window.AventJeux && window.AventJeux[reponse.jeu.slug];
            if (!module || typeof module.demarrer !== 'function') {
                throw new Error('Le jeu est introuvable.');
            }

            avant.hidden = true;
            resultat.hidden = true;
            arene.hidden = false;
            var fixe = document.getElementById('revelation-fixe');
            if (fixe) { fixe.hidden = true; }
            conteneur.innerHTML = '';

            etat = {
                partie: reponse.partie,
                module: module,
                debut: Date.now(),
                fin: Date.now() + reponse.partie.restant_ms,
                termine: false
            };
            lancerChrono();
            module.demarrer(conteneur, reponse.config, {
                terminer: function (payload) { terminer(payload); },
                restant: function () { return Math.max(0, etat.fin - Date.now()); }
            });
        }).catch(function (erreur) {
            afficherErreur(erreur.message);
        });
    }

    function lancerChrono() {
        var total = etat.partie.duree_ms;
        function tic() {
            if (!etat || etat.termine) { return; }
            var restant = Math.max(0, etat.fin - Date.now());
            barre.style.width = Math.max(0, Math.min(100, (restant / total) * 100)) + '%';
            texteChrono.textContent = Math.ceil(restant / 1000) + ' s';
            blocChrono.classList.toggle('urgent', restant <= 10000);
            if (restant <= 0) {
                var payload = {};
                try { payload = etat.module.arreter ? (etat.module.arreter() || {}) : {}; } catch (e) { payload = {}; }
                terminer(payload);
            }
        }
        tic();
        etat.minuteur = setInterval(tic, 200);
    }

    function terminer(payload) {
        if (!etat || etat.termine) { return; }
        etat.termine = true;
        clearInterval(etat.minuteur);
        conteneur.style.pointerEvents = 'none';
        texteChrono.textContent = '…';

        post('api/finish.php', {
            partie: etat.partie.id,
            payload: JSON.stringify(payload || {})
        }).then(function (reponse) {
            if (!reponse.ok) { throw new Error(reponse.erreur || 'Impossible d\'enregistrer le résultat.'); }
            afficherResultat(reponse);
        }).catch(function (erreur) {
            conteneur.style.pointerEvents = '';
            arene.hidden = true;
            afficherErreur(erreur.message);
        });
    }

    function afficherResultat(r) {
        arene.hidden = true;
        resultat.hidden = false;

        var titre = document.getElementById('resultat-titre');
        var detail = document.getElementById('resultat-detail');
        titre.textContent = r.hors_delai ? 'Temps écoulé !' : (r.reussite ? 'Bravo, bien joué !' : 'Pas mal, tu y es presque !');
        document.getElementById('resultat-points').textContent = r.points;

        var texte = 'Score de la partie : ' + r.score + '/100.';
        if (r.hors_delai) { texte = 'Ta réponse est arrivée après la fin du temps : elle ne rapporte aucun point.'; }
        if (r.test) {
            texte += ' (Partie de test : elle ne compte pas.)';
        } else {
            texte += ' Ton meilleur score sur cette case : ' + r.meilleur_points + ' points.';
            if (r.peut_rejouer) { texte += ' Il te reste ' + r.essais_restants + ' essai' + (r.essais_restants > 1 ? 's' : '') + '.'; }
        }
        detail.textContent = texte;

        var bloc = document.getElementById('resultat-revelation');
        bloc.innerHTML = '';
        if (r.revelation && (r.revelation.texte || r.revelation.image)) {
            var titreRevelation = document.createElement('h3');
            titreRevelation.textContent = 'Le mot de l\'équipe';
            bloc.appendChild(titreRevelation);
            if (r.revelation.image) {
                var image = document.createElement('img');
                image.src = r.revelation.image;
                image.alt = '';
                bloc.appendChild(image);
            }
            if (r.revelation.texte) {
                var paragraphe = document.createElement('p');
                paragraphe.style.whiteSpace = 'pre-line';
                paragraphe.textContent = r.revelation.texte;
                bloc.appendChild(paragraphe);
            }
            bloc.hidden = false;
        } else {
            bloc.hidden = true;
        }

        if (boutonRejouer) {
            boutonRejouer.hidden = !r.peut_rejouer;
            boutonRejouer.disabled = false;
        }
        resultat.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (boutonCommencer) { boutonCommencer.addEventListener('click', demarrer); }
    if (boutonRejouer) {
        boutonRejouer.addEventListener('click', function () {
            // L'état de la page est rechargé pour repartir d'un affichage propre.
            window.location.reload();
        });
    }
}());
