<?php
/**
 * Décors du chalet (SVG et blocs purement décoratifs, tous marqués aria-hidden).
 * Le style correspondant est dans assets/avent.css.
 */

/** Ciel : aurores, étoiles, lune et traîneau du Père Noël. */
function avent_decor_ciel(): void
{
    ?>
<div class="ciel" aria-hidden="true">
    <div class="aurore aurore-1"></div>
    <div class="aurore aurore-2"></div>
    <?php for ($i = 0; $i < 30; $i++) : ?>
        <span class="etoile-ciel" style="left:<?= ($i * 37 + 11) % 100 ?>%;top:<?= (int) ((($i * 53 + 7) % 97) * 5.2) ?>px;--t:<?= 2 + $i % 3 ?>px;--d:<?= number_format(2.4 + ($i % 4) * 0.7, 1, '.', '') ?>s;--r:<?= number_format(($i * 0.37) % 3, 2, '.', '') ?>s"></span>
    <?php endfor; ?>
    <div class="lune"><i></i><i></i></div>
    <div class="traineau">
        <svg viewBox="0 0 280 90" width="260" height="84">
            <g transform="translate(190 16)">
                <path d="M18 46 L10 58 M24 48 L20 60 M38 48 L48 58 M34 49 L36 60" stroke="#C99A6B" stroke-width="3" stroke-linecap="round"/>
                <ellipse cx="30" cy="40" rx="18" ry="9" fill="#C99A6B"/>
                <path d="M42 34 L52 22 L58 24 L48 40Z" fill="#C99A6B"/>
                <ellipse cx="58" cy="22" rx="7" ry="5" fill="#C99A6B"/>
                <circle cx="65" cy="22" r="2.8" fill="#FF4D4D"/>
                <path d="M56 17 L54 8 M54 11 L50 8 M58 16 L62 8 M60 11 L64 9" stroke="#E8D2B0" stroke-width="1.6" fill="none" stroke-linecap="round"/>
            </g>
            <g transform="translate(128 18)">
                <path d="M18 46 L10 58 M24 48 L20 60 M38 48 L48 58 M34 49 L36 60" stroke="#C99A6B" stroke-width="3" stroke-linecap="round"/>
                <ellipse cx="30" cy="40" rx="18" ry="9" fill="#C99A6B"/>
                <path d="M42 34 L52 22 L58 24 L48 40Z" fill="#C99A6B"/>
                <ellipse cx="58" cy="22" rx="7" ry="5" fill="#C99A6B"/>
                <circle cx="65" cy="22" r="2.4" fill="#C99A6B"/>
                <path d="M56 17 L54 8 M54 11 L50 8 M58 16 L62 8 M60 11 L64 9" stroke="#E8D2B0" stroke-width="1.6" fill="none" stroke-linecap="round"/>
            </g>
            <path d="M100 58 H132 M168 58 H198" stroke="#FFC857" stroke-width="1.4"/>
            <path d="M8 44 Q8 30 24 30 H78 Q90 30 90 40 L84 58 H14 Z" fill="#D93A3F" stroke="#FFC857" stroke-width="1.6"/>
            <path d="M2 68 H92 Q106 68 102 56" stroke="#FFC857" stroke-width="3" fill="none" stroke-linecap="round"/>
            <path d="M22 58 V68 M70 58 V68" stroke="#FFC857" stroke-width="2.4"/>
            <circle cx="26" cy="28" r="11" fill="#6A4BE0"/>
            <rect x="46" y="26" width="18" height="14" rx="4" fill="#D93A3F"/>
            <circle cx="55" cy="21" r="6" fill="#F6C9A0"/>
            <path d="M48 19 L62 19 L56 5Z" fill="#D93A3F"/>
            <circle cx="56" cy="4" r="2.6" fill="#FFF6E3"/>
            <rect x="47" y="18" width="16" height="3" rx="1.5" fill="#FFF6E3"/>
        </svg>
    </div>
</div>
<?php
}

