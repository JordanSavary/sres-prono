/* Parité PHP / JavaScript du générateur de flocons : node avent/jeux/flocons/test.js
 * Compare, pour plusieurs graines et réglages, les flocons calculés par server.php (test.php dump) et par game.js. */
'use strict';
const { execFileSync } = require('child_process');
const path = require('path');
global.window = {};
require(path.join(__dirname, 'game.js'));
const genererFlocons = window.AventJeux.flocons.genererFlocons;

const reglages = [
    {},
    { rythme_ms: 400, distance_min: 350, piege_debut: 300, piege_fin: 500 },
    { rythme_ms: 1000, delai_sans_piege_ms: 0, duree_ref_ms: 20000, piege_debut: 100, piege_fin: 900 },
    { piege_debut: 500, piege_fin: 100 }
];
const graines = [1, 42, 123456789, 4294967295, 2718281828, 3141592653];
let echecs = 0;
for (const cfg of reglages) {
    for (const graine of graines) {
        const sortie = JSON.parse(execFileSync('php', [path.join(__dirname, 'test.php'), 'dump', String(graine), JSON.stringify(cfg)]).toString());
        const js = genererFlocons(sortie.params, graine, 300);
        const ok = JSON.stringify(js.map(f => [f.t, f.chute, f.x, !!f.piege, !!f.absent])) === JSON.stringify(sortie.flocons.map(f => [f.t, f.chute, f.x, !!f.piege, !!f.absent]));
        const pieges = js.filter(f => f.piege).length, absents = js.filter(f => f.absent).length;
        console.log((ok ? '  ok    ' : '  ÉCHEC ') + JSON.stringify(cfg) + ' graine ' + graine + ' (' + pieges + ' pièges, ' + absents + ' absents)');
        if (!ok) { echecs++; }
    }
}
console.log(echecs === 0 ? '\nTout est bon.' : '\n' + echecs + ' échec(s).');
process.exit(echecs === 0 ? 0 : 1);
