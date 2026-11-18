/* Jeu « Père Noël Turbo-Hotte » — module navigateur. Voir avent/README.md pour le contrat.
 *
 * Deux blocs :
 *  1. SIMULATION : miroir exact de server.php (entiers uniquement). Toute modification doit être
 *     répercutée dans server.php, puis AVENT_PNT_VERSION incrémenté des deux côtés.
 *  2. RENDU / ENTRÉES : canvas 360×300 logiques, boucle à pas fixe 60 ticks/s, aucun son.
 * Le navigateur n'envoie que le journal des entrées ; le score est recalculé par le serveur. */
(function () {
    'use strict';
    window.AventJeux = window.AventJeux || {};

    /* =====================================================================
     * 1. SIMULATION (identique à server.php)
     * ===================================================================== */
    var TICKS = 5400, SC = 64, PX = 80, SOL = 272, HW = 10, HH = 13;
    var YMIN = 19 * SC, YMAX = 259 * SC, VHAUT = 352, VBAS = 416;
    var COEURS = 3, INVUL = 120, CAP = 40, MAX_ENTREES = 1500, CROW_V = 120, ARC = 36;
    var W = 360, H = 300;

    function div(a, b) { return Math.trunc(a / b); }

    function Rng(graine) {
        this.x = (graine >>> 0) === 0 ? 0x9E3779B9 : (graine >>> 0);
    }
    Rng.prototype.suivant = function () {
        var x = this.x;
        x = (x ^ (x << 13)) >>> 0;
        x = (x ^ (x >>> 17)) >>> 0;
        x = (x ^ (x << 5)) >>> 0;
        this.x = x;
        return x;
    };
    Rng.prototype.rnd = function (n) { return this.suivant() % n; };

    function defilement(p) {
        var unites = [0], px = [0], vd = p.vitesse_depart, vf = p.vitesse_fin;
        for (var t = 0; t <= TICKS; t++) {
            unites[t + 1] = unites[t] + vd + div((vf - vd) * t, TICKS);
            px[t + 1] = div(unites[t + 1], SC);
        }
        return { unites: unites, px: px };
    }

    function premierTick(px, v) {
        var bas = 0, haut = TICKS + 2;
        while (bas < haut) {
            var m = div(bas + haut, 2);
            if (px[m] >= v) { haut = m; } else { bas = m + 1; }
        }
        return bas;
    }

    function tri(p) { return p < 60 ? p : 120 - p; }

    function niveau(p, graine, px) {
        var rng = new Rng(graine), objets = [], id = 0;
        function ajouter(o) { o.id = id++; objets.push(o); }
        function fixe(o, xmin, xmax) {
            var t0 = premierTick(px, xmin - PX - 20);
            var t1 = premierTick(px, xmax - PX + 21) - 1;
            if (t0 > TICKS) { return; }
            o.t0 = t0; o.t1 = t1; ajouter(o);
        }
        function cadeau(xw, y) {
            y = Math.max(28, Math.min(250, y));
            fixe({ k: 'g', xw: xw, y: y, pris: 0 }, xw, xw);
        }
        function stalactite(xw, len) { fixe({ k: 'i', xw: xw, len: len }, xw, xw); }
        function guirlande(x1, y1, x2, y2, ph) {
            fixe({ k: 'a', x1: x1, y1: y1, x2: x2, y2: y2, ph: ph }, Math.min(x1, x2), Math.max(x1, x2));
        }
        function corbeau(ta, y0, ph) {
            ajouter({ k: 'c', ta: ta, y0: y0, ph: ph, t0: Math.max(0, ta - 14), t1: ta + 14 });
        }
        function boule(ts, dt, yt) {
            var ta = ts + dt;
            ajouter({ k: 's', ts: ts, dt: dt, yt: yt, ta: ta, t0: Math.max(0, ta - 10), t1: ta + 10 });
        }

        var onde = [0, 14, 24, 28, 24, 14, 0, -14, -24, -28, -24, -14];
        var arbre = ['...X...', '..XXX..', '.XXXXX.', 'XXXXXXX', '...X...'];
        var coeur = ['.XX.XX.', 'XXXXXXX', 'XXXXXXX', '.XXXXX.', '..XXX..', '...X...'];
        var densite = p.densite, probaObstacle = 20 + div(densite * 6, 10), ta = 240;
        var i, n, x, y, y1, y2, dx, dy, ya, h, m, g, c, r, col, ancre, largeur, ecart, suivant;

        function motif(grille, c0) {
            for (r = 0; r < grille.length; r++) {
                for (col = 0; col < 7; col++) {
                    if (grille[r].charAt(col) === 'X') { cadeau(ancre + 20 * col, c0 + 20 * r); }
                }
            }
        }

        while (ta < TICKS - 60) {
            ancre = px[ta] + PX;
            largeur = 0;
            if (rng.rnd(100) < probaObstacle) {
                if (ta < 900) {
                    m = rng.rnd(3);
                    h = m === 0 ? 10 : (m === 1 ? 65 : 75);
                } else {
                    h = rng.rnd(100);
                }
                if (h < 25) {
                    n = 1 + rng.rnd(3);
                    var derniere = 0;
                    for (i = 0; i < n; i++) {
                        dx = i * 70 + rng.rnd(20);
                        derniere = 60 + rng.rnd(111);
                        stalactite(ancre + dx, derniere);
                    }
                    var yc = Math.min(SOL - 30, derniere + 55);
                    var gx = ancre + (n - 1) * 70 - 24;
                    for (i = 0; i < 4; i++) { cadeau(gx + 24 * i, yc); }
                    largeur = n * 70;
                } else if (h < 45) {
                    x = ancre + rng.rnd(30);
                    y1 = 50 + rng.rnd(81);
                    y2 = Math.min(268, y1 + 100 + rng.rnd(40));
                    guirlande(x, y1, x, y2, rng.rnd(96));
                    for (i = 0; i < 5; i++) { cadeau(x - 48 + 24 * i, y1 - 34); }
                    largeur = 60;
                } else if (h < 60) {
                    dx = 70 + rng.rnd(41);
                    dy = 90 + rng.rnd(61);
                    ya = 40 + rng.rnd(80);
                    if (rng.rnd(2) === 0) { y1 = ya; y2 = Math.min(268, ya + dy); }
                    else { y1 = Math.min(268, ya + dy); y2 = ya; }
                    guirlande(ancre, y1, ancre + dx, y2, rng.rnd(96));
                    for (i = 0; i < 3; i++) { cadeau(ancre + dx + 30 + 24 * i, div(y1 + y2, 2)); }
                    largeur = dx + 40;
                } else if (h < 70) {
                    var longueur = 100 + rng.rnd(71);
                    y = 60 + rng.rnd(171);
                    guirlande(ancre, y, ancre + longueur, y, rng.rnd(96));
                    var yg = y >= 90 ? y - 32 : y + 32;
                    for (i = 0; i < 4; i++) { cadeau(ancre + div(longueur * (i + 1), 5), yg); }
                    largeur = longueur;
                } else if (h < 85) {
                    n = 1 + rng.rnd(3);
                    for (i = 0; i < n; i++) {
                        var cy0 = 50 + rng.rnd(191);
                        corbeau(ta + 22 * i, cy0, rng.rnd(120));
                    }
                    largeur = 40 * n + 60;
                } else {
                    n = 2 + rng.rnd(3);
                    for (i = 0; i < n; i++) {
                        var dt = 60 + rng.rnd(21);
                        boule(ta + 16 * i - dt, dt, 40 + rng.rnd(211));
                    }
                    largeur = 30 * n + 80;
                }
            } else {
                g = rng.rnd(100);
                if (g < 30) {
                    n = 4 + rng.rnd(4);
                    y = 60 + rng.rnd(171);
                    for (i = 0; i < n; i++) { cadeau(ancre + 24 * i, y); }
                    largeur = 24 * n;
                } else if (g < 60) {
                    n = 6 + rng.rnd(4);
                    c = 100 + rng.rnd(101);
                    for (i = 0; i < n; i++) { cadeau(ancre + 22 * i, c + onde[i % 12]); }
                    largeur = 22 * n;
                } else if (g < 75) {
                    n = 5 + rng.rnd(3);
                    var y0 = 50 + rng.rnd(100);
                    var montant = rng.rnd(2);
                    var pas = 14 + rng.rnd(6);
                    for (i = 0; i < n; i++) {
                        cadeau(ancre + 24 * i, montant === 1 ? y0 + i * pas : y0 + (n - 1 - i) * pas);
                    }
                    largeur = 24 * n;
                } else if (g < 88) {
                    c = 40 + rng.rnd(111);
                    motif(arbre, c);
                    largeur = 140;
                } else {
                    c = 40 + rng.rnd(111);
                    motif(coeur, c);
                    largeur = 140;
                }
            }
            ecart = 260 - div(densite * 9, 10) + rng.rnd(120);
            suivant = premierTick(px, ancre + largeur + ecart - PX);
            ta = suivant <= ta ? ta + 1 : suivant;
        }
        objets.sort(function (a, b) { return a.t0 - b.t0 || a.id - b.id; });
        return objets;
    }

    function presDuSegment(pxx, pyy, ax, ay, bx, by) {
        var r2 = 196, dx = bx - ax, dy = by - ay, den = dx * dx + dy * dy;
        var wx = pxx - ax, wy = pyy - ay, scal = wx * dx + wy * dy;
        if (scal <= 0) { return wx * wx + wy * wy <= r2; }
        if (scal >= den) { var ux = pxx - bx, uy = pyy - by; return ux * ux + uy * uy <= r2; }
        var croix = wx * dy - wy * dx;
        return croix * croix <= r2 * den;
    }

    function garnitureAllumee(tick, ph) { return ((tick + ph) % 96) >= 56; }

    /** Simulation incrémentale : un appel de pas(presse) = un tick. */
    function Sim(p, graine) {
        this.p = p;
        this.px = defilement(p).px;
        this.unites = defilement(p).unites;
        this.objets = niveau(p, graine, this.px);
        this.y = 130 * SC; this.vy = 0;
        this.coeurs = COEURS; this.invul = 0; this.cadeaux = 0;
        this.T = 0; this.prochain = 0; this.actifs = []; this.mort = false;
        this.yAvant = this.y;
    }
    /** Renvoie { cadeau: [objets pris], touche: bool, mort: bool }. */
    Sim.prototype.pas = function (presse) {
        var p = this.p, ev = { pris: [], touche: false, mort: false };
        this.yAvant = this.y;
        if (presse) { this.vy -= p.poussee; } else { this.vy += p.gravite; }
        if (this.vy < -VHAUT) { this.vy = -VHAUT; }
        if (this.vy > VBAS) { this.vy = VBAS; }
        this.y += this.vy;
        if (this.y < YMIN) { this.y = YMIN; if (this.vy < 0) { this.vy = 0; } }
        if (this.y > YMAX) { this.y = YMAX; if (this.vy > 0) { this.vy = 0; } }

        var T = this.T + 1, spx = this.px[T], ppy = div(this.y, SC), objets = this.objets, touche = false;
        this.T = T;
        while (this.prochain < objets.length && objets[this.prochain].t0 <= T) { this.actifs.push(this.prochain++); }
        var reste = [];
        for (var a = 0; a < this.actifs.length; a++) {
            var o = objets[this.actifs[a]];
            if (o.t1 < T) { continue; }
            reste.push(this.actifs[a]);
            var sx;
            switch (o.k) {
                case 'g':
                    if (o.pris === 0) {
                        sx = o.xw - spx;
                        if (Math.abs(sx - PX) <= 7 + HW && Math.abs(o.y - ppy) <= 7 + HH) {
                            o.pris = 1; this.cadeaux++; ev.pris.push(o);
                        }
                    }
                    break;
                case 'i':
                    sx = o.xw - spx;
                    if (Math.abs(sx - PX) <= 6 + HW && ppy - HH <= o.len - 5) { touche = true; }
                    break;
                case 'a':
                    if (garnitureAllumee(T, o.ph) && presDuSegment(PX, ppy, o.x1 - spx, o.y1, o.x2 - spx, o.y2)) { touche = true; }
                    break;
                case 'c':
                    var pc = Sim.posCorbeau(this, o, T);
                    if (Math.abs(pc.x - PX) <= 11 + HW && Math.abs(pc.y - ppy) <= 8 + HH) { touche = true; }
                    break;
                case 's':
                    var pb = Sim.posBoule(o, T);
                    if (Math.abs(pb.x - PX) <= 7 + HW && Math.abs(pb.y - ppy) <= 7 + HH) { touche = true; }
                    break;
            }
        }
        this.actifs = reste;
        if (this.invul > 0) {
            this.invul--;
        } else if (touche) {
            this.coeurs--; this.invul = INVUL; ev.touche = true;
            if (this.coeurs <= 0) { this.mort = true; ev.mort = true; }
        }
        return ev;
    };
    Sim.posCorbeau = function (sim, o, T) {
        return {
            x: PX + (sim.px[o.ta] - sim.px[T]) - div(CROW_V * (T - o.ta), SC),
            y: o.y0 + tri(((T + o.ph) * 4) % 120) - 30
        };
    };
    Sim.posBoule = function (o, T) {
        var u = T - o.ts;
        return {
            x: (W + 16) - div(((W + 16) - PX) * u, o.dt),
            y: 260 + div((o.yt - 260) * u, o.dt) - div(4 * ARC * u * (o.dt - u), o.dt * o.dt)
        };
    };

    function score(cadeaux, ticks) {
        return div(315 * Math.min(cadeaux, CAP) + Math.min(ticks, TICKS) + 90, 180);
    }

    /** Rejoue un journal d'entrées (pour les tests Node). */
    function simuler(p, graine, entrees, ticksMax) {
        var sim = new Sim(p, graine), idx = 0, presse = false;
        ticksMax = Math.min(ticksMax, TICKS);
        for (var t = 0; t < ticksMax; t++) {
            while (idx < entrees.length && entrees[idx] <= t) { presse = !presse; idx++; }
            sim.pas(presse);
            if (sim.mort) { break; }
        }
        return { ticks: sim.T, cadeaux: sim.cadeaux, coeurs: Math.max(0, sim.coeurs), mort: sim.mort };
    }

    /* =====================================================================
     * 2. RENDU ET ENTRÉES
     * ===================================================================== */
    var etat = null;

    function nettoyer() {
        if (!etat) { return; }
        var e = etat;
        e.actif = false;
        if (e.raf) { cancelAnimationFrame(e.raf); }
        e.ecouteurs.forEach(function (l) { l[0].removeEventListener(l[1], l[2], l[3]); });
        if (e.racine && e.racine.parentNode) { e.racine.parentNode.removeChild(e.racine); }
    }

    function hash(n) {
        n = (n ^ 61) ^ (n >>> 16); n = (n + (n << 3)) | 0; n = n ^ (n >>> 4);
        n = Math.imul(n, 0x27d4eb2d); n = n ^ (n >>> 15);
        return (n >>> 0) / 4294967296;
    }

    function demarrer(conteneur, config, api) {
        nettoyer();
        var p = config.parametres;
        var racine = document.createElement('div');
        racine.className = 'pnt';
        racine.innerHTML =
            '<canvas class="pnt-canvas" width="720" height="600" aria-label="Père Noël en jetpack : maintiens pour monter, relâche pour descendre"></canvas>' +
            '<div class="pnt-ecran pnt-accueil">' +
            '<h3>Père Noël Turbo-Hotte</h3>' +
            '<p class="pnt-slogan">Sa hotte a été trafiquée. Les rennes ont démissionné. Les chamois sont jaloux.</p>' +
            '<p class="pnt-regles">Maintiens ton doigt (ou la barre Espace) pour <b>monter</b>, relâche pour <b>descendre</b>.<br>' +
            'Ramasse les cadeaux, évite stalactites, guirlandes, corbeaux et boules de neige. 3 pulls moches, pas un de plus.</p>' +
            '<button type="button" class="bouton pnt-go">Décoller</button></div>' +
            '<div class="pnt-ecran pnt-fin" hidden></div>';
        conteneur.appendChild(racine);
        var canvas = racine.querySelector('canvas');
        var ctx = canvas.getContext('2d');
        var accueil = racine.querySelector('.pnt-accueil');
        var finEcran = racine.querySelector('.pnt-fin');

        var e = etat = {
            actif: true, raf: 0, racine: racine, ecouteurs: [],
            sim: new Sim(p, config.graine), entrees: [], logge: false, voulu: false,
            lance: false, fini: false, envoye: false, acc: 0, dernier: 0,
            flash: 0, particules: [], accident: 0, finTimer: 0, alpha: 0, bannieres: []
        };
        function ecoute(cible, type, fn, opt) { cible.addEventListener(type, fn, opt); e.ecouteurs.push([cible, type, fn, opt]); }

        function payload() { return { entrees: e.entrees.slice(), ticks: e.sim.T }; }

        /* --- Entrées --- */
        function poser(ev) { if (ev.cancelable) { ev.preventDefault(); } e.voulu = true; }
        function lever(ev) { if (ev.cancelable) { ev.preventDefault(); } e.voulu = false; }
        ecoute(canvas, 'pointerdown', function (ev) {
            poser(ev);
            try { canvas.setPointerCapture(ev.pointerId); } catch (x) { /* ignoré */ }
        }, { passive: false });
        ecoute(canvas, 'pointerup', lever, { passive: false });
        ecoute(canvas, 'pointercancel', lever, { passive: false });
        ecoute(canvas, 'lostpointercapture', function () { e.voulu = false; });
        ecoute(canvas, 'contextmenu', function (ev) { ev.preventDefault(); });
        ecoute(canvas, 'touchstart', function (ev) { ev.preventDefault(); }, { passive: false });
        ecoute(window, 'keydown', function (ev) {
            if (ev.code === 'Space' || ev.code === 'ArrowUp') { if (e.lance && !e.fini) { ev.preventDefault(); } e.voulu = true; }
        });
        ecoute(window, 'keyup', function (ev) {
            if (ev.code === 'Space' || ev.code === 'ArrowUp') { e.voulu = false; }
        });
        ecoute(window, 'blur', function () { e.voulu = false; });
        ecoute(document, 'visibilitychange', function () { if (document.hidden) { e.voulu = false; } });

        var bouton = racine.querySelector('.pnt-go');
        ecoute(bouton, 'click', function () {
            accueil.hidden = true;
            e.lance = true;
            e.dernier = performance.now();
            canvas.focus();
        });

        /* --- Fin de partie --- */
        function terminerPartie(cause) {
            if (e.fini) { return; }
            e.fini = true;
            e.cause = cause;
            e.finTimer = 0;
        }
        function afficherFin() {
            var s = e.sim, pts = score(s.cadeaux, s.T);
            var secondes = Math.floor(s.T / 60);
            finEcran.hidden = false;
            finEcran.innerHTML = '<h3>' + (e.cause === 'temps' ? 'Livraison terminée !' : 'Atterrissage en urgence') + '</h3>' +
                '<p class="pnt-bilan">🎁 ' + s.cadeaux + ' cadeau' + (s.cadeaux > 1 ? 'x' : '') + ' · ⏱ ' + secondes + ' s</p>' +
                '<p class="pnt-slogan">Le jury calcule ton score…</p>';
            if (!e.envoye) {
                e.envoye = true;
                setTimeout(function () { if (e.actif) { api.terminer(payload()); } }, 900);
            }
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
                    // journal : l'état voulu au début du tick
                    if (e.voulu !== e.logge && e.entrees.length < MAX_ENTREES - 5) {
                        e.entrees.push(e.sim.T);
                        e.logge = e.voulu;
                    }
                    var ev = e.sim.pas(e.logge);
                    evenements(ev);
                    if (e.sim.mort) { terminerPartie('mort'); }
                    else if (e.sim.T >= TICKS) { terminerPartie('temps'); }
                }
                e.alpha = e.acc / PAS_MS;
            } else if (e.fini) {
                e.finTimer += dt;
                e.accident += dt;
                if (e.finTimer > (e.cause === 'mort' ? 1100 : 600) && finEcran.hidden) { afficherFin(); }
            }
            dessiner(maintenant);
        }

        function evenements(ev) {
            var s = e.sim, ppx = PX, ppy = div(s.y, SC);
            if (ev.pris.length) {
                for (var i = 0; i < ev.pris.length; i++) {
                    for (var k = 0; k < 6; k++) {
                        e.particules.push({ x: ppx + 6, y: ppy, vx: (Math.random() - 0.3) * 2.4, vy: (Math.random() - 0.7) * 2.4, vie: 24, c: k % 2 ? '#ffd45e' : '#fff' });
                    }
                }
            }
            if (ev.touche) { e.flash = 14; }
        }

        /* --- Dessin --- */
        function redimensionner() {
            var largeur = Math.max(1, racine.clientWidth);
            var ratio = Math.min(2, window.devicePixelRatio || 1);
            var cw = Math.round(largeur * ratio), ch = Math.round(largeur * ratio * H / W);
            if (canvas.width !== cw) { canvas.width = cw; canvas.height = ch; }
        }
        ecoute(window, 'resize', redimensionner);
        redimensionner();

        function scrollFlottant() {
            var s = e.sim, T = s.T;
            if (T === 0) { return 0; }
            var a = e.fini ? 0 : e.alpha;
            var u0 = s.unites[T], u1 = s.unites[T + 1];
            return (u0 + (u1 - u0) * a) / SC;
        }

        function dessinerMontagnes(sc) {
            var off = sc * 0.1;
            ctx.fillStyle = '#9fb4d6';
            ctx.beginPath(); ctx.moveTo(0, 230);
            for (var x = 0; x <= W; x += 6) {
                var wx = x + off, hh = 70 + 38 * Math.sin(wx / 53) + 26 * Math.sin(wx / 27 + 1) + 14 * Math.sin(wx / 11);
                ctx.lineTo(x, 230 - hh * 0.9);
            }
            ctx.lineTo(W, 230); ctx.closePath(); ctx.fill();
            ctx.fillStyle = '#c9d8ee';
            ctx.beginPath(); ctx.moveTo(0, 250);
            for (x = 0; x <= W; x += 6) {
                wx = x + sc * 0.18;
                hh = 46 + 26 * Math.sin(wx / 41 + 2) + 14 * Math.sin(wx / 17);
                ctx.lineTo(x, 250 - hh);
            }
            ctx.lineTo(W, 250); ctx.closePath(); ctx.fill();
        }

        function pin(x, base, h, ombre) {
            ctx.fillStyle = ombre ? '#2c5a4a' : '#1f4a3c';
            for (var i = 0; i < 3; i++) {
                var y = base - h + i * h * 0.28, l = h * (0.28 + i * 0.12);
                ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x - l, y + h * 0.4); ctx.lineTo(x + l, y + h * 0.4); ctx.closePath(); ctx.fill();
                ctx.fillStyle = '#f4f8ff';
                ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x - l * 0.55, y + h * 0.22); ctx.lineTo(x + l * 0.55, y + h * 0.22); ctx.closePath(); ctx.fill();
                ctx.fillStyle = ombre ? '#2c5a4a' : '#1f4a3c';
            }
            ctx.fillRect(x - 2, base - 6, 4, 8);
        }

        function chalet(x, base) {
            ctx.fillStyle = '#7a4a2b'; ctx.fillRect(x, base - 26, 46, 26);
            ctx.fillStyle = '#5a3419'; ctx.fillRect(x + 4, base - 22, 38, 2);
            ctx.fillStyle = '#e9eef8';
            ctx.beginPath(); ctx.moveTo(x - 6, base - 24); ctx.lineTo(x + 23, base - 46); ctx.lineTo(x + 52, base - 24); ctx.closePath(); ctx.fill();
            ctx.fillStyle = '#ffd36b'; ctx.fillRect(x + 8, base - 18, 9, 9); ctx.fillRect(x + 28, base - 18, 9, 9);
            ctx.fillStyle = '#5a3419'; ctx.fillRect(x + 36, base - 40, 5, 10);
        }

        function dessinerMilieu(sc) {
            var pas = 130, off = sc * 0.25, i0 = Math.floor(off / pas);
            for (var i = i0 - 1; i <= i0 + 4; i++) {
                var x = i * pas - off, r = hash(i * 7 + 3);
                if (r < 0.35) { chalet(x + 20, 262); }
                else { pin(x + 20, 262, 62 + r * 20, true); pin(x + 58, 262, 46 + r * 12, true); }
            }
        }

        function dessinerRemontee(sc) {
            var off = sc * 0.5, pas = 220, i0 = Math.floor(off / pas);
            ctx.strokeStyle = '#3b3f4d'; ctx.lineWidth = 1.5;
            ctx.beginPath(); ctx.moveTo(0, 36); ctx.lineTo(W, 44); ctx.stroke();
            for (var i = i0 - 1; i <= i0 + 3; i++) {
                var x = i * pas - off + 40;
                ctx.fillStyle = '#3b3f4d'; ctx.fillRect(x - 2, 30, 4, 242);
                ctx.fillRect(x - 10, 29, 20, 3);
                var cx = x + 110;
                ctx.strokeStyle = '#3b3f4d'; ctx.beginPath(); ctx.moveTo(cx, 40); ctx.lineTo(cx, 58); ctx.stroke();
                ctx.fillStyle = i % 2 ? '#c8493c' : '#d9a93a'; ctx.fillRect(cx - 9, 58, 18, 13);
                ctx.fillStyle = '#e9f3ff'; ctx.fillRect(cx - 6, 61, 12, 6);
            }
        }

        function dessinerStalactite(x, len) {
            ctx.fillStyle = '#bfe6f7';
            ctx.beginPath(); ctx.moveTo(x - 7, 8); ctx.lineTo(x + 7, 8); ctx.lineTo(x + 1, len); ctx.lineTo(x - 1, len); ctx.closePath(); ctx.fill();
            ctx.fillStyle = '#eefaff';
            ctx.beginPath(); ctx.moveTo(x - 4, 8); ctx.lineTo(x - 1, 8); ctx.lineTo(x - 0.5, len - 8); ctx.closePath(); ctx.fill();
        }

        function dessinerGuirlande(o, spx, T) {
            var x1 = o.x1 - spx, x2 = o.x2 - spx, on = garnitureAllumee(T, o.ph), ph = (T + o.ph) % 96;
            var avert = ph >= 40 && ph < 56;
            ctx.lineCap = 'round';
            ctx.strokeStyle = on ? '#fff6a8' : (avert && (T % 8 < 4) ? '#ffb4a8' : '#4a5b4e');
            ctx.lineWidth = on ? 4 : 2;
            if (on) { ctx.shadowColor = '#ffe45e'; ctx.shadowBlur = 12; }
            ctx.beginPath(); ctx.moveTo(x1, o.y1); ctx.lineTo(x2, o.y2); ctx.stroke();
            ctx.shadowBlur = 0;
            var n = Math.max(2, Math.round(Math.hypot(x2 - x1, o.y2 - o.y1) / 16));
            for (var i = 0; i <= n; i++) {
                var f = i / n, bx = x1 + (x2 - x1) * f, by = o.y1 + (o.y2 - o.y1) * f;
                ctx.fillStyle = on ? (i % 2 ? '#ff7a6b' : '#7ad7ff') : '#58665c';
                ctx.beginPath(); ctx.arc(bx, by, 3.2, 0, 6.283); ctx.fill();
            }
            // pylônes
            ctx.fillStyle = '#6b4a2e';
            ctx.beginPath(); ctx.arc(x1, o.y1, 5, 0, 6.283); ctx.arc(x2, o.y2, 5, 0, 6.283); ctx.fill();
        }

        function dessinerCadeau(x, y, t) {
            var rebond = Math.sin(t / 220 + x * 0.05) * 1.5;
            y += rebond;
            ctx.fillStyle = '#d6453d'; ctx.fillRect(x - 6, y - 6, 12, 12);
            ctx.fillStyle = '#ffd45e'; ctx.fillRect(x - 1.2, y - 6, 2.4, 12); ctx.fillRect(x - 6, y - 1.2, 12, 2.4);
            ctx.beginPath(); ctx.arc(x - 2.5, y - 7.5, 2.6, 0, 6.283); ctx.arc(x + 2.5, y - 7.5, 2.6, 0, 6.283); ctx.fill();
        }

        function dessinerCorbeau(x, y, t) {
            var aile = Math.sin(t / 60) * 8;
            ctx.fillStyle = '#1c1c26';
            ctx.beginPath(); ctx.ellipse(x, y, 11, 7, 0, 0, 6.283); ctx.fill();
            ctx.beginPath(); ctx.moveTo(x - 2, y - 2); ctx.lineTo(x + 4, y - 14 - aile); ctx.lineTo(x + 9, y - 2); ctx.closePath(); ctx.fill();
            ctx.beginPath(); ctx.moveTo(x + 9, y + 1); ctx.lineTo(x + 20, y + 3); ctx.lineTo(x + 9, y + 5); ctx.closePath(); ctx.fill();
            ctx.fillStyle = '#e8a02b';
            ctx.beginPath(); ctx.moveTo(x - 9, y - 1); ctx.lineTo(x - 17, y + 1); ctx.lineTo(x - 9, y + 4); ctx.closePath(); ctx.fill();
            ctx.fillStyle = '#fff'; ctx.beginPath(); ctx.arc(x - 5, y - 2, 2.4, 0, 6.283); ctx.fill();
            ctx.fillStyle = '#000'; ctx.beginPath(); ctx.arc(x - 5.6, y - 2, 1.1, 0, 6.283); ctx.fill();
            ctx.strokeStyle = '#000'; ctx.lineWidth = 1.4;
            ctx.beginPath(); ctx.moveTo(x - 9, y - 7); ctx.lineTo(x - 2, y - 4); ctx.stroke();
        }

        function dessinerBoule(x, y) {
            ctx.fillStyle = '#fff'; ctx.strokeStyle = '#b8c9e6'; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.arc(x, y, 7, 0, 6.283); ctx.fill(); ctx.stroke();
            ctx.fillStyle = '#d6e2f5'; ctx.beginPath(); ctx.arc(x - 2, y + 2, 2.4, 0, 6.283); ctx.fill();
        }

        function dessinerPere(x, y, t, crash) {
            ctx.save();
            ctx.translate(x, y);
            if (crash) { ctx.rotate(crash * 0.012); }
            // flamme
            var fl = 7 + (t % 100 < 50 ? 4 : 0) + (e.logge && !e.fini ? 6 : 0);
            ctx.fillStyle = '#ff9d2e';
            ctx.beginPath(); ctx.moveTo(-14, 4); ctx.lineTo(-14 - fl, 8); ctx.lineTo(-14, 12); ctx.closePath(); ctx.fill();
            ctx.fillStyle = '#fff2a6';
            ctx.beginPath(); ctx.moveTo(-14, 6); ctx.lineTo(-14 - fl * 0.55, 8); ctx.lineTo(-14, 10); ctx.closePath(); ctx.fill();
            // hotte-réacteur
            ctx.fillStyle = '#8a8d99'; ctx.fillRect(-17, -4, 9, 16);
            ctx.fillStyle = '#c8493c'; ctx.fillRect(-15, -9, 9, 8);
            // corps
            ctx.fillStyle = '#d6453d'; ctx.fillRect(-8, -2, 16, 15);
            ctx.fillStyle = '#fff'; ctx.fillRect(-8, 10, 16, 3);
            ctx.fillStyle = '#2a2a33'; ctx.fillRect(-8, 6, 16, 2.5);
            // tête
            ctx.fillStyle = '#f3c9a0'; ctx.beginPath(); ctx.arc(2, -8, 6, 0, 6.283); ctx.fill();
            ctx.fillStyle = '#fff'; ctx.beginPath(); ctx.arc(3, -3, 6.2, 0.1, 3.05); ctx.fill();
            ctx.fillStyle = '#d6453d';
            ctx.beginPath(); ctx.moveTo(-4, -11); ctx.lineTo(0, -21); ctx.lineTo(9, -11); ctx.closePath(); ctx.fill();
            ctx.fillStyle = '#fff'; ctx.fillRect(-4, -12, 14, 3);
            ctx.beginPath(); ctx.arc(0, -21, 2.6, 0, 6.283); ctx.fill();
            // lunettes d'aviateur
            ctx.fillStyle = '#2a2a33'; ctx.fillRect(2, -10, 7, 3.6);
            ctx.restore();
        }

        function dessinerCoeurs() {
            for (var i = 0; i < COEURS; i++) {
                var x = 12 + i * 22, y = 8, ok = i < e.sim.coeurs;
                ctx.globalAlpha = ok ? 1 : 0.28;
                ctx.fillStyle = ok ? '#2f7a4f' : '#555';
                ctx.fillRect(x, y + 2, 17, 14);
                ctx.fillRect(x - 4, y + 2, 5, 9); ctx.fillRect(x + 16, y + 2, 5, 9);
                ctx.fillStyle = ok ? '#d6453d' : '#777';
                ctx.fillRect(x + 2, y + 6, 13, 2); ctx.fillRect(x + 2, y + 11, 13, 2);
                ctx.fillStyle = '#fff';
                ctx.fillRect(x + 7, y + 3, 3, 3);
                ctx.globalAlpha = 1;
            }
        }

        function dessiner(maintenant) {
            var s = e.sim, T = s.T, ratio = canvas.width / W;
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            var sc = scrollFlottant();
            var spx = sc; // défilement flottant pour les objets fixes
            var g = ctx.createLinearGradient(0, 0, 0, H);
            g.addColorStop(0, '#16284f'); g.addColorStop(0.55, '#5b7bb5'); g.addColorStop(1, '#f2b88c');
            ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);
            // étoiles et lune
            ctx.fillStyle = '#fff';
            for (var i = 0; i < 24; i++) { ctx.globalAlpha = 0.4 + 0.5 * hash(i + 99); ctx.fillRect(hash(i) * W, hash(i + 50) * 120, 1.5, 1.5); }
            ctx.globalAlpha = 1;
            ctx.fillStyle = '#fff6d6'; ctx.beginPath(); ctx.arc(290, 52, 17, 0, 6.283); ctx.fill();

            dessinerMontagnes(sc);
            dessinerMilieu(sc);
            dessinerRemontee(sc);

            // sol
            ctx.fillStyle = '#f4f8ff'; ctx.fillRect(0, SOL, W, H - SOL);
            ctx.fillStyle = '#d3def0'; ctx.fillRect(0, SOL + 10, W, 2);
            // corniche
            ctx.fillStyle = '#e9f3ff'; ctx.fillRect(0, 0, W, 8);
            ctx.fillStyle = '#9fb4d6'; ctx.fillRect(0, 8, W, 3);

            // objets
            var objets = s.objets, pxScroll = sc;
            for (var a = 0; a < objets.length; a++) {
                var o = objets[a], sx;
                switch (o.k) {
                    case 'g':
                        sx = o.xw - pxScroll;
                        if (!o.pris && sx > -12 && sx < W + 12) { dessinerCadeau(sx, o.y, maintenant); }
                        break;
                    case 'i':
                        sx = o.xw - pxScroll;
                        if (sx > -12 && sx < W + 12) { dessinerStalactite(sx, o.len); }
                        break;
                    case 'a':
                        if (Math.max(o.x1, o.x2) - pxScroll > -12 && Math.min(o.x1, o.x2) - pxScroll < W + 12) { dessinerGuirlande(o, pxScroll, T); }
                        break;
                    case 'c':
                        if (T >= o.ta - 80 && T <= o.ta + 30) { var pc = Sim.posCorbeau(s, o, T); dessinerCorbeau(pc.x, pc.y, maintenant); }
                        break;
                    case 's':
                        if (T >= o.ts && T <= o.ta + 12) { var pb = Sim.posBoule(o, T); dessinerBoule(pb.x, pb.y); }
                        break;
                }
            }

            // particules
            e.particules = e.particules.filter(function (q) { return q.vie > 0; });
            e.particules.forEach(function (q) {
                q.x += q.vx; q.y += q.vy; q.vie--;
                ctx.globalAlpha = q.vie / 24; ctx.fillStyle = q.c; ctx.fillRect(q.x, q.y, 2.5, 2.5);
            });
            ctx.globalAlpha = 1;

            // joueur
            var yv = (s.yAvant + (s.y - s.yAvant) * (e.fini ? 1 : e.alpha)) / SC;
            if (e.fini && e.cause === 'mort') {
                var t2 = e.accident / 16;
                yv = Math.min(SOL - 6, yv + t2 * t2 * 0.18);
            }
            var clignote = s.invul > 0 && !e.fini && (Math.floor(s.invul / 6) % 2 === 0);
            if (!clignote) { dessinerPere(PX, yv, maintenant, e.fini && e.cause === 'mort' ? e.accident : 0); }

            if (e.flash > 0) { ctx.fillStyle = 'rgba(255,255,255,' + (e.flash / 28) + ')'; ctx.fillRect(0, 0, W, H); e.flash--; }

            // HUD
            dessinerCoeurs();
            ctx.fillStyle = 'rgba(20,24,40,.55)'; ctx.fillRect(W - 128, 6, 120, 22);
            ctx.fillStyle = '#fff'; ctx.font = 'bold 13px sans-serif'; ctx.textBaseline = 'middle';
            ctx.fillText('🎁 ' + s.cadeaux, W - 120, 17);
            ctx.textAlign = 'right'; ctx.fillText(Math.floor(T / 60) + ' s', W - 14, 17); ctx.textAlign = 'left';
        }

        e.raf = requestAnimationFrame(function (t) { e.dernier = t; e.raf = requestAnimationFrame(boucle); });
    }

    window.AventJeux.pere_noel_turbo = {
        demarrer: demarrer,
        arreter: function () {
            var charge = { entrees: [], ticks: 0 };
            if (etat) {
                charge = { entrees: etat.entrees.slice(), ticks: etat.sim.T };
                etat.envoye = true;
            }
            nettoyer();
            return charge;
        },
        // exposés pour les tests Node (aucun usage par le jeu)
        _sim: { Sim: Sim, simuler: simuler, score: score, niveau: niveau, defilement: defilement, Rng: Rng }
    };
    if (typeof module !== 'undefined') { module.exports = window.AventJeux.pere_noel_turbo; }
}());
