<?php
/**
 * Décor de la page de connexion : le hall d'accueil du Gîte des Flocons (SVG décoratifs, aria-hidden).
 * Style : avent/assets/accueil.css. Fichier autonome (la page de connexion ne charge pas le reste du calendrier).
 */

/** Point d'une courbe de Bézier quadratique. */
function accueil_bezier(array $p0, array $p1, array $p2, float $t): array
{
    $u = 1 - $t;
    return [$u * $u * $p0[0] + 2 * $u * $t * $p1[0] + $t * $t * $p2[0], $u * $u * $p0[1] + 2 * $u * $t * $p1[1] + $t * $t * $p2[1]];
}

/** Guirlande lumineuse accrochée à la poutre (fils en SVG étiré, ampoules en HTML). */
function accueil_guirlande(): void
{
    $arcs = 4;
    $par = 7;
    ?>
<div class="acc-guirlande" aria-hidden="true">
    <svg viewBox="0 0 100 20" preserveAspectRatio="none">
        <path d="<?php
            $d = 'M0 2';
            for ($a = 0; $a < $arcs; $a++) {
                $x0 = $a * 100 / $arcs;
                $d .= ' Q' . ($x0 + 50 / $arcs) . ' 20 ' . (($a + 1) * 100 / $arcs) . ' 2';
            }
            echo $d;
        ?>" fill="none" stroke="#1d0f06" stroke-width="1.6" vector-effect="non-scaling-stroke"/>
    </svg>
    <?php
    $couleurs = ['#ff5a4f', '#ffc857', '#5fd3a1', '#7ab8ff', '#fff6e3'];
    for ($a = 0; $a < $arcs; $a++) {
        for ($i = 0; $i < $par; $i++) {
            $t = ($i + 0.5) / $par;
            [$x, $y] = accueil_bezier([$a * 100 / $arcs, 2], [$a * 100 / $arcs + 50 / $arcs, 20], [($a + 1) * 100 / $arcs, 2], $t);
            $k = $a * $par + $i;
            ?>
    <i style="left:<?= number_format($x, 2, '.', '') ?>%;top:<?= number_format($y * 2, 1, '.', '') ?>px;--c:<?= $couleurs[$k % 5] ?>;--r:<?= number_format(($k * 0.37) % 2.4, 2, '.', '') ?>s"></i>
        <?php
        }
    }
    ?>
</div>
    <?php
}

