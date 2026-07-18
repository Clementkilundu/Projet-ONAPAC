<?php
// panier.php
session_start();
require_once 'bdd/db.php';
require_once 'inc/header.php'; // Gère déjà la session et la structure HTML de base

// Redirection si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour accéder à votre panier.";
    header('Location: connexion.php');
    exit();
}

$id_utilisateur = $_SESSION['user_id'];

try {
    // Récupérer les produits dans le panier de l'utilisateur avec les infos produit et catégorie
    $stmt = $pdo->prepare("SELECT p.id_panier, p.quantite, prod.id_produit, prod.nom_produit, 
                                  prod.prix_unitaire_usd, prod.unite_mesure, prod.stock_disponible, 
                                  prod.origine_provenance, cat.nom_categorie
                           FROM paniers p
                           INNER JOIN produits prod ON p.id_produit = prod.id_produit
                           INNER JOIN categories cat ON prod.id_categorie = cat.id_categorie
                           WHERE p.id_utilisateur = :user_id
                           ORDER BY p.date_ajout DESC");
    $stmt->execute([':user_id' => $id_utilisateur]);
    $items_panier = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur technique de récupération du panier : " . $e->getMessage());
}
?>

<link rel="stylesheet" href="css/panier.css">

<div class="cart-container">
    <div class="cart-header">
        <h1><i class="fa-solid fa-basket-shopping"></i> Votre Panier d'Achat</h1>
        <p>Gérez vos réservations de lots agricoles certifiés avant de soumettre votre demande d'exportation.</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($items_panier)): ?>
        <div class="empty-cart-box">
            <i class="fa-solid fa-box-open empty-icon"></i>
            <h2>Votre panier est actuellement vide</h2>
            <p>Explorez notre catalogue de matières premières certifiées (Café, Cacao) pour commencer vos réservations.</p>
            <a href="produits.php" class="btn-browse">Découvrir le catalogue</a>
        </div>
    <?php else: ?>
        <div class="cart-grid">
            
            <div class="cart-items-list">
                <?php 
                $sous_total = 0;
                foreach ($items_panier as $item): 
                    $total_item = $item['prix_unitaire_usd'] * $item['quantite'];
                    $sous_total += $total_item;

                    // Image de catégorie
                    $image_url = 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=300&q=80'; // Café par défaut
                    $cat_clean = strtolower($item['nom_categorie']);
                    if ($cat_clean === 'cacao') {
                        $image_url = 'https://images.unsplash.com/photo-1587132137056-bfbf0166836e?auto=format&fit=crop&w=300&q=80';
                    } elseif ($cat_clean === 'pyrethe' || $cat_clean === 'plantes à parfum') {
                        $image_url = 'https://images.unsplash.com/photo-1608797178974-15b35a61d121?auto=format&fit=crop&w=300&q=80';
                    }
                ?>
                    <div class="cart-item-card" data-id-panier="<?php echo $item['id_panier']; ?>">
                        <div class="item-img-wrapper">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($item['nom_produit']); ?>">
                        </div>

                        <div class="item-details">
                            <span class="item-category"><?php echo htmlspecialchars($item['nom_categorie']); ?></span>
                            <h3 class="item-title">
                                <a href="detail_produit.php?id=<?php echo $item['id_produit']; ?>">
                                    <?php echo htmlspecialchars($item['nom_produit']); ?>
                                </a>
                            </h3>
                            <p class="item-provenance"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($item['origine_provenance']); ?></p>
                        </div>

                        <div class="item-price">
                            <span class="price-val"><?php echo number_format($item['prix_unitaire_usd'], 2); ?> $</span>
                            <span class="price-unit">/ <?php echo htmlspecialchars($item['unite_mesure']); ?></span>
                        </div>

                        <div class="item-qty-selector">
                            <form action="modifier_panier.php" method="POST" class="qty-form">
                                <input type="hidden" name="id_panier" value="<?php echo $item['id_panier']; ?>">
                                <div class="qty-control-box">
                                    <button type="button" class="qty-btn-minus">-</button>
                                    <input type="number" name="quantite" class="qty-input" 
                                           value="<?php echo $item['quantite']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock_disponible']; ?>" 
                                           data-stock="<?php echo $item['stock_disponible']; ?>">
                                    <button type="button" class="qty-btn-plus">+</button>
                                </div>
                            </form>
                            <span class="stock-alert">Stock max: <?php echo $item['stock_disponible']; ?></span>
                        </div>

                        <div class="item-total-price">
                            <span class="total-val"><?php echo number_format($total_item, 2); ?> $</span>
                        </div>

                        <div class="item-actions">
                            <form action="supprimer_panier.php" method="POST" onsubmit="return confirm('Voulez-vous vraiment retirer ce lot de votre panier ?');">
                                <input type="hidden" name="id_panier" value="<?php echo $item['id_panier']; ?>">
                                <button type="submit" class="btn-delete" title="Supprimer du panier">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary-sidebar">
                <div class="summary-card">
                    <h3>Résumé de la Demande</h3>
                    <hr class="summary-divider">

                    <div class="summary-row">
                        <span>Sous-total FOB :</span>
                        <strong class="subtotal-val"><?php echo number_format($sous_total, 2); ?> $</strong>
                    </div>
                    
                    <div class="summary-row">
                        <span>Frais d'analyse ONAPAC (Fictif - 1%) :</span>
                        <span class="fee-val"><?php echo number_format($sous_total * 0.01, 2); ?> $</span>
                    </div>

                    <div class="summary-row certification-row">
                        <span>Certificat phytosanitaire :</span>
                        <span class="free-badge">Inclus</span>
                    </div>

                    <hr class="summary-divider">

                    <div class="summary-row total-row">
                        <span>Total estimé :</span>
                        <strong class="grand-total-val"><?php echo number_format($sous_total * 1.01, 2); ?> $</strong>
                    </div>

                    <div class="onapac-guarantees">
                        <p><i class="fa-solid fa-shield-halved"></i> Prix basé sur la mercuriale officielle.</p>
                        <p><i class="fa-solid fa-circle-check"></i> Les lots restent bloqués pendant 72 heures.</p>
                    </div>

                    <a href="passer_commande.php" class="btn-checkout">
                        Valider et Demander la Facture <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    
                    <a href="produits.php" class="btn-continue">
                        <i class="fa-solid fa-chevron-left"></i> Continuer mes achats
                    </a>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<script src="js/panier.js"></script>

<?php 
echo "</div>"; // Fermeture du site-container
?>
</body>
</html>