<?php $page_actuelle = basename($_SERVER['PHP_SELF']); ?>
<div class="header">
    <a href="dashboard.php" class="logo">
        <img src="./styles/logo-header.png" alt="illustration" class="logo-img"/>
        <h1>SREStimate</h1>
    </a>
    <nav id="nav-links">
        <a href="dashboard.php" <?= $page_actuelle === 'dashboard.php' ? 'class="active"' : '' ?>>
            <img src="./styles/logo-header.png" alt="illustration" class="logo-img"/>
            <span>Accueil</span>
        </a>
        <a href="classement.php" <?= $page_actuelle === 'classement.php' ? 'class="active"' : '' ?>>
            <i class="fa-solid fa-trophy"></i>
            <span>Classement</span>
        </a>
        <a href="compte.php" <?= $page_actuelle === 'compte.php' ? 'class="active"' : '' ?>>
            <i class="fa-solid fa-user"></i>
            <span>Mon compte</span>
        </a>
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Déconnexion</span>
        </a>
        <?php if ($_SESSION['utilisateur'] == 'admin') : ?>
            <a href="admin.php" <?= $page_actuelle === 'admin.php' ? 'class="active"' : '' ?>>
                <i class="fa-solid fa-gear"></i>
                <span>Admin</span>
            </a>
        <?php endif; ?>
    </nav>
    <!-- Burger gardé pour les tailles intermédiaires si besoin -->
    <div class="burger" id="burger">&#9776;</div>
</div>