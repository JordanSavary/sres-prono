<?php
session_start();
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}
include 'includes/db.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

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

            // Redirige vers le dashboard ou l'accueil
            header('Location: dashboard.php');
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
<?php include 'head.php'; ?>
<body>
    <section class="container">
        <div class="login-container">
            <div class="circle circle-one"></div>
            <div class="form-container">
                <img src="./styles/trophy.png" alt="illustration" class="illustration" />
                <h1 class="">Connexion</h1>
                <?php if ($message): ?>
                    <p style="color: red;"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
                <form method="post" action="" class="login-form">
                    <input type="pseudo" name="pseudo" id="pseudo" placeholder="Identifiant ou Email" />
                    <input type="password" name="password" id="password" placeholder="Mot de passe" />
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button class="btn-enregistrer" type="submit">Se connecter</button>
                </form>
                <div class="register-forget opacity">
                    <a href="register.php">S'inscrire</a>
                    <a href="reset_password.php">Mot de passe oublié</a>
                </div>
            </div>
            <div class="circle circle-two"></div>
        </div>

    </section>

</body>
<?php include 'script.php'; ?>
</html>
