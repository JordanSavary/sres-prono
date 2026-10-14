<?php
session_start();
include 'includes/db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur'])) {
    header('Location: login.php');
    exit();
}
else {
    header('Location: dashboard.php');
    exit();
}

?>