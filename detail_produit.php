<?php
// detail_produit.php
require_once 'bdd/db.php';
require_once 'inc/header.php'; // Gère déjà la session et la structure HTML de base

// 1. Récupération et sécurisation de l'ID du produit
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: produits.php');
    exit();
}

$id_produit = (int)$_GET['id'];

try {
    // 2. Requête SQL pour récupérer le produit et sa catégorie
    $stmt = $pdo->prepare("SELECT p.*, c.nom_categorie 
                           FROM produits p 
                           INNER JOIN categories c ON p.id_categorie = c.id_categorie 
                           WHERE p.id_produit = :id");
    $stmt->execute([':id' => $id_produit]);
    $produit = $stmt->fetch();

    // Si le produit n'existe pas en BDD
    if (!$produit) {
        die("<div class='container' style='padding: 50px 0; text-align: center;'><h2>Le lot agricole demandé est introuvable.</h2><a href='produits.php' class='btn-view'>Retour au catalogue</a></div>");
    }

    $cat_clean = strtolower($produit['nom_categorie']);

    // 3. Reconstitution du chemin d'accès relatif depuis la racine
    $raw_path = $produit['img_url'] ?? '';
    
    if (!empty($raw_path) && strpos($raw_path, 'admin/') !== 0) {
        $real_path = 'admin/' . $raw_path;
    } else {
        $real_path = $raw_path;
    }

    // 4. Vérification de l'existence du fichier physique sur le serveur
    if (!empty($real_path) && file_exists($real_path)) {
        $image_url = htmlspecialchars($real_path);
    } else {
        // Image Unsplash de secours si le fichier n'existe pas
        $image_url = 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=800&q=80'; // Café par défaut
        if ($cat_clean === 'cacao') {
            $image_url = 'https://images.unsplash.com/photo-1587132137056-bfbf0166836e?auto=format&fit=crop&w=800&q=80';
        } elseif (in_array($cat_clean, ['pyrethe', 'plantes à parfum', 'épices'])) {
            $image_url = 'https://images.unsplash.com/photo-1608797178974-15b35a61d121?auto=format&fit=crop&w=800&q=80';
        }
    }

} catch (PDOException $e) {
    die("Erreur technique : " . $e->getMessage());
}
?>

<!-- Lien vers le fichier CSS dédié -->
<link rel="stylesheet" href="css/detail_produit.css">

<div class="detail-container">
    <!-- Fil d'Ariane -->
    <div class="breadcrumb">
        <a href="index.php">Accueil</a> <i class="fa-solid fa-chevron-right"></i> 
        <a href="produits.php">Catalogue</a> <i class="fa-solid fa-chevron-right"></i> 
        <span><?php echo htmlspecialchars($produit['nom_produit']); ?></span>
    </div>

    <div class="product-grid">
        <!-- COLONNE GAUCHE : VISUEL ET BADGES -->
        <div class="product-gallery">
            <div class="main-image-wrapper">
                <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($produit['nom_produit']); ?>" id="mainProductImg" style="object-fit: cover; width: 100%; max-height: 400px;">
                <span class="category-badge"><?php echo htmlspecialchars($produit['nom_categorie']); ?></span>
            </div>
            <div class="onapac-stamp-box">
                <i class="fa-solid fa-certificate stamp-icon"></i>
                <div>
                    <h5>Lot Certifié ONAPAC</h5>
                    <p>Conforme aux standards d'exportation de la RDC.</p>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : INFORMATIONS PRINCIPALES & COMMANDE -->
        <div class="product-info-box">
            <h1 class="product-title"><?php echo htmlspecialchars($produit['nom_produit']); ?></h1>
            
            <div class="meta-row">
                <span class="meta-item"><i class="fa-solid fa-location-dot"></i> Provenance : <strong><?php echo htmlspecialchars($produit['origine_provenance']); ?></strong></span>
                <span class="meta-item"><i class="fa-solid fa-award"></i> Grade de Qualité : 
                    <strong class="badge-grade">
                        <?php 
                            $grade = $produit['grade_qualite'] ?? '';
                            echo (!empty($grade) && strpos($grade, 'uploads/') === false) ? htmlspecialchars($grade) : 'Standard'; 
                        ?>
                    </strong>
                </span>
            </div>

            <div class="price-container">
                <span class="price-amount"><?php echo number_format($produit['prix_unitaire_usd'], 2); ?> $</span>
                <span class="price-unit">/ <?php echo htmlspecialchars($produit['unite_mesure']); ?> (Prix FOB indicatif)</span>
            </div>

            <p class="quick-description">
                <?php echo nl2br(htmlspecialchars($produit['description'])); ?>
            </p>

            <hr class="divider">

            <!-- CART DE COMMANDE ET RÉSERVATION -->
            <div class="order-card">
                <div class="stock-status">
                    <i class="fa-solid fa-warehouse"></i> Stock disponible : 
                    <span id="stockMax" data-stock="<?php echo $produit['stock_disponible']; ?>">
                        <strong><?php echo number_format($produit['stock_disponible'], 0, ',', ' '); ?> <?php echo htmlspecialchars($produit['unite_mesure']); ?></strong>
                    </span>
                </div>

                <form action="ajouter_panier.php" method="POST" id="orderForm">
                    <input type="hidden" name="id_produit" value="<?php echo $produit['id_produit']; ?>">
                    
                    <div class="qty-selector-wrapper">
                        <label for="quantite">Quantité souhaitée :</label>
                        <div class="qty-controls">
                            <button type="button" class="qty-btn" id="qtyMinus">-</button>
                            <input type="number" id="quantite" name="quantite" value="1" min="1" max="<?php echo $produit['stock_disponible']; ?>">
                            <button type="button" class="qty-btn" id="qtyPlus">+</button>
                        </div>
                    </div>

                    <div class="total-estimated">
                        <span>Estimation de la valeur du lot :</span>
                        <strong id="totalPrice" data-price="<?php echo $produit['prix_unitaire_usd']; ?>">0.00 $</strong>
                    </div>

                    <button type="submit" class="btn-submit-order">
                        <i class="fa-solid fa-cart-arrow-down"></i> Ajouter ce lot au panier
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ZONE BASSE : ONGLETS DÉTAILLÉS (RAPPORT LABO / PROCESSUS) -->
    <div class="tabs-container">
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="rapport">Rapport d'Analyses Physiques</button>
            <button class="tab-btn" data-tab="processus">Réglementation & Logistique</button>
        </div>

        <div class="tabs-content">
            <!-- Onglet 1 : Rapport d'analyse fictif basé sur la BDD -->
            <div class="tab-panel active" id="rapport">
                <div class="lab-report-wrapper">
                    <h3><i class="fa-solid fa-microscope"></i> Fiche d'Expertise de Laboratoire (ONAPAC)</h3>
                    <p>Ce document résume les conclusions issues des échantillons analysés par nos services techniques agréés.</p>
                    
                    <div class="report-table">
                        <div class="report-row">
                            <span class="report-label">Taux d'humidité</span>
                            <span class="report-value"><strong>11.5%</strong> (Conforme aux normes &lt; 12.5%)</span>
                        </div>
                        <div class="report-row">
                            <span class="report-label">Matières étrangères (Impuretés)</span>
                            <span class="report-value">0.4% maximum</span>
                        </div>
                        <div class="report-row">
                            <span class="report-label">Calibrage (Tamis)</span>
                            <span class="report-value">Au moins 90% d'échantillons retenus au tamis 18</span>
                        </div>
                        <div class="report-row">
                            <span class="report-label">Profil de saveur (Analyse Sensorielle)</span>
                            <span class="report-value"><?php echo $cat_clean === 'cacao' ? 'Notes florales intenses, faible acidité, corps généreux.' : 'Acidité citrique brillante, notes de chocolat noir et d\'agrumes.'; ?></span>
                        </div>
                        <div class="report-row">
                            <span class="report-label">Statut Phytosanitaire</span>
                            <span class="report-value text-success"><i class="fa-solid fa-check-circle"></i> Validé et Certifié Exempt de parasites</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet 2 : Processus d'exportation -->
            <div class="tab-panel" id="processus">
                <div class="logistic-info-wrapper">
                    <h3><i class="fa-solid fa-plane-departure"></i> Procédure Standard d'Exportation</h3>
                    <p>En tant qu'acheteur agréé, l'acquisition de ce lot déclenche automatiquement l'accompagnement administratif suivant :</p>
                    <ul>
                        <li><i class="fa-solid fa-circle-chevron-right"></i> <strong>Verrouillage du stock :</strong> La validation de la commande bloque immédiatement ce volume pour empêcher d'autres réservations.</li>
                        <li><i class="fa-solid fa-circle-chevron-right"></i> <strong>Génération des documents :</strong> Édition immédiate du pré-certificat de conformité et de l'attestation de provenance.</li>
                        <li><i class="fa-solid fa-circle-chevron-right"></i> <strong>Logistique de transport :</strong> Coordination depuis l'entrepôt local jusqu'aux points de sortie portuaires nationaux ou régionaux.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script JS de la page produit -->
<script src="js/detail_produit.js"></script>

<?php 
echo "</div>"; // Ferme la div .site-container globale du header
?>
</body>
</html>