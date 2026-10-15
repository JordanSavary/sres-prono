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
    <div class="enseigne">Gîte des Flocons</div>
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

/** Rez-de-chaussée : lanternes, guirlandes et porte de la réception. */
function avent_decor_porte(): void
{
    $arche = '<svg class="arche" width="30" height="96" viewBox="0 0 30 96" fill="none"><path d="M8 96V28a8 8 0 0 1 16 0" stroke="#FFF6E3" stroke-width="9" stroke-linecap="round"/><path d="M8 96V28a8 8 0 0 1 16 0" stroke="#D93A3F" stroke-width="9" stroke-dasharray="7 7"/></svg>';
    ?>
<div class="rdc" aria-hidden="true">
    <div class="applique"></div>
    <?= $arche ?>
    <div class="reception">
        <div class="reception-nom">Réception</div>
        <div class="porte">
            <div class="porte-fente"></div>
            <div class="couronne"><i></i><b class="p1"></b><b class="p2"></b></div>
            <div class="poignee g"></div><div class="poignee d"></div>
        </div>
    </div>
    <?= $arche ?>
    <div class="applique"></div>
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
