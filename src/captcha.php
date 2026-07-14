<?php
// Public endpoint (no auth): renders a self-hosted CAPTCHA image for register.php.
// No third-party service (reCAPTCHA/Turnstile/etc) - a simple math challenge drawn
// with GD onto a noisy background, expected answer kept server-side in the session.
require_once 'includes/bootstrap.php';

$a = random_int(1, 9);
$b = random_int(1, 9);
$_SESSION['captcha_answer'] = (string) ($a + $b);
$text = "{$a} + {$b} =";

$width = 160;
$height = 60;
$image = imagecreatetruecolor($width, $height);
$bg = imagecolorallocate($image, 245, 245, 245);
$fg = imagecolorallocate($image, 30, 30, 30);
imagefill($image, 0, 0, $bg);

// Noise: random lines behind the text, purely cosmetic distortion.
for ($i = 0; $i < 8; $i++) {
    $lineColor = imagecolorallocate($image, random_int(180, 220), random_int(180, 220), random_int(180, 220));
    imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
}

$fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
if (is_file($fontFile) && function_exists('imagettftext')) {
    $fontSize = 22;
    $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
    $textWidth = abs($bbox[2] - $bbox[0]);
    $textHeight = abs($bbox[1] - $bbox[7]);
    $x = (int) (($width - $textWidth) / 2);
    $y = (int) (($height + $textHeight) / 2);
    imagettftext($image, $fontSize, 0, $x, $y, $fg, $fontFile, $text);
} else {
    imagestring($image, 5, 10, 20, $text, $fg);
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
imagepng($image);
imagedestroy($image);
