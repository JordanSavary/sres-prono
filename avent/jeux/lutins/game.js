/* Jeu « Les Lutins du Chalet » — module navigateur. Voir avent/README.md pour le contrat.
 *
 * La grille arrive avec l'action « demarrer » ; chaque lutin posé est vérifié par le serveur
 * (api.appeler('poser', {r, c}) → bon / mauvais). Le chrono affiché est indicatif : le temps
 * qui compte est celui du serveur. La solution n'est jamais envoyée au navigateur. */
(function () {
    'use strict';
    window.AventJeux = window.AventJeux || {};

    var CELLULE = 40;
    // Couleurs de Noël (écart de teinte vérifié) et motifs : le motif suffit à reconnaître une zone.
    var COULEURS = ['#d6453d', '#2f7a4f', '#e8b83a', '#8fd0ee', '#f3ece0', '#8e5bb5', '#ee8a2f', '#2cb5a4'];
    var MOTIFS = [
        '<path d="M-2 10 L10 -2 M-2 18 L18 -2 M6 18 L18 6" />',                 // rayures diagonales
        '<circle cx="3" cy="3" r="1.6" /><circle cx="11" cy="11" r="1.6" />',   // points
        '<path d="M0 0 H12 M0 0 V12" />',                                       // quadrillage
        '<path d="M0 3 H12 M0 9 H12" />',                                       // lignes horizontales
        '<path d="M6 2 V10 M2 6 H10" />',                                       // croix
        '<rect x="0" y="0" width="6" height="6" /><rect x="6" y="6" width="6" height="6" />', // damier
        '<path d="M3 0 V12 M9 0 V12" />',                                       // rayures verticales
        '<path d="M0 8 Q3 2 6 8 T12 8" />'                                      // vagues
    ];
    var REFUS = [
        'Ce lutin a refusé de travailler ici.',
        'Ce lutin a fait grève sur cette case.',
        'Le syndicat des lutins déclare cette case non conforme.',
        'Ce lutin dit que ça sent la fondue, mais pas ici.',
        'Ce lutin préfère faire la sieste ailleurs.',
        'Ce lutin a mangé son bonnet de dépit.',
        'Ce lutin réclame une autre case, avec vue.'
    ];
    var BONS = ['Lutin embauché !', 'Voilà un lutin motivé.', 'Celui-là a l\'air content.', 'Contrat signé.'];

    var etat = null;

    function nettoyer() {
        if (!etat) { return; }
        var e = etat;
        e.actif = false;
        if (e.minuteur) { clearInterval(e.minuteur); }
        if (e.pause) { clearTimeout(e.pause); }
        e.ecouteurs.forEach(function (l) { l[0].removeEventListener(l[1], l[2], l[3]); });
        if (e.racine && e.racine.parentNode) { e.racine.parentNode.removeChild(e.racine); }
        etat = null;
    }

    function defs() {
        var s = '<defs>';
        MOTIFS.forEach(function (m, i) {
            s += '<pattern id="lut-m' + i + '" width="12" height="12" patternUnits="userSpaceOnUse">' +
                '<g fill="rgba(0,0,0,.22)" stroke="rgba(0,0,0,.25)" stroke-width="1.6" stroke-linecap="round">' + m + '</g></pattern>';
        });
        // Lutin : visage, oreilles, bonnet vert à bande rouge, pompon doré. Contour blanc pour ressortir sur toutes les zones.
        s += '<symbol id="lut-elfe" viewBox="0 0 40 40">' +
            '<g paint-order="stroke" stroke="#fff" stroke-width="3" stroke-linejoin="round">' +
            '<circle cx="9.5" cy="26" r="3.4" fill="#f6d2ae"/><circle cx="30.5" cy="26" r="3.4" fill="#f6d2ae"/>' +
            '<circle cx="20" cy="26" r="9.6" fill="#f6d2ae"/>' +
            '<path d="M10.5 22 Q13 9 25 3.5 Q23 12 29.5 22 Z" fill="#1f8f4e"/>' +
            '<circle cx="25" cy="4.2" r="3.2" fill="#ffd45e"/></g>' +
            '<path d="M10.8 20.4 Q20 24 29.2 20.4 L29.5 22.6 Q20 26 10.5 22.6 Z" fill="#d6453d"/>' +
            '<circle cx="16.4" cy="27" r="1.4" fill="#2b2b2b"/><circle cx="23.6" cy="27" r="1.4" fill="#2b2b2b"/>' +
            '<path d="M16.2 31 Q20 34.4 23.8 31" fill="none" stroke="#7a3a2b" stroke-width="1.5" stroke-linecap="round"/>' +
            '<circle cx="13.6" cy="30" r="1.6" fill="#f4a0a0" opacity=".7"/><circle cx="26.4" cy="30" r="1.6" fill="#f4a0a0" opacity=".7"/>' +
            '</symbol>';
        // Bonnet de vie
        s += '<symbol id="lut-bonnet" viewBox="0 0 28 28">' +
            '<path d="M3 21 Q5 6 20 4 Q17 11 24 21 Z" fill="#d6453d" stroke="#8a2530" stroke-width="1" stroke-linejoin="round"/>' +
            '<rect x="2" y="20" width="23" height="6" rx="3" fill="#fff" stroke="#c9c2b4" stroke-width=".8"/>' +
            '<circle cx="21" cy="4.5" r="3.4" fill="#fff" stroke="#c9c2b4" stroke-width=".8"/></symbol>';
        return s + '</defs>';
    }

    function croix(r, c, refus) {
        var x = c * CELLULE, y = r * CELLULE, a = 11, b = CELLULE - 11;
        var d = 'M' + (x + a) + ' ' + (y + a) + ' L' + (x + b) + ' ' + (y + b) + ' M' + (x + b) + ' ' + (y + a) + ' L' + (x + a) + ' ' + (y + b);
        return '<path d="' + d + '" stroke="#fff" stroke-width="6.5" stroke-linecap="round" fill="none"/>' +
            '<path d="' + d + '" stroke="' + (refus ? '#b3202e' : '#2a2a33') + '" stroke-width="3.4" stroke-linecap="round" fill="none"/>';
    }

    function plateau(e) {
        var n = e.n, T = n * CELLULE, z = e.zones, r, c, s = '';
        s += '<svg class="lut-svg" viewBox="-3 -3 ' + (T + 6) + ' ' + (T + 6) + '" role="img" aria-label="Grille de ' + n + ' par ' + n + '">';
        for (r = 0; r < n; r++) {
            for (c = 0; c < n; c++) {
                s += '<rect x="' + (c * CELLULE) + '" y="' + (r * CELLULE) + '" width="' + CELLULE + '" height="' + CELLULE + '" fill="' + COULEURS[z[r][c]] + '"/>' +
                    '<rect x="' + (c * CELLULE) + '" y="' + (r * CELLULE) + '" width="' + CELLULE + '" height="' + CELLULE + '" fill="url(#lut-m' + z[r][c] + ')"/>';
            }
        }
        // traits fins entre cases d'une même zone, puis traits épais entre zones
        var fin = '', epais = '', x, y;
        for (r = 0; r <= n; r++) {
            for (c = 0; c <= n; c++) {
                x = c * CELLULE; y = r * CELLULE;
                if (c < n && r > 0 && r < n) { // trait horizontal entre (r-1,c) et (r,c)
                    if (z[r - 1][c] !== z[r][c]) { epais += 'M' + x + ' ' + y + ' h' + CELLULE; } else { fin += 'M' + x + ' ' + y + ' h' + CELLULE; }
                }
                if (r < n && c > 0 && c < n) { // trait vertical entre (r,c-1) et (r,c)
                    if (z[r][c - 1] !== z[r][c]) { epais += 'M' + x + ' ' + y + ' v' + CELLULE; } else { fin += 'M' + x + ' ' + y + ' v' + CELLULE; }
                }
            }
        }
        s += '<path d="' + fin + '" stroke="rgba(40,24,12,.35)" stroke-width="1" fill="none"/>';
        s += '<path d="' + epais + '" stroke="#2a1a0e" stroke-width="3.6" fill="none" stroke-linecap="square"/>';
        s += '<rect x="0" y="0" width="' + T + '" height="' + T + '" fill="none" stroke="#2a1a0e" stroke-width="3.6"/>';
        s += '<g class="lut-symboles"></g></svg>';
        return s;
    }

    function demarrer(conteneur, config, api) {
        nettoyer();
        var n = config.taille, viesMax = config.vies;
        var racine = document.createElement('div');
        racine.className = 'lut';
        racine.innerHTML =
            '<div class="lut-haut">' +
            '<div class="lut-vies" aria-label="Vies"></div>' +
            '<div class="lut-compteur"><span class="lut-nb">0</span>/' + n + ' lutins</div>' +
            '<div class="lut-chrono">0:00</div></div>' +
            '<p class="lut-message" aria-live="polite">&nbsp;</p>' +
            '<div class="lut-plateau"></div>' +
            '<p class="lut-aide">Un appui : ✕ · deux appuis : lutin · trois : effacer</p>' +
            '<div class="lut-sapins" aria-hidden="true"></div>' +
            '<div class="lut-ecran lut-accueil">' +
            '<h3>Les Lutins du Chalet</h3>' +
            '<p class="lut-slogan">' + n + ' lutins, ' + n + ' couleurs, zéro envie de se toucher : le gîte est en grève des câlins.</p>' +
            '<p class="lut-regles">Un lutin par <b>ligne</b>, par <b>colonne</b> et par <b>zone de couleur</b>. Jamais deux lutins voisins, même en diagonale.<br>' +
            'Une seule solution : pas besoin de deviner. Un mauvais lutin coûte un bonnet (' + viesMax + ' au total).</p>' +
            '<button type="button" class="bouton lut-go">Commencer</button></div>' +
            '<div class="lut-ecran lut-fin" hidden></div>';
        conteneur.appendChild(racine);

        var e = etat = {
            actif: true, racine: racine, ecouteurs: [], n: n, zones: null, grille: [], vies: viesMax,
            posees: 0, neuf: -1, debut: 0, base: 0, minuteur: 0, pause: 0, fini: false, file: Promise.resolve(),
            dernierAppui: { k: -1, t: 0 }
        };
        var elVies = racine.querySelector('.lut-vies');
        var elNb = racine.querySelector('.lut-nb');
        var elChrono = racine.querySelector('.lut-chrono');
        var elMessage = racine.querySelector('.lut-message');
        var elPlateau = racine.querySelector('.lut-plateau');
        var accueil = racine.querySelector('.lut-accueil');
        var ecranFin = racine.querySelector('.lut-fin');
        var svg = null, calque = null;
        var sapins = racine.querySelector('.lut-sapins');
        sapins.innerHTML = '<svg viewBox="0 0 360 40" preserveAspectRatio="xMidYMax slice"><g fill="#1f4a3c">' +
            '<path d="M10 40 L30 6 L50 40Z"/><path d="M60 40 L76 14 L92 40Z"/><path d="M120 40 L142 4 L164 40Z"/><path d="M200 40 L214 18 L228 40Z"/><path d="M250 40 L274 8 L298 40Z"/><path d="M310 40 L328 14 L346 40Z"/></g>' +
            '<g fill="#f4f8ff"><path d="M30 6 L22 20 L38 20Z"/><path d="M142 4 L132 22 L152 22Z"/><path d="M274 8 L264 24 L284 24Z"/><path d="M76 14 L70 24 L82 24Z"/><path d="M328 14 L322 24 L334 24Z"/></g></svg>';

        function ecoute(cible, type, fn, opt) { cible.addEventListener(type, fn, opt); e.ecouteurs.push([cible, type, fn, opt]); }
        function dire(texte, classe) {
            elMessage.textContent = texte;
            elMessage.className = 'lut-message' + (classe ? ' ' + classe : '');
        }
        function dessinerVies() {
            var s = '';
            for (var i = 0; i < viesMax; i++) {
                s += '<svg class="lut-bonnet' + (i < e.vies ? '' : ' perdu') + '" viewBox="0 0 28 28"><use href="#lut-bonnet"/></svg>';
            }
            elVies.innerHTML = s;
        }
        // Les symboles <use href="#…"> sont dans le <defs> du plateau ; les bonnets du HUD en ont besoin aussi.
        function definirSymboles() {
            var d = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            d.setAttribute('width', '0'); d.setAttribute('height', '0'); d.setAttribute('aria-hidden', 'true');
            d.style.position = 'absolute';
            d.innerHTML = defs();
            racine.appendChild(d);
        }
        definirSymboles();
        dessinerVies();

        function formater(ms) {
            var s = Math.floor(ms / 1000);
            return Math.floor(s / 60) + ':' + (s % 60 < 10 ? '0' : '') + (s % 60);
        }
        function tic() {
            if (e.fini) { return; }
            elChrono.textContent = formater(e.base + (performance.now() - e.debut));
        }

        function dessinerSymboles() {
            var s = '', r, c, k;
            for (r = 0; r < n; r++) {
                for (c = 0; c < n; c++) {
                    k = e.grille[r][c];
                    if (k === 1) { s += croix(r, c, false); }
                    else if (k === 4) { s += croix(r, c, true); }
                    else if (k === 2 || k === 3) {
                        s += '<use href="#lut-elfe" x="' + (c * CELLULE + 1) + '" y="' + (r * CELLULE + 1) + '" width="38" height="38"' +
                            (k === 2 ? ' class="lut-attente"' : (e.neuf === r * n + c ? ' class="lut-pose"' : '')) + '/>';
                    }
                }
            }
            calque.innerHTML = s;
            e.neuf = -1; // l'animation de pose ne joue qu'une fois, pas à chaque redessin
            elNb.textContent = e.posees;
        }

        /* --- Lancement : la grille ne vient qu'avec l'action « demarrer » --- */
        function installer(vue) {
            e.zones = vue.zones;
            e.vies = vue.vies;
            e.grille = [];
            var r, c;
            for (r = 0; r < n; r++) { e.grille.push(new Array(n).fill(0)); }
            vue.bons.forEach(function (p) { e.grille[p[0]][p[1]] = 3; });
            vue.refus.forEach(function (p) { e.grille[p[0]][p[1]] = 4; });
            e.posees = vue.bons.length;
            elPlateau.innerHTML = plateau(e);
            svg = elPlateau.querySelector('svg');
            calque = svg.querySelector('.lut-symboles');
            dessinerVies();
            dessinerSymboles();
            e.base = vue.ecoule_ms || 0;
            e.debut = performance.now();
            tic();
            e.minuteur = setInterval(tic, 250);
            ecoute(elPlateau, 'pointerdown', appui, { passive: false });
            ecoute(elPlateau, 'contextmenu', function (ev) { ev.preventDefault(); });
        }

        function lancer() {
            accueil.hidden = true;
            dire('Les lutins s\'échauffent…');
            api.appeler('demarrer', {}).then(function (rep) {
                if (!e.actif) { return; }
                if (!rep || !rep.ok) { throw new Error((rep && rep.erreur) || 'Le serveur ne répond pas.'); }
                installer(rep);
                dire('C\'est parti !');
                if (rep.fini || rep.perdu) { conclure(rep); }
            }).catch(function (err) {
                if (!e.actif) { return; }
                dire(err.message + ' Recharge la page pour reprendre.', 'lut-erreur');
            });
        }

        /* --- Appui sur une case --- */
        function appui(ev) {
            if (!ev.isPrimary || e.fini) { return; }
            ev.preventDefault();
            var box = svg.getBoundingClientRect();
            var marge = 3 / (n * CELLULE + 6) * box.width; // le viewBox déborde de 3 unités de chaque côté
            var c = Math.floor((ev.clientX - box.left - marge) / ((box.width - 2 * marge) / n));
            var r = Math.floor((ev.clientY - box.top - marge) / ((box.height - 2 * marge) / n));
            if (r < 0 || c < 0 || r >= n || c >= n) { return; }
            var k = r * n + c, maintenant = performance.now();
            if (e.dernierAppui.k === k && maintenant - e.dernierAppui.t < 40) { return; } // double déclenchement
            e.dernierAppui = { k: k, t: maintenant };
            var v = e.grille[r][c];
            if (v === 0) { e.grille[r][c] = 1; }
            else if (v === 1) { e.grille[r][c] = 2; poser(r, c); }
            else if (v === 2) { return; }                 // en attente de réponse du serveur
            else if (v === 3 || v === 4) { return; }      // verrouillé
            else { e.grille[r][c] = 0; }
            dessinerSymboles();
        }

        function poser(r, c) {
            // mise à jour optimiste : le lutin est déjà affiché ; les appels partent dans l'ordre
            e.file = e.file.then(function () {
                if (!e.actif) { return null; }
                return api.appeler('poser', { r: r, c: c });
            }).then(function (rep) {
                if (!e.actif || rep === null) { return; }
                if (!rep || !rep.ok) { throw new Error((rep && rep.erreur) || 'Réponse inattendue.'); }
                if (rep.bon) {
                    e.grille[r][c] = 3;
                    e.neuf = r * n + c;
                    e.posees++;
                    dire(BONS[Math.floor(Math.random() * BONS.length)], 'lut-bon');
                } else {
                    e.grille[r][c] = 4;
                    var avant = e.vies;
                    e.vies = rep.vies;
                    dire(REFUS[Math.floor(Math.random() * REFUS.length)], 'lut-mauvais');
                    secouer();
                    dessinerVies();
                    if (avant > e.vies) { var b = elVies.querySelectorAll('.lut-bonnet')[e.vies]; if (b) { b.classList.add('chute'); } }
                }
                dessinerSymboles();
                if (rep.fini || rep.perdu) { conclure(rep); }
            }).catch(function (err) {
                if (!e.actif) { return; }
                e.grille[r][c] = 1; // retour à la croix : on pourra réessayer
                dessinerSymboles();
                dire(err.message || 'Le réseau a trébuché, réessaie.', 'lut-erreur');
            });
        }

        function secouer() {
            elPlateau.classList.remove('secoue');
            void elPlateau.offsetWidth; // relance l'animation
            elPlateau.classList.add('secoue');
        }

        /* --- Fin de partie --- */
        function conclure(rep) {
            if (e.fini) { return; }
            e.fini = true;
            clearInterval(e.minuteur);
            var gagne = !!rep.fini;
            var temps = rep.temps_ms !== undefined ? rep.temps_ms : rep.ecoule_ms;
            ecranFin.hidden = false;
            ecranFin.innerHTML =
                '<h3>' + (gagne ? 'Tous les lutins sont au boulot !' : 'Les lutins ont fait grève') + '</h3>' +
                '<p class="lut-bilan">⏱ ' + (temps !== undefined ? formater(temps) : '–') + ' · ' + e.vies + ' bonnet' + (e.vies > 1 ? 's' : '') + ' restant' + (e.vies > 1 ? 's' : '') + '</p>' +
                '<p class="lut-slogan">' + (gagne ? 'Le jury lutin calcule ton score…' : 'Plus de bonnets : la partie est perdue, 0 point.') + '</p>';
            e.pause = setTimeout(function () { if (e.actif) { api.terminer({}); } }, 1800);
        }

        ecoute(racine.querySelector('.lut-go'), 'click', lancer);

        // Rechargement de la page pendant une partie : l'état serveur est restitué (vies et chrono inclus).
        if (config.reprise) {
            accueil.hidden = true;
            installer(config.reprise);
            dire('Te revoilà ! Les lutins ont gardé ta place.');
            if (config.reprise.fini || config.reprise.perdu) { conclure(config.reprise); }
        }
    }

    window.AventJeux.lutins = {
        demarrer: demarrer,
        // Fin du temps : rien à envoyer, l'état de la partie est tenu par le serveur.
        arreter: function () {
            nettoyer();
            return {};
        }
    };
}());
