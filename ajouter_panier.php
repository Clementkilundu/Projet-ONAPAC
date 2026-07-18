<?php
// ajouter_panier.php
session_start();
require_once 'bdd/db.php';

// Initialisation de la réponse pour les requêtes AJAX/JSON
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$response = ['success' => false, 'message' => ''];

// 1. Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $msg = "Vous devez être connecté pour ajouter des produits au panier.";
    if ($is_ajax) {
        $response['message'] = $msg;
        echo json_encode($response);
        exit();
    } else {
        $_SESSION['error'] = $msg;
        header('Location: connexion.php');
        exit();
    }
}

$id_utilisateur = $_SESSION['user_id'];

// 2. Vérifier la soumission des données requises
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_produit']) || !isset($_POST['quantite'])) {
    $msg = "Données d'intégration au panier invalides.";
    if ($is_ajax) {
        $response['message'] = $msg;
        echo json_encode($response);
        exit();
    } else {
        $_SESSION['error'] = $msg;
        header('Location: produits.php');
        exit();
    }
}

$id_produit = (int)$_POST['id_produit'];
$quantite_demandee = (int)$_POST['quantite'];

if ($quantite_demandee <= 0) {
    $msg = "La quantité doit être supérieure à 0.";
    if ($is_ajax) {
        $response['message'] = $msg;
        echo json_encode($response);
        exit();
    } else {
        $_SESSION['error'] = $msg;
        header('Location: detail_produit.php?id=' . $id_produit);
        exit();
    }
}

try {
    // 3. Vérifier l'existence du produit et l'état de son stock réel
    $stmt_prod = $pdo->prepare("SELECT nom_produit, stock_disponible FROM produits WHERE id_produit = :id");
    $stmt_prod->execute([':id' => $id_produit]);
    $produit = $stmt_prod->fetch();

    if (!$produit) {
        throw new Exception("Le produit demandé n'existe pas.");
    }

    $stock_disponible = (int)$produit['stock_disponible'];

    // 4. Vérifier si ce produit est déjà présent dans le panier de l'utilisateur
    $stmt_panier = $pdo->prepare("SELECT id_panier, quantite FROM paniers WHERE id_utilisateur = :user_id AND id_produit = :prod_id");
    $stmt_panier->execute([
        ':user_id' => $id_utilisateur,
        ':prod_id' => $id_produit
    ]);
    $ligne_panier = $stmt_panier->fetch();

    if ($ligne_panier) {
        // Le produit est déjà dans le panier, on calcule la nouvelle quantité cumulée
        $nouvelle_quantite = $ligne_panier['quantite'] + $quantite_demandee;
        $id_panier = $ligne_panier['id_panier'];

        // On valide que le cumul ne dépasse pas le stock physique disponible
        if ($nouvelle_quantite > $stock_disponible) {
            throw new Exception("Action impossible : vous avez déjà des unités de ce lot dans votre panier. Le stock disponible total est de " . $stock_disponible . " unités.");
        }

        // Mise à jour de la quantité
        $stmt_update = $pdo->prepare("UPDATE paniers SET quantite = :qty WHERE id_panier = :id_panier");
        $stmt_update->execute([
            ':qty' => $nouvelle_quantite,
            ':id_panier' => $id_panier
        ]);
    } else {
        // Nouveau produit dans le panier, on valide simplement par rapport au stock
        if ($quantite_demandee > $stock_disponible) {
            throw new Exception("La quantité demandée dépasse le stock disponible (" . $stock_disponible . ").");
        }

        // Insertion dans la table des paniers
        $stmt_insert = $pdo->prepare("INSERT INTO paniers (id_utilisateur, id_produit, quantite, date_ajout) VALUES (:user_id, :prod_id, :qty, NOW())");
        $stmt_insert->execute([
            ':user_id' => $id_utilisateur,
            ':prod_id' => $id_produit,
            ':qty' => $quantite_demandee
        ]);
    }

    // 5. Retourner le succès de l'opération
    $success_msg = "Le lot \"" . htmlspecialchars($produit['nom_produit']) . "\" a bien été ajouté à votre panier.";

    if ($is_ajax) {
        $response['success'] = true;
        $response['message'] = $success_msg;
        echo json_encode($response);
        exit();
    } else {
        $_SESSION['success'] = $success_msg;
        header('Location: panier.php'); // On redirige l'utilisateur vers son panier visuel
        exit();
    }

} catch (Exception $e) {
    // Gestion des erreurs (Bdd, validation de stock...)
    if ($is_ajax) {
        $response['message'] = $e->getMessage();
        echo json_encode($response);
        exit();
    } else {
        $_SESSION['error'] = $e->getMessage();
        header('Location: detail_produit.php?id=' . $id_produit);
        exit();
    }
}