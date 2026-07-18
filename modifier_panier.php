<?php
// modifier_panier.php
session_start();
require_once 'bdd/db.php';

// 1. Protection d'accès : l'utilisateur doit être connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour modifier votre panier.";
    header('Location: connexion.php');
    exit();
}

$id_utilisateur = $_SESSION['user_id'];

// 2. Vérification des données requises en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_panier']) || !isset($_POST['quantite'])) {
    $_SESSION['error'] = "Requête invalide.";
    header('Location: panier.php');
    exit();
}

$id_panier = (int)$_POST['id_panier'];
$nouvelle_quantite = (int)$_POST['quantite'];

if ($nouvelle_quantite <= 0) {
    $_SESSION['error'] = "La quantité doit être supérieure à 0.";
    header('Location: panier.php');
    exit();
}

try {
    // 3. Vérifier que la ligne de panier appartient bien à l'utilisateur connecté
    // et récupérer en même temps le stock disponible pour le produit associé
    $stmt = $pdo->prepare("SELECT p.id_panier, p.id_produit, prod.nom_produit, prod.stock_disponible 
                           FROM paniers p
                           INNER JOIN produits prod ON p.id_produit = prod.id_produit
                           WHERE p.id_panier = :id_panier AND p.id_utilisateur = :user_id");
    $stmt->execute([
        ':id_panier' => $id_panier,
        ':user_id' => $id_utilisateur
    ]);
    $item = $stmt->fetch();

    if (!$item) {
        throw new Exception("Ce lot n'existe pas dans votre panier ou vous n'avez pas l'autorisation d'y accéder.");
    }

    $stock_disponible = (int)$item['stock_disponible'];
    $nom_produit = $item['nom_produit'];

    // 4. Validation par rapport au stock physique réel
    if ($nouvelle_quantite > $stock_disponible) {
        throw new Exception("La quantité demandée pour le lot \"" . htmlspecialchars($nom_produit) . "\" dépasse la limite du stock disponible (" . $stock_disponible . ").");
    }

    // 5. Mise à jour de la quantité en base de données
    $stmt_update = $pdo->prepare("UPDATE paniers SET quantite = :qty WHERE id_panier = :id_panier");
    $stmt_update->execute([
        ':qty' => $nouvelle_quantite,
        ':id_panier' => $id_panier
    ]);

    $_SESSION['success'] = "La quantité pour le lot \"" . htmlspecialchars($nom_produit) . "\" a bien été mise à jour.";

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

// Redirection vers le panier visuel
header('Location: panier.php');
exit();