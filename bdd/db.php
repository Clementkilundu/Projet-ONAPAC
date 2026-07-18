<?php
// inc/db.php

// Définition des paramètres de connexion à la base de données
$host     = 'localhost';
$dbname   = 'onapac_db'; // Remplacez par le nom exact de votre base de données à 12 tables
$username = 'root';
$password = ''; // Laissez vide sous XAMPP/WampServer par défaut, ou mettez votre mot de passe

try {
    // Initialisation de la connexion avec le jeu de caractères UTF-8 pour les accents
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configuration des options PDO pour lever des exceptions en cas d'erreur SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mode de récupération par défaut : Tableaux associatifs
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // En cas d'échec, on arrête le script et on affiche l'erreur (Utile en développement)
    die("Erreur de connexion à la base de données de l'ONAPAC : " . $e->getMessage());
}
?>