/** Un sapin décoré (guirlandes, boules, étoile). */
function avent_decor_sapin(string $style): void
{
    ?>
<div class="deco sapin" aria-hidden="true" style="<?= h($style) ?>">
    <svg viewBox="0 0 100 132" width="100%" style="display:block;overflow:visible">
        <polygon points="50,6 78,52 22,52" fill="#15795F"/>
        <polygon points="50,28 88,86 12,86" fill="#0F6350"/>
        <polygon points="50,52 98,118 2,118" fill="#15795F"/>
        <rect x="44" y="118" width="12" height="14" fill="#5A3420"/>
        <polygon points="50,6 60,22 54,19 50,24 46,19 40,22" fill="#F4F8FF"/>
        <polygon points="50,28 64,48 57,44 50,50 43,44 36,48" fill="#F4F8FF"/>
        <polygon points="50,52 70,80 61,75 50,84 39,75 30,80" fill="#F4F8FF"/>
        <path d="M32,46 Q50,58 68,46" stroke="#FFC857" stroke-width="1.6" fill="none"/>
        <path d="M24,80 Q50,98 76,80" stroke="#FFC857" stroke-width="1.6" fill="none"/>
        <path d="M12,112 Q50,132 90,112" stroke="#FFC857" stroke-width="1.6" fill="none"/>
        <circle cx="46" cy="38" r="3.6" fill="#FFC857"/><circle cx="57" cy="46" r="3.6" fill="#D93A3F"/>
        <circle cx="34" cy="68" r="3.6" fill="#B9A6FF"/><circle cx="52" cy="72" r="3.6" fill="#FFC857"/>
        <circle cx="68" cy="78" r="3.6" fill="#D93A3F"/><circle cx="26" cy="102" r="3.6" fill="#FFC857"/>
        <circle cx="46" cy="104" r="3.6" fill="#B9A6FF"/><circle cx="66" cy="108" r="3.6" fill="#D93A3F"/>
        <circle cx="82" cy="108" r="3.6" fill="#FFC857"/>
        <path transform="translate(50 4) scale(0.75)" d="M0,-12 L3.5,-4 L12,-3.7 L5.5,2 L7.5,10 L0,5.5 L-7.5,10 L-5.5,2 L-12,-3.7 L-3.5,-4 Z" fill="#FFC857" stroke="#C98F10" stroke-width="1"/>
    </svg>
</div>
<?php
}

/** Bonhomme de neige. */
function avent_decor_bonhomme(): void
{
    ?>
<div class="deco bonhomme" aria-hidden="true">
    <div class="chapeau"><i></i><b></b><u></u></div>
    <div class="tete"><i class="oeil g"></i><i class="oeil d"></i><i class="nez"></i></div>
    <div class="echarpe"></div>
    <div class="torse"><i class="bras g"></i><i class="bras d"></i><i class="bt"></i></div>
    <div class="ventre"><i class="bt"></i><i class="bt b2"></i></div>
</div>
<?php
}

/** Toit : étoile, cheminée avec le Père Noël coincé, enseigne, ampoules. */
function avent_decor_toit(): void
{
    $couleurs = ['#FFC857', '#FF6B6B', '#7EE0C8', '#B9A6FF'];
    ?>
<div class="toit" aria-hidden="true">
    <div class="pere-noel"><i class="jambe g"></i><i class="botte g"></i><i class="jambe d"></i><i class="botte d"></i></div>
    <div class="cheminee"></div>
    <div class="cheminee-neige"></div>
    <div class="pan"></div>
    <div class="pan-neige"></div>
    <svg class="etoile-toit" viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.2 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8z" fill="#FFC857" stroke="#C98F10" stroke-width="1" stroke-linejoin="round"/></svg>
    <div class="enseigne">
        <i class="chaine g"></i><i class="chaine d"></i>
        <div class="panneau">
            <?= avent_gravure('sapin') ?>
            <span class="gravure-texte">Gîte des Flocons</span>
            <?= avent_gravure('flocon') ?>
        </div>
    </div>
    <?php for ($s = 0; $s < 2; $s++) : for ($k = 0; $k < 14; $k++) :
        $x = 2 + $k * (46 / 13);
        $couleur = $couleurs[($k + $s * 2) % 4]; ?>
        <i class="ampoule" style="left:<?= number_format($s === 0 ? $x : 100 - $x, 1, '.', '') ?>%;top:<?= number_format(100 - 2 * $x, 1, '.', '') ?>%;--c:<?= $couleur ?>;--d:<?= number_format(1.4 + (($k + $s) % 3) * 0.5, 1, '.', '') ?>s;--r:<?= number_format((($k + $s * 3) % 5) * 0.25, 2, '.', '') ?>s"></i>
    <?php endfor; endfor; ?>
</div>
<?php
}

