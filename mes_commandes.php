<?php
// mes_commandes.php
require_once 'bdd/db.php';
require_once 'inc/header.php'; // Gère la session et la structure HTML globale

// Sécurité : l'utilisateur doit être connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour accéder à vos commandes.";
    header('Location: connexion.php');
    exit();
}

$id_utilisateur = $_SESSION['user_id'];

try {
    // Récupérer l'historique des commandes avec le statut de leur paiement
    $stmt = $pdo->prepare("SELECT c.id_commande, c.reference_commande, c.montant_total_usd, 
                                  c.statut_commande, c.date_commande, 
                                  p.statut_paiement, p.mode_paiement
                           FROM commandes c
                           LEFT JOIN paiements p ON c.id_commande = p.id_commande
                           WHERE c.id_acheteur = :user_id
                           ORDER BY c.date_commande DESC");
    $stmt->execute([':user_id' => $id_utilisateur]);
    $commandes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur technique de récupération des commandes : " . $e->getMessage());
}
?>

<link rel="stylesheet" href="css/mes_commandes.css">

<div class="orders-container">
    <div class="orders-header">
        <h1><i class="fa-solid fa-box-archive"></i> Mes Commandes & Certifications</h1>
        <p>Suivez l'état de validation de vos lots agricoles par les inspecteurs de l'ONAPAC et téléchargez vos justificatifs.</p>
    </div>

    <?php if (empty($commandes)): ?>
        <div class="empty-orders-box">
            <i class="fa-regular fa-folder-open empty-icon"></i>
            <h2>Aucune commande enregistrée</h2>
            <p>Vous n'avez pas encore soumis de demande d'exportation de lots.</p>
            <a href="produits.php" class="btn-browse-products">Parcourir le catalogue</a>
        </div>
    <?php else: ?>
        <div class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Paiement MaishaPay</th>
                        <th>Statut ONAPAC</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $cmd): 
                        // Formater la date en français
                        $date_formatee = date("d/m/Y à H:i", strtotime($cmd['date_commande']));
                        
                        // Classes CSS dynamiques pour les statuts de paiement
                        $pay_class = 'status-pending';
                        if ($cmd['statut_paiement'] === 'Validé') $pay_class = 'status-success';
                        if ($cmd['statut_paiement'] === 'Échoué') $pay_class = 'status-danger';

                        // Classes CSS dynamiques pour les statuts de commande (ONAPAC)
                        $cmd_class = 'status-pending'; // En attente
                        if ($cmd['statut_commande'] === 'Payée') $cmd_class = 'status-info';
                        if ($cmd['statut_commande'] === 'Validée') $cmd_class = 'status-success';
                        if ($cmd['statut_commande'] === 'Annulée') $cmd_class = 'status-danger';
                    ?>
                        <tr>
                            <td>
                                <strong class="order-ref"><?php echo htmlspecialchars($cmd['reference_commande']); ?></strong>
                            </td>
                            <td class="order-date"><?php echo $date_formatee; ?></td>
                            <td class="order-total"><?php echo number_format($cmd['montant_total_usd'], 2); ?> $</td>
                            <td>
                                <span class="badge <?php echo $pay_class; ?>">
                                    <i class="fa-solid fa-circle"></i> <?php echo htmlspecialchars($cmd['statut_paiement'] ?? 'Non initié'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $cmd_class; ?>">
                                    <i class="fa-solid fa-circle-notch fa-spin-slow"></i> <?php echo htmlspecialchars($cmd['statut_commande']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="action-buttons">
                                    <a href="detail_commande.php?id=<?php echo $cmd['id_commande']; ?>" class="btn-action-view" title="Consulter les détails">
                                        <i class="fa-solid fa-eye"></i> Détails
                                    </a>
                                    <?php if ($cmd['statut_commande'] === 'Payée' || $cmd['statut_commande'] === 'Validée'): ?>
                                        <a href="telecharger_facture.php?id=<?php echo $cmd['id_commande']; ?>" class="btn-action-pdf" title="Télécharger la facture officielle">
                                            <i class="fa-solid fa-file-pdf"></i> Facture
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php 
echo "</div>"; // Ferme le site-container
?>
</body>
</html>