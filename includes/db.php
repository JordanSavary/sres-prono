<?php
// Paramètres de connexion à la base de données
$host = 'localhost';      // Hôte de la base de données (souvent 'localhost')
$db   = 'prono';   // Nom de ta base de données
$user = 'root';           // Nom d'utilisateur de la base
$pass = '';               // Mot de passe (vide sur XAMPP/MAMP par défaut)
$charset = 'utf8mb4';     // Jeu de caractères

// Construction du DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Gestion des erreurs
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Résultats sous forme de tableau associatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Désactive l'émulation des requêtes préparées
];

// Connexion à la base
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // En cas d'erreur, on arrête tout et on affiche le message
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>