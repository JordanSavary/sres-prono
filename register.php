<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Jeton CSRF invalide. Veuillez recharger la page.');
    }
    
    $captcha_input = isset($_POST['captcha']) ? (int)$_POST['captcha'] : null;
    $captcha_expected = isset($_SESSION['captcha_answer']) ? (int)$_SESSION['captcha_answer'] : null;

    if ($captcha_input !== $captcha_expected) {
        $message = "Captcha incorrect.";
        // Regénère une nouvelle question à la prochaine vue
        header("Location: register.php?error=captcha");
        exit;
    }
    
    $pseudo = trim($_POST['pseudo']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($pseudo) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "L'email n'est pas valide.";
    } elseif ($password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'email ou le pseudo existent déjà
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? OR pseudo = ?");
        $stmt->execute([$email, $pseudo]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $message = "Cet email ou ce pseudo est déjà utilisé.";
        } else {
            // Hasher le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insérer l'utilisateur dans la base
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (pseudo, email, mot_de_passe) VALUES (?, ?, ?)");
            $stmt->execute([$pseudo, $email, $hashedPassword]);

            // Rediriger vers la page de connexion
            header('Location: login.php');
            exit();
        }
    }
}

unset($_SESSION['captcha_answer']);

?>

<!DOCTYPE html>
<html lang="fr">
<?php include 'head.php'; ?>
<body>
    <?php if ($message): ?>
        <p style="color: red;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <section class="container">
        <div class="login-container">
            <div class="circle circle-one"></div>
            <div class="form-container">
                <img src="./styles/trophy.png" alt="illustration" class="illustration" />
                <h1 class="">Inscription</h1>
                <form method="post" action="" class="login-form">
                    <input type="pseudo" name="pseudo" id="pseudo" placeholder="Identifiant" required />
                    <input type="email" name="email" id="email" placeholder="Email" required/>
                    <input type="password" name="password" id="password" placeholder="Mot de passe" required />
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirmer le mot de passe" required/>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <label for="captcha">Captcha :</label>
                    <div style="margin-bottom: 8px;">
                        <img src="captcha.php?rand=<?= time() ?>" alt="Captcha" />
                    </div>
                    <input type="text" name="captcha" id="captcha" placeholder="Résultat" required />
                    <button class="btn-enregistrer" type="submit">S'inscrire</button>
                </form>
                <div class="register-forget opacity">
                    <a href="index.php">Retour à l'accueil</a>
                </div>
            </div>
            <div class="circle circle-two"></div>
        </div>
    </section>

</body>
</html>
