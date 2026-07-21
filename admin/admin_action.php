<?php
// admin_action.php
require_once 'bdd/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération des données du formulaire
    $nom_produit        = htmlspecialchars(trim($_POST['titre']));
    $id_categorie       = intval($_POST['categorie']); // Récupère directement l'ID sélectionné
    $origine_provenance = htmlspecialchars(trim($_POST['origine']));
    $prix_unitaire_usd  = floatval($_POST['prix']);
    $description        = htmlspecialchars(trim($_POST['description']));
    $grade_qualite      = isset($_POST['grade']) ? htmlspecialchars(trim($_POST['grade'])) : 'Grade A';

    // 1. Gestion du téléversement de l'image
    $target_path = "uploads/default.png"; // Image par défaut si aucun fichier
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_extension, $allowed_extensions)) {
            $image_name = time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $image_name;
            
            move_uploaded_file($_FILES['image']['tmp_name'], $target_path);
        }
    }

    // 2. Insertion SQL propre avec la colonne img_url
    try {
        $sql = "INSERT INTO produits (
                    nom_produit, 
                    description, 
                    prix_unitaire_usd, 
                    unite_mesure, 
                    stock_disponible, 
                    grade_qualite, 
                    origine_provenance, 
                    id_categorie, 
                    date_enregistrement,
                    img_url
                ) 
                VALUES (
                    :nom_produit, 
                    :description, 
                    :prix_unitaire_usd, 
                    'Kg', 
                    1000.00, 
                    :grade_qualite, 
                    :origine_provenance, 
                    :id_categorie, 
                    NOW(),
                    :img_url
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom_produit'        => $nom_produit,
            ':description'        => $description,
            ':prix_unitaire_usd'  => $prix_unitaire_usd,
            ':grade_qualite'      => $grade_qualite,
            ':origine_provenance' => $origine_provenance,
            ':id_categorie'       => $id_categorie,
            ':img_url'            => $target_path
        ]);

        header('Location: admin.php?status=success');
        exit();

    } catch (PDOException $e) {
        die("Erreur SQL lors de l'insertion : " . $e->getMessage());
    }
} else {
    header('Location: admin.php');
    exit();
}