/** Comptoir de réception, enseigne gravée, présentoir de clés. */
function accueil_reception(): void
{
    $tags = ['#d93a3f', '#15795f', '#fff6e3', '#ffc857', '#3a5fa8'];
    ?>
<svg class="acc-reception" viewBox="0 0 460 360" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="acc-bois-h" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c0794a"/><stop offset="1" stop-color="#8a5130"/></linearGradient>
        <linearGradient id="acc-laiton" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffe29a"/><stop offset=".55" stop-color="#e0a93a"/><stop offset="1" stop-color="#a8741a"/></linearGradient>
        <radialGradient id="acc-halo"><stop offset="0" stop-color="#ffd27a" stop-opacity=".75"/><stop offset="1" stop-color="#ffd27a" stop-opacity="0"/></radialGradient>
    </defs>
    <!-- enseigne -->
    <path d="M168 0 V12 M292 0 V12" stroke="#2d160a" stroke-width="3" stroke-linecap="round"/>
    <rect x="120" y="10" width="220" height="44" rx="9" fill="url(#acc-bois-h)" stroke="#3d2112" stroke-width="3.5"/>
    <rect x="127" y="16" width="206" height="32" rx="6" fill="none" stroke="#5a3420" stroke-width="1.5"/>
    <text x="231" y="41" text-anchor="middle" font-family="Bricolage Grotesque, Trebuchet MS, sans-serif" font-weight="800" font-size="23" letter-spacing="1" fill="#2d160a">Réception</text>
    <text x="230" y="40" text-anchor="middle" font-family="Bricolage Grotesque, Trebuchet MS, sans-serif" font-weight="800" font-size="23" letter-spacing="1" fill="#ffe3a3">Réception</text>
    <!-- présentoir à clés -->
    <rect x="66" y="64" width="328" height="150" rx="13" fill="#6b3f22" stroke="#3d2112" stroke-width="4"/>
    <rect x="77" y="75" width="306" height="128" rx="8" fill="#7d4a29" stroke="#4a2a14" stroke-width="2"/>
    <path d="M82 138 H378" stroke="#5a3420" stroke-width="3"/>
    <?php
    for ($rang = 0; $rang < 2; $rang++) {
        $yh = $rang === 0 ? 92 : 154;
        for ($i = 0; $i < 6; $i++) {
            $x = 111 + $i * 47.6;
            $numero = $rang * 6 + $i + 1;
            $couleur = $tags[($numero - 1) % 5];
            $texte = in_array($couleur, ['#fff6e3', '#ffc857'], true) ? '#2d160a' : '#fff';
            ?>
    <circle cx="<?= $x ?>" cy="<?= $yh ?>" r="3.4" fill="#2d160a"/>
            <?php if ($numero === 10) : // la clé manquante : le mystère du gîte ?>
    <path d="M<?= $x ?> <?= $yh ?> v10" stroke="#2d160a" stroke-width="1" opacity=".5"/>
    <circle cx="<?= $x ?>" cy="<?= $yh + 20 ?>" r="6" fill="none" stroke="#5a3420" stroke-width="1.4" stroke-dasharray="3 2.4"/>
            <?php else : ?>
    <g>
        <path d="M<?= $x ?> <?= $yh ?> v8" stroke="#e0a93a" stroke-width="1.6"/>
        <circle cx="<?= $x ?>" cy="<?= $yh + 14 ?>" r="6.2" fill="none" stroke="url(#acc-laiton)" stroke-width="3"/>
        <rect x="<?= $x - 1.6 ?>" y="<?= $yh + 20 ?>" width="3.2" height="17" fill="url(#acc-laiton)"/>
        <rect x="<?= $x + 1.6 ?>" y="<?= $yh + 30 ?>" width="4.6" height="3" fill="url(#acc-laiton)"/>
        <rect x="<?= $x + 1.6 ?>" y="<?= $yh + 35 ?>" width="3.4" height="3" fill="url(#acc-laiton)"/>
        <rect x="<?= $x - 8.5 ?>" y="<?= $yh + 22 ?>" width="17" height="19" rx="4" fill="<?= $couleur ?>" stroke="#2d160a" stroke-width="1.3"/>
        <circle cx="<?= $x ?>" cy="<?= $yh + 25 ?>" r="1.3" fill="#2d160a" opacity=".55"/>
        <text x="<?= $x ?>" y="<?= $yh + 37 ?>" text-anchor="middle" font-family="DM Sans, sans-serif" font-weight="700" font-size="9.5" fill="<?= $texte ?>"><?= $numero ?></text>
    </g>
            <?php endif;
        }
    }
    ?>
    <!-- comptoir -->
    <rect x="22" y="238" width="416" height="22" rx="7" fill="url(#acc-bois-h)" stroke="#3d2112" stroke-width="3.5"/>
    <path d="M32 244 H428" stroke="#e1a275" stroke-width="2" opacity=".6" stroke-linecap="round"/>
    <rect x="36" y="260" width="388" height="100" fill="#6b3f22" stroke="#3d2112" stroke-width="3.5"/>
    <?php foreach ([54, 178, 302] as $x) : ?>
    <rect x="<?= $x ?>" y="274" width="104" height="72" rx="6" fill="#7d4a29" stroke="#4a2a14" stroke-width="2.5"/>
    <rect x="<?= $x + 9 ?>" y="283" width="86" height="54" rx="4" fill="none" stroke="#8f5a36" stroke-width="1.6"/>
    <?php endforeach; ?>
    <!-- guirlande sur le devant du comptoir -->
    <?php
    $pts = [[[36, 262], [134, 296], [230, 262]], [[230, 262], [326, 296], [424, 262]]];
    $couleurs = ['#ff5a4f', '#ffc857', '#5fd3a1', '#7ab8ff'];
    foreach ($pts as $arc => [$p0, $p1, $p2]) : ?>
    <path d="M<?= $p0[0] ?> <?= $p0[1] ?> Q<?= $p1[0] ?> <?= $p1[1] ?> <?= $p2[0] ?> <?= $p2[1] ?>" fill="none" stroke="#1d3a2c" stroke-width="3.4" stroke-linecap="round"/>
        <?php for ($i = 0; $i < 6; $i++) :
            [$x, $y] = accueil_bezier($p0, $p1, $p2, ($i + 0.5) / 6); ?>
    <circle class="acc-ampoule" cx="<?= number_format($x, 1, '.', '') ?>" cy="<?= number_format($y + 3.5, 1, '.', '') ?>" r="3.6" fill="<?= $couleurs[($arc * 6 + $i) % 4] ?>" style="--r:<?= number_format((($arc * 6 + $i) * 0.41) % 2.4, 2, '.', '') ?>s"/>
        <?php endfor;
    endforeach; ?>
    <!-- lampe -->
    <circle cx="82" cy="206" r="62" fill="url(#acc-halo)"/>
    <ellipse cx="82" cy="236" rx="19" ry="5" fill="#3d2112"/>
    <rect x="79" y="214" width="6" height="22" fill="url(#acc-laiton)"/>
    <path d="M60 192 H104 L112 218 H52 Z" fill="#ffd98a" stroke="#a8741a" stroke-width="2.5" stroke-linejoin="round"/>
    <path d="M70 195 L66 216 M82 193 V216 M94 195 L98 216" stroke="#e8b95a" stroke-width="1.2"/>
    <!-- sonnette -->
    <rect x="304" y="229" width="46" height="9" rx="4" fill="url(#acc-laiton)" stroke="#7a5214" stroke-width="1.5"/>
    <path d="M309 229 a18 17 0 0 1 36 0 Z" fill="url(#acc-laiton)" stroke="#7a5214" stroke-width="1.5"/>
    <path d="M316 222 q3 -9 11 -11" stroke="#fff3c9" stroke-width="2.2" fill="none" stroke-linecap="round" opacity=".8"/>
    <rect x="325" y="206" width="4" height="6" fill="#7a5214"/>
    <circle cx="327" cy="204" r="4.2" fill="url(#acc-laiton)" stroke="#7a5214" stroke-width="1.3"/>
    <!-- petit sapin et cadeau -->
    <g transform="translate(396 238)">
        <rect x="-3" y="-8" width="6" height="8" fill="#5a3420"/>
        <path d="M0 -62 L-20 -30 H-10 L-26 -8 H26 L10 -30 H20 Z" fill="#15795f" stroke="#0b4a39" stroke-width="2" stroke-linejoin="round"/>
        <path d="M0 -62 L-8 -48 H8 Z M-13 -30 L-19 -20 H-7 Z M13 -30 L19 -20 H7 Z" fill="#f4f8ff"/>
        <circle cx="-8" cy="-22" r="2.6" fill="#ffc857"/><circle cx="9" cy="-14" r="2.6" fill="#ff5a4f"/><circle cx="0" cy="-38" r="2.4" fill="#7ab8ff"/>
        <path d="M0 -70 l2.4 5 5.4 .8 -3.9 3.8 .9 5.4 -4.8 -2.6 -4.8 2.6 .9 -5.4 -3.9 -3.8 5.4 -.8 Z" fill="#ffc857"/>
    </g>
</svg>
    <?php
}

