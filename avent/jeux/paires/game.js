/* Jeu « Les Paires du Père Noël » — module navigateur. Voir avent/README.md pour le contrat.
 *
 * La disposition des cartes ne quitte jamais le serveur : chaque retournement est un appel
 * api.appeler('retourner', {i, seq}) qui renvoie la seule carte demandée. L'interface est optimiste
 * (la carte commence à se retourner au toucher, l'emoji apparaît à la réponse). Le temps affiché est
 * indicatif : le serveur mesure le temps, compte les charbons et calcule le score. */
(function () {
    'use strict';
    window.AventJeux = window.AventJeux || {};

    var DELAI_CACHE = 700;      // ms pendant lesquelles une mauvaise paire reste visible
    var ECART_MIN = 270;        // ms entre deux retournements (le serveur refuse moins de 250 ms)
    var CHARBON = '<svg viewBox="0 0 48 48" aria-label="Charbon"><polygon points="8,34 12,20 22,14 30,18 36,12 42,24 38,36 24,40 14,40" fill="#2b2b2f" stroke="#111" stroke-width="2" stroke-linejoin="round"/>' +
        '<polygon points="12,20 22,14 24,26 14,30" fill="#44444b"/><polygon points="30,18 36,12 42,24 34,26" fill="#3a3a41"/><polygon points="24,26 34,26 38,36 24,40" fill="#1c1c20"/>' +
        '<path d="M18 22 l3 -2 M33 20 l3 1 M20 34 l5 -2" stroke="#8d8d99" stroke-width="1.6" stroke-linecap="round"/><path d="M40 8 l1.5 3 3 1.5 -3 1.5 -1.5 3 -1.5 -3 -3 -1.5 3 -1.5z" fill="#ffe08a"/></svg>';
    var DOS = '<svg viewBox="0 0 48 48" aria-hidden="true"><g fill="none" stroke="#c8d9ee" stroke-width="3" stroke-linecap="round"><path d="M24 6v36M8.4 15l31.2 18M8.4 33l31.2-18"/>' +
        '<path d="M24 12l-4-4M24 12l4-4M24 36l-4 4M24 36l4 4M13.6 18l-5.4 1.4M13.6 18l1.4-5.4M34.4 30l5.4-1.4M34.4 30l-1.4 5.4M13.6 30l-5.4-1.4M13.6 30l1.4 5.4M34.4 18l5.4 1.4M34.4 18l-1.4-5.4"/></g><circle cx="24" cy="24" r="3.4" fill="#c8d9ee"/></svg>';
    var MSG_PAIRE = ['Une paire ! Les lutins applaudissent.', 'Bien vu, ta mémoire n\'a pas fondu.', 'Paire validée. Le sapin est fier.', 'Tu as l\'œil de lynx du pôle Nord.'];
    var MSG_RATE = ['Pas de paire. Le sapin soupire.', 'Raté, mais avec panache.', 'Perdu : ces deux-là ne se connaissent pas.', 'Ni une ni deux… ni une paire.'];
    var MSG_CHARBON = ['Le Père Noël te trouve pas très sage.', 'Du charbon ! Même le renne a honte.', 'Cadeau empoisonné, vilain lutin.', 'Ça sent le roussi.'];
    var etat = null;

    function nettoyer() {
        if (!etat) { return; }
        var e = etat;
        e.actif = false;
        clearInterval(e.minuteur);
        e.temporisations.forEach(clearTimeout);
        e.ecouteurs.forEach(function (l) { l[0].removeEventListener(l[1], l[2], l[3]); });
        if (e.racine && e.racine.parentNode) { e.racine.parentNode.removeChild(e.racine); }
        etat = null;
    }

    /** Même barème que le serveur (affichage du score potentiel uniquement). */
    function score(effectifS, C) {
        if (effectifS <= C.temps_parfait) { return 100; }
        if (effectifS >= C.duree_max) { return C.points_min; }
        var part = (effectifS - C.temps_parfait) / (C.duree_max - C.temps_parfait);
        return Math.max(C.points_min, Math.min(100, Math.round(100 - (100 - C.points_min) * part)));
    }
    function formater(ms) {
        var s = Math.floor(ms / 1000);
        return Math.floor(s / 60) + ':' + (s % 60 < 10 ? '0' : '') + (s % 60);
    }
    function hasard(liste) { return liste[Math.floor(Math.random() * liste.length)]; }

    function demarrer(conteneur, config, api) {
        nettoyer();
        var C = config, n = C.lignes * C.colonnes, paires = (n - 1) / 2;
        var racine = document.createElement('div');
        racine.className = 'pai';
        racine.style.setProperty('--colonnes', C.colonnes);
        racine.innerHTML =
            '<h3 class="pai-titre">Les Paires du Père Noël</h3>' +
            '<div class="pai-compteurs"><div><span>Paires</span><b class="pai-nb">0/' + paires + '</b></div>' +
            '<div><span>Temps</span><b class="pai-temps">0:00</b></div>' +
            '<div><span>Score potentiel</span><b class="pai-score">100</b></div></div>' +
            '<div class="pai-barre" aria-hidden="true"><i></i></div>' +
            '<p class="pai-message" aria-live="polite">&nbsp;</p>' +
            '<div class="pai-grille" role="group" aria-label="Cartes"></div>' +
            '<section class="pai-fin" hidden></section>' +
            '<div class="pai-ecran pai-accueil"><h3>Les Paires du Père Noël</h3>' +
            '<p class="pai-slogan">Le Père Noël a perdu la mémoire. Toi, tu as encore la tienne ?</p>' +
            '<p class="pai-regles">Retrouve les ' + paires + ' paires le plus vite possible. Attention à la carte <b>charbon</b> : la retourner coûte ' + C.penalite + ' s et annule ta première carte. ' +
            'Le chrono démarre quand tu appuies sur Commencer.</p>' +
            '<button type="button" class="bouton pai-go">Commencer</button></div>';
        conteneur.appendChild(racine);

        var elGrille = racine.querySelector('.pai-grille'), elNb = racine.querySelector('.pai-nb'), elTemps = racine.querySelector('.pai-temps');
        var elScore = racine.querySelector('.pai-score'), elBarre = racine.querySelector('.pai-barre i'), elMsg = racine.querySelector('.pai-message');
        var elFin = racine.querySelector('.pai-fin'), accueil = racine.querySelector('.pai-accueil');

        var e = etat = {
            actif: true, racine: racine, minuteur: 0, temporisations: [], ecouteurs: [],
            commence: false, termine: false, expire: false, base: 0, debut: 0, charbons: 0,
            verrou: [], premiere: -1, enVol: false, bloque: false, dernierEnvoi: -1000, seq: 0, nbPaires: 0, file: Promise.resolve(), cartes: []
        };
        function ecoute(cible, type, fn, opt) { cible.addEventListener(type, fn, opt); e.ecouteurs.push([cible, type, fn, opt]); }
        function apres(ms, fn) { var t = setTimeout(function () { if (e.actif) { fn(); } }, ms); e.temporisations.push(t); }
        function dire(texte, classe) { elMsg.textContent = texte; elMsg.className = 'pai-message' + (classe ? ' ' + classe : ''); }

        // --- Grille ---
        var i;
        for (i = 0; i < n; i++) {
            var c = document.createElement('div');
            c.className = 'pai-carte';
            c.setAttribute('data-i', i);
            c.innerHTML = '<div class="pai-int"><div class="pai-dos">' + DOS + '</div><div class="pai-face"></div></div>';
            elGrille.appendChild(c);
            e.cartes.push({ el: c, face: c.querySelector('.pai-face'), verrou: false });
        }
        function retourner(k, visible) { e.cartes[k].el.classList.toggle('retournee', visible); }
        function montrer(k, emoji, charbon) {
            var f = e.cartes[k].face;
            f.className = 'pai-face' + (charbon ? ' pai-charbon' : '');
            if (charbon) { f.innerHTML = CHARBON; } else { f.textContent = emoji; }
        }
        function verrouiller(k) { e.cartes[k].verrou = true; e.cartes[k].el.classList.add('retournee', 'pai-trouvee'); }

        // --- Chrono (indicatif) ---
        function reelMs() { return e.base + (performance.now() - e.debut); }
        function tic() {
            if (!e.commence || e.termine) { return; }
            var reel = reelMs(), effectif = reel + C.penalite * 1000 * e.charbons;
            elTemps.textContent = formater(effectif);
            elScore.textContent = score(effectif / 1000, C);
            var restant = Math.max(0, 1 - reel / (C.duree_max * 1000));
            elBarre.style.width = (restant * 100).toFixed(1) + '%';
            elBarre.parentNode.classList.toggle('urgent', restant < 0.2);
            if (reel >= C.duree_max * 1000) { expirer(); }
        }
        function expirer() {
            if (e.expire || e.termine) { return; }
            e.expire = true;
            e.file.then(function () { if (e.actif && !e.termine) { conclure({ expire: true }); } });
        }

        // --- Fin de partie ---
        function conclure(rep) {
            if (e.termine) { return; }
            e.termine = true;
            clearInterval(e.minuteur);
            var gagne = !!rep.fini;
            var effectif = rep.effectif_ms !== undefined ? rep.effectif_ms : (rep.ecoule_ms || reelMs()) + C.penalite * 1000 * (rep.charbons !== undefined ? rep.charbons : e.charbons);
            var pts = rep.score !== undefined ? rep.score : (gagne ? score(effectif / 1000, C) : 0);
            elFin.hidden = false;
            elFin.textContent = '';
            var h = document.createElement('h3'), b = document.createElement('p'), s = document.createElement('p'), ph = document.createElement('p');
            h.textContent = gagne ? 'Toutes les paires sont trouvées !' : 'Temps écoulé !';
            b.className = 'pai-bilan';
            b.textContent = (gagne ? '⏱ ' + formater(effectif) + ' (effectif)' : '⏱ ' + C.duree_max + ' s') + ' · Paires : ' + e.nbPaires + '/' + paires;
            s.className = 'pai-bilan';
            s.textContent = 'Score : ' + pts;
            ph.className = 'pai-slogan';
            ph.textContent = gagne
                ? (pts >= 90 ? 'Mémoire d\'éléphant. Le Père Noël te propose un CDI.' : (pts >= 50 ? 'Pas mal : le Père Noël a retenu ton nom, et la liste des sages.' : 'Ça y est, enfin ! Les lutins ont eu le temps de tricoter trois bonnets.'))
                : 'Le Père Noël est reparti avec les cadeaux. Il a dit qu\'il t\'oublierait pas.';
            elFin.appendChild(h); elFin.appendChild(b); elFin.appendChild(s); elFin.appendChild(ph);
            apres(2000, function () { api.terminer({}); });
        }

        // --- Un retournement : optimiste, réponse du serveur ensuite ---
        function toucher(k) {
            if (!e.commence || e.termine || e.expire || e.bloque || e.enVol) { return; }
            var carte = e.cartes[k];
            if (carte.verrou || k === e.premiere) { return; }
            var maintenant = performance.now();
            if (maintenant - e.dernierEnvoi < ECART_MIN) { return; }
            e.dernierEnvoi = maintenant;
            e.enVol = true;
            carte.face.className = 'pai-face pai-attente';
            carte.face.textContent = '';
            retourner(k, true);                                   // la carte commence à se retourner tout de suite
            var seq = ++e.seq;
            envoyer(k, seq, 1).then(function (rep) {
                e.enVol = false;
                if (!e.actif) { return; }
                traiter(k, rep);
            }).catch(function (err) {
                e.enVol = false;
                if (!e.actif) { return; }
                retourner(k, false);
                dire(err.message || 'Le réseau a trébuché, réessaie.', 'pai-erreur');
            });
        }
        // Un renvoi après coupure réseau reprend le même « seq » : le serveur rejoue sa réponse sans rien refaire.
        function envoyer(k, seq, essai) {
            return api.appeler('retourner', { i: k, seq: seq }).then(function (rep) {
                if (!rep || !rep.ok) { throw new Error((rep && rep.erreur) || 'Réponse inattendue.'); }
                return rep;
            }, function (err) {
                if (essai >= 2 || !e.actif) { throw err; }
                return new Promise(function (ok) { apres(500, ok); }).then(function () { return envoyer(k, seq, essai + 1); });
            });
        }
        function traiter(k, rep) {
            if (rep.termine) { conclure(rep); retourner(k, false); return; }
            e.charbons = rep.charbons;
            if (rep.charbon) {
                montrer(k, '', true);
                dire(hasard(MSG_CHARBON) + ' +' + C.penalite + ' s', 'pai-mauvais');
                elGrille.classList.remove('secoue'); void elGrille.offsetWidth; elGrille.classList.add('secoue');
                var premiere = e.premiere;
                e.premiere = -1;
                e.bloque = true;
                apres(DELAI_CACHE, function () {
                    retourner(k, false);
                    if (premiere >= 0) { retourner(premiere, false); }
                    e.bloque = false;
                });
                return;
            }
            montrer(k, rep.carte, false);
            if (rep.paire === null) {                              // première carte
                e.premiere = k;
                dire('Et sa jumelle, elle est où ?');
                return;
            }
            var p = e.premiere;
            e.premiere = -1;
            if (rep.paire) {
                verrouiller(p); verrouiller(k);
                e.nbPaires = rep.nb_paires;
                elNb.textContent = e.nbPaires + '/' + paires;
                dire(hasard(MSG_PAIRE), 'pai-bon');
                if (rep.fini) { conclure(rep); }
            } else {
                e.bloque = true;                                   // pas de troisième carte pendant le délai
                dire(hasard(MSG_RATE));
                apres(DELAI_CACHE, function () { retourner(p, false); retourner(k, false); e.bloque = false; });
            }
        }

        ecoute(elGrille, 'pointerdown', function (ev) {
            var el = ev.target.closest ? ev.target.closest('.pai-carte') : null;
            if (!el) { return; }
            if (ev.cancelable) { ev.preventDefault(); }
            toucher(parseInt(el.getAttribute('data-i'), 10));
        }, { passive: false });
        ecoute(elGrille, 'contextmenu', function (ev) { ev.preventDefault(); });
        ecoute(elGrille, 'touchstart', function (ev) { if (ev.cancelable) { ev.preventDefault(); } }, { passive: false });

        // --- Lancement et reprise ---
        function installer(vue) {
            e.commence = true;
            e.base = vue.ecoule_ms || 0;
            e.debut = performance.now();
            e.charbons = vue.charbons || 0;
            (vue.trouvees || []).forEach(function (t) { montrer(t[0], t[1], false); verrouiller(t[0]); });
            e.nbPaires = (vue.trouvees || []).length / 2;
            elNb.textContent = e.nbPaires + '/' + paires;
            tic();
            e.minuteur = setInterval(tic, 200);
        }
        ecoute(racine.querySelector('.pai-go'), 'click', function () {
            accueil.hidden = true;
            dire('Le Père Noël compte les secondes…');
            api.appeler('demarrer', {}).then(function (rep) {
                if (!e.actif) { return; }
                if (!rep || !rep.ok) { throw new Error((rep && rep.erreur) || 'Le serveur ne répond pas.'); }
                installer(rep);
                dire('C\'est parti !');
                if (rep.fini || rep.expire) { conclure(rep); }
            }).catch(function (err) {
                if (!e.actif) { return; }
                dire((err.message || 'Le serveur ne répond pas.') + ' Recharge la page pour reprendre.', 'pai-erreur');
            });
        });
        if (C.reprise) {                                           // page rechargée : on reprend avec le temps déjà écoulé
            accueil.hidden = true;
            installer(C.reprise);
            dire('Te revoilà ! Le chrono a continué sans toi.');
            if (C.reprise.fini || C.reprise.expire) { conclure(C.reprise); }
        }
    }

    window.AventJeux.paires = {
        demarrer: demarrer,
        // Fin du temps : rien à envoyer, l'état de la partie est tenu par le serveur.
        arreter: function () {
            nettoyer();
            return {};
        }
    };
}());
