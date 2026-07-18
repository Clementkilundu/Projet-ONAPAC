<?php
// supprimer_produit.php
// 1. Connexion obligatoire à la base de données
require_once 'bdd/db.php';

// 2. Vérification de la présence d'un ID de produit valide dans l'URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_produit = intval($_GET['id']);

    try {
        // Optionnel : On récupère d'abord le chemin de l'image pour la nettoyer du serveur
        $stmtImage = $pdo->prepare("SELECT grade_qualite FROM produits WHERE id_produit = :id");
        $stmtImage->execute([':id' => $id_produit]);
        $imagePath = $stmtImage->fetchColumn();
        
        // Si le fichier image existe sur le disque et n'est pas l'image par défaut, on le supprime
        if ($imagePath && file_exists($imagePath) && $imagePath !== "uploads/default.png") {
            unlink($imagePath);
        }

        // 3. Exécution de la requête SQL de suppression définitive
        $sql = "DELETE FROM produits WHERE id_produit = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_produit]);

        // 4. Redirection immédiate vers l'espace admin avec le statut de suppression
        header('Location: admin.php?status=deleted');
        exit();

    } catch (PDOException $e) {
        // En cas de problème SQL (ex: contrainte de clé étrangère avec une commande)
        die("Erreur technique lors de la suppression du lot ONAPAC : " . $e->getMessage());
    }
} else {
    // Si l'ID est manquant ou invalide, retour direct à l'administration
    header('Location: admin.php');
    exit();
}
?>