/** Frises de la corniche : motif nordique, glaçons et lambrequin. */
function avent_decor_corniche(): void
{
    $couleurs = ['#FFC857', '#FF6B6B', '#7EE0C8', '#B9A6FF'];
    ?>
<svg class="frise" aria-hidden="true" width="100%" height="40">
    <defs>
        <pattern id="nordique" width="60" height="40" patternUnits="userSpaceOnUse">
            <rect width="60" height="40" fill="#9C3F4A"/>
            <path d="M0 5H60M0 35H60" stroke="#F6EBD9" stroke-width="2"/>
            <polygon points="30,9 41,20 30,31 19,20" fill="#F6EBD9"/>
            <polygon points="30,14 36,20 30,26 24,20" fill="#FFC857"/>
            <polygon points="-6,20 0,14 6,20 0,26" fill="#F6EBD9"/>
            <polygon points="54,20 60,14 66,20 60,26" fill="#F6EBD9"/>
            <circle cx="15" cy="20" r="2.6" fill="#12A594"/><circle cx="45" cy="20" r="2.6" fill="#12A594"/>
            <polygon points="15,10 18,15 12,15" fill="#12A594"/><polygon points="45,30 48,25 42,25" fill="#12A594"/>
        </pattern>
    </defs>
    <rect width="100%" height="40" fill="url(#nordique)"/>
</svg>
<svg class="glacons" aria-hidden="true" width="100%" height="36">
    <defs>
        <pattern id="glacons" width="90" height="36" patternUnits="userSpaceOnUse">
            <polygon points="6,0 14,0 10,22" fill="#E8F4FF" opacity="0.95"/>
            <polygon points="24,0 36,0 30,34" fill="#E8F4FF" opacity="0.95"/>
            <polygon points="46,0 54,0 50,16" fill="#E8F4FF" opacity="0.95"/>
            <polygon points="64,0 76,0 70,28" fill="#E8F4FF" opacity="0.95"/>
        </pattern>
    </defs>
    <rect width="100%" height="36" fill="url(#glacons)"/>
</svg>
<svg class="lambrequin" aria-hidden="true" width="100%" height="22">
    <defs>
        <pattern id="lambrequin" width="28" height="22" patternUnits="userSpaceOnUse">
            <path d="M0 0H28V7Q14 28 0 7Z" fill="#F6EBD9" stroke="#5A3420" stroke-width="2"/>
            <circle cx="14" cy="9" r="2.8" fill="#D93A3F"/>
        </pattern>
    </defs>
    <rect width="100%" height="22" fill="url(#lambrequin)"/>
</svg>
<div class="guirlande" aria-hidden="true">
    <?php for ($i = 0; $i < 17; $i++) : ?>
        <i style="margin-top:<?= (int) round(12 * sin(M_PI * $i / 16)) ?>px;--c:<?= $couleurs[$i % 4] ?>;--d:<?= number_format(1.6 + ($i % 3) * 0.5, 1, '.', '') ?>s;--r:<?= number_format(($i % 5) * 0.3, 1, '.', '') ?>s"></i>
    <?php endfor; ?>
</div>
<?php
}