/** Cheminée en pierre, flammes, bas de Noël et couronne. */
function accueil_cheminee(): void
{
    ?>
<svg class="acc-cheminee" viewBox="0 0 300 360" aria-hidden="true" focusable="false">
    <defs>
        <pattern id="acc-pierre" width="48" height="26" patternUnits="userSpaceOnUse">
            <rect width="48" height="26" fill="#8d8579"/>
            <path d="M0 0 H48 M0 13 H48 M0 0 V13 M24 13 V26" stroke="#5f584f" stroke-width="2" fill="none"/>
            <path d="M3 4 H18 M28 17 H42" stroke="#a69d90" stroke-width="2" stroke-linecap="round"/>
        </pattern>
        <radialGradient id="acc-braise" cx=".5" cy=".85" r=".8"><stop offset="0" stop-color="#ff9a2e"/><stop offset=".55" stop-color="#8f2a12"/><stop offset="1" stop-color="#1a0a05"/></radialGradient>
    </defs>
    <rect x="62" y="0" width="176" height="130" fill="url(#acc-pierre)" stroke="#4a443c" stroke-width="3"/>
    <rect x="30" y="126" width="240" height="234" fill="url(#acc-pierre)" stroke="#4a443c" stroke-width="3"/>
    <path d="M92 360 V214 Q92 160 150 160 Q208 160 208 214 V360 Z" fill="url(#acc-braise)" stroke="#2a1d14" stroke-width="5"/>
    <!-- bûches et flammes -->
    <g class="acc-flammes">
        <path class="f1" d="M150 346 C112 336 108 296 134 262 C136 284 150 288 154 268 C178 292 192 326 150 346Z" fill="#ff7a1c"/>
        <path class="f2" d="M150 346 C124 338 124 308 142 286 C144 302 154 304 157 292 C172 308 178 330 150 346Z" fill="#ffc23a"/>
        <path class="f3" d="M150 346 C136 340 136 322 148 308 C150 318 156 320 158 312 C164 324 164 336 150 346Z" fill="#fff0a6"/>
    </g>
    <rect x="104" y="334" width="92" height="15" rx="7.5" fill="#4a2a14" stroke="#2a1608" stroke-width="2" transform="rotate(-6 150 341)"/>
    <rect x="108" y="338" width="86" height="14" rx="7" fill="#5f3a1d" stroke="#2a1608" stroke-width="2" transform="rotate(7 150 345)"/>
    <!-- manteau -->
    <rect x="12" y="116" width="276" height="24" rx="5" fill="url(#acc-bois-h2)" stroke="#3d2112" stroke-width="3.5"/>
    <defs><linearGradient id="acc-bois-h2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b97048"/><stop offset="1" stop-color="#7d4a29"/></linearGradient></defs>
    <path d="M18 122 H282" stroke="#dfa074" stroke-width="2" opacity=".6" stroke-linecap="round"/>
    <!-- couronne -->
    <circle cx="150" cy="62" r="25" fill="none" stroke="#15795f" stroke-width="12"/>
    <circle cx="150" cy="62" r="25" fill="none" stroke="#0e5f4a" stroke-width="12" stroke-dasharray="3 9"/>
    <circle cx="136" cy="48" r="3" fill="#ff5a4f"/><circle cx="168" cy="72" r="3" fill="#ff5a4f"/><circle cx="170" cy="50" r="3" fill="#ffc857"/>
    <path d="M150 90 l-14 12 M150 90 l14 12" stroke="#d93a3f" stroke-width="7" stroke-linecap="round"/>
    <circle cx="150" cy="90" r="5.5" fill="#d93a3f" stroke="#8f1f27" stroke-width="1.5"/>
    <!-- bas de Noël -->
    <?php foreach ([[44, '#d93a3f'], [82, '#15795f'], [220, '#fff6e3'], [258, '#d93a3f']] as $k => [$x, $c]) : ?>
    <g transform="translate(<?= $x ?> 140)">
        <rect x="-8" y="0" width="16" height="9" rx="3" fill="#f4f8ff" stroke="#c9c2b4" stroke-width="1"/>
        <path d="M-7 9 h14 v20 q0 8 9 9 q9 1 9 9 q0 8 -9 8 h-12 q-11 0 -11 -12 Z" fill="<?= $c ?>" stroke="#2d160a" stroke-width="1.6" stroke-linejoin="round"/>
        <path d="M-5 20 h12 M-5 27 h12" stroke="<?= $c === '#fff6e3' ? '#d93a3f' : '#fff6e3' ?>" stroke-width="2" opacity=".7"/>
    </g>
    <?php endforeach; ?>
    <!-- bougies sur le manteau -->
    <g transform="translate(118 116)"><rect x="-4" y="-22" width="8" height="22" rx="2" fill="#fff6e3" stroke="#c9c2b4"/><path class="acc-mini-flamme" d="M0 -36 q-6 8 0 13 q6 -5 0 -13Z" fill="#ffc23a"/></g>
    <g transform="translate(182 116)"><rect x="-4" y="-16" width="8" height="16" rx="2" fill="#d93a3f" stroke="#8f1f27"/><path class="acc-mini-flamme" d="M0 -30 q-6 8 0 13 q6 -5 0 -13Z" fill="#ffc23a" style="animation-delay:.7s"/></g>
    <rect x="10" y="350" width="280" height="10" fill="#5f584f" stroke="#3a352e" stroke-width="2.5"/>
</svg>
    <?php
}

