<?php
session_start();
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}
include 'includes/db.php';
require_once __DIR__ . '/avent/includes/decor_accueil.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

// Retour vers le calendrier de l'avent après connexion (chemins avent/... uniquement)
if (isset($_GET['redirect']) && is_string($_GET['redirect'])
    && preg_match('#^avent/[A-Za-z0-9_./-]*(\?[A-Za-z0-9_=&.-]*)?$#', $_GET['redirect'])
    && strpos($_GET['redirect'], '..') === false) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Jeton CSRF invalide. Veuillez recharger la page.');
    }

    if ($_SESSION['login_attempts'] >= 5) {
        $waitTime = 300 - (time() - $_SESSION['last_attempt_time']); // 300 = 5min

        if ($waitTime > 0) {
            die("Trop de tentatives. Réessayez dans $waitTime secondes.");
        } else {
            // On réinitialise le compteur après le délai
            $_SESSION['login_attempts'] = 0;
        }
    }   

    $email = strtolower(trim($_POST['pseudo']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Veuillez remplir tous les champs.";
    } else {
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = ? OR email = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Connexion réussie : on stocke le pseudo dans la session
            $_SESSION['utilisateur'] = $user['pseudo'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt_time'] = time();

            // Redirige vers le calendrier de l'avent si demandé, sinon vers le dashboard
            $destination = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $destination);
            exit();
        } else {
            $message = "Identifiant ou mot de passe incorrect.";
            $_SESSION['login_attempts'] += 1;
            $_SESSION['last_attempt_time'] = time();
            $error = "Identifiants incorrects.";
        }
        
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion – Gîte des Flocons</title>
    <link rel="preload" href="avent/assets/fonts/bricolage-grotesque-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="apple-touch-icon" href="assets/favicon.png">
    <link rel="stylesheet" href="avent/assets/accueil.css?v=1">
</head>
<body class="accueil">
    <?php accueil_hall(); ?>
    <div class="acc-ombre" aria-hidden="true"></div>
    <div class="acc-poutre" aria-hidden="true"></div>
    <?php accueil_guirlande(); ?>

    <main class="acc-scene">
        <section class="acc-carte" aria-labelledby="titre-connexion">
            <span class="acc-plaque">Registre de l'hôtel</span>
            <h1 id="titre-connexion">Connexion</h1>
            <p class="acc-sous">Signe le registre pour récupérer ta clé.</p>
            <?php if ($message) : ?>
                <p class="acc-erreur" role="alert"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>
            <form method="post" action="">
                <label class="acc-champ">
                    <span>Identifiant ou email</span>
                    <input type="text" name="pseudo" id="pseudo" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" required>
                </label>
                <label class="acc-champ">
                    <span>Mot de passe</span>
                    <input type="password" name="password" id="password" autocomplete="current-password" required>
                </label>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button class="acc-bouton" type="submit">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="7.5" cy="12" r="4.5" stroke="#3b1d0a" stroke-width="2.4"/><path d="M12 12h9M18 12v4M21 12v3" stroke="#3b1d0a" stroke-width="2.4" stroke-linecap="round"/></svg>
                    Se connecter
                </button>
            </form>
            <div class="acc-liens">
                <a href="register.php">S'inscrire</a>
                <a href="reset_password.php">Mot de passe oublié</a>
            </div>
        </section>

    </main>
</body>
</html>