/** Sapin en pot, pour le rez-de-chaussée des grands écrans. */
function avent_decor_sapin_pot(): string
{
    $lumieres = '';
    foreach ([[34, 70, '#ff5a5a'], [58, 62, '#ffd45e'], [26, 98, '#6bb6ff'], [66, 96, '#ff5a5a'], [40, 122, '#ffd45e'], [60, 128, '#6bb6ff'], [32, 146, '#ff5a5a'], [70, 150, '#ffd45e']] as [$x, $y, $c]) {
        $lumieres .= '<circle cx="' . $x . '" cy="' . $y . '" r="3.4" fill="' . $c . '"/>';
    }
    return '<svg class="sapin-pot" viewBox="0 0 100 190" aria-hidden="true">' .
        '<polygon points="50,14 82,76 66,76 90,118 70,118 98,166 2,166 30,118 10,118 34,76 18,76" fill="#1f6b4c"/>' .
        '<polygon points="50,14 62,40 38,40" fill="#f4f8ff"/><path d="M20 76 h14 l-8 10z M66 76 h14 l-6 10z M12 118 h14 l-7 10z M72 118 h16 l-8 10z M6 164 h90 v4 h-90z" fill="#f4f8ff" opacity=".9"/>' .
        $lumieres .
        '<path d="M50 4 l4 8 9 1 -6.5 6 2 9 -8.5 -5 -8.5 5 2 -9 -6.5 -6 9 -1z" fill="#ffc857"/>' .
        '<path d="M26 166 h48 l-6 22 h-36z" fill="#b8442f"/><rect x="22" y="162" width="56" height="8" rx="3" fill="#d4573f"/></svg>';
}