/** Canapé en velours rouge, plaid, coussins et chat endormi. */
function accueil_canape(): void
{
    ?>
<svg class="acc-canape" viewBox="0 0 380 190" aria-hidden="true" focusable="false">
    <defs>
        <pattern id="acc-plaid" width="16" height="16" patternUnits="userSpaceOnUse">
            <rect width="16" height="16" fill="#c0392f"/>
            <rect x="0" y="5" width="16" height="4" fill="#15795f" opacity=".85"/>
            <rect x="5" y="0" width="4" height="16" fill="#15795f" opacity=".85"/>
            <path d="M0 12.5 H16 M12.5 0 V16" stroke="#fff6e3" stroke-width="1"/>
        </pattern>
    </defs>
    <rect x="14" y="170" width="352" height="9" rx="4.5" fill="#000" opacity=".25"/>
    <rect x="34" y="156" width="11" height="26" rx="3" fill="#3d2112"/><rect x="335" y="156" width="11" height="26" rx="3" fill="#3d2112"/>
    <rect x="34" y="16" width="312" height="110" rx="30" fill="#8f2f3a" stroke="#4d121b" stroke-width="3"/>
    <?php for ($i = 0; $i < 6; $i++) : ?>
    <circle cx="<?= 66 + $i * 50 ?>" cy="52" r="4" fill="#5e1822"/><circle cx="<?= 91 + $i * 50 ?>" cy="84" r="4" fill="#5e1822"/>
    <?php endfor; ?>
    <rect x="22" y="112" width="336" height="48" rx="14" fill="#7a2531" stroke="#4d121b" stroke-width="3"/>
    <rect x="54" y="96" width="134" height="50" rx="15" fill="#a73b47" stroke="#4d121b" stroke-width="3"/>
    <rect x="192" y="96" width="134" height="50" rx="15" fill="#a73b47" stroke="#4d121b" stroke-width="3"/>
    <path d="M62 108 q60 -6 118 0 M200 108 q60 -6 118 0" stroke="#c4616b" stroke-width="2.5" fill="none" stroke-linecap="round" opacity=".7"/>
    <rect x="6" y="58" width="50" height="106" rx="24" fill="#9a3441" stroke="#4d121b" stroke-width="3"/>
    <rect x="324" y="58" width="50" height="106" rx="24" fill="#9a3441" stroke="#4d121b" stroke-width="3"/>
    <!-- plaid sur l'accoudoir droit -->
    <path d="M318 66 Q354 66 366 106 L374 160 L336 164 Q334 120 308 98 Z" fill="url(#acc-plaid)" stroke="#6e1f19" stroke-width="2.5" stroke-linejoin="round"/>
    <path d="M338 164 v12 M346 163 v12 M354 162 v12 M362 161 v12 M370 160 v12" stroke="#fff6e3" stroke-width="2.2" stroke-linecap="round"/>
    <!-- coussins -->
    <g transform="rotate(-10 84 84)"><rect x="58" y="56" width="52" height="52" rx="12" fill="#fff6e3" stroke="#a8693e" stroke-width="2.5"/><path d="M84 66 l4.6 9.6 10.4 1.4 -7.6 7.4 1.9 10.4 -9.3 -5 -9.3 5 1.9 -10.4 -7.6 -7.4 10.4 -1.4 Z" fill="#ffc857" stroke="#c98f10" stroke-width="1.2"/></g>
    <g transform="rotate(9 292 86)"><rect x="266" y="60" width="52" height="50" rx="12" fill="#15795f" stroke="#0b4a39" stroke-width="2.5"/><path d="M276 70 H308 M276 80 H308 M276 90 H308 M276 100 H308" stroke="#ffc857" stroke-width="2.4" opacity=".8"/></g>
    <!-- chat endormi -->
    <g class="acc-chat">
        <path d="M262 100 q26 8 32 -8 q4 -12 -8 -14" fill="none" stroke="#1f1f2b" stroke-width="6" stroke-linecap="round"/>
        <ellipse cx="228" cy="96" rx="34" ry="14" fill="#2a2a38" stroke="#14141c" stroke-width="2"/>
        <circle cx="198" cy="94" r="12" fill="#2a2a38" stroke="#14141c" stroke-width="2"/>
        <path d="M189 86 l-2 -12 l9 6 Z M205 82 l5 -11 l3 12 Z" fill="#2a2a38" stroke="#14141c" stroke-width="2" stroke-linejoin="round"/>
        <path d="M192 94 q3 3 6 0 M202 94 q3 3 6 0" stroke="#e9d9a8" stroke-width="1.6" fill="none" stroke-linecap="round"/>
        <path d="M200 99 l2 2 l2 -2" fill="none" stroke="#f0a0a0" stroke-width="1.4" stroke-linecap="round"/>
        <text class="acc-zzz" x="178" y="72" font-family="DM Sans, sans-serif" font-weight="700" font-size="13" fill="#fff6e3">z</text>
        <text class="acc-zzz z2" x="168" y="58" font-family="DM Sans, sans-serif" font-weight="700" font-size="16" fill="#fff6e3">Z</text>
    </g>
</svg>
    <?php
}

