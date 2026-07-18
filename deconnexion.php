<?php
// deconnexion.php
session_start();

// Étape 1 : On vide toutes les variables de session
$_SESSION = array();

// Étape 2 : On détruit le cookie de session si présent
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Étape 3 : On détruit la session sur le serveur
session_destroy();

// Étape 4 : Redirection vers la page de connexion (ou d'accueil)
header("Location: connexion.php");
exit();