<?php
// admin_action.php
require_once 'bdd/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération des données du formulaire
    $nom_produit        = htmlspecialchars(trim($_POST['titre']));
    $categorie_texte    = $_POST['categorie']; // Contient 'Café', 'Cacao' ou 'Épices'
    $origine_provenance = htmlspecialchars(trim($_POST['origine']));
    $prix_unitaire_usd  = floatval($_POST['prix']);
    $description        = htmlspecialchars(trim($_POST['description']));

    // 1. Conversion de la catégorie textuelle en ID correspondant à ton fichier SQL
    $id_categorie = 1; // Par défaut Café
    if ($categorie_texte === 'Cacao') {
        $id_categorie = 2;
    } elseif ($categorie_texte === 'Épices' || $categorie_texte === 'Plantes à Parfum') {
        $id_categorie = 3;
    }

    // 2. Gestion du téléversement de l'image
    $target_path = "uploads/default.png"; // Image par défaut si aucun fichier
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $image_name;

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            move_uploaded_file($_FILES['image']['tmp_name'], $target_path);
        }
    }

    // 3. Insertion SQL en stricte conformité avec ton fichier SQL
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
                    date_enregistrement
                ) 
                VALUES (
                    :nom_produit, 
                    :description, 
                    :prix_unitaire_usd, 
                    'Kg', 
                    1000.00, 
                    :grade_qualite, -- On détourne temporairement ce champ pour stocker le chemin de l'image
                    :origine_provenance, 
                    :id_categorie, 
                    NOW()
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom_produit'        => $nom_produit,
            ':description'        => $description,
            ':prix_unitaire_usd'  => $prix_unitaire_usd,
            ':grade_qualite'      => $target_path, // Stockage du chemin d'image
            ':origine_provenance' => $origine_provenance,
            ':id_categorie'       => $id_categorie
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