/** Fenêtre sur un salon du gîte (4 variantes) : décor intérieur chaleureux, vu de l'extérieur. */
function avent_decor_lounge(int $variante): string
{
    $mur = '<rect width="300" height="200" fill="#c26a35"/><path d="M0 0v150M60 0v150M120 0v150M180 0v150M240 0v150" stroke="#a65a2c" stroke-width="2" opacity=".5"/>' .
        '<rect y="150" width="300" height="50" fill="#5a3320"/><path d="M0 164h300M0 180h300" stroke="#43251a" stroke-width="2"/>';
    $lueur = '<radialGradient id="lg' . $variante . '" cx=".5" cy=".6" r=".7"><stop offset="0" stop-color="#ffd98a" stop-opacity=".55"/><stop offset="1" stop-color="#ffd98a" stop-opacity="0"/></radialGradient>';
    $corps = '';
    if ($variante === 1) {        // cheminée, bas de Noël, fauteuil, lampe
        $corps = '<rect x="92" y="62" width="116" height="94" fill="#8f8f98"/><path d="M92 86h116M92 110h116M92 134h116M120 62v24M170 62v24M145 86v24M195 86v24M120 110v24M170 110v24" stroke="#6f6f78" stroke-width="2"/>' .
            '<rect x="116" y="96" width="68" height="60" rx="3" fill="#1d1210"/><rect x="122" y="142" width="56" height="8" rx="4" fill="#6b3e2e"/>' .
            '<g class="flamme"><path d="M130 144c-8-14 2-22 4-32 8 8 10 14 6 22 6-2 8-8 8-14 12 12 14 24 2 24z" fill="#ff8a1f"/><path d="M142 144c-6-9 0-14 3-20 6 5 7 10 4 15 5-2 6-6 6-10 6 8 6 15-2 15z" fill="#ffd45e"/></g>' .
            '<rect x="82" y="52" width="136" height="12" rx="3" fill="#5c3a1b"/><path d="M84 52q16-10 33 0t33 0 33 0 33 0" fill="none" stroke="#1f6b4c" stroke-width="6"/>' .
            '<g><rect x="102" y="64" width="9" height="14" fill="#d93a3f"/><ellipse cx="104" cy="80" rx="8" ry="4.5" fill="#d93a3f"/><rect x="144" y="64" width="9" height="14" fill="#1f8a64"/><ellipse cx="146" cy="80" rx="8" ry="4.5" fill="#1f8a64"/><rect x="186" y="64" width="9" height="14" fill="#d93a3f"/><ellipse cx="188" cy="80" rx="8" ry="4.5" fill="#d93a3f"/></g>' .
            '<circle cx="150" cy="32" r="15" fill="none" stroke="#1f6b4c" stroke-width="7"/><path d="M142 46l8 -6 8 6" fill="none" stroke="#d93a3f" stroke-width="4"/>' .
            '<rect x="14" y="108" width="52" height="46" rx="12" fill="#2f6e55"/><rect x="8" y="124" width="64" height="28" rx="9" fill="#3c8a6b"/><rect x="22" y="152" width="6" height="8" fill="#43251a"/><rect x="52" y="152" width="6" height="8" fill="#43251a"/>' .
            '<line x1="252" y1="66" x2="252" y2="156" stroke="#2a1a10" stroke-width="4"/><path d="M238 66l8-18h12l8 18z" fill="#ffe29a"/><circle cx="252" cy="62" r="30" fill="url(#lg1)"/>';
    } elseif ($variante === 2) {  // canapé, tableaux, lampadaire, petit sapin
        $corps = '<rect x="36" y="26" width="52" height="40" fill="#5c3a1b"/><rect x="42" y="32" width="40" height="28" fill="#8fc3ea"/><path d="M42 54l12-14 10 10 8-8 10 12v6h-40z" fill="#4d8a5f"/>' .
            '<rect x="108" y="22" width="44" height="52" fill="#5c3a1b"/><rect x="114" y="28" width="32" height="40" fill="#f1d9a8"/><circle cx="130" cy="48" r="10" fill="#d93a3f"/>' .
            '<rect x="70" y="100" width="170" height="52" rx="16" fill="#a8262d"/><rect x="62" y="124" width="186" height="32" rx="10" fill="#c0343e"/><rect x="54" y="108" width="24" height="48" rx="10" fill="#8f1f26"/><rect x="232" y="108" width="24" height="48" rx="10" fill="#8f1f26"/>' .
            '<rect x="92" y="108" width="30" height="26" rx="6" fill="#1f8a64" transform="rotate(-8 107 121)"/><rect x="190" y="108" width="30" height="26" rx="6" fill="#f6ebd9" transform="rotate(8 205 121)"/><rect x="130" y="112" width="48" height="22" rx="6" fill="#e6b84a"/>' .
            '<line x1="24" y1="60" x2="24" y2="156" stroke="#2a1a10" stroke-width="4"/><path d="M10 60l8-20h12l8 20z" fill="#ffe29a"/><circle cx="24" cy="56" r="34" fill="url(#lg2)"/>' .
            '<polygon points="276,84 292,124 260,124" fill="#1f6b4c"/><polygon points="276,106 296,148 256,148" fill="#1f8a64"/><rect x="272" y="148" width="8" height="10" fill="#6b3e2e"/><circle cx="272" cy="112" r="2.6" fill="#ffd45e"/><circle cx="283" cy="130" r="2.6" fill="#ff5a5a"/><circle cx="268" cy="138" r="2.6" fill="#6bb6ff"/>';
    } elseif ($variante === 3) {  // bibliothèque, fauteuil, guirlande
        $couleurs = ['#d93a3f', '#1f8a64', '#e6b84a', '#4b5fd1', '#8f4a9a', '#f6ebd9', '#c2570c'];
        $corps = '<rect x="14" y="20" width="272" height="132" fill="#5c3a1b"/>';
        foreach ([[26, 66], [68, 108], [110, 150]] as $r => [$y0, $y1]) {
            $corps .= '<rect x="20" y="' . $y0 . '" width="260" height="' . ($y1 - $y0 - 6) . '" fill="#3a2415"/>';
            $x = 24;
            for ($k = 0; $x < 270; $k++) {
                $w = 7 + (($k * 5 + $r * 3) % 6);
                $h = 26 + (($k * 7 + $r * 11) % 14);
                if ($k % 9 === 8) { $x += 10; continue; }   // petit trou : un objet posé plus loin
                $corps .= '<rect x="' . $x . '" y="' . ($y1 - 6 - $h - 0) . '" width="' . $w . '" height="' . $h . '" fill="' . $couleurs[($k + $r * 2) % 7] . '"/>';
                $x += $w + 1;
            }
            $corps .= '<rect x="14" y="' . ($y1 - 6) . '" width="272" height="6" fill="#6b3e2e"/>';
        }
        $corps .= '<rect x="150" y="96" width="120" height="58" rx="14" fill="#2f4f9c"/><rect x="140" y="118" width="140" height="38" rx="12" fill="#3f64bd"/>' .
            '<rect x="170" y="108" width="30" height="24" rx="6" fill="#e6b84a" transform="rotate(-6 185 120)"/>' .
            '<rect x="40" y="132" width="64" height="8" rx="3" fill="#6b3e2e"/><rect x="48" y="140" width="6" height="16" fill="#43251a"/><rect x="90" y="140" width="6" height="16" fill="#43251a"/>' .
            '<rect x="62" y="120" width="18" height="12" rx="3" fill="#f6ebd9"/><path d="M66 118q2-6 0-9M74 118q2-6 0-9" stroke="#fff" stroke-width="2" fill="none" opacity=".7"/>' .
            '<path d="M0 8q38 18 75 0t75 0 75 0 75 0" fill="none" stroke="#1f6b4c" stroke-width="5"/>';
        foreach ([[19, 14, '#ff5a5a'], [56, 20, '#ffd45e'], [94, 14, '#6bb6ff'], [131, 8, '#ff5a5a'], [169, 14, '#ffd45e'], [206, 20, '#6bb6ff'], [244, 14, '#ff5a5a'], [281, 8, '#ffd45e']] as [$x, $y, $c]) {
            $corps .= '<circle cx="' . $x . '" cy="' . $y . '" r="3.6" fill="' . $c . '"/>';
        }
        $corps .= '<circle cx="150" cy="110" r="70" fill="url(#lg3)"/>';
    } else {                      // grand sapin décoré, cadeaux, rideaux
        $corps = '<ellipse cx="150" cy="168" rx="120" ry="12" fill="#8f1f26" opacity=".85"/>' .
            '<rect x="144" y="130" width="12" height="30" fill="#6b3e2e"/>' .
            '<polygon points="150,16 210,84 186,84 224,126 196,126 232,160 68,160 104,126 76,126 114,84 90,84" fill="#1f6b4c"/>' .
            '<polygon points="150,16 164,36 136,36" fill="#f4f8ff"/>';
        foreach ([[132, 60, '#ff5a5a'], [168, 78, '#ffd45e'], [120, 100, '#6bb6ff'], [180, 110, '#ff5a5a'], [150, 92, '#ffd45e'], [98, 142, '#6bb6ff'], [206, 144, '#ffd45e'], [140, 128, '#ff5a5a'], [172, 140, '#6bb6ff']] as [$x, $y, $c]) {
            $corps .= '<circle cx="' . $x . '" cy="' . $y . '" r="4.4" fill="' . $c . '"/>';
        }
        $corps .= '<path d="M150 6l5 10 11 1.5 -8 7.5 2 11 -10 -5.5 -10 5.5 2 -11 -8 -7.5 11 -1.5z" fill="#ffc857"/>' .
            '<rect x="70" y="150" width="34" height="24" fill="#d93a3f"/><rect x="84" y="150" width="6" height="24" fill="#f6ebd9"/>' .
            '<rect x="196" y="146" width="30" height="28" fill="#1f8a64"/><rect x="208" y="146" width="6" height="28" fill="#e6b84a"/>' .
            '<rect x="110" y="160" width="26" height="16" fill="#4b5fd1"/><rect x="120" y="160" width="6" height="16" fill="#f6ebd9"/>' .
            '<path d="M0 0h42v176q-10-24-21-18t-21-18z" fill="#a8262d"/><path d="M300 0h-42v176q10-24 21-18t21-18z" fill="#a8262d"/><path d="M14 0v150M28 0v160M286 0v150M272 0v160" stroke="#8f1f26" stroke-width="3"/>' .
            '<circle cx="150" cy="100" r="80" fill="url(#lg4)"/>';
    }
    return '<div class="lounge" aria-hidden="true"><svg viewBox="0 0 300 200" preserveAspectRatio="xMidYMid slice"><defs>' . $lueur . '</defs>' . $mur . $corps . '</svg></div>';
}