/** Fenêtre sur la nuit enneigée. */
function accueil_fenetre(): void
{
    ?>
<svg class="acc-fenetre" viewBox="0 0 220 300" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="acc-nuit" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0b1437"/><stop offset=".6" stop-color="#2a2a78"/><stop offset="1" stop-color="#5a4a9a"/></linearGradient>
    </defs>
    <rect x="22" y="20" width="176" height="240" rx="9" fill="#5a3420" stroke="#2d160a" stroke-width="4"/>
    <rect x="34" y="32" width="152" height="216" rx="3" fill="url(#acc-nuit)"/>
    <?php for ($i = 0; $i < 14; $i++) : ?>
    <circle cx="<?= 42 + ($i * 37) % 138 ?>" cy="<?= 40 + ($i * 53) % 110 ?>" r="<?= $i % 3 === 0 ? 1.6 : 1 ?>" fill="#fff6e3" opacity=".85"/>
    <?php endfor; ?>
    <circle cx="150" cy="66" r="15" fill="#fff6d6"/><circle cx="156" cy="62" r="13" fill="#2a2a78" opacity=".35"/>
    <path d="M34 214 Q80 196 120 208 T186 204 V248 H34 Z" fill="#f4f8ff"/>
    <?php foreach ([[62, 214, 34], [98, 220, 44], [150, 214, 36]] as [$x, $y, $h]) : ?>
    <path d="M<?= $x ?> <?= $y - $h ?> L<?= $x - $h * 0.38 ?> <?= $y ?> H<?= $x + $h * 0.38 ?> Z" fill="#14503f"/>
    <path d="M<?= $x ?> <?= $y - $h ?> L<?= $x - $h * 0.2 ?> <?= $y - $h * 0.55 ?> H<?= $x + $h * 0.2 ?> Z" fill="#f4f8ff"/>
    <?php endforeach; ?>
    <?php for ($i = 0; $i < 10; $i++) : ?>
    <circle class="acc-flocon" cx="<?= 44 + ($i * 41) % 132 ?>" cy="36" r="<?= $i % 3 === 0 ? 2.2 : 1.5 ?>" fill="#fff" style="animation-delay:<?= number_format($i * 0.9, 1, '.', '') ?>s;animation-duration:<?= 7 + $i % 4 ?>s"/>
    <?php endfor; ?>
    <rect x="106" y="32" width="8" height="216" fill="#5a3420" stroke="#2d160a" stroke-width="2"/>
    <rect x="34" y="104" width="152" height="8" fill="#5a3420" stroke="#2d160a" stroke-width="2"/>
    <rect x="34" y="174" width="152" height="8" fill="#5a3420" stroke="#2d160a" stroke-width="2"/>
    <rect x="12" y="254" width="196" height="16" rx="8" fill="#f4f8ff" stroke="#c9c2b4" stroke-width="2"/>
    <path d="M22 26 Q4 100 8 232 L36 232 Q30 110 38 28 Z" fill="#9c3f4a" stroke="#5e1822" stroke-width="2.5" stroke-linejoin="round"/>
    <path d="M198 26 Q216 100 212 232 L184 232 Q190 110 182 28 Z" fill="#9c3f4a" stroke="#5e1822" stroke-width="2.5" stroke-linejoin="round"/>
    <path d="M18 100 Q24 130 20 232 M28 92 Q32 130 28 232 M202 100 Q196 130 200 232 M192 92 Q188 130 192 232" stroke="#7a2531" stroke-width="2" fill="none" opacity=".8"/>
    <path d="M6 108 H40 M214 108 H180" stroke="#ffc857" stroke-width="5" stroke-linecap="round"/>
</svg>
    <?php
}

