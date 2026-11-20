/* Jeu « Chasse aux flocons » — module navigateur. Voir avent/README.md pour le contrat.
 *
 * Le calendrier des flocons (apparition, chute, position, piégé ou non) vient d'une graine du serveur : le
 * navigateur et le serveur le calculent à l'identique (bloc « GÉNÉRATEUR », même algorithme que server.php).
 * Le navigateur journalise chaque toucher [numéro du flocon, temps en ms depuis le départ] et envoie ce journal
 * à la fin ; le serveur rejoue la partie et recalcule score et vies. Les vies et le score affichés ici ne sont
 * qu'indicatifs. SI VOUS MODIFIEZ UNE RÈGLE DE GÉNÉRATION, FAITES-LA AUSSI DANS server.php. */
(function () {
    'use strict';
    window.AventJeux = window.AventJeux || {};

    var ECART_MS = 100;          // deux touchers plus proches : le second est ignoré (serveur identique)
    var ESSAIS_X = 24;
    var PHRASES_PERDU = ['Plus de vies : le chalet sent le roussi. Le Père Noël parle de « légèrement cuit ».',
        'Les flocons ont fondu, ton honneur aussi. La raclette console.',
        'Trois brûlures, c\'est le record du chalet. Pas celui qu\'on vise.'];
    var PHRASES_VIES_PLEINES = ['Zéro brûlure et des flocons plein les poches : le Père Noël recrute.',
        'Sans une cloque. Même le saint-bernard applaudit.'];
    var PHRASES_VIES_PERDUES = ['Quelques bobos, mais la neige est à toi.',
        'Un peu roussi sur les bords, comme une bonne tartiflette.'];
    var MSG_AIE = ['Aïe, ça brûle !', 'Chaud devant !', 'Ça sent le sapin grillé !', 'Pas celui-là, c\'était le chaud !'];

    var etat = null;

    /* ----- GÉNÉRATEUR (identique à server.php ; entiers uniquement) ----- */
    function alea(g) {                         // xorshift32 : g.x est l'état
        var x = g.x;
        x ^= (x << 13); x >>>= 0;
        x ^= (x >>> 17); x >>>= 0;
        x ^= (x << 5); x >>>= 0;
        g.x = x;
        return x;
    }
    function proches(a, b, d) {
        if (Math.abs(a.x - b.x) >= d) { return false; }
        var debut = Math.max(a.t, b.t), fin = Math.min(a.t + a.chute, b.t + b.chute);
        for (var t = debut; t <= fin; t += 50) {
            var ya = Math.floor((t - a.t) * 1000 / a.chute), yb = Math.floor((t - b.t) * 1000 / b.chute);
            if (Math.abs(ya - yb) < d) { return true; }
        }
        return false;
    }
    function libre(f, voisins, d, piege) {
        for (var i = 0; i < voisins.length; i++) {
            if ((piege || voisins[i].piege) && proches(f, voisins[i], d)) { return false; }
        }
        return true;
    }
    function genererFlocons(p, graine, n) {
        var g = { x: graine >>> 0 || 1 };
        for (var i = 0; i < 4; i++) { alea(g); }
        var liste = [];
        for (var k = 0; k < n; k++) {
            var t = k * p.rythme_ms;
            var chute = 2600 + alea(g) % 1401;
            var piege = false;
            if (t >= p.delai_sans_piege_ms) {
                var part = Math.min(1000, Math.floor(t * 1000 / p.duree_ref_ms));
                var proportion = p.piege_debut + Math.trunc((p.piege_fin - p.piege_debut) * part / 1000);
                piege = alea(g) % 1000 < proportion;
            }
            var voisins = [];
            for (var j = k - 1; j >= 0 && liste[j].t + 4000 > t; j--) { if (!liste[j].absent && liste[j].t + liste[j].chute > t) { voisins.push(liste[j]); } }
            var choisi = null, f, essai;
            for (essai = 0; essai < ESSAIS_X && choisi === null; essai++) {
                f = { t: t, chute: chute, x: 40 + alea(g) % 821, piege: piege };
                if (libre(f, voisins, p.distance_min, piege)) { choisi = f; }
            }
            if (choisi === null && piege) {
                piege = false;
                for (essai = 0; essai < ESSAIS_X && choisi === null; essai++) {
                    f = { t: t, chute: chute, x: 40 + alea(g) % 821, piege: false };
                    if (libre(f, voisins, p.distance_min, false)) { choisi = f; }
                }
            }
            if (choisi === null) {
                var depart = alea(g) % 41;
                for (var s = 0; s < 41 && choisi === null; s++) {
                    f = { t: t, chute: chute, x: 40 + ((depart + s) % 41) * 20, piege: false };
                    if (libre(f, voisins, p.distance_min, false)) { choisi = f; }
                }
                if (choisi === null) { choisi = { t: t, chute: chute, x: 0, piege: false, absent: true }; }
            }
            liste.push(choisi);
        }
        return liste;
    }
    /* ----- fin du GÉNÉRATEUR ----- */

    function nettoyer() {
        if (!etat) { return; }
        var e = etat;
        e.actif = false;
        clearInterval(e.generateur);
        e.temporisations.forEach(clearTimeout);
        if (e.racine && e.racine.parentNode) { e.racine.parentNode.removeChild(e.racine); }   // retire aussi les écouteurs (ils sont sur ses éléments)
        etat = null;
    }

    function hasard(liste) { return liste[Math.floor(Math.random() * liste.length)]; }

    function demarrer(conteneur, config, api) {
        nettoyer();
        var P = config;
        var racine = document.createElement('div');
        racine.className = 'flocons';
        racine.innerHTML =
            '<div class="flocons-entete"><p class="flocons-score" aria-live="off"></p><p class="flocons-vies"></p></div>' +
            '<p class="flocons-msg" aria-live="polite">&nbsp;</p>' +
            '<div class="flocons-terrain"></div>';
        conteneur.appendChild(racine);
        var elScore = racine.querySelector('.flocons-score'), elVies = racine.querySelector('.flocons-vies'),
            elMsg = racine.querySelector('.flocons-msg'), terrain = racine.querySelector('.flocons-terrain');

        var e = etat = {
            actif: true, racine: racine, generateur: 0, temporisations: [], t0: performance.now(),
            attrapes: 0, vies: P.vies, clics: [], dernier: null, prochain: 0, fini: false, flocons: []
        };
        function apres(ms, fn) { var t = setTimeout(function () { if (e.actif) { fn(); } }, ms); e.temporisations.push(t); }
        function majScore() { elScore.textContent = '❄ ' + e.attrapes + ' / ' + P.cible; }
        function majVies() {
            var h = '';
            for (var i = 0; i < P.vies; i++) { h += '<span class="' + (i < e.vies ? 'vie' : 'vie perdue') + '" aria-hidden="true">' + (i < e.vies ? '❤️' : '🖤') + '</span>'; }
            elVies.innerHTML = h;
            elVies.setAttribute('aria-label', 'Vies : ' + e.vies + ' sur ' + P.vies);
        }
        function dire(texte) {
            elMsg.textContent = texte;
            if (e.effaceMsg) { clearTimeout(e.effaceMsg); }
            e.effaceMsg = setTimeout(function () { if (e.actif) { elMsg.innerHTML = '&nbsp;'; } }, 1500);
            e.temporisations.push(e.effaceMsg);
        }
        majScore();
        majVies();

        // Rechargement après le départ : la partie est déjà consommée, on ne repart pas de zéro.
        if (P.deja_parti) {
            e.fini = true;
            terrain.innerHTML = '<div class="flocons-fin"><h3>Les flocons sont tombés sans toi</h3><p>Tu as rechargé la page : la chasse ne se rattrape pas.</p></div>';
            apres(2200, function () { api.terminer({ clics: [] }); });
            return;
        }
        // Le serveur note le départ (anti-rechargement). Jamais attendu : si l'appel échoue, la partie se joue quand même.
        try {
            var appel = api.appeler('depart', {});
            if (appel && appel.catch) { appel.catch(function () { /* ignoré */ }); }
        } catch (x) { /* ignoré */ }

        function flocon(k, maintenant) {
            var f = e.flocons[k];
            if (f.absent) { return; }
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'flocons-flocon' + (f.piege ? ' piege' : '');
            b.textContent = f.piege ? '🔥' : '❄';
            b.setAttribute('aria-label', f.piege ? 'Flocon piégé, ne pas toucher' : 'Flocon');
            b.setAttribute('data-k', k);
            b.style.left = (f.x / 10) + '%';
            b.style.animationDuration = f.chute + 'ms';
            b.style.animationDelay = '-' + Math.max(0, Math.round(maintenant - f.t)) + 'ms';   // alignée sur l'horloge du calendrier
            b.addEventListener('pointerdown', function (ev) { ev.preventDefault(); toucher(k, b, f); });
            b.addEventListener('animationend', function () { if (b.parentNode) { b.parentNode.removeChild(b); } });
            terrain.appendChild(b);
        }
        function toucher(k, b, f) {
            if (e.fini || b.classList.contains('attrape') || b.classList.contains('fume')) { return; }
            var t = Math.round(performance.now() - e.t0);
            if (e.dernier !== null && t - e.dernier < ECART_MS) { return; }       // comme le serveur : trop rapproché, ignoré
            e.dernier = t;
            e.clics.push([k, t]);
            if (f.piege) {
                e.vies--;
                b.innerHTML = '<span>💨</span>';
                b.className = 'flocons-flocon fume';
                b.style.animationPlayState = 'paused';                   // la fumée reste là où le piège a été touché
                apres(600, function () { if (b.parentNode) { b.parentNode.removeChild(b); } });
                majVies();
                terrain.classList.remove('flash', 'secoue'); void terrain.offsetWidth; terrain.classList.add('flash', 'secoue');
                dire(hasard(MSG_AIE));
                if (e.vies <= 0) { conclure('perdu'); }
            } else {
                b.classList.add('attrape');
                e.attrapes++;
                majScore();
                apres(160, function () { if (b.parentNode) { b.parentNode.removeChild(b); } });
                if (e.attrapes >= P.cible) { conclure('gagne'); }
            }
        }

        function conclure(raison) {
            if (e.fini) { return; }
            e.fini = true;
            clearInterval(e.generateur);
            var score = Math.min(100, Math.round(100 * e.attrapes / P.cible));
            var phrase = raison === 'perdu' ? hasard(PHRASES_PERDU) : (e.vies === P.vies ? hasard(PHRASES_VIES_PLEINES) : hasard(PHRASES_VIES_PERDUES));
            var coeurs = '';
            for (var i = 0; i < P.vies; i++) { coeurs += i < e.vies ? '❤️' : '🖤'; }
            var fin = document.createElement('div');
            fin.className = 'flocons-fin';
            var h = document.createElement('h3'), s = document.createElement('p'), v = document.createElement('p'), q = document.createElement('p');
            h.textContent = raison === 'perdu' ? 'Plus de vies !' : 'Chasse terminée !';
            s.className = 'flocons-bilan'; s.textContent = 'Score : ' + score + ' / 100';
            v.className = 'flocons-bilan'; v.textContent = 'Vies : ' + coeurs;
            q.textContent = phrase;
            fin.appendChild(h); fin.appendChild(s); fin.appendChild(v); fin.appendChild(q);
            terrain.appendChild(fin);
            apres(2300, function () { api.terminer({ clics: e.clics }); });
        }

        // Calendrier : le flocon k apparaît à k × rythme (horloge de la partie), comme dans la génération du serveur.
        var MAX = Math.min(520, Math.ceil(150000 / P.rythme_ms) + 2);
        e.flocons = genererFlocons(P, P.graine, MAX);
        function tic() {
            if (e.fini) { return; }
            var maintenant = performance.now() - e.t0;
            while (e.prochain < MAX && e.flocons[e.prochain].t <= maintenant) { flocon(e.prochain, maintenant); e.prochain++; }
        }
        tic();
        e.generateur = setInterval(tic, 30);
    }

    window.AventJeux.flocons = {
        demarrer: demarrer,
        genererFlocons: genererFlocons,       // pour les tests de parité PHP/JS
        // Fin du temps : on renvoie le journal des touchers, le serveur rejoue la partie.
        arreter: function () {
            var clics = etat ? etat.clics : [];
            nettoyer();
            return { clics: clics };
        }
    };
}());
