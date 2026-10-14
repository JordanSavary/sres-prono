<?php
include 'includes/db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];

    // Vérifie si l’email existe
    $stmt = $pdo->prepare("SELECT pseudo FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Génère un mot de passe temporaire
        $tempPassword = bin2hex(random_bytes(4)); // Ex: "a3f5c2d8"
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

        // Met à jour dans la base
        $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE pseudo = ?");
        $stmt->execute([$hashedPassword, $user['pseudo']]);

        // Sujet encodé correctement
        $subject = "=?UTF-8?B?" . base64_encode("Réinitialisation de votre mot de passe") . "?=";

        // Corps du mail HTML
        $mel = "
        <html>
        <head>
        <meta charset='UTF-8'>
        </head>
        <body>
        <p>Bonjour,</p>
        <p>Voici votre mot de passe temporaire : <strong>$tempPassword</strong></p>
        <p>Merci de le changer après connexion.</p>
        <p><a href='https://sres-prono.fr'>Retour au site</a></p>
        </body>
        </html>
        ";

        // En-têtes complets et cohérents
        $from = "support@sres-prono.fr";  // Adresse valide liée au domaine

        $text = "Bonjour,\n\nVoici votre mot de passe temporaire : $tempPassword\nMerci de le changer après connexion.\n\nhttps://sres-prono.fr";
        
        // Encodage multipart/alternative
        $boundary = uniqid("boundary");
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: $from\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "Return-Path: $from\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "Message-ID: <" . uniqid() . "@sres-prono.fr>\r\n";

        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= $text . "\r\n\r\n";

        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $mel . "\r\n\r\n";

        $body .= "--$boundary--";

        // Envoie du mail
        mail($email, $subject, $body, $headers);

        // Message de confirmation
        $message = "Un mot de passe temporaire a été envoyé par e-mail.";
    } else {
        $message = "Adresse e-mail introuvable.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include 'head.php'; ?>
<body>
    <section class="container">
        <div class="login-container">
            <div class="circle circle-one"></div>
            <div class="form-container">
                <img src="./styles/trophy.png" alt="illustration" class="illustration" />
                <h1 class="">Réinitialiser</h1>
                <?php if ($message): ?>
                    <p style="color: red;"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
                <form method="post" action="" class="login-form">
                    <input type="email" name="email" id="email" placeholder="Email" required/>
                    <button class="btn-enregistrer" type="submit">Réinitialiser le mot de passe</button>
                </form>
                <div class="register-forget opacity">
                    <a href="login.php">Retour à l'accueil</a>
                </div>
            </div>
            <div class="circle circle-two"></div>
        </div>

    </section>

</body>
</html>
