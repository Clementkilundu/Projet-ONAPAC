<?php
// supprimer_panier.php
session_start();
require_once 'bdd/db.php';

// 1. Protection d'accès
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour modifier votre panier.";
    header('Location: connexion.php');
    exit();
}

$id_utilisateur = $_SESSION['user_id'];

// 2. Vérification des données requises en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_panier'])) {
    $_SESSION['error'] = "Requête de suppression invalide.";
    header('Location: panier.php');
    exit();
}

$id_panier = (int)$_POST['id_panier'];

try {
    // 3. Récupérer les détails pour un message personnalisé (optionnel mais tellement plus pro !)
    $stmt_info = $pdo->prepare("SELECT prod.nom_produit FROM paniers p 
                                INNER JOIN produits prod ON p.id_produit = prod.id_produit
                                WHERE p.id_panier = :id_panier AND p.id_utilisateur = :user_id");
    $stmt_info->execute([
        ':id_panier' => $id_panier,
        ':user_id' => $id_utilisateur
    ]);
    $item = $stmt_info->fetch();

    if (!$item) {
        throw new Exception("Ce lot n'est plus présent dans votre panier.");
    }

    $nom_produit = $item['nom_produit'];

    // 4. Suppression physique de la ligne
    $stmt_delete = $pdo->prepare("DELETE FROM paniers WHERE id_panier = :id_panier AND id_utilisateur = :user_id");
    $stmt_delete->execute([
        ':id_panier' => $id_panier,
        ':user_id' => $id_utilisateur
    ]);

    $_SESSION['success'] = "Le lot \"" . htmlspecialchars($nom_produit) . "\" a été retiré avec succès.";

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

// Redirection vers le panier visuel
header('Location: panier.php');
exit();