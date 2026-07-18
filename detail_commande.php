<?php
// detail_commande.php
session_start();
require_once 'bdd/db.php';

// Protection
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$id_utilisateur = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard_acheteur.php');
    exit();
}

$id_commande = (int)$_GET['id'];

try {
    // 1. Récupérer la commande
    $stmt_cmd = $pdo->prepare("SELECT c.*, p.mode_paiement, p.statut_paiement, l.adresse_livraison, l.statut_livraison, l.societe_transport, l.numero_suivi
                               FROM commandes c
                               LEFT JOIN paiements p ON c.id_commande = p.id_commande
                               LEFT JOIN livraisons l ON c.id_commande = l.id_commande
                               WHERE c.id_commande = :id_cmd AND c.id_acheteur = :user_id");
    $stmt_cmd->execute([
        ':id_cmd' => $id_commande,
        ':user_id' => $id_utilisateur
    ]);
    $commande = $stmt_cmd->fetch();

    if (!$commande) {
        die("Commande introuvable ou accès non autorisé.");
    }

    // 2. Récupérer les lignes de produits de la commande
    $stmt_items = $pdo->prepare("SELECT lc.*, prod.nom_produit, prod.unite_mesure
                                 FROM lignes_commande lc
                                 INNER JOIN produits prod ON lc.id_produit = prod.id_produit
                                 WHERE lc.id_commande = :id_cmd");
    $stmt_items->execute([':id_cmd' => $id_commande]);
    $produits = $stmt_items->fetchAll();

    // 3. Récupérer l'éventuel Certificat de Qualité ONAPAC
    $stmt_cert = $pdo->prepare("SELECT * FROM certificats_qualite WHERE id_commande = :id_cmd");
    $stmt_cert->execute([':id_cmd' => $id_commande]);
    $certificat = $stmt_cert->fetch();

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

require_once 'inc/header.php';
?>

<link rel="stylesheet" href="css/detail_commande.css">

<div class="detail-container">
    <div class="back-bar">
        <a href="dashboard_acheteur.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Retour au tableau de bord</a>
    </div>

    <div class="detail-grid">
        <div class="main-detail-col">
            <div class="card order-summary-card">
                <div class="card-header">
                    <h3>Commande #<?php echo htmlspecialchars($commande['reference_commande']); ?></h3>
                    <span class="order-date">Initiée le : <?php echo date("d/m/Y H:i", strtotime($commande['date_commande'])); ?></span>
                </div>
                
                <div class="products-list">
                    <h4>Produits de la commande :</h4>
                    <table class="detail-products-table">
                        <thead>
                            <tr>
                                <th>Désignation du lot</th>
                                <th>Prix FOB</th>
                                <th>Quantité</th>
                                <th>Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sous_total = 0;
                            foreach ($produits as $prod): 
                                $total_ligne = $prod['prix_applique_usd'] * $prod['quantite_commandee'];
                                $sous_total += $total_ligne;
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prod['nom_produit']); ?></strong></td>
                                    <td><?php echo number_format($prod['prix_applique_usd'], 2); ?> $ / <?php echo $prod['unite_mesure']; ?></td>
                                    <td><?php echo number_format($prod['quantite_commandee'], 1); ?> <?php echo $prod['unite_mesure']; ?></td>
                                    <td class="bold-text"><?php echo number_format($total_ligne, 2); ?> $</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="detail-totals">
                        <div class="total-row">
                            <span>Sous-total FOB :</span>
                            <span><?php echo number_format($sous_total, 2); ?> $</span>
                        </div>
                        <div class="total-row">
                            <span>Frais de contrôle ONAPAC (1%) :</span>
                            <span><?php echo number_format($sous_total * 0.01, 2); ?> $</span>
                        </div>
                        <div class="total-row grand-total">
                            <span>Montant Total Réglé :</span>
                            <span class="total-highlight"><?php echo number_format($commande['montant_total_usd'], 2); ?> $</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card certificat-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-award"></i> Bulletin d'analyse de l'ONAPAC</h3>
                </div>
                <div class="card-body">
                    <?php if ($certificat): ?>
                        <div class="cert-approved-box">
                            <i class="fa-solid fa-circle-check approved-icon"></i>
                            <div>
                                <h4>Lot certifié conforme pour exportation</h4>
                                <p class="cert-num">Certificat N° : <strong><?php echo htmlspecialchars($certificat['numero_certificat']); ?></strong></p>
                                <p class="cert-date">Émis le : <?php echo date("d/m/Y", strtotime($certificat['date_emission'])); ?></p>
                            </div>
                        </div>
                        <div class="cert-analysis-details">
                            <h5>Résultat officiel des analyses de laboratoire :</h5>
                            <blockquote class="analysis-quote">
                                <?php echo nl2br(htmlspecialchars($certificat['resultat_analyse'])); ?>
                            </blockquote>
                        </div>
                    <?php else: ?>
                        <div class="cert-pending-box">
                            <i class="fa-solid fa-hourglass-half pending-icon"></i>
                            <div>
                                <h4>Analyse technique en cours</h4>
                                <p>Nos agents de laboratoire de l'ONAPAC prélèvent actuellement vos échantillons pour valider le grade phytosanitaire et autoriser l'exportation.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="side-detail-col">
            <div class="card shipping-card">
                <h3><i class="fa-solid fa-truck-ramp-box"></i> Logistique d'expédition</h3>
                <div class="shipping-info-box">
                    <p class="shipping-status-label">Statut de la livraison :</p>
                    <span class="badge-status lvr-<?php echo strtolower(str_replace(' ', '-', $commande['statut_livraison'] ?? 'En préparation')); ?>">
                        <?php echo htmlspecialchars($commande['statut_livraison'] ?? 'En préparation'); ?>
                    </span>

                    <hr class="card-divider">

                    <p><strong>Adresse de destination :</strong></p>
                    <p class="address-text"><?php echo htmlspecialchars($commande['adresse_livraison'] ?? 'Non spécifiée'); ?></p>

                    <?php if (!empty($commande['societe_transport'])): ?>
                        <p><strong>Société de fret :</strong> <?php echo htmlspecialchars($commande['societe_transport']); ?></p>
                        <p><strong>Numéro de suivi :</strong> <span class="tracking-number"><?php echo htmlspecialchars($commande['numero_suivi']); ?></span></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card payment-card">
                <h3><i class="fa-solid fa-credit-card"></i> Paiement de la transaction</h3>
                <div class="payment-info-box">
                    <p>Moyen utilisé : <strong><?php echo htmlspecialchars($commande['mode_paiement'] ?? 'Mobile Money'); ?></strong></p>
                    <p>Statut du paiement : 
                        <strong class="pay-status-text <?php echo ($commande['statut_paiement'] === 'Confirmé' || $commande['statut_paiement'] === 'Validé') ? 'text-green' : 'text-orange'; ?>">
                            <?php echo htmlspecialchars($commande['statut_paiement'] ?? 'En attente'); ?>
                        </strong>
                    </p>

                    <?php if ($commande['statut_commande'] !== 'En attente'): ?>
                        <a href="telecharger_facture.php?id=<?php echo $commande['id_commande']; ?>" target="_blank" class="btn-full btn-pdf-large">
                            <i class="fa-solid fa-file-pdf"></i> Télécharger la facture officielle (PDF)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>