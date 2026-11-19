/* Jeu « Cap sur Robinson island » — module navigateur. Voir avent/README.md pour le contrat.
 *
 * Deux blocs :
 *  1. SIMULATION : miroir exact de server.php (entiers uniquement). Toute modification doit être
 *     répercutée dans server.php, puis AVENT_BARQUE_VERSION incrémenté des deux côtés.
 *  2. RENDU / ENTRÉES : scène SVG 360×380, boucle à pas fixe 60 ticks/s, aucun son.
 * Aucun appel serveur pendant la partie : le navigateur n'envoie que le journal des ticks où une
 * impulsion a été prise en compte ; le serveur rejoue la partie et recalcule le score. */
(function () {
    'use strict';
    var global = typeof window !== 'undefined' ? window : {};
    global.AventJeux = global.AventJeux || {};

    /* =====================================================================
     * 1. SIMULATION (identique à server.php)
     * ===================================================================== */
    function div(a, b) { return Math.trunc(a / b); }

    /** C : résultat de config_publique() (parametres, ticks, ecart, lutins, chutes). */
    function Sim(C) {
        this.C = C;
        this.p = C.parametres;
        this.T = 0;
        this.pos = 0;
        this.v = 0;
        this.equipage = C.lutins;
        this.iChute = 0;
        this.arrivee = 0;   // ticks écoulés à l'arrivée (0 : pas encore arrivé)
        this.coule = false;
    }
    /** Un tick. imp : une impulsion est journalisée à ce tick. Renvoie le nombre de lutins tombés à l'eau. */
    Sim.prototype.pas = function (imp) {
        var C = this.C, p = this.p, tombes = 0;
        while (this.iChute < C.chutes.length && C.chutes[this.iChute] <= this.T) {
            this.equipage--; this.iChute++; tombes++;
        }
        if (imp) { this.v += div(p.impulsion * this.equipage, C.lutins); }
        this.v -= div(this.v * p.frottement, 1024);
        this.v -= p.courant;
        this.pos += this.v;
        if (this.pos < 0) { this.pos = 0; if (this.v < 0) { this.v = 0; } }
        this.T++;
        if (this.pos >= p.distance) { this.arrivee = this.T; }
        else if (this.T >= C.ticks) { this.coule = true; }
        return tombes;
    };
    /** Taille de la barque en ‰ (1000 au départ), rétrécissement linéaire jusqu'à taille_fin à 60 s. */
    function taille(C, T) { return 1000 - (1000 - C.parametres.taille_fin) * T / C.ticks; }

    /** Rejoue un journal complet (tests). Renvoie { arrivee, equipage, pos }. */
    function simuler(C, impulsions) {
        var s = new Sim(C), marque = {}, i;
        for (i = 0; i < impulsions.length; i++) { marque[impulsions[i]] = true; }
        while (!s.arrivee && !s.coule) { s.pas(marque[s.T] === true); }
        return { arrivee: s.arrivee || null, equipage: s.equipage, pos: s.pos };
    }
    function score(C, arrivee) {
        var p = C.parametres, plein = p.temps_plein * 60;
        if (arrivee <= plein) { return 100; }
        var largeur = C.ticks - plein, perte = (100 - p.points_min) * (arrivee - plein);
        var pts = 100 - div(2 * perte + largeur, 2 * largeur);
        return Math.max(p.points_min, Math.min(100, pts));
    }

    /* =====================================================================
     * 2. RENDU ET ENTRÉES (purement visuel : n'influence jamais la simulation)
     * ===================================================================== */
    var W = 360, H = 380, HZ = 172, OX = 180, OY = 372, BS = 0.76;
    var BANCS = [-42, -96, -144, -186];              // ordonnée locale des bancs, du plus proche au plus lointain
    var ECHELLE_RANG = [1.15, 1.0, 0.88, 0.78];
    var ORDRE_CHUTE = [5, 2, 6, 1, 4, 7, 0];          // le lutin 3 n'est jamais à l'eau
    var REPLIQUES = [
        'Plouf. Je démissionne.',
        'Mon contrat s\'arrête ici.',
        'J\'avais dit que je savais nager. J\'ai menti.',
        'Je descends, c\'est ma station.',
        'Dites à ma mère que j\'ai ramé.',
        'Ce bateau n\'était pas dans la brochure.',
        'Je prends la barque suivante.'
    ];
    var NB_VAGUES = 9;
    var etat = null;

    function nettoyer() {
        if (!etat) { return; }
        var e = etat;
        e.actif = false;
        if (e.raf) { cancelAnimationFrame(e.raf); }
        if (e.minuteur) { clearTimeout(e.minuteur); }
        e.ecouteurs.forEach(function (l) { l[0].removeEventListener(l[1], l[2], l[3]); });
        if (e.racine && e.racine.parentNode) { e.racine.parentNode.removeChild(e.racine); }
    }

    function sapin(x, y, h) {
        var l = h * 0.34;
        return '<polygon points="' + (x - l) + ',' + y + ' ' + x + ',' + (y - h) + ' ' + (x + l) + ',' + y + '" fill="#1f4d3a"/>' +
            '<polygon points="' + (x - l * 0.55) + ',' + (y - h * 0.62) + ' ' + x + ',' + (y - h) + ' ' + (x + l * 0.55) + ',' + (y - h * 0.62) + '" fill="#f4f8ff"/>';
    }

    function lutinSvg(i) {
        var chapeau = i % 2 ? '#1f7a4d' : '#d1303b', tunique = i % 2 ? '#c0343e' : '#25784e', peau = '#f1c9a0';
        var rame = function (cote) {
            return '<g id="bq-r' + (cote < 0 ? 'g' : 'd') + i + '"><line x1="0" y1="0" x2="' + (cote * 80) + '" y2="12" stroke="#6b4423" stroke-width="4" stroke-linecap="round"/>' +
                '<rect x="' + (cote < 0 ? -98 : 78) + '" y="6" width="20" height="11" rx="5" fill="#8a5a2d" transform="rotate(' + (cote * 8) + ' ' + (cote * 84) + ' 12)"/></g>';
        };
        return '<g id="bq-l' + i + '">' + rame(-1) + rame(1) +
            '<rect x="-12" y="-21" width="24" height="21" rx="8" fill="' + tunique + '"/>' +
            '<rect x="-12" y="-9" width="24" height="3" fill="#2a1a10" opacity=".55"/>' +
            '<path d="M-9.5 -30 l-6 -3 l3 9z M9.5 -30 l6 -3 l-3 9z" fill="' + peau + '"/>' +
            '<circle cx="0" cy="-29" r="9.5" fill="' + peau + '"/>' +
            '<path d="M-9.4 -29 a9.4 9.4 0 0 1 18.8 0 v2 q-9.4 7 -18.8 0z" fill="#8a5a2d"/>' +
            '<path d="M-10.5 -33 Q0 -41 10.5 -33 L2.5 -58 Q-2 -58 -10.5 -33z" fill="' + chapeau + '"/>' +
            '<path d="M-10.5 -33 Q0 -40 10.5 -33 L9.6 -29.5 Q0 -36 -9.6 -29.5z" fill="#fff"/>' +
            '<circle cx="1.5" cy="-58.5" r="3.8" fill="#fff"/></g>';
    }

    function scene() {
        var i, s = '';
        s += '<defs><linearGradient id="bq-ciel" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6f9bd1"/><stop offset="1" stop-color="#f8dcc2"/></linearGradient>' +
            '<linearGradient id="bq-eau" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5a97b3"/><stop offset=".45" stop-color="#2c6485"/><stop offset="1" stop-color="#143a58"/></linearGradient></defs>';
        s += '<rect width="' + W + '" height="' + HZ + '" fill="url(#bq-ciel)"/>';
        s += '<polygon points="0,172 0,118 38,86 66,112 108,62 158,116 200,92 250,124 292,76 332,108 360,98 360,172" fill="#b9cbe4"/>' +
            '<polygon points="108,62 96,80 104,76 112,84 118,76 124,82" fill="#fff"/><polygon points="292,76 281,92 288,88 294,96 301,89 306,93" fill="#fff"/><polygon points="38,86 28,100 36,96 42,102 48,96" fill="#fff"/>' +
            '<polygon points="0,172 0,140 50,112 95,138 140,118 190,145 235,122 285,148 330,126 360,140 360,172" fill="#8da9c9"/>' +
            '<polygon points="50,112 40,124 48,121 53,127 58,121" fill="#f4f8ff"/><polygon points="235,122 226,133 233,130 238,136 244,130" fill="#f4f8ff"/>';
        s += sapin(10, 176, 40) + sapin(30, 178, 54) + sapin(52, 176, 36) + sapin(72, 177, 26) +
            sapin(350, 176, 42) + sapin(328, 178, 56) + sapin(306, 176, 34) + sapin(288, 177, 24);
        s += '<rect y="' + (HZ + 2) + '" width="' + W + '" height="' + (H - HZ) + '" fill="url(#bq-eau)"/>';
        s += '<g id="bq-vagues" fill="none" stroke="#dff1fa" stroke-linecap="round"></g>';
        // île (base en 0,0 ; échelle et position pilotées par la distance restante)
        s += '<g id="bq-ile"><ellipse cx="0" cy="2" rx="86" ry="9" fill="#0e3550" opacity=".35"/>' +
            '<path d="M-80 0 Q-60 -22 -20 -28 Q20 -34 52 -24 Q80 -14 82 0 Z" fill="#e8d3a1"/>' +
            '<path d="M-52 -14 Q-30 -26 -6 -28 Q22 -32 44 -22 Q22 -20 -6 -17 Q-30 -13 -52 -14 Z" fill="#fff" opacity=".9"/>' +
            '<path d="M-32 -22 Q-36 -48 -26 -70" fill="none" stroke="#7a5230" stroke-width="5" stroke-linecap="round"/>' +
            '<g fill="none" stroke="#2f8f4e" stroke-width="5" stroke-linecap="round"><path d="M-26 -70 q-18 -4 -30 6"/><path d="M-26 -70 q-12 -14 -28 -12"/><path d="M-26 -70 q4 -16 20 -18"/><path d="M-26 -70 q16 -6 28 6"/></g>' +
            '<circle cx="-24" cy="-67" r="3" fill="#c0343e"/><circle cx="-31" cy="-66" r="3" fill="#f7d455"/>' +
            '<line x1="34" y1="-18" x2="34" y2="-72" stroke="#6b4423" stroke-width="4"/>' +
            '<rect x="-4" y="-80" width="92" height="22" rx="4" fill="#a9733b" stroke="#5c3a1b" stroke-width="2"/>' +
            '<text x="42" y="-65" text-anchor="middle" font-size="10.5" font-weight="700" fill="#fff4dc" font-family="Georgia,\'Times New Roman\',serif">Robinson island</text></g>';
        // barque
        s += '<ellipse id="bq-ecume" cx="' + OX + '" cy="' + (OY + 6) + '" rx="150" ry="11" fill="#e9f7ff" opacity="0"/>';
        s += '<g id="bq-barque">' +
            '<path d="M-125 0 L-98 -190 Q-80 -236 0 -252 Q80 -236 98 -190 L125 0 Z" fill="#a46a35" stroke="#5c3a1b" stroke-width="5" stroke-linejoin="round"/>' +
            '<path d="M-110 -6 L-86 -188 Q-70 -228 0 -240 Q70 -228 86 -188 L110 -6 Z" fill="#6e4524"/>';
        for (i = 0; i < BANCS.length; i++) {
            var hw = 108 - (-BANCS[i]) * 0.1;
            s += '<rect x="' + (-hw) + '" y="' + (BANCS[i] - 5) + '" width="' + (2 * hw) + '" height="10" rx="4" fill="#c18b4d" stroke="#5c3a1b" stroke-width="2"/>';
        }
        s += '<g id="bq-equipage">';
        for (i = 7; i >= 0; i--) { s += lutinSvg(i); }       // du plus loin au plus proche
        s += '</g><rect x="-129" y="-4" width="258" height="20" rx="8" fill="#a46a35" stroke="#5c3a1b" stroke-width="4"/>' +
            '<path d="M-110 6 H110" stroke="#c18b4d" stroke-width="3" opacity=".7"/></g>';
        s += '<g id="bq-chutes"></g><g id="bq-eclats"></g>';
        return '<svg class="bq-svg" viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="xMidYMid slice" aria-label="Une barque de lutins ramant vers Robinson island">' + s + '</svg>';
    }

    function demarrer(conteneur, config, api) {
        nettoyer();
        var C = config;
        var racine = document.createElement('div');
        racine.className = 'bq';
        racine.innerHTML =
            '<div class="bq-piste" aria-hidden="true"><span class="bq-piste-barre"><i class="bq-piste-rempli"></i></span><span class="bq-piste-ile">🏝</span></div>' +
            '<div class="bq-info" aria-live="off"><span class="bq-lutins">🧝 8</span></div>' +
            '<div class="bq-zone">' + scene() + '<div class="bq-bulles"></div></div>' +
            '<button type="button" class="bq-ramer" disabled>RAMER !</button>' +
            '<div class="bq-ecran bq-accueil">' +
            '<h3>Cap sur Robinson island</h3>' +
            '<p class="bq-slogan">Barque low-cost, taille unique, qui rétrécit à l\'usage. Huit lutins, zéro assurance.</p>' +
            '<p class="bq-regles">Tapote n\'importe où (ou la barre Espace) pour <b>ramer</b>, en rythme : le courant te ramène en arrière si tu t\'arrêtes. ' +
            'À chaque coup de rame, la barque rétrécit et un lutin démissionne. Arrive avant que tout coule : une minute maximum.</p>' +
            '<button type="button" class="bouton bq-go">Embarquer</button></div>' +
            '<div class="bq-ecran bq-fin" hidden></div>';
        conteneur.appendChild(racine);

        var svg = racine.querySelector('svg');
        var el = function (id) { return svg.querySelector('#' + id); };
        var zone = racine.querySelector('.bq-zone');
        var accueil = racine.querySelector('.bq-accueil');
        var finEcran = racine.querySelector('.bq-fin');
        var bouton = racine.querySelector('.bq-ramer');
        var bulles = racine.querySelector('.bq-bulles');
        var rempli = racine.querySelector('.bq-piste-rempli');
        var compteur = racine.querySelector('.bq-lutins');

        var e = etat = {
            actif: true, raf: 0, minuteur: 0, racine: racine, ecouteurs: [],
            sim: new Sim(C), impulsions: [], lance: false, fini: false, envoye: false,
            attente: false, dernierMs: -1000, dernierTick: -1000, derniereRame: -1000,
            acc: 0, dernier: 0, tomb: [], chutes: [], eclats: [], restants: [0, 1, 2, 3, 4, 5, 6, 7],
            pos: [], t0: 0
        };
        function ecoute(cible, type, fn, opt) { cible.addEventListener(type, fn, opt); e.ecouteurs.push([cible, type, fn, opt]); }
        function payload() { return { impulsions: e.impulsions.slice() }; }

        var vagues = [];
        var gv = el('bq-vagues'), k;
        for (k = 0; k < NB_VAGUES; k++) {
            var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            var d = 'M-300 0';
            for (var x = -300; x < 700; x += 40) { d += ' q10 -4 20 0 t20 0'; }
            p.setAttribute('d', d);
            gv.appendChild(p);
            vagues.push(p);
        }
        var noeuds = {
            barque: el('bq-barque'), ile: el('bq-ile'), ecume: el('bq-ecume'),
            chutes: el('bq-chutes'), eclats: el('bq-eclats'), lutin: [], rg: [], rd: []
        };
        for (k = 0; k < 8; k++) { noeuds.lutin[k] = el('bq-l' + k); noeuds.rg[k] = el('bq-rg' + k); noeuds.rd[k] = el('bq-rd' + k); e.pos[k] = null; }

        /* --- Entrées : pointerdown sans délai sur la zone de jeu et le bouton, Espace au clavier --- */
        function ramer(ev) {
            if (ev.cancelable) { ev.preventDefault(); }
            if (!e.lance || e.fini) { return; }
            var maintenant = performance.now();
            if (maintenant - e.dernierMs < 80) { return; }   // au moins 80 ms entre deux impulsions
            e.dernierMs = maintenant;
            e.attente = true;                                 // prise en compte au prochain tick (si l'écart en ticks le permet)
        }
        [zone, bouton].forEach(function (cible) {
            ecoute(cible, 'pointerdown', ramer, { passive: false });
            ecoute(cible, 'touchstart', function (ev) { if (ev.cancelable) { ev.preventDefault(); } }, { passive: false });
            ecoute(cible, 'contextmenu', function (ev) { ev.preventDefault(); });
        });
        ecoute(window, 'keydown', function (ev) {
            if (ev.code !== 'Space') { return; }
            if (e.lance && !e.fini) { ev.preventDefault(); }
            if (ev.repeat) { return; }                       // pas de répétition automatique de touche
            ramer(ev);
        });
        ecoute(window, 'keyup', function (ev) { if (ev.code === 'Space' && e.lance && !e.fini) { ev.preventDefault(); } });

        if (C.deja_parti) {
            // Page rechargée après le départ : la barque est partie sans le joueur, on ne repart pas de zéro.
            accueil.querySelector('h3').textContent = 'La barque est partie sans toi';
            accueil.querySelector('.bq-slogan').textContent = 'Tu as rechargé la page : Robinson island ne se rattrape pas.';
            accueil.querySelector('.bq-regles').textContent = 'Les lutins ont ramé sans toi, et le courant a fait le reste.';
            accueil.querySelector('.bq-go').textContent = 'Voir le résultat';
        }
        ecoute(racine.querySelector('.bq-go'), 'click', function () {
            if (C.deja_parti) {
                if (!e.envoye) { e.envoye = true; api.terminer({ impulsions: [] }); }
                return;
            }
            // Le serveur note le départ (anti-rechargement). Jamais attendu : si l'appel échoue, la partie démarre quand même.
            try {
                var appel = api.appeler('embarquer', {});
                if (appel && appel.catch) { appel.catch(function () { /* ignoré */ }); }
            } catch (x) { /* ignoré */ }
            accueil.hidden = true;
            bouton.disabled = false;
            e.lance = true;
            e.dernier = performance.now();
            e.t0 = e.dernier;
            try { racine.scrollIntoView({ block: 'center' }); } catch (x) { /* ignoré */ }
        });

        /* --- Fin de partie --- */
        var PHRASES_ARRIVEE = [
            [25, 'Traversée de record. Robinson te propose déjà la mairie.'],
            [38, 'Belle traversée. L\'île t\'accueille avec une noix de coco tiède.'],
            [60, 'Arrivé de justesse. L\'île a failli partir sans toi.']
        ];
        function terminerPartie() {
            if (e.fini) { return; }
            e.fini = true;
            bouton.disabled = true;
            e.minuteur = setTimeout(afficherFin, e.sim.arrivee ? 900 : 1500);
        }
        function afficherFin() {
            if (!e.actif) { return; }
            var s = e.sim, gagne = !!s.arrivee, secondes = s.arrivee / 60;
            finEcran.hidden = false;
            finEcran.textContent = '';
            var h = document.createElement('h3'), b = document.createElement('p'), ph = document.createElement('p'), j = document.createElement('p');
            h.textContent = gagne ? 'Terre en vue !' : 'Glou glou.';
            b.className = 'bq-bilan';
            if (gagne) {
                b.textContent = '⏱ ' + secondes.toFixed(1).replace('.', ',') + ' s · 🧝 ' + s.equipage + ' lutin' + (s.equipage > 1 ? 's' : '') + ' sauvé' + (s.equipage > 1 ? 's' : '');
                var texte = PHRASES_ARRIVEE[PHRASES_ARRIVEE.length - 1][1];
                for (var i = 0; i < PHRASES_ARRIVEE.length; i++) { if (secondes <= PHRASES_ARRIVEE[i][0]) { texte = PHRASES_ARRIVEE[i][1]; break; } }
                ph.textContent = texte;
            } else {
                b.textContent = '🧝 ' + s.equipage + ' lutin' + (s.equipage > 1 ? 's' : '') + ' au fond avec la barque';
                ph.textContent = 'Robinson island attendra l\'année prochaine. Les lutins, eux, ont déjà posé leur démission.';
            }
            ph.className = 'bq-slogan';
            j.className = 'bq-slogan';
            j.textContent = 'Le jury compte les points…';
            finEcran.appendChild(h); finEcran.appendChild(b); finEcran.appendChild(ph); finEcran.appendChild(j);
            if (!e.envoye) {
                e.envoye = true;
                e.minuteur = setTimeout(function () { if (e.actif) { api.terminer(payload()); } }, 1400);
            }
        }

        /* --- Éléments purement visuels --- */
        function bulle(x, y, texte) {
            var d = document.createElement('div');
            d.className = 'bq-bulle';
            d.style.left = Math.max(14, Math.min(86, x / W * 100)) + '%';
            d.style.top = Math.max(4, (y - 26) / H * 100) + '%';
            d.textContent = texte;
            bulles.appendChild(d);
            setTimeout(function () { if (d.parentNode) { d.parentNode.removeChild(d); } }, 2300);
        }
        function ecran(xl, yl, s) { return [OX + xl * BS * s, OY + yl * BS * s]; }
        function faireTomber(num, s, maintenant) {
            var id = ORDRE_CHUTE[num];
            var rang = e.restants.indexOf(id);
            if (rang < 0) { return; }
            var p = e.pos[id] || { x: id & 1 ? 26 : -26, y: BANCS[id >> 1], k: ECHELLE_RANG[id >> 1] };
            var pt = ecran(p.x, p.y, s);
            e.restants.splice(rang, 1);
            noeuds.lutin[id].setAttribute('display', 'none');
            var g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            g.innerHTML = lutinSvg(id).replace(/ id="[^"]*"/g, '').replace(/<g><line[^]*?<\/g>/g, '');
            noeuds.chutes.appendChild(g);
            e.chutes.push({ g: g, x: pt[0], y: pt[1], k: p.k * 1.2 * BS * s, dir: id & 1 ? 1 : -1, t0: maintenant, splash: false });
            bulle(pt[0], pt[1], REPLIQUES[num % REPLIQUES.length]);
        }
        function eclaboussure(x, y, maintenant) {
            var ns = 'http://www.w3.org/2000/svg', g = document.createElementNS(ns, 'g'), i;
            var an = document.createElementNS(ns, 'ellipse');
            an.setAttribute('fill', 'none'); an.setAttribute('stroke', '#fff'); an.setAttribute('stroke-width', '3');
            g.appendChild(an);
            var gouttes = [];
            for (i = 0; i < 7; i++) {
                var c = document.createElementNS(ns, 'circle');
                c.setAttribute('r', '2.6'); c.setAttribute('fill', '#e9f7ff');
                g.appendChild(c); gouttes.push({ c: c, vx: (i - 3) * 14 + (i % 2) * 5, vy: -(70 + (i * 37) % 40) });
            }
            noeuds.eclats.appendChild(g);
            e.eclats.push({ g: g, an: an, gouttes: gouttes, x: x, y: y, t0: maintenant });
        }

        /* --- Dessin --- */
        var tailleEcran = { s: 1 };
        function dessiner(maintenant) {
            var s = e.sim, r = Math.max(0, 1 - s.pos / s.p.distance);
            var tr = taille(C, Math.min(s.T, C.ticks)) / 1000;
            tailleEcran.s = tr;
            var tt = maintenant / 1000;
            var flotte = e.fini && s.coule ? 0 : Math.sin(tt * 2.6) * 1.6;
            var couleVers = 0;
            if (e.fini && s.coule) { couleVers = Math.min(1, (maintenant - (e.tFin || maintenant)) / 1300); }
            noeuds.barque.setAttribute('transform', 'translate(' + OX + ' ' + (OY + flotte + couleVers * 150 * tr) + ') rotate(' + (couleVers * 14) + ') scale(' + (BS * tr).toFixed(4) + ')');
            noeuds.barque.setAttribute('opacity', (1 - couleVers * 0.85).toFixed(3));

            // île : grossit avec la proximité
            var ki = 0.2 / (0.2 + 0.8 * r);
            var yi = HZ + 6 + Math.pow(1 - r, 2) * 54;
            noeuds.ile.setAttribute('transform', 'translate(' + (OX - 6) + ' ' + yi.toFixed(1) + ') scale(' + ki.toFixed(4) + ')');

            // vagues : avancent vers le joueur avec la position (reculent si la barque recule)
            var base = s.pos / 30000;
            for (var i = 0; i < NB_VAGUES; i++) {
                var u = ((i / NB_VAGUES + base) % 1 + 1) % 1;
                var y = HZ + 6 + (H - HZ) * Math.pow(u, 1.7);
                var sx = 0.35 + u * 1.7, glisse = (Math.sin(tt * 0.9 + i * 1.7) * 14 + i * 23) % 40;
                vagues[i].setAttribute('transform', 'translate(' + (glisse - 40).toFixed(1) + ' ' + y.toFixed(1) + ') scale(' + sx.toFixed(3) + ' ' + (0.4 + u * 1.5).toFixed(3) + ')');
                vagues[i].setAttribute('stroke-width', (1.2 + u * 1.6).toFixed(2));
                vagues[i].setAttribute('opacity', (0.15 + u * 0.4).toFixed(2));
            }
            noeuds.ecume.setAttribute('opacity', Math.max(0, Math.min(0.5, s.v / 900)).toFixed(2));

            // lutins : positions cibles resserrées selon l'effectif, rames selon le temps écoulé depuis la dernière impulsion
            var n = e.restants.length, ecart = 26 * (0.62 + 0.38 * n / 8);
            var coup = Math.max(0, 1 - (maintenant - e.derniereRame) / 300);
            for (var j = 0; j < n; j++) {
                var id = e.restants[j], rang = j >> 1, cote = j & 1 ? 1 : -1;
                var cible = { x: cote * ecart, y: BANCS[rang], k: ECHELLE_RANG[rang] };
                var p = e.pos[id] || (e.pos[id] = { x: cible.x, y: cible.y, k: cible.k });
                p.x += (cible.x - p.x) * 0.1; p.y += (cible.y - p.y) * 0.1; p.k += (cible.k - p.k) * 0.1;
                var ang = (coup * 34 - 14) * (1 + 0.15 * ((id * 7) % 3)), pas = Math.sin(tt * 3 + id) * 0;
                noeuds.lutin[id].setAttribute('transform', 'translate(' + p.x.toFixed(1) + ' ' + p.y.toFixed(1) + ') scale(' + (p.k * 1.2).toFixed(3) + ')');
                noeuds.rg[id].setAttribute('transform', 'translate(-12 -9) rotate(' + (ang + pas).toFixed(1) + ')');
                noeuds.rd[id].setAttribute('transform', 'translate(12 -9) rotate(' + (-ang - pas).toFixed(1) + ')');
            }

            // lutins à l'eau
            for (var c = e.chutes.length - 1; c >= 0; c--) {
                var f = e.chutes[c], t = (maintenant - f.t0) / 1000;
                var yEau = f.y + 10 * f.k;
                var vol = 0.5, x2 = f.x + f.dir * 78 * Math.min(t, vol), y2;
                if (t < vol) { y2 = f.y - 70 * t + 330 * t * t; if (y2 > yEau) { y2 = yEau; } }
                else {
                    if (!f.splash) { f.splash = true; eclaboussure(x2, yEau, maintenant); }
                    y2 = yEau + (t - vol) * 26 + Math.sin(t * 7) * 2;
                    x2 += f.dir * (t - vol) * 8;
                }
                f.g.setAttribute('transform', 'translate(' + x2.toFixed(1) + ' ' + y2.toFixed(1) + ') rotate(' + (f.dir * Math.min(t, 1.2) * 150).toFixed(0) + ') scale(' + f.k.toFixed(3) + ')');
                f.g.setAttribute('opacity', Math.max(0, Math.min(1, 2.6 - t)).toFixed(2));
                if (t > 2.6) { if (f.g.parentNode) { f.g.parentNode.removeChild(f.g); } e.chutes.splice(c, 1); }
            }
            for (var q = e.eclats.length - 1; q >= 0; q--) {
                var x = e.eclats[q], a = (maintenant - x.t0) / 1000;
                if (a > 0.8) { if (x.g.parentNode) { x.g.parentNode.removeChild(x.g); } e.eclats.splice(q, 1); continue; }
                x.an.setAttribute('cx', x.x); x.an.setAttribute('cy', x.y);
                x.an.setAttribute('rx', (4 + a * 60).toFixed(1)); x.an.setAttribute('ry', (1.5 + a * 16).toFixed(1));
                x.an.setAttribute('opacity', (1 - a / 0.8).toFixed(2));
                x.gouttes.forEach(function (gt) {
                    gt.c.setAttribute('cx', (x.x + gt.vx * a).toFixed(1));
                    gt.c.setAttribute('cy', (x.y + gt.vy * a + 240 * a * a).toFixed(1));
                    gt.c.setAttribute('opacity', (1 - a / 0.8).toFixed(2));
                });
            }

            rempli.style.width = Math.max(0, Math.min(100, s.pos / s.p.distance * 100)).toFixed(1) + '%';
            if (compteur.dataset.n !== String(n)) { compteur.dataset.n = String(n); compteur.textContent = '🧝 ' + n; }
        }

        /* --- Boucle à pas fixe --- */
        var PAS_MS = 1000 / 60;
        function boucle(maintenant) {
            if (!e.actif) { return; }
            e.raf = requestAnimationFrame(boucle);
            var dt = Math.min(100, maintenant - e.dernier);
            e.dernier = maintenant;
            if (e.lance && !e.fini) {
                e.acc += dt;
                while (e.acc >= PAS_MS && !e.fini) {
                    e.acc -= PAS_MS;
                    var s = e.sim, imp = false;
                    if (e.attente) {
                        e.attente = false;
                        if (s.T - e.dernierTick >= C.ecart) {         // écart minimum en ticks, comme le serveur
                            imp = true;
                            e.dernierTick = s.T;
                            e.impulsions.push(s.T);
                            e.derniereRame = maintenant;
                            bouton.classList.add('bq-presse');
                            setTimeout(function () { bouton.classList.remove('bq-presse'); }, 90);
                        }
                    }
                    var tombes = s.pas(imp);
                    for (var z = 0; z < tombes; z++) { faireTomber(s.iChute - tombes + z, taille(C, s.T) / 1000, maintenant); }
                    if (s.arrivee || s.coule) { e.tFin = maintenant; terminerPartie(); }
                }
            }
            dessiner(maintenant);
        }
        dessiner(performance.now());
        e.raf = requestAnimationFrame(function (t) { e.dernier = t; e.raf = requestAnimationFrame(boucle); });
    }

    global.AventJeux.barque = {
        demarrer: demarrer,
        arreter: function () {
            var charge = { impulsions: [] };
            if (etat) {
                charge = { impulsions: etat.impulsions.slice() };
                etat.envoye = true;
            }
            nettoyer();
            return charge;
        },
        // exposés pour les tests Node (aucun usage par le jeu)
        _sim: { Sim: Sim, simuler: simuler, score: score, taille: taille }
    };
    if (typeof module !== 'undefined') { module.exports = global.AventJeux.barque; }
}());
