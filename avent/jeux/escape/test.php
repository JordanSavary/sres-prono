<?php
/**
 * Test en ligne de commande : php avent/jeux/escape/test.php
 * Joue des parties contre avent_jeu_escape_action() / _verifier() avec une horloge simulée.
 * Termine avec le code 1 si une attente n'est pas respectée.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/server.php';

$echecs = 0;
$verifie = static function (string $nom, bool $ok) use (&$echecs): void {
    echo ($ok ? '  ok    ' : '  ÉCHEC ') . $nom . "\n";
    if (!$ok) {
        $echecs++;
    }
};

final class Partie
{
    public array $etat = [];
    public int $seq = 0;
    public function __construct(public array $config = [], public string $pseudo = 'alice', public int $jour = 1)
    {
        $GLOBALS['AVENT_ESCAPE_HORLOGE'] = 1000.0;
    }
    public function appel(string $action, array $donnees, float $t, bool $nouvelleSeq = true): array
    {
        $GLOBALS['AVENT_ESCAPE_HORLOGE'] = 1000.0 + $t;
        if (!in_array($action, ['demarrer', 'enigme'], true) && $nouvelleSeq && !isset($donnees['seq'])) {
            $donnees['seq'] = ++$this->seq;
        }
        return avent_jeu_escape_action($this->config, ['action' => $action, 'donnees' => $donnees], $this->etat,
            ['pseudo' => $this->pseudo, 'jour' => $this->jour, 'partie' => 1], 0);
    }
    public function demarrer(float $t = 0.0): array { return $this->appel('demarrer', [], $t); }
    public function enigme(float $t): array { return $this->appel('enigme', [], $t); }
    public function reponse(int $i, float $t): array { return $this->appel('reponse', ['i' => $i], $t); }
    public function code(string $c, float $t): array { return $this->appel('code', ['code' => $c], $t); }
    public function verifier(): array
    {
        return avent_jeu_escape_verifier($this->config, ['score' => 100, 'temps_ms' => 1], 0, ['etat' => $this->etat]);
    }
    public function bonne(int $k): int { return $this->etat['enigmes'][$k]['bonne']; }
    public function mauvaise(int $k): int { return ($this->bonne($k) + 1) % 3; }
    public function code_secret(): string { return $this->etat['code']; }
}

echo "Partie parfaite\n";
$p = new Partie();
$p->demarrer();
$t = 0.5;
$ok = true;
$chiffres = '';
for ($k = 0; $k < 4; $k++) {
    $e = $p->enigme($t);
    $ok = $ok && isset($e['enigme']) && !isset($e['enigme']['bonne']) && !isset($e['enigme']['chiffre']) && count($e['enigme']['reponses']) === 3;
    $r = $p->reponse($p->bonne($k), $t + 2);
    $ok = $ok && ($r['bon'] ?? false) && $r['gain'] === 20 && isset($r['chiffre']);
    $chiffres .= $r['chiffre'];
    $t += 3;
}
$verifie('4 énigmes à 20 points, chiffres révélés', $ok);
$verifie('le code est la suite des chiffres révélés', $chiffres === $p->code_secret());
$c = $p->enigme($t);
$verifie('cadenas après la 4e énigme', isset($c['code']) && $c['code']['tentatives_restantes'] === 3);
$r = $p->code($chiffres, $t + 5);
$verifie('code juste du 1er coup : +20, partie terminée', ($r['bon'] ?? false) && $r['gain'] === 20 && $r['score'] === 100 && ($r['termine'] ?? false));
$v = $p->verifier();
$verifie('verifier : score 100, réussite', $v['score'] === 100 && $v['reussite'] === true);
$verifie('verifier ignore le payload du client', $v['score'] === 100 && avent_jeu_escape_verifier([], ['score' => 5], 0, ['etat' => $p->etat])['score'] === 100);

echo "Config publique\n";
$cfg = avent_jeu_escape_config_publique([], []);
$json = json_encode($cfg);
$verifie('config_publique : pas de réponses ni de code', !str_contains($json, 'bonne') && !str_contains($json, 'reponses') && !str_contains($json, 'chiffre') && $cfg['nb_enigmes'] === 4);
$verifie('énigmes de la réserve différentes selon le joueur/jour (déterministe pour un même joueur/jour)', (function () {
    $a = new Partie([], 'alice', 1); $a->demarrer();
    $b = new Partie([], 'alice', 1); $b->demarrer();
    $c = new Partie([], 'bob', 1); $c->demarrer();
    $d = new Partie([], 'alice', 2); $d->demarrer();
    return $a->etat['enigmes'] === $b->etat['enigmes'] && ($a->etat['enigmes'] !== $c->etat['enigmes'] || $a->etat['enigmes'] !== $d->etat['enigmes']);
})());

echo "Barème : erreurs et temps écoulés\n";
$p = new Partie();
$p->demarrer();
$p->enigme(1.0);                                   // énigme 1 servie à t=1
$r = $p->reponse($p->mauvaise(0), 2.0);
$verifie('mauvaise réponse : bon=false, aucun indice', $r['bon'] === false && !isset($r['chiffre']) && !isset($r['bonne']));
$r = $p->reponse($p->bonne(0), 4.0);               // 3 s après service, 1 faute : 20 - 5
$verifie('1 faute à ≤ 4 s : 15 points', $r['bon'] && $r['gain'] === 15);
$p->enigme(5.0);                                   // énigme 2 servie à t=5
$r = $p->reponse($p->bonne(1), 5.0 + 10.0);        // 10 s : 8 points
$verifie('10 s : 8 points', $r['bon'] && $r['gain'] === 8);
$p->enigme(16.0);                                  // énigme 3 à t=16
$r = $p->reponse($p->bonne(2), 16.0 + 7.0);        // 7 s : 20 - 12*(3/6) = 14
$verifie('7 s : 14 points (linéaire)', $r['bon'] && $r['gain'] === 14);
$p->enigme(24.0);                                  // énigme 4 à t=24
$r = $p->reponse($p->bonne(3), 24.0 + 11.5);       // 11,5 s : au-delà de 10 s + 1 s de tolérance -> temps écoulé, rejouée
$verifie('réponse à 11,5 s (> 10 s + 1 s de tolérance) : temps écoulé, énigme rejouée, pas de chiffre', ($r['expire'] ?? false) && !isset($r['chiffre']) && ($r['enigme']['numero'] ?? 0) === 4);
$r = $p->reponse($p->bonne(3), 35.5 + 1.0);        // 1 s après le nouveau délai : 20 - 5
$verifie('énigme rejouée résolue : 20 - 5 = 15', $r['bon'] && $r['gain'] === 15);
$verifie('score cumulé 15+8+14+15 = 52', $r['score'] === 52);
$p->enigme(37.0);
$r = $p->code('0000' === $p->code_secret() ? '1111' : '0000', 40.0);
$verifie('code faux : 2 tentatives restantes', $r['bon'] === false && $r['tentatives_restantes'] === 2 && !isset($r['termine']) || ($r['termine'] ?? true) === false);
$r = $p->code($p->code_secret(), 45.0);
$verifie('code juste à la 2e tentative : +13', $r['bon'] && $r['gain'] === 13 && $r['score'] === 65);
$verifie('verifier : 65', $p->verifier()['score'] === 65);

echo "Temps écoulé signalé par le client\n";
$p = new Partie();
$p->demarrer();
$p->enigme(0.5);
$r = $p->appel('temps', [], 5.0);
$verifie('« temps » trop tôt : refusé', isset($r['erreur']));
$r = $p->appel('temps', [], 10.6);
$verifie('« temps » après 10 s : énigme rejouée, pénalité 5', ($r['expire'] ?? false) && $r['penalite'] === 5 && $r['enigme']['restant_ms'] > 9000);
$r = $p->reponse($p->bonne(0), 10.6 + 1.0);
$verifie('résolue 1 s après le rejeu : 20 - 5 = 15', $r['bon'] && $r['gain'] === 15);

echo "Code faux 3 fois\n";
$p = new Partie();
$p->demarrer();
$t = 0.0;
for ($k = 0; $k < 4; $k++) {
    $p->enigme($t);
    $p->reponse($p->bonne($k), $t + 1);
    $t += 2;
}
$faux = $p->code_secret() === '0000' ? '1111' : '0000';
$r1 = $p->code($faux, $t + 3);
$r2 = $p->code($faux, $t + 4);
$r3 = $p->code($faux, $t + 5);
$verifie('3 codes faux : tentatives 2, 1, 0', $r1['tentatives_restantes'] === 2 && $r2['tentatives_restantes'] === 1 && $r3['tentatives_restantes'] === 0);
$verifie('3e échec : partie terminée, 80 points', ($r3['termine'] ?? false) && $r3['score'] === 80);
$r4 = $p->code($p->code_secret(), $t + 6);
$verifie('code juste après 3 échecs : refusé, score inchangé', ($r4['termine'] ?? false) && !isset($r4['gain']) && $p->verifier()['score'] === 80 && $p->verifier()['reussite'] === false);
$p2 = new Partie();
$p2->demarrer();
$t = 0.0;
for ($k = 0; $k < 4; $k++) { $p2->enigme($t); $p2->reponse($p2->bonne($k), $t + 1); $t += 2; }
$faux = $p2->code_secret() === '0000' ? '1111' : '0000';
$p2->code($faux, $t + 3); $p2->code($faux, $t + 4);
$r = $p2->code($p2->code_secret(), $t + 5);
$verifie('code juste à la 3e tentative : +6', $r['bon'] && $r['gain'] === 6 && $r['score'] === 86);
$r = (function () {
    $p = new Partie(); $p->demarrer(); $t = 0.0;
    for ($k = 0; $k < 4; $k++) { $p->enigme($t); $p->reponse($p->bonne($k), $t + 1); $t += 2; }
    return [$p, $p->code('12', $t + 3), $p->code('abcd', $t + 3.5), $p->code('12345', $t + 3.6)];
})();
$verifie('code mal formé refusé sans consommer de tentative', isset($r[1]['erreur']) && isset($r[2]['erreur']) && isset($r[3]['erreur']) && $r[0]->etat['tentatives'] === 0);
$p = $r[0];
$r = $p->appel('temps_code', [], 3 + 2 + 5);
$verifie('« temps_code » trop tôt refusé', isset($r['erreur']));
$r = $p->appel('temps_code', [], 8.0 + 2 + 20 + 0.1);
$verifie('délai de 20 s dépassé : fin, 80 points', ($r['termine'] ?? false) && $r['score'] === 80 && $p->verifier()['score'] === 80);

echo "Appels hors séquence\n";
$p = new Partie();
$verifie('action avant démarrer : refusée', isset($p->reponse(0, 1.0)['erreur']) && isset($p->enigme(1.0)['erreur']));
$p->demarrer();
$verifie('réponse sans énigme servie : refusée', isset($p->reponse(0, 1.0)['erreur']));
$verifie('code avant la fin des énigmes : refusé', isset($p->code('1234', 1.0)['erreur']));
$e = $p->enigme(1.0);
$e2 = $p->enigme(5.0);
$verifie('redemander l\'énigme : la même, sans nouvelle énigme ni remise à zéro du délai', $e2['enigme']['numero'] === 1 && $e2['enigme']['question'] === $e['enigme']['question'] && $e2['enigme']['restant_ms'] < 6500);
$verifie('index de réponse invalide refusé', isset($p->reponse(5, 6.0)['erreur']) && isset($p->appel('reponse', ['i' => 'x'], 6.0)['erreur']) && isset($p->appel('reponse', ['i' => 1], 6.0, false)['erreur']));
$verifie('action inconnue refusée', isset($p->appel('triche', [], 6.0)['erreur']));
$p->reponse($p->bonne(0), 7.0);
$e3 = $p->enigme(8.0);
$verifie('énigme 2 servie seulement après résolution de la 1re', $e3['enigme']['numero'] === 2);
$p3 = new Partie(); $p3->demarrer(); $p3->enigme(1.0);
$e4 = $p3->enigme(2.0);
$verifie('« enigme » en boucle sans répondre ne passe pas à la suivante', $e4['enigme']['numero'] === 1);
$seq = $p->seq;
$r1 = $p->appel('reponse', ['i' => $p->bonne(1), 'seq' => $seq + 1], 9.0);
$etatApres = $p->etat;
$r2 = $p->appel('reponse', ['i' => $p->mauvaise(1), 'seq' => $seq + 1], 9.5, false);
$verifie('renvoi avec le même seq : même réponse, aucun effet', $r1 === $r2 && $p->etat === $etatApres);
$verifie('seq ancien : refusé', isset($p->appel('reponse', ['i' => 0, 'seq' => 1], 10.0, false)['erreur']));

echo "Client qui triche sur son temps\n";
$p = new Partie();
$p->demarrer();
$p->enigme(0.0);
$r = avent_jeu_escape_verifier([], ['score' => 100, 'temps_ms' => 1, 'duree' => 0, 'etat' => ['pts' => [20, 20, 20, 20]]], 1, ['etat' => $p->etat]);
$verifie('payload falsifié ignoré (aucune énigme résolue : 0)', $r['score'] === 0);
$p->reponse($p->bonne(0), 30.0);
$verifie('réponse à 30 s : temps écoulé (pas de chiffre), tant pis pour les « 1 ms » annoncées', !isset($p->etat['pts'][0]));
$p = new Partie();
$p->demarrer();
$p->enigme(0.0);
$r = $p->appel('reponse', ['i' => $p->bonne(0), 'seq' => 1, 'temps_ms' => 1, 'ecoule' => 0], 9.0);
$verifie('temps annoncé par le client ignoré : 9 s mesurées = 10 points', $r['bon'] && $r['gain'] === 10);
$p->seq = 1;
$verifie('rejeu d\'un « temps » : refusé tant que le délai court', isset($p->appel('temps', [], 10.0)['erreur']));

echo "Temps global\n";
$p = new Partie();
$p->demarrer();
$t = 0.0;
for ($k = 0; $k < 2; $k++) { $p->enigme($t); $p->reponse($p->bonne($k), $t + 1); $t += 2; }
$p->enigme(4.0);
$r = $p->reponse($p->bonne(2), 111.0);
$verifie('réponse après 110 s : refusée, partie terminée, points acquis (40)', ($r['termine'] ?? false) && $r['score'] === 40 && $p->verifier()['score'] === 40);
$verifie('toute action après 110 s : plus de gain', ($p->code($p->code_secret(), 112.0)['termine'] ?? false) && $p->verifier()['score'] === 40);
$verifie('enigme après 110 s : fin', ($p->enigme(113.0)['termine'] ?? false));
$cfg = avent_jeu_escape_config_publique([], []);
$verifie('reprise absente sans partie', $cfg['reprise'] === null);

echo "Reprise après rechargement\n";
$p = new Partie();
$p->demarrer();
$p->enigme(1.0);
$p->reponse($p->bonne(0), 2.0);
$p->enigme(3.0);
$GLOBALS['AVENT_ESCAPE_HORLOGE'] = 1000.0 + 8.0;
$vue = avent_esc_vue($p->etat, avent_esc_params([]));
$verifie('vue de reprise : étape 2, 1 chiffre, temps serveur écoulé', $vue['etape'] === 2 && $vue['nb_chiffres'] === 1 && $vue['ecoule_ms'] === 8000 && !str_contains(json_encode($vue), 'chiffre":'));
$e = $p->enigme(9.0);
$verifie('énigme 2 reprise : délai non remis à zéro (6 s restantes sur 10)', $e['enigme']['numero'] === 2 && $e['enigme']['restant_ms'] <= 4100 && $e['enigme']['restant_ms'] >= 3900);
$e = $p->enigme(14.5);
$verifie('rechargement après un délai dépassé : pénalité appliquée, nouveau délai', $e['enigme']['restant_ms'] > 9000 && $p->etat['ecoules'] === 1);

echo "Validation du contenu\n";
$verifie('config vide valide', avent_jeu_escape_valider([]) === null);
$verifie('exemple du manifest valide', avent_jeu_escape_valider(json_decode(file_get_contents(__DIR__ . '/manifest.json'), true)['exemple']) === null);
$verifie('bonne hors bornes refusée', avent_jeu_escape_valider(['enigmes' => array_fill(0, 4, ['question' => 'Q ?', 'reponses' => ['a', 'b', 'c'], 'bonne' => 3])]) !== null);
$verifie('réponses en double refusées', avent_jeu_escape_valider(['enigmes' => array_fill(0, 4, ['question' => 'Q ?', 'reponses' => ['a', 'a', 'c'], 'bonne' => 0])]) !== null);
$verifie('trop peu d\'énigmes refusé', avent_jeu_escape_valider(['enigmes' => [['question' => 'Q ?', 'reponses' => ['a', 'b', 'c'], 'bonne' => 0]]]) !== null);
$verifie('paramètre hors bornes refusé', avent_jeu_escape_valider(['parametres' => ['duree_enigme' => 3]]) !== null && avent_jeu_escape_valider(['parametres' => ['duree_globale' => 999]]) !== null);

echo $echecs === 0 ? "\nTout est bon.\n" : "\n$echecs échec(s).\n";
exit($echecs === 0 ? 0 : 1);
