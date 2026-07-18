<?php
// passer_commande.php
require_once 'bdd/db.php';
require_once 'inc/header.php';

// Sécurité : l'utilisateur doit être connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour passer une commande.";
    header('Location: connexion.php');
    exit();
}

$id_utilisateur = $_SESSION['user_id'];

try {
    // 1. Récupérer les éléments du panier pour validation finale
    $stmt = $pdo->prepare("SELECT p.quantite, prod.id_produit, prod.nom_produit, 
                                  prod.prix_unitaire_usd, prod.unite_mesure, prod.stock_disponible
                           FROM paniers p
                           INNER JOIN produits prod ON p.id_produit = prod.id_produit
                           WHERE p.id_utilisateur = :user_id");
    $stmt->execute([':user_id' => $id_utilisateur]);
    $items_panier = $stmt->fetchAll();

    if (empty($items_panier)) {
        $_SESSION['error'] = "Votre panier est vide. Ajoutez des lots avant de commander.";
        header('Location: produits.php');
        exit();
    }

    // Calcul du total
    $sous_total = 0;
    foreach ($items_panier as $item) {
        $sous_total += $item['prix_unitaire_usd'] * $item['quantite'];
    }
    $frais_onapac = $sous_total * 0.01; // 1% de frais d'analyse
    $total_general = $sous_total + $frais_onapac;

} catch (PDOException $e) {
    die("Erreur technique : " . $e->getMessage());
}
?>

<link rel="stylesheet" href="css/passer_commande.css">

<div class="checkout-container">
    <div class="checkout-header">
        <h1><i class="fa-solid fa-file-invoice-dollar"></i> Finalisation de votre Demande d'Achat</h1>
        <p>Veuillez renseigner vos coordonnées d'exportation et procéder au paiement sécurisé de vos lots certifiés.</p>
    </div>

    <div class="checkout-grid">
        <div class="checkout-form-box">
            <form action="process_paiement.php" method="POST" id="checkoutForm">
                <h3><i class="fa-solid fa-truck-ramp-box"></i> Informations de Livraison & Exportation</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="adresse_livraison">Adresse de livraison / Entrepôt de transit *</label>
                        <input type="text" id="adresse_livraison" name="adresse_livraison" placeholder="Ex: Entrepôt n°4, Port de Goma" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville">Ville de destination *</label>
                        <input type="text" id="ville" name="ville" placeholder="Ex: Bukavu, Goma, Kinshasa" required>
                    </div>
                    <div class="form-group">
                        <label for="pays">Pays de destination *</label>
                        <input type="text" id="pays" name="pays" placeholder="Ex: République Démocratique du Congo" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telephone">Téléphone de contact (Format Mobile Money) *</label>
                        <input type="tel" id="telephone" name="telephone" placeholder="Ex: +243812345678" required>
                    </div>
                    <div class="form-group">
                        <label for="mode_paiement">Moyen de paiement en ligne (MaishaPay) *</label>
                        <select id="mode_paiement" name="mode_paiement" required>
                            <option value="maishapay_mobile" selected>Mobile Money (M-Pesa, Airtel, Orange, Equity)</option>
                            <option value="maishapay_card">Carte Bancaire (Visa, Mastercard)</option>
                        </select>
                    </div>
                </div>

                <div class="maishapay-badge-info">
                    <img src="https://maishapay.online/assets/images/logo.png" alt="MaishaPay" class="maishapay-logo" onerror="this.style.display='none'">
                    <p><i class="fa-solid fa-lock"></i> Transaction sécurisée par la passerelle de paiement **MaishaPay**. Vous allez être redirigé vers leur plateforme sécurisée de test.</p>
                </div>

                <button type="submit" class="btn-submit-checkout">
                    <i class="fa-solid fa-credit-card"></i> Procéder au paiement de <?php echo number_format($total_general, 2); ?> $
                </button>
            </form>
        </div>

        <div class="checkout-summary-sidebar">
            <div class="summary-card">
                <h3>Récapitulatif des Lots</h3>
                <hr class="summary-divider">

                <div class="checkout-items-mini">
                    <?php foreach ($items_panier as $item): ?>
                        <div class="mini-item">
                            <div class="mini-item-info">
                                <strong><?php echo htmlspecialchars($item['nom_produit']); ?></strong>
                                <span>Qté : <?php echo $item['quantite']; ?> <?php echo htmlspecialchars($item['unite_mesure']); ?></span>
                            </div>
                            <span class="mini-item-price"><?php echo number_format($item['prix_unitaire_usd'] * $item['quantite'], 2); ?> $</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr class="summary-divider">

                <div class="summary-row">
                    <span>Sous-total Lots :</span>
                    <strong><?php echo number_format($sous_total, 2); ?> $</strong>
                </div>
                
                <div class="summary-row">
                    <span>Frais administratifs ONAPAC (1%) :</span>
                    <span><?php echo number_format($frais_onapac, 2); ?> $</span>
                </div>

                <hr class="summary-divider">

                <div class="summary-row total-row">
                    <span>Montant total :</span>
                    <strong class="grand-total-val"><?php echo number_format($total_general, 2); ?> $</strong>
                </div>

                <div class="security-stamp">
                    <i class="fa-solid fa-shield-halved"></i>
                    <p>Vos transactions sont protégées. L'ONAPAC utilise MaishaPay pour garantir la transparence des flux d'exportation.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
echo "</div>"; // Ferme le site-container
?>
</body>
</html>