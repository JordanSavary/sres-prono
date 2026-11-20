/* Jeu « Escape Christmas » — module navigateur. Voir avent/README.md pour le contrat.
 *
 * Aucune énigme, bonne réponse ni chiffre n'est dans ce fichier : tout vient du serveur, un appel à la fois
 * (api.appeler : demarrer, enigme, reponse, temps, code, temps_code). Les chronos affichés sont indicatifs :
 * le serveur mesure lui-même chaque délai, calcule le score et refuse toute action hors séquence.
 * Les chiffres gagnés ne sont affichés que 2 secondes ; le joueur doit les retenir. */
(function () {
    'use strict';
    window.AventJeux = window.AventJeux || {};

    var FLOCONS = 12;
    var EMOJIS = ['✨', '❄️', '⭐', '🎁', '🎄'];
    var MSG_BON = ['Bien joué, petit lutin !', 'Exact ! Le renne est impressionné.', 'Dans le mille, comme une boule de neige.', 'Cerveau de Père Noël détecté.'];
    var MSG_MAUVAIS = ['Aïe ! Le renne a levé un sourcil.', 'Raté. Le sapin fait semblant de ne rien avoir vu.', 'Non ! Même la fondue en rit.', 'Mauvaise réponse, mais belle assurance.'];
    var MSG_TEMPS = ['Trop lent : le chocolat chaud a refroidi.', 'Temps écoulé ! La bûche a fini de brûler.', 'Le sablier a fondu. On recommence.'];
    var MSG_CODE = ['Raté ! Le cadenas ricane.', 'Ce n\'est pas ça. Le traîneau reste au garage.', 'Mauvais code. Le renne soupire.'];
    var etat = null;

    function hasard(liste) { return liste[Math.floor(Math.random() * liste.length)]; }

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

    function demarrer(conteneur, config, api) {
        nettoyer();
        var C = config, N = C.nb_enigmes;
        var racine = document.createElement('div');
        racine.className = 'esc';
        racine.innerHTML =
            '<div class="esc-neige" aria-hidden="true"></div>' +
            '<div class="esc-panneau">' +
            '<h3 class="esc-titre">Escape Christmas</h3>' +
            '<div class="esc-stats"><div><span>Étape</span><b class="esc-etape">1/' + N + '</b></div>' +
            '<div><span>Score</span><b class="esc-score">0</b></div>' +
            '<div><span>Code</span><b class="esc-code" aria-label="0 chiffre sur ' + N + '"></b></div></div>' +
            '<div class="esc-barre" aria-hidden="true"><i></i></div>' +
            '<p class="esc-message" aria-live="polite">&nbsp;</p>' +
            '<section class="esc-vue esc-accueil"><p class="esc-slogan">Le traîneau du Père Noël est verrouillé, et le Père Noël a oublié le code. Classique.</p>' +
            '<p class="esc-regles">Résous ' + N + ' énigmes de ' + C.duree_enigme + ' s : chacune te donne un chiffre, affiché 2 s seulement. ' +
            'Retiens-les dans l\'ordre, puis tape le code sur le cadenas (' + C.tentatives + ' essais, ' + C.duree_code + ' s).</p>' +
            '<button type="button" class="bouton esc-go">Démarrer la mission</button></section>' +
            '<section class="esc-vue esc-enigme" hidden><div class="esc-illu" aria-hidden="true"></div><p class="esc-question"></p>' +
            '<div class="esc-reponses"></div></section>' +
            '<section class="esc-vue esc-cadenas" hidden><div class="esc-saisie" aria-live="polite"></div>' +
            '<div class="esc-clavier"></div></section>' +
            '<section class="esc-vue esc-fin" hidden></section>' +
            '<div class="esc-chiffre" hidden><span>Retiens ce chiffre !</span><b></b></div>' +
            '</div><div class="esc-rafale" aria-hidden="true"></div>';
        conteneur.appendChild(racine);

        var $ = function (s) { return racine.querySelector(s); };
        var elEtape = $('.esc-etape'), elScore = $('.esc-score'), elCode = $('.esc-code'), elBarre = $('.esc-barre'), elBarreI = $('.esc-barre i');
        var elMsg = $('.esc-message'), vAccueil = $('.esc-accueil'), vEnigme = $('.esc-enigme'), vCadenas = $('.esc-cadenas'), vFin = $('.esc-fin');
        var elIllu = $('.esc-illu'), elQuestion = $('.esc-question'), elReponses = $('.esc-reponses'), elSaisie = $('.esc-saisie'), elClavier = $('.esc-clavier');
        var elChiffre = $('.esc-chiffre'), elRafale = $('.esc-rafale'), elPanneau = $('.esc-panneau');

        var e = etat = {
            actif: true, racine: racine, minuteur: 0, temporisations: [], ecouteurs: [],
            seq: 0, bloque: true, termine: false, echeance: 0, duree: 0, mode: '', saisie: '', nbChiffres: 0,
            fin: 0, relances: 0, boutons: [], touches: {}
        };
        function ecoute(cible, type, fn, opt) { cible.addEventListener(type, fn, opt); e.ecouteurs.push([cible, type, fn, opt]); }
        function apres(ms, fn) { var t = setTimeout(function () { if (e.actif) { fn(); } }, ms); e.temporisations.push(t); return t; }
        function dire(texte, classe) { elMsg.textContent = texte; elMsg.className = 'esc-message' + (classe ? ' ' + classe : ''); }
        function secouer() { elPanneau.classList.remove('secoue'); void elPanneau.offsetWidth; elPanneau.classList.add('secoue'); }
        function vue(v) { [vAccueil, vEnigme, vCadenas, vFin].forEach(function (x) { x.hidden = (x !== v); }); }
        function rafale() {
            for (var i = 0; i < 9; i++) {
                var s = document.createElement('span');
                s.textContent = hasard(EMOJIS);
                s.style.left = (10 + Math.random() * 80) + '%';
                s.style.setProperty('--dx', Math.round((Math.random() - .5) * 120) + 'px');
                s.style.setProperty('--dy', Math.round(60 + Math.random() * 90) + 'px');
                s.style.animationDelay = (Math.random() * .12).toFixed(2) + 's';
                elRafale.appendChild(s);
                (function (n) { apres(1100, function () { if (n.parentNode) { n.parentNode.removeChild(n); } }); }(s));
            }
        }
        // Neige : flocons ❄ en CSS
        for (var f = 0; f < FLOCONS; f++) {
            var fl = document.createElement('i');
            fl.textContent = '❄';
            fl.style.left = Math.round(Math.random() * 96) + '%';
            fl.style.fontSize = (8 + Math.random() * 10).toFixed(0) + 'px';
            fl.style.animationDuration = (7 + Math.random() * 8).toFixed(1) + 's';
            fl.style.animationDelay = (-Math.random() * 12).toFixed(1) + 's';
            $('.esc-neige').appendChild(fl);
        }

        // --- Pastilles ---
        function majCode(n) {
            e.nbChiffres = n;
            var t = '';
            for (var i = 0; i < N; i++) { t += (i ? ' ' : '') + (i < n ? '●' : '•'); }
            elCode.textContent = t;
            elCode.setAttribute('aria-label', n + ' chiffre' + (n > 1 ? 's' : '') + ' sur ' + N);
        }
        function majScore(s) { if (typeof s === 'number') { elScore.textContent = s; } }
        majCode(0);

        // --- Barre de temps (indicative) ---
        function lancerBarre(restantMs, dureeMs, surFin) {
            e.echeance = performance.now() + restantMs;
            e.duree = dureeMs * 1;
            e.surFin = surFin;
            e.relances = 0;
            clearInterval(e.minuteur);
            e.minuteur = setInterval(tic, 80);
            tic();
        }
        function arreterBarre() { clearInterval(e.minuteur); e.minuteur = 0; e.surFin = null; elBarreI.style.width = '0%'; }
        function tic() {
            var reste = Math.max(0, e.echeance - performance.now());
            var part = Math.min(1, reste / e.duree);
            elBarreI.style.width = (part * 100).toFixed(1) + '%';
            elBarre.className = 'esc-barre' + (part < 0.25 ? ' rouge' : (part < 0.55 ? ' orange' : ''));
            if (reste <= 0 && e.surFin) {
                var f = e.surFin;
                e.surFin = null;
                f();
            }
        }

        // --- Appels serveur : un renvoi reprend le même « seq » (le serveur rejoue sa réponse) ---
        function appeler(action, donnees, avecSeq, essai) {
            essai = essai || 1;
            return api.appeler(action, donnees).then(function (rep) {
                if (!rep || !rep.ok) { throw new Error((rep && rep.erreur) || 'Réponse inattendue.'); }
                return rep;
            }, function (err) {
                if (essai >= 3 || !e.actif) { throw err; }
                return new Promise(function (ok) { apres(500, ok); }).then(function () { return appeler(action, donnees, avecSeq, essai + 1); });
            });
        }
        function envoyer(action, donnees) {
            donnees = donnees || {};
            donnees.seq = ++e.seq;
            return appeler(action, donnees, true);
        }
        function panne(err) {
            if (!e.actif) { return; }
            e.bloque = true;
            dire((err && err.message ? err.message : 'Le serveur ne répond pas.') + ' Recharge la page pour reprendre.', 'esc-erreur');
        }

        // --- Écrans ---
        function suite(rep) {                                           // l'état du serveur décide de l'écran suivant
            majScore(rep.score);
            if (rep.termine || rep.phase === 'fini') { conclure(rep); return; }
            if (rep.phase === 'code' && rep.code) { montrerCadenas(rep.code); return; }
            if (rep.enigme) { montrerEnigme(rep.enigme); return; }
            appeler('enigme', {}).then(suite).catch(panne);
        }
        function chargerSuite() { e.bloque = true; appeler('enigme', {}).then(function (rep) { if (e.actif) { suite(rep); } }).catch(panne); }

        function montrerEnigme(en) {
            e.mode = 'enigme';
            vue(vEnigme);
            elEtape.textContent = en.numero + '/' + N;
            elIllu.textContent = en.illustration;
            elQuestion.textContent = en.question;
            elReponses.textContent = '';
            e.boutons = [];
            en.reponses.forEach(function (texte, i) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'bouton esc-rep';
                b.textContent = texte;
                b.setAttribute('data-i', i);
                if ((en.essayees || []).indexOf(i) >= 0) { b.disabled = true; b.classList.add('rate'); }
                elReponses.appendChild(b);
                e.boutons.push(b);
            });
            e.bloque = false;
            lancerBarre(en.restant_ms, C.duree_enigme * 1000, delaiEcoule);
        }
        function delaiEcoule() {
            if (e.bloque || e.termine) { e.surFin = function () { apres(250, delaiEcoule); e.surFin = null; }; e.echeance = performance.now() + 250; return; }
            e.bloque = true;
            envoyer('temps').then(function (rep) {
                if (!e.actif) { return; }
                dire(hasard(MSG_TEMPS) + ' −' + C.penalite, 'esc-mauvais');
                secouer();
                suite(rep);
            }).catch(function () {                                      // « trop tôt » (horloges légèrement décalées) : on resynchronise
                if (!e.actif) { return; }
                apres(400, chargerSuite);
            });
        }
        function repondre(i) {
            if (e.bloque || e.mode !== 'enigme' || e.boutons[i].disabled) { return; }
            e.bloque = true;
            e.boutons[i].classList.add('attente');
            envoyer('reponse', { i: i }).then(function (rep) {
                if (!e.actif) { return; }
                majScore(rep.score);
                if (rep.expire) {
                    dire(hasard(MSG_TEMPS) + ' −' + C.penalite, 'esc-mauvais');
                    secouer();
                    suite(rep);
                } else if (rep.bon) {
                    gagne(rep);
                } else if (rep.bon === false) {
                    e.boutons[i].classList.remove('attente');
                    e.boutons[i].classList.add('rate');
                    e.boutons[i].disabled = true;
                    dire(hasard(MSG_MAUVAIS) + ' −' + C.penalite, 'esc-mauvais');
                    secouer();
                    e.bloque = false;
                } else { suite(rep); }
            }).catch(panne);
        }
        function gagne(rep) {
            arreterBarre();
            e.mode = 'chiffre';
            majCode(e.nbChiffres + 1);
            dire(hasard(MSG_BON) + ' +' + rep.gain, 'esc-bon');
            rafale();
            elChiffre.querySelector('b').textContent = rep.chiffre;
            elChiffre.hidden = false;
            apres(C.affichage_ms, function () {
                elChiffre.hidden = true;
                elChiffre.querySelector('b').textContent = '';
                chargerSuite();
            });
        }

        // --- Cadenas ---
        function construireClavier() {
            if (elClavier.firstChild) { return; }
            ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'eff', '0', 'ok'].forEach(function (k) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'esc-touche' + (k === 'ok' ? ' esc-ok' : (k === 'eff' ? ' esc-eff' : ''));
                b.setAttribute('data-k', k);
                b.textContent = k === 'eff' ? 'Effacer' : (k === 'ok' ? 'OK' : k);
                if (k === 'eff') { b.setAttribute('aria-label', 'Effacer'); }
                elClavier.appendChild(b);
                e.touches[k] = b;
            });
        }
        function majSaisie() {
            var t = '';
            for (var i = 0; i < N; i++) { t += '<b>' + (i < e.saisie.length ? e.saisie.charAt(i) : '•') + '</b>'; }
            elSaisie.innerHTML = t;
            e.touches.ok.disabled = e.saisie.length !== N;
        }
        function montrerCadenas(info) {
            e.mode = 'code';
            e.saisie = '';
            e.tentatives = info.tentatives_restantes;
            vue(vCadenas);
            elEtape.textContent = 'Cadenas';
            construireClavier();
            majSaisie();
            dire('Le cadenas : ' + info.tentatives_restantes + ' essai' + (info.tentatives_restantes > 1 ? 's' : '') + '. Dans l\'ordre des énigmes !');
            e.bloque = false;
            lancerBarre(info.restant_ms, C.duree_code * 1000, codeEcoule);
        }
        function codeEcoule() {
            if (e.bloque || e.termine) { e.surFin = function () { apres(250, codeEcoule); e.surFin = null; }; e.echeance = performance.now() + 250; return; }
            e.bloque = true;
            envoyer('temps_code').then(function (rep) { if (e.actif) { suite(rep); } }).catch(function () { if (e.actif) { apres(400, chargerSuite); } });
        }
        function valider() {
            if (e.bloque || e.mode !== 'code' || e.saisie.length !== N) { return; }
            e.bloque = true;
            var saisi = e.saisie;
            envoyer('code', { code: saisi }).then(function (rep) {
                if (!e.actif) { return; }
                majScore(rep.score);
                if (rep.bon) { rafale(); conclure(rep); return; }
                if (rep.expire) { suite(rep); return; }
                if (rep.termine) { conclure(rep); return; }
                e.tentatives = rep.tentatives_restantes;
                e.saisie = '';
                majSaisie();
                secouer();
                dire(hasard(MSG_CODE) + ' ' + rep.tentatives_restantes + ' essai' + (rep.tentatives_restantes > 1 ? 's' : '') + ' restant' + (rep.tentatives_restantes > 1 ? 's' : '') + '.', 'esc-mauvais');
                e.bloque = false;
            }).catch(panne);
        }
        function touche(k) {
            if (e.mode !== 'code' || e.bloque) { return; }
            if (k === 'ok') { valider(); return; }
            if (k === 'eff') { e.saisie = e.saisie.slice(0, -1); majSaisie(); return; }
            if (e.saisie.length < N) { e.saisie += k; majSaisie(); }
        }

        // --- Fin ---
        function conclure(rep) {
            if (e.termine) { return; }
            e.termine = true;
            e.mode = 'fin';
            e.bloque = true;
            arreterBarre();
            elChiffre.hidden = true;
            vue(vFin);
            elEtape.textContent = 'Fin';
            majScore(rep.score);
            var raison = rep.raison || (rep.bon ? 'code' : 'essais');
            var titre = raison === 'code' ? 'Traîneau déverrouillé !' : (raison === 'global' ? 'Temps écoulé !' : (raison === 'delai_code' ? 'Le cadenas a gagné…' : 'Cadenas bloqué !'));
            var slogan = raison === 'code' ? 'Les rennes ronflent déjà de bonheur, direction le pôle Nord.'
                : (raison === 'global' ? 'Le Père Noël a fini par prendre le train.' : 'Le traîneau reste au garage. Les cadeaux attendront l\'an prochain.');
            vFin.textContent = '';
            var h = document.createElement('h4'), s = document.createElement('p'), q = document.createElement('p');
            h.textContent = titre;
            s.className = 'esc-bilan';
            s.textContent = 'Score : ' + (typeof rep.score === 'number' ? rep.score : 0) + ' / 100';
            q.className = 'esc-slogan';
            q.textContent = slogan;
            vFin.appendChild(h); vFin.appendChild(s); vFin.appendChild(q);
            dire(raison === 'code' ? 'Mission accomplie !' : 'Mission terminée.', raison === 'code' ? 'esc-bon' : '');
            apres(2500, function () { api.terminer({}); });
        }

        // --- Événements : réaction au pointerdown, sans délai ---
        ecoute(elReponses, 'pointerdown', function (ev) {
            var b = ev.target.closest ? ev.target.closest('.esc-rep') : null;
            if (!b) { return; }
            if (ev.cancelable) { ev.preventDefault(); }
            repondre(parseInt(b.getAttribute('data-i'), 10));
        }, { passive: false });
        ecoute(elClavier, 'pointerdown', function (ev) {
            var b = ev.target.closest ? ev.target.closest('.esc-touche') : null;
            if (!b || b.disabled) { return; }
            if (ev.cancelable) { ev.preventDefault(); }
            b.classList.add('appui');
            apres(120, function () { b.classList.remove('appui'); });
            touche(b.getAttribute('data-k'));
        }, { passive: false });
        ecoute(racine, 'contextmenu', function (ev) { ev.preventDefault(); });
        ecoute(racine, 'touchstart', function (ev) { if (ev.target.closest && ev.target.closest('.esc-rep, .esc-touche') && ev.cancelable) { ev.preventDefault(); } }, { passive: false });

        // --- Lancement et reprise ---
        function surveillerGlobal(ecouleMs) {
            apres(Math.max(0, C.duree_globale * 1000 - ecouleMs) + 700, function () {
                if (!e.termine && !e.bloque) { chargerSuite(); } else if (!e.termine) { apres(800, function () { if (!e.termine) { chargerSuite(); } }); }
            });
        }
        ecoute($('.esc-go'), 'click', function () {
            var go = $('.esc-go');
            go.disabled = true;
            dire('Le Père Noël cherche ses clés…');
            envoyer('demarrer').then(function (rep) {
                if (!e.actif) { return; }
                majCode(0);
                surveillerGlobal(rep.ecoule_ms || 0);
                dire('Vite, le traîneau n\'attend pas !');
                chargerSuite();
            }).catch(function (err) { go.disabled = false; panne(err); });
        });
        if (C.reprise) {                                                // page rechargée : on reprend là où le serveur en est
            vue(null || vAccueil);
            vAccueil.hidden = true;
            majCode(C.reprise.nb_chiffres || 0);
            majScore(C.reprise.score);
            surveillerGlobal(C.reprise.ecoule_ms || 0);
            dire('Te revoilà ! Le chrono a continué sans toi.');
            chargerSuite();
        }
    }

    window.AventJeux.escape = {
        demarrer: demarrer,
        // Fin du temps de la case : rien à envoyer, l'état de la partie est tenu par le serveur.
        arreter: function () {
            nettoyer();
            return {};
        }
    };
}());