/** Rez-de-chaussée : lanternes, guirlandes et porte de la réception. */
function avent_decor_porte(): void
{
    $arche = '<svg class="arche" width="30" height="96" viewBox="0 0 30 96" fill="none"><path d="M8 96V28a8 8 0 0 1 16 0" stroke="#FFF6E3" stroke-width="9" stroke-linecap="round"/><path d="M8 96V28a8 8 0 0 1 16 0" stroke="#D93A3F" stroke-width="9" stroke-dasharray="7 7"/></svg>';
    ?>
<div class="rdc" aria-hidden="true">
    <div class="rdc-cote"><?= avent_decor_lounge(1) ?><?= avent_decor_sapin_pot() ?><?= avent_decor_lounge(2) ?></div>
    <div class="applique"></div>
    <?= $arche ?>
    <div class="reception">
        <div class="potence"><i class="corde g"></i><i class="corde d"></i></div>
        <div class="panneau panneau-reception">
            <?= avent_gravure('cloche') ?>
            <span class="gravure-texte">Réception</span>
        </div>
        <div class="porte">
            <div class="porte-fente"></div>
            <div class="couronne"><i></i><b class="p1"></b><b class="p2"></b></div>
            <div class="poignee g"></div><div class="poignee d"></div>
        </div>
    </div>
    <?= $arche ?>
    <div class="applique"></div>
    <div class="rdc-cote"><?= avent_decor_lounge(3) ?><?= avent_decor_sapin_pot() ?><?= avent_decor_lounge(4) ?></div>
</div>
<?php
}

