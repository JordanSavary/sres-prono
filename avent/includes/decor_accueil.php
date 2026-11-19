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

/** Graine pseudo-aléatoire simple (entier 0..1) pour les décors. */
function accueil_alea(int $a, int $b): float
{
    $n = ($a * 374761393 + $b * 668265263) & 0x7FFFFFFF;
    $n = (($n ^ ($n >> 13)) * 1274126177) & 0x7FFFFFFF;
    return ($n % 1000) / 1000;
}

/**
 * Hall d'accueil : mur de rondins rétro-éclairé, présentoir de clés, mur latéral à lattes, élan en bonnet,
 * comptoir en marbre noir. Scène en plein écran, calée en bas et centrée (le formulaire se pose dessus).
 */
function accueil_hall(): void
{
    $tags = ['#d93a3f', '#15795f', '#fff6e3', '#ffc857', '#3a5fa8'];
    $bois = [['#e2bb82', '#b8864f'], ['#d3a566', '#a77a45'], ['#ecd0a2', '#c29a63'], ['#c98f5a', '#9a6a3a']];
    ?>
<svg class="acc-hall" viewBox="0 0 1200 760" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="acc-mur-g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1c1613"/><stop offset="1" stop-color="#2b2220"/></linearGradient>
        <linearGradient id="acc-bois-h" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c0794a"/><stop offset="1" stop-color="#8a5130"/></linearGradient>
        <linearGradient id="acc-laiton" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffe29a"/><stop offset=".55" stop-color="#e0a93a"/><stop offset="1" stop-color="#a8741a"/></linearGradient>
        <linearGradient id="acc-led" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#ffcf7a" stop-opacity="0"/><stop offset=".5" stop-color="#ffcf7a" stop-opacity=".7"/><stop offset="1" stop-color="#ffcf7a" stop-opacity="0"/></linearGradient>
        <linearGradient id="acc-marbre" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#0d0d10"/><stop offset=".6" stop-color="#1a1a1f"/><stop offset="1" stop-color="#0b0b0e"/></linearGradient>
        <linearGradient id="acc-sol-g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2a2321"/><stop offset="1" stop-color="#0f0b0a"/></linearGradient>
        <radialGradient id="acc-halo"><stop offset="0" stop-color="#ffd27a" stop-opacity=".8"/><stop offset="1" stop-color="#ffd27a" stop-opacity="0"/></radialGradient>
        <radialGradient id="acc-spot" cx=".5" cy="0" r="1"><stop offset="0" stop-color="#ffe0a0" stop-opacity=".55"/><stop offset="1" stop-color="#ffe0a0" stop-opacity="0"/></radialGradient>
        <clipPath id="acc-clip-comptoir"><rect x="150" y="506" width="900" height="260"/></clipPath>
        <symbol id="acc-buche" viewBox="-50 -50 100 100" overflow="visible">
            <circle r="49" fill="#5a3a20"/>
            <circle r="43" style="fill:var(--c1,#e2bb82)"/>
            <circle r="31" fill="none" style="stroke:var(--c2,#b8864f)" stroke-width="2.4"/>
            <circle r="18" fill="none" style="stroke:var(--c2,#b8864f)" stroke-width="2.2"/>
            <circle r="4.5" style="fill:var(--c2,#b8864f)"/>
            <path d="M3 -3 L30 -22" style="stroke:var(--c2,#b8864f)" stroke-width="1.6" stroke-linecap="round"/>
        </symbol>
    </defs>

    <!-- murs -->
    <rect width="1200" height="760" fill="url(#acc-mur-g)"/>
    <?php for ($i = 0; $i < 9; $i++) : ?><path d="M<?= 30 + $i * 36 ?> 40 V640" stroke="#000" stroke-width="2" opacity=".35"/><?php endfor; ?>

    <!-- mur de rondins central, cadre bois et rubans lumineux -->
    <rect x="330" y="36" width="540" height="620" fill="#241509"/>
    <?php
    for ($r = 0; $r < 14; $r++) {
        for ($c = 0; $c < 12; $c++) {
            $rayon = 17 + accueil_alea($r, $c) * 8;
            $x = 352 + $c * 44 + ($r % 2) * 22 + (accueil_alea($c, $r + 40) - 0.5) * 5;
            $y = 62 + $r * 40 + (accueil_alea($r + 7, $c + 9) - 0.5) * 5;
            $v = $bois[(int) (accueil_alea($r + 3, $c + 11) * 4)];
            $rot = (int) (accueil_alea($c + 5, $r + 2) * 360);
            ?>
    <use href="#acc-buche" x="-50" y="-50" width="100" height="100" transform="translate(<?= number_format($x, 1, '.', '') ?> <?= number_format($y, 1, '.', '') ?>) rotate(<?= $rot ?>) scale(<?= number_format($rayon / 50, 3, '.', '') ?>)" style="--c1:<?= $v[0] ?>;--c2:<?= $v[1] ?>"/>
        <?php
        }
    }
    ?>
    <ellipse cx="600" cy="300" rx="330" ry="300" fill="url(#acc-halo)" opacity=".5"/>
    <rect x="326" y="36" width="26" height="620" fill="#4a2a14" stroke="#2d160a" stroke-width="3"/>
    <rect x="848" y="36" width="26" height="620" fill="#4a2a14" stroke="#2d160a" stroke-width="3"/>
    <rect class="acc-led" x="352" y="36" width="34" height="620" fill="url(#acc-led)"/>
    <rect class="acc-led" x="814" y="36" width="34" height="620" fill="url(#acc-led)"/>
    <rect x="352" y="36" width="4" height="620" fill="#fff0c8"/>
    <rect x="844" y="36" width="4" height="620" fill="#fff0c8"/>
    <rect x="326" y="30" width="548" height="14" fill="#3d2112" stroke="#2d160a" stroke-width="3"/>

    <!-- enseigne et présentoir de clés -->
    <g transform="translate(370 92)">
        <circle cx="230" cy="150" r="190" fill="url(#acc-halo)" opacity=".55"/>
        <path d="M168 14 V26 M292 14 V26" stroke="#1a0d05" stroke-width="3" stroke-linecap="round"/>
        <rect x="120" y="24" width="220" height="44" rx="9" fill="url(#acc-bois-h)" stroke="#2d160a" stroke-width="3.5"/>
        <rect x="127" y="30" width="206" height="32" rx="6" fill="none" stroke="#5a3420" stroke-width="1.5"/>
        <text x="231" y="55" text-anchor="middle" font-family="Bricolage Grotesque, Trebuchet MS, sans-serif" font-weight="800" font-size="23" letter-spacing="1" fill="#2d160a">Réception</text>
        <text x="230" y="54" text-anchor="middle" font-family="Bricolage Grotesque, Trebuchet MS, sans-serif" font-weight="800" font-size="23" letter-spacing="1" fill="#ffe3a3">Réception</text>
        <g transform="translate(0 16)">
        <rect x="66" y="64" width="328" height="150" rx="13" fill="#4a2a14" stroke="#1a0d05" stroke-width="4"/>
        <rect x="77" y="75" width="306" height="128" rx="8" fill="#5a331b" stroke="#2d160a" stroke-width="2"/>
        <path d="M82 138 H378" stroke="#3d2112" stroke-width="3"/>
        <?php
        for ($rang = 0; $rang < 2; $rang++) {
            $yh = $rang === 0 ? 92 : 154;
            for ($i = 0; $i < 6; $i++) {
                $x = 111 + $i * 47.6;
                $numero = $rang * 6 + $i + 1;
                $couleur = $tags[($numero - 1) % 5];
                $texte = in_array($couleur, ['#fff6e3', '#ffc857'], true) ? '#2d160a' : '#fff';
                ?>
        <circle cx="<?= $x ?>" cy="<?= $yh ?>" r="3.4" fill="#1a0d05"/>
                <?php if ($numero === 10) : // la clé manquante : le mystère du gîte ?>
        <circle cx="<?= $x ?>" cy="<?= $yh + 20 ?>" r="6" fill="none" stroke="#7d4a29" stroke-width="1.4" stroke-dasharray="3 2.4"/>
                <?php else : ?>
        <path d="M<?= $x ?> <?= $yh ?> v8" stroke="#e0a93a" stroke-width="1.6"/>
        <circle cx="<?= $x ?>" cy="<?= $yh + 14 ?>" r="6.2" fill="none" stroke="url(#acc-laiton)" stroke-width="3"/>
        <rect x="<?= $x - 1.6 ?>" y="<?= $yh + 20 ?>" width="3.2" height="17" fill="url(#acc-laiton)"/>
        <rect x="<?= $x + 1.6 ?>" y="<?= $yh + 30 ?>" width="4.6" height="3" fill="url(#acc-laiton)"/>
        <rect x="<?= $x + 1.6 ?>" y="<?= $yh + 35 ?>" width="3.4" height="3" fill="url(#acc-laiton)"/>
        <rect x="<?= $x - 8.5 ?>" y="<?= $yh + 22 ?>" width="17" height="19" rx="4" fill="<?= $couleur ?>" stroke="#1a0d05" stroke-width="1.3"/>
        <text x="<?= $x ?>" y="<?= $yh + 37 ?>" text-anchor="middle" font-family="DM Sans, sans-serif" font-weight="700" font-size="9.5" fill="<?= $texte ?>"><?= $numero ?></text>
                <?php endif;
            }
        }
        ?>
        </g>
    </g>

    <!-- mur gauche : enseigne du gîte et élan en bonnet -->
    <g transform="translate(40 96)">
        <path d="M30 76 L78 20 L100 46 L128 6 L180 76" fill="none" stroke="#fff6e3" stroke-width="3.4" stroke-linejoin="round" stroke-linecap="round"/>
        <path d="M118 22 L128 6 L140 24 L132 20 L126 26Z" fill="#fff6e3"/>
        <text x="105" y="112" text-anchor="middle" font-family="DM Sans, sans-serif" font-weight="700" font-size="14" letter-spacing="7" fill="#fff6e3" opacity=".9">GÎTE DES</text>
        <text x="105" y="146" text-anchor="middle" font-family="Bricolage Grotesque, Trebuchet MS, sans-serif" font-weight="800" font-size="34" letter-spacing="3" fill="#fff6e3">FLOCONS</text>
        <path d="M-6 160 H216" stroke="#ffc857" stroke-width="2" opacity=".8"/>
    </g>
    <g transform="translate(170 372) scale(.92)">
        <ellipse cx="0" cy="40" rx="78" ry="86" fill="#4a2a14" stroke="#1a0d05" stroke-width="4"/>
        <ellipse cx="0" cy="40" rx="68" ry="76" fill="#5a331b"/>
        <path d="M-22 -8 C-44 -30 -62 -26 -72 -52 M-46 -26 C-48 -44 -38 -58 -44 -76 M-62 -34 C-76 -36 -86 -52 -84 -66 M22 -8 C44 -30 62 -26 72 -52 M46 -26 C48 -44 38 -58 44 -76 M62 -34 C76 -36 86 -52 84 -66" fill="none" stroke="#e2c9a0" stroke-width="7" stroke-linecap="round"/>
        <ellipse cx="-34" cy="6" rx="14" ry="8" fill="#7a4f2e" stroke="#2d160a" stroke-width="2.5" transform="rotate(-30 -34 6)"/>
        <ellipse cx="34" cy="6" rx="14" ry="8" fill="#7a4f2e" stroke="#2d160a" stroke-width="2.5" transform="rotate(30 34 6)"/>
        <path d="M-26 6 Q-30 50 -22 84 Q0 104 22 84 Q30 50 26 6 Q0 -6 -26 6Z" fill="#7a4f2e" stroke="#2d160a" stroke-width="3" stroke-linejoin="round"/>
        <ellipse cx="0" cy="76" rx="20" ry="16" fill="#a8744a" stroke="#2d160a" stroke-width="2.5"/>
        <circle cx="0" cy="68" r="8" fill="#ff3b3b" stroke="#8f1f27" stroke-width="2"/><circle cx="-3" cy="65" r="2.2" fill="#fff" opacity=".8"/>
        <circle cx="-14" cy="36" r="4" fill="#1a0d05"/><circle cx="14" cy="36" r="4" fill="#1a0d05"/><circle cx="-13" cy="35" r="1.2" fill="#fff"/><circle cx="15" cy="35" r="1.2" fill="#fff"/>
        <path d="M-21 24 L-8 28 M21 24 L8 28" stroke="#2d160a" stroke-width="3" stroke-linecap="round"/>
        <path d="M-18 -4 L8 -50 L30 -2 Z" fill="#d93a3f" stroke="#8f1f27" stroke-width="2.5" stroke-linejoin="round" transform="rotate(8 0 -4)"/>
        <rect x="-22" y="-8" width="56" height="12" rx="6" fill="#fff6e3" stroke="#c9c2b4" stroke-width="1.5" transform="rotate(8 0 -4)"/>
        <circle cx="10" cy="-52" r="8" fill="#fff6e3" stroke="#c9c2b4" stroke-width="1.5"/>
    </g>

    <!-- mur droit : lattes de bois, fenêtre sur la nuit, applique -->
    <?php for ($i = 0; $i < 9; $i++) : ?>
    <rect x="<?= 874 + $i * 37 ?>" y="36" width="37" height="620" fill="<?= $i % 2 ? '#7d4f30' : '#8a5a37' ?>" stroke="#3d2112" stroke-width="2"/>
    <?php endfor; ?>
    <rect x="874" y="36" width="326" height="620" fill="#000" opacity=".22"/>
    <svg x="940" y="110" width="190" height="260" viewBox="0 0 220 300">
        <rect x="22" y="20" width="176" height="240" rx="9" fill="#5a3420" stroke="#1a0d05" stroke-width="4"/>
        <rect x="34" y="32" width="152" height="216" rx="3" fill="#0b1437"/>
        <rect x="34" y="32" width="152" height="216" rx="3" fill="#2a2a78" opacity=".55"/>
        <?php for ($i = 0; $i < 14; $i++) : ?><circle cx="<?= 42 + ($i * 37) % 138 ?>" cy="<?= 40 + ($i * 53) % 110 ?>" r="<?= $i % 3 === 0 ? 1.6 : 1 ?>" fill="#fff6e3" opacity=".85"/><?php endfor; ?>
        <circle cx="150" cy="66" r="15" fill="#fff6d6"/><circle cx="156" cy="62" r="13" fill="#2a2a78" opacity=".35"/>
        <path d="M34 214 Q80 196 120 208 T186 204 V248 H34 Z" fill="#f4f8ff"/>
        <?php foreach ([[62, 214, 34], [98, 220, 44], [150, 214, 36]] as [$x, $y, $h]) : ?>
        <path d="M<?= $x ?> <?= $y - $h ?> L<?= $x - $h * 0.38 ?> <?= $y ?> H<?= $x + $h * 0.38 ?> Z" fill="#14503f"/>
        <path d="M<?= $x ?> <?= $y - $h ?> L<?= $x - $h * 0.2 ?> <?= $y - $h * 0.55 ?> H<?= $x + $h * 0.2 ?> Z" fill="#f4f8ff"/>
        <?php endforeach; ?>
        <?php for ($i = 0; $i < 10; $i++) : ?>
        <circle class="acc-flocon" cx="<?= 44 + ($i * 41) % 132 ?>" cy="36" r="<?= $i % 3 === 0 ? 2.2 : 1.5 ?>" fill="#fff" style="animation-delay:<?= number_format($i * 0.9, 1, '.', '') ?>s;animation-duration:<?= 7 + $i % 4 ?>s"/>
        <?php endfor; ?>
        <rect x="106" y="32" width="8" height="216" fill="#5a3420" stroke="#1a0d05" stroke-width="2"/>
        <rect x="34" y="104" width="152" height="8" fill="#5a3420" stroke="#1a0d05" stroke-width="2"/>
        <rect x="34" y="174" width="152" height="8" fill="#5a3420" stroke="#1a0d05" stroke-width="2"/>
        <rect x="12" y="254" width="196" height="16" rx="8" fill="#f4f8ff" stroke="#c9c2b4" stroke-width="2"/>
        <path d="M22 26 Q4 100 8 232 L36 232 Q30 110 38 28 Z" fill="#9c3f4a" stroke="#5e1822" stroke-width="2.5" stroke-linejoin="round"/>
        <path d="M198 26 Q216 100 212 232 L184 232 Q190 110 182 28 Z" fill="#9c3f4a" stroke="#5e1822" stroke-width="2.5" stroke-linejoin="round"/>
    </svg>
    <circle cx="918" cy="450" r="70" fill="url(#acc-halo)" opacity=".7"/>
    <path d="M906 430 H930 L938 458 H898 Z" fill="#ffd98a" stroke="#a8741a" stroke-width="2.5" stroke-linejoin="round"/>
    <rect x="914" y="458" width="8" height="14" fill="url(#acc-laiton)"/>

    <!-- plafond : spots -->
    <?php foreach ([180, 600, 1020] as $x) : ?>
    <path d="M<?= $x - 6 ?> 36 L<?= $x - 150 ?> 600 H<?= $x + 150 ?> L<?= $x + 6 ?> 36 Z" fill="url(#acc-spot)" opacity=".35"/>
    <?php endforeach; ?>

    <!-- sol -->
    <rect x="0" y="636" width="1200" height="124" fill="url(#acc-sol-g)"/>
    <rect x="0" y="630" width="1200" height="12" fill="#3d2112"/>
    <path d="M0 690 H1200 M0 730 H1200" stroke="#000" stroke-width="2" opacity=".5"/>
    <?php for ($i = 0; $i < 14; $i++) : ?><path d="M<?= $i * 100 + 20 ?> 642 L<?= $i * 100 - 40 + (($i % 3) * 30) ?> 760" stroke="#000" stroke-width="2" opacity=".35"/><?php endfor; ?>

    <!-- comptoir en marbre noir -->
    <g clip-path="url(#acc-clip-comptoir)">
        <rect x="150" y="506" width="900" height="260" fill="url(#acc-marbre)"/>
        <polygon points="330,506 470,506 700,770 560,770" fill="#8a5a37" opacity=".5"/>
        <polygon points="330,506 346,506 576,770 560,770" fill="#d9a46a" opacity=".5"/>
        <g fill="none" stroke="#efece6" stroke-linecap="round">
            <path d="M160 560 C230 540 280 600 350 580 S470 620 520 700" stroke-width="2.2" opacity=".6"/>
            <path d="M210 520 C250 580 230 640 300 700 S380 740 400 766" stroke-width="1.4" opacity=".5"/>
            <path d="M610 520 C650 570 700 560 740 620 S820 640 860 700" stroke-width="2.6" opacity=".6"/>
            <path d="M720 506 C760 540 800 530 840 580 S940 600 1040 560" stroke-width="1.6" opacity=".55"/>
            <path d="M880 620 C920 650 940 700 1000 720" stroke-width="1.3" opacity=".5"/>
            <path d="M420 640 C470 630 520 660 560 640" stroke-width="1" opacity=".4"/>
            <path d="M960 520 C980 570 1010 590 1040 650" stroke-width="2" opacity=".5"/>
        </g>
    </g>
    <rect x="140" y="492" width="920" height="18" rx="6" fill="#26262c" stroke="#0b0b0e" stroke-width="3"/>
    <path d="M150 498 H1050" stroke="#7a7a86" stroke-width="2" opacity=".7" stroke-linecap="round"/>
    <rect x="150" y="740" width="900" height="3" fill="#ffc857" opacity=".35"/>

    <!-- sur le comptoir : écrans, diffuseur, poinsettia, sonnette -->
    <?php foreach ([300, 790] as $x) : ?>
    <g transform="translate(<?= $x ?> 404)">
        <rect x="0" y="0" width="120" height="76" rx="5" fill="#16171a" stroke="#050506" stroke-width="3"/>
        <rect x="3" y="3" width="114" height="70" rx="3" fill="none" stroke="#ffd27a" stroke-width="1.4" opacity=".4"/>
        <circle cx="60" cy="38" r="4" fill="#3a3a42"/>
        <rect x="52" y="76" width="16" height="14" fill="#0e0e10"/>
        <rect x="34" y="88" width="52" height="6" rx="3" fill="#0e0e10"/>
    </g>
    <?php endforeach; ?>
    <g transform="translate(228 440)">
        <path d="M0 -4 L-12 -64 M6 -4 L8 -76 M12 -4 L24 -66 M-4 -4 L-26 -48" stroke="#c89a5c" stroke-width="1.8" stroke-linecap="round"/>
        <rect x="-6" y="-8" width="24" height="52" rx="8" fill="#cfe3e8" opacity=".85" stroke="#8aa9b2" stroke-width="2"/>
        <rect x="-2" y="14" width="16" height="22" rx="3" fill="#e8c27a" opacity=".6"/>
    </g>
    <g transform="translate(958 492)">
        <path d="M-22 0 H22 L18 -34 H-18 Z" fill="#fff6e3" stroke="#a8693e" stroke-width="2.5" stroke-linejoin="round"/>
        <?php foreach ([[-26, -62, -30], [0, -78, 0], [26, -62, 30], [-14, -46, -60], [14, -46, 60]] as [$dx, $dy, $rot]) : ?>
        <path d="M0 -36 Q<?= $dx - 12 ?> <?= $dy ?> <?= $dx ?> <?= $dy - 14 ?> Q<?= $dx + 10 ?> <?= $dy + 6 ?> 0 -36Z" fill="#c52f36" stroke="#7a161c" stroke-width="2" transform="rotate(<?= $rot ?> 0 -36)"/>
        <?php endforeach; ?>
        <circle cx="0" cy="-46" r="4.5" fill="#ffc857" stroke="#c98f10" stroke-width="1.5"/>
    </g>
    <g transform="translate(468 492)">
        <rect x="-24" y="-9" width="48" height="9" rx="4" fill="url(#acc-laiton)" stroke="#7a5214" stroke-width="1.5"/>
        <path d="M-19 -9 a19 18 0 0 1 38 0 Z" fill="url(#acc-laiton)" stroke="#7a5214" stroke-width="1.5"/>
        <path d="M-12 -16 q3 -9 11 -11" stroke="#fff3c9" stroke-width="2.2" fill="none" stroke-linecap="round" opacity=".8"/>
        <circle cx="0" cy="-32" r="4.2" fill="url(#acc-laiton)" stroke="#7a5214" stroke-width="1.3"/>
    </g>
</svg>
    <?php
}
