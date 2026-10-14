<?php
session_start();

// Génère une question simple
$a = rand(1, 9);
$b = rand(1, 9);
$question = "$a + $b = ?";
$_SESSION['captcha_answer'] = $a + $b;

// Création de l'image
$width = 150;
$height = 50;
$image = imagecreatetruecolor($width, $height);

// Couleurs
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
$gray = imagecolorallocate($image, 128, 128, 128);

// Fond
imagefilledrectangle($image, 0, 0, $width, $height, $white);

// Ajoute des lignes aléatoires
for ($i = 0; $i < 5; $i++) {
    $lineColor = imagecolorallocate($image, rand(100,255), rand(100,255), rand(100,255));
    imageline($image, 0, rand(0,$height), $width, rand(0,$height), $lineColor);
}

// Ajoute du bruit
for ($i = 0; $i < 1000; $i++) {
    $noiseColor = imagecolorallocate($image, rand(150,255), rand(150,255), rand(150,255));
    imagesetpixel($image, rand(0,$width), rand(0,$height), $noiseColor);
}

// Écrit le texte avec une légère rotation
$fontSize = 20;
$fontFile = __DIR__ . '/arial.ttf'; // Mets un fichier TTF ici
if (!file_exists($fontFile)) {
    // Si pas de TTF, fallback avec imagestring
    imagestring($image, 5, 45, 15, $question, $black);
} else {
    imagettftext($image, $fontSize, rand(-15, 15), 25, 35, $black, $fontFile, $question);
}

// Appliquer une légère distorsion sinusoïdale
$distorted = imagecreatetruecolor($width, $height);
imagefill($distorted, 0, 0, $white);

for ($x = 0; $x < $width; $x++) {
    for ($y = 0; $y < $height; $y++) {
        $newY = $y + sin($x / 10) * 5;
        $newYInt = (int)round($newY);

        if ($newYInt >= 0 && $newYInt < $height) {
            imagesetpixel($distorted, $x, $y, imagecolorat($image, $x, $newYInt));
        }
    }
}

// En-tête HTTP
header("Content-Type: image/png");
imagepng($distorted);
imagedestroy($image);
imagedestroy($distorted);