/** Cadeaux au pied du chalet. */
function avent_decor_cadeaux(): void
{
    ?>
<div class="deco cadeaux cadeaux-g" aria-hidden="true">
    <div class="cadeau c1"><i class="v"></i><i class="h"></i><b></b></div>
    <div class="cadeau c2"><i class="v"></i><b></b></div>
    <div class="cadeau c3"><i class="v"></i></div>
</div>
<div class="deco cadeaux cadeaux-d" aria-hidden="true">
    <div class="cadeau c4"><i class="v"></i><b></b></div>
    <div class="cadeau c5"><i class="v"></i></div>
</div>
<?php
}

/** Balcon sous une fenêtre ; le motif (couronne, lanternes, cadeaux, sapins) alterne. */
function avent_decor_balcon(int $indice): string
{
    return '<span class="balcon b' . ($indice % 4) . '" aria-hidden="true"><i></i></span>';
}

/** Petits motifs « gravés » dans le bois des panneaux. */
function avent_gravure(string $motif): string
{
    $formes = [
        'sapin' => '<path d="M12 2 18 10h-3.4l4.4 6.5H13V22h-2v-5.5H5L9.4 10H6z"/>',
        'flocon' => '<g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 2v20M3.3 7l17.4 10M3.3 17 20.7 7"/><path d="M9.5 4 12 6.5 14.5 4M9.5 20 12 17.5 14.5 20M4.6 9.2l3.4.9-.9 3.4M19.4 14.8l-3.4-.9.9-3.4M4.6 14.8l3.4-.9-.9-3.4M19.4 9.2l-3.4.9.9 3.4"/></g>',
        'cloche' => '<path d="M12 2.5a1.6 1.6 0 0 1 1.6 1.6v.6c2.8.7 4.4 3 4.4 6v3.4l2 2.4v1H4v-1l2-2.4V10.7c0-3 1.6-5.3 4.4-6v-.6A1.6 1.6 0 0 1 12 2.5zM9.6 19.2h4.8a2.4 2.4 0 0 1-4.8 0z"/>',
    ];
    return '<svg class="gravure" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">' . ($formes[$motif] ?? '') . '</svg>';
}