/** Grand sapin décoré et cadeaux. */
function accueil_sapin(): void
{
    ?>
<svg class="acc-sapin" viewBox="0 0 170 300" aria-hidden="true" focusable="false">
    <rect x="76" y="238" width="18" height="30" fill="#5a3420" stroke="#2d160a" stroke-width="2.5"/>
    <?php foreach ([[20, 92, 50], [64, 142, 64], [108, 198, 76], [152, 250, 84]] as $i => [$haut, $bas, $demi]) : ?>
    <path d="M85 <?= $haut ?> L<?= 85 - $demi ?> <?= $bas ?> H<?= 85 + $demi ?> Z" fill="<?= $i % 2 ? '#0e6a50' : '#15795f' ?>" stroke="#0b4a39" stroke-width="2.5" stroke-linejoin="round"/>
    <?php endforeach; ?>
    <?php foreach ([[85, 56], [68, 104], [104, 116], [48, 156], [88, 146], [124, 164], [30, 214], [66, 200], [104, 214], [142, 226]] as $k => [$x, $y]) : ?>
    <circle class="acc-ampoule" cx="<?= $x ?>" cy="<?= $y ?>" r="4" fill="<?= ['#ff5a4f', '#ffc857', '#7ab8ff', '#5fd3a1'][$k % 4] ?>" style="--r:<?= number_format(($k * 0.43) % 2.4, 2, '.', '') ?>s"/>
    <?php endforeach; ?>
    <path d="M85 4 l5 11 12 1.6 -8.8 8.4 2.2 12 -10.4 -5.8 -10.4 5.8 2.2 -12 -8.8 -8.4 12 -1.6 Z" fill="#ffc857" stroke="#c98f10" stroke-width="1.5"/>
    <rect x="14" y="258" width="44" height="34" fill="#d93a3f" stroke="#8f1f27" stroke-width="2.5"/><path d="M36 258 V292 M14 275 H58" stroke="#ffc857" stroke-width="5"/>
    <rect x="108" y="266" width="38" height="26" fill="#3a5fa8" stroke="#1d3568" stroke-width="2.5"/><path d="M127 266 V292 M108 279 H146" stroke="#fff6e3" stroke-width="4"/>
    <rect x="62" y="272" width="30" height="20" fill="#ffc857" stroke="#c98f10" stroke-width="2.5"/><path d="M77 272 V292" stroke="#d93a3f" stroke-width="4"/>
</svg>
    <?php
}
