<?php
// modifier_produit.php
// 1. Connexion à la base de données
require_once 'bdd/db.php';

// Vérification de la validité de l'ID fourni dans l'URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: admin.php');
    exit();
}

$id_produit = intval($_GET['id']);
$produit = null;
$categories = [];

try {
    // 2. Récupération des données actuelles du produit à modifier
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id_produit = :id");
    $stmt->execute([':id' => $id_produit]);
    $produit = $stmt->fetch();

    if (!$produit) {
        die("Erreur : Ce lot agricole n'existe pas ou a déjà été retiré.");
    }

    // Récupération dynamique de la liste des catégories pour le menu déroulant
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll();

} catch (PDOException $e) {
    die("Erreur de chargement des données SQL : " . $e->getMessage());
}

// 3. Traitement de la mise à jour lors de la validation du formulaire (Requête POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_produit        = htmlspecialchars(trim($_POST['titre']));
    $id_categorie       = intval($_POST['id_categorie']);
    $origine_provenance = htmlspecialchars(trim($_POST['origine']));
    $prix_unitaire_usd  = floatval($_POST['prix']);
    $description        = htmlspecialchars(trim($_POST['description']));
    
    // Par défaut, on conserve l'ancien chemin d'image stocké dans la base
    $target_path        = $produit['grade_qualite']; 

    // Gestion du nouveau téléversement d'image (si un nouveau fichier est sélectionné)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . '_' . uniqid() . '.' . $file_extension;
        $new_path = $upload_dir . $image_name;

        if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'webp'])) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $new_path)) {
                // Nettoyage : On supprime l'ancienne image du serveur pour économiser l'espace disque
                if (file_exists($target_path) && $target_path !== "uploads/default.png") {
                    unlink($target_path);
                }
                $target_path = $new_path;
            }
        }
    }

    try {
        // 4. Exécution de la requête SQL UPDATE
        $sql = "UPDATE produits SET 
                    nom_produit = :nom, 
                    description = :desc, 
                    prix_unitaire_usd = :prix, 
                    grade_qualite = :img, 
                    origine_provenance = :origine, 
                    id_categorie = :cat 
                WHERE id_produit = :id";
        
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute([
            ':nom'     => $nom_produit,
            ':desc'    => $description,
            ':prix'    => $prix_unitaire_usd,
            ':img'     => $target_path, // Stockage du chemin mis à jour
            ':origine' => $origine_provenance,
            ':cat'     => $id_categorie,
            ':id'      => $id_produit
        ]);

        // Redirection vers l'espace d'administration principal avec succès
        header('Location: admin.php?status=updated');
        exit();
        
    } catch (PDOException $e) {
        die("Erreur lors de la mise à jour des données du lot : " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONAPAC - Modifier Spécifications Lot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body style="background:#f4f6f5; padding: 40px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

    <div style="max-width: 700px; margin: 0 auto; background:#fff; padding:35px; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-top: 4px solid #1e5631;">
        <div style="margin-bottom: 25px;">
            <h2 style="color: #1e5631; margin-bottom: 5px;"><i class="fa-solid fa-pen-to-square"></i> Modification Certifiée ONAPAC</h2>
            <p style="color:#666; font-size: 0.95rem;">Ajustement des spécifications techniques pour le lot : <strong><?php echo htmlspecialchars($produit['nom_produit']); ?></strong></p>
        </div>
        
        <form action="" method="POST" enctype="multipart/form-data" class="admin-form" style="box-shadow:none; padding:0; background: transparent;">
            
            <div class="input-group" style="margin-bottom:18px;">
                <label style="font-weight: 600; color: #333;">Dénomination officielle du Produit</label>
                <input type="text" name="titre" value="<?php echo htmlspecialchars($produit['nom_produit']); ?>" required style="width:100%; padding:12px; border:1px solid #ccc; border-radius:5px; margin-top:6px; font-size:1rem;">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:18px;">
                <div class="input-group">
                    <label style="font-weight: 600; color: #333;">Catégorie de produit</label>
                    <select name="id_categorie" required style="width:100%; padding:12px; border:1px solid #ccc; border-radius:5px; margin-top:6px; font-size:1rem;">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id_categorie']; ?>" <?php echo ($cat['id_categorie'] == $produit['id_categorie']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label style="font-weight: 600; color: #333;">Province / Zone de Provenance</label>
                    <input type="text" name="origine" value="<?php echo htmlspecialchars($produit['origine_provenance']); ?>" required style="width:100%; padding:12px; border:1px solid #ccc; border-radius:5px; margin-top:6px; font-size:1rem;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:18px;">
                <div class="input-group">
                    <label style="font-weight: 600; color: #333;">Prix Unitaire Fixé (USD / Kg)</label>
                    <input type="number" step="0.01" name="prix" value="<?php echo htmlspecialchars($produit['prix_unitaire_usd']); ?>" required style="width:100%; padding:12px; border:1px solid #ccc; border-radius:5px; margin-top:6px; font-size:1rem;">
                </div>
                <div class="input-group">
                    <label style="font-weight: 600; color: #333;">Remplacer le document visuel (Optionnel)</label>
                    <input type="file" name="image" accept="image/*" style="width:100%; padding:9px; border:1px solid #ccc; border-radius:5px; margin-top:6px; font-size:0.95rem; background:#f9f9f9;">
                </div>
            </div>

            <div class="input-group" style="margin-bottom:25px;">
                <label style="font-weight: 600; color: #333;">Description & Notes de Conformité Agricole</label>
                <textarea name="description" rows="4" required style="width:100%; padding:12px; border:1px solid #ccc; border-radius:5px; margin-top:6px; font-size:1rem; resize: vertical;"><?php echo htmlspecialchars($produit['description']); ?></textarea>
            </div>

            <div style="display:flex; gap:12px; margin-top:10px;">
                <button type="submit" class="btn-admin-submit" style="background:#1e5631; color:#fff; padding:12px 24px; border:none; border-radius:5px; font-weight:600; cursor:pointer; font-size:1rem; transition: background 0.2s;">
                    <i class="fa-solid fa-floppy-disk"></i> Valider les modifications
                </button>
                <a href="admin.php" style="background:#6c757d; color:#fff; padding:12px 24px; text-decoration:none; border-radius:5px; font-weight:600; font-size:1rem; text-align:center; transition: background 0.2s;">
                    Annuler
                </a>
            </div>
        </form>
    </div>

</body>
</html>