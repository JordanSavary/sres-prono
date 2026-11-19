/* Test de parité PHP/JS : node avent/jeux/barque/test.js
 * Rejoue des journaux (réguliers, gigue déterministe, paramètres modifiés) avec la simulation de game.js
 * et compare le tick d'arrivée avec celui de server.php (php doit être installé). */
'use strict';
const { execFileSync } = require('child_process');
const path = require('path');
const jeu = require('./game.js');
const { simuler, score } = jeu._sim;

function config(parametres) {
    return JSON.parse(execFileSync('php', ['-r',
        'require "' + path.join(__dirname, 'server.php') + '";' +
        'echo json_encode(avent_jeu_barque_config_publique(["parametres" => json_decode($argv[1], true)]));',
        '--', JSON.stringify(parametres)]).toString());
}
function lcg(graine) { let x = graine >>> 0; return () => (x = (Math.imul(x, 1664525) + 1013904223) >>> 0) / 4294967296; }
function journal(rnd, parSeconde, gigue) {
    const j = []; let t = Math.floor(rnd() * 40);
    while (t < 3600) { j.push(t); t += Math.max(5, Math.round(60 / parSeconde * (1 - gigue + 2 * gigue * rnd()))); }
    return j;
}

const jeux = [{}, { distance: 300000, courant: 3, taille_fin: 300 }, { impulsion: 200, frottement: 70, temps_plein: 15, points_min: 0 }];
let total = 0, ecarts = 0;
jeux.forEach((param, jp) => {
    const C = config(param), rnd = lcg(42 + jp);
    for (let n = 0; n < 150; n++) {
        const j = journal(rnd, 2.5 + rnd() * 9.5, rnd() * 0.5);
        const js = simuler(C, j);
        const php = JSON.parse(execFileSync('php', ['-r',
            'require "' + path.join(__dirname, 'server.php').replace(/\\/g, '/') + '";' +
            '$p = avent_barque_params(["parametres" => json_decode($argv[1], true)]);' +
            '$r = avent_barque_simuler($p, json_decode($argv[2], true));' +
            'echo json_encode($r + ["score" => $r["arrivee"] ? avent_barque_score($r["arrivee"], $p) : 0]);',
            '--', JSON.stringify(param), JSON.stringify(j)]).toString());
        total++;
        const sj = js.arrivee ? score(C, js.arrivee) : 0;
        if (js.arrivee !== php.arrivee || js.equipage !== php.equipage || js.pos !== php.position || sj !== php.score) {
            ecarts++; console.log('ÉCART', param, js, php);
        }
    }
});
console.log(total + ' journaux rejoués, ' + ecarts + ' écart(s) entre JavaScript et PHP.');
process.exit(ecarts ? 1 : 0);
