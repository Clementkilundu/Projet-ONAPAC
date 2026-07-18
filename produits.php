<?php
// produits.php
session_start();
require_once 'bdd/db.php';
require_once 'inc/header.php'; // Inclut le header commun (avec session_start)

try {
    // 1. Récupérer toutes les catégories pour le menu des filtres
    $stmtCats = $pdo->query("SELECT * FROM categories ORDER BY nom_categorie ASC");
    $categories = $stmtCats->fetchAll();

    // 2. Récupérer tous les produits avec leur catégorie associée
    $stmtProds = $pdo->query("SELECT p.*, c.nom_categorie 
                              FROM produits p 
                              INNER JOIN categories c ON p.id_categorie = c.id_categorie 
                              ORDER BY p.nom_produit ASC");
    $produits = $stmtProds->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}
?>

<!-- Lien vers le fichier CSS dédié -->
<link rel="stylesheet" href="css/produits.css">

<div class="catalog-container">
    <!-- En-tête du catalogue -->
    <div class="catalog-header">
        <h1>Catalogue des Lots Certifiés</h1>
        <p>Explorez et sélectionnez les matières premières agricoles analysées et validées par l'ONAPAC pour vos opérations d'exportation.</p>
    </div>

    <!-- Barre de filtres & Recherche -->
    <div class="filter-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Rechercher par lot, provenance, grade...">
        </div>
        
        <div class="filter-tags" id="filterTags">
            <button class="filter-btn active" data-category="all">Tous les produits</button>
            <?php foreach ($categories as $cat): ?>
                <button class="filter-btn" data-category="<?php echo htmlspecialchars(strtolower($cat['nom_categorie'])); ?>">
                    <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Grille des produits -->
    <div class="products-grid" id="productsGrid">
        <?php if (empty($produits)): ?>
            <div class="no-products">
                <i class="fa-regular fa-folder-open"></i>
                <p>Aucun lot n'est disponible pour le moment dans le catalogue.</p>
            </div>
        <?php else: ?>
            <?php foreach ($produits as $prod): 
                // Assignation d'images selon la catégorie
                $image_url = 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80'; // Par défaut : Café
                $cat_clean = strtolower($prod['nom_categorie']);
                if ($cat_clean === 'cacao') {
                    $image_url = 'https://images.unsplash.com/photo-1587132137056-bfbf0166836e?auto=format&fit=crop&w=600&q=80';
                } elseif ($cat_clean === 'pyrethe' || $cat_clean === 'plantes à parfum') {
                    $image_url = 'https://images.unsplash.com/photo-1608797178974-15b35a61d121?auto=format&fit=crop&w=600&q=80';
                }
            ?>
                <!-- Carte Produit avec attributs data pour le filtrage en JS -->
                <div class="product-card" data-category="<?php echo htmlspecialchars($cat_clean); ?>" data-name="<?php echo htmlspecialchars(strtolower($prod['nom_produit'])); ?>" data-origin="<?php echo htmlspecialchars(strtolower($prod['origine_provenance'])); ?>">
                    <div class="product-image-container">
                        <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($prod['nom_produit']); ?>" class="product-card-img">
                        <div class="card-badge"><?php echo htmlspecialchars($prod['nom_categorie']); ?></div>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="product-title"><?php echo htmlspecialchars($prod['nom_produit']); ?></h3>
                        
                        <div class="product-meta">
                            <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($prod['origine_provenance']); ?></span>
                            <span><i class="fa-solid fa-award"></i> Grade: <?php echo htmlspecialchars($prod['grade_qualite'] ?? 'Non défini'); ?></span>
                        </div>
                        
                        <p class="product-desc">
                            <?php 
                                $desc = htmlspecialchars($prod['description']);
                                echo strlen($desc) > 110 ? substr($desc, 0, 110) . '...' : $desc; 
                            ?>
                        </p>
                        
                        <div class="stock-info">
                            <i class="fa-solid fa-warehouse"></i> Stock dispo : <strong><?php echo number_format($prod['stock_disponible'], 0, ',', ' '); ?> <?php echo htmlspecialchars($prod['unite_mesure']); ?></strong>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="price-box">
                            <span class="price"><?php echo number_format($prod['prix_unitaire_usd'], 2); ?> $</span>
                            <span class="unit">/ <?php echo htmlspecialchars($prod['unite_mesure']); ?></span>
                        </div>
                        
                        <div class="action-buttons">
                            <!-- Lien vers les détails -->
                            <a href="detail_produit.php?id=<?php echo $prod['id_produit']; ?>" class="btn-secondary" title="Fiche technique complète">
                                <i class="fa-solid fa-circle-info"></i>
                            </a>
                            <!-- Formulaire rapide d'ajout au panier -->
                            <form action="ajouter_panier.php" method="POST" class="quick-cart-form">
                                <input type="hidden" name="id_produit" value="<?php echo $prod['id_produit']; ?>">
                                <input type="hidden" name="quantite" value="1">
                                <button type="submit" class="btn-add-cart" title="Ajouter au panier">
                                    <i class="fa-solid fa-basket-shopping"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Script JS du catalogue -->
<script src="js/produits.js"></script>

<?php 
echo "</div>"; // Ferme le conteneur global du header
?>
</body>
</html>