<?php
// retour_paiement.php
session_start();
require_once 'bdd/db.php';
require_once 'inc/header.php'; // Gère la structure HTML globale et la barre de navigation

// Protection : l'utilisateur doit idéalement être connecté, mais nous restons tolérants 
// si la session s'est déconnectée temporairement pendant le transit vers MaishaPay.
$id_utilisateur = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$paiement_valide = false;
$commande = null;
$erreur_message = null;

try {
    // 1. Récupération robuste de la référence brute
    $reference_brute = null;
    if (isset($_GET['ref'])) {
        $reference_brute = trim($_GET['ref']);
    } elseif (isset($_POST['transactionReference'])) {
        $reference_brute = trim($_POST['transactionReference']);
    } elseif (isset($_GET['transactionReference'])) {
        $reference_brute = trim($_GET['transactionReference']);
    }

    if (!$reference_brute) {
        throw new Exception("Aucune référence de commande n'a pu être détectée dans le retour de paiement.");
    }

    // CORRECTIF : Si MaishaPay colle ses paramètres avec un second "?" (ex: CMD-20260714-9880?status=200)
    // On extrait uniquement la partie située AVANT le "?" pour obtenir la référence exacte enregistrée en BDD.
    if (strpos($reference_brute, '?') !== false) {
        $parties = explode('?', $reference_brute);
        $reference_commande = htmlspecialchars($parties[0]);
    } else {
        $reference_commande = htmlspecialchars($reference_brute);
    }

    // 2. Recherche de la commande associée dans la base de données
    if ($id_utilisateur) {
        // Recherche sécurisée liée à l'utilisateur connecté
        $stmt = $pdo->prepare("SELECT c.*, p.statut_paiement, p.mode_paiement 
                               FROM commandes c
                               LEFT JOIN paiements p ON c.id_commande = p.id_commande
                               WHERE c.reference_commande = :ref AND c.id_acheteur = :user_id");
        $stmt->execute([
            ':ref' => $reference_commande,
            ':user_id' => $id_utilisateur
        ]);
    } else {
        // Mode de secours (si session perdue en localhost) : recherche par la référence unique globale
        $stmt = $pdo->prepare("SELECT c.*, p.statut_paiement, p.mode_paiement 
                               FROM commandes c
                               LEFT JOIN paiements p ON c.id_commande = p.id_commande
                               WHERE c.reference_commande = :ref");
        $stmt->execute([':ref' => $reference_commande]);
    }
    
    $commande = $stmt->fetch();

    if (!$commande) {
        throw new Exception("La commande avec la référence \"" . htmlspecialchars($reference_commande) . "\" n'existe pas ou ne vous appartient pas.");
    }

    // 3. Traitement et validation automatique du paiement de test
    // Pour l'environnement Sandbox, si le statut est encore 'En attente', on confirme la transaction.
    if ($commande['statut_commande'] === 'En attente') {
        $pdo->beginTransaction();

        // Passage de la commande à l'état 'Payée'
        $stmt_up_cmd = $pdo->prepare("UPDATE commandes SET statut_commande = 'Payée' WHERE id_commande = :id_cmd");
        $stmt_up_cmd->execute([':id_cmd' => $commande['id_commande']]);

        // Validation du paiement associé
        $stmt_up_pay = $pdo->prepare("UPDATE paiements SET statut_paiement = 'Validé', date_paiement = NOW() WHERE id_commande = :id_cmd");
        $stmt_up_pay->execute([':id_cmd' => $commande['id_commande']]);

        $pdo->commit();
        
        // On rafraîchit les données locales de l’objet commande pour l’affichage
        $commande['statut_commande'] = 'Payée';
        $commande['statut_paiement'] = 'Validé';
        $paiement_valide = true;
    } else if ($commande['statut_commande'] === 'Payée' || $commande['statut_commande'] === 'Validée') {
        // La commande est déjà validée (cas d'un rafraîchissement de page par l'utilisateur)
        $paiement_valide = true;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $erreur_message = $e->getMessage();
}
?>

<link rel="stylesheet" href="css/retour_paiement.css">

<div class="payment-return-container">
    <?php if ($erreur_message): ?>
        <div class="result-card error-card">
            <i class="fa-solid fa-triangle-exclamation status-icon error"></i>
            <h2>Une erreur est survenue</h2>
            <p class="error-details"><?php echo htmlspecialchars($erreur_message); ?></p>
            <a href="produits.php" class="btn-action btn-secondary">Retourner au catalogue</a>
        </div>
    <?php elseif ($paiement_valide && $commande): ?>
        <div class="result-card success-card">
            <i class="fa-solid fa-circle-check status-icon success"></i>
            <h2>Paiement Accepté !</h2>
            <p class="subtitle">Merci pour votre confiance. Votre transaction de test via <strong>MaishaPay</strong> a été approuvée avec succès.</p>
            
            <div class="receipt-box">
                <h3><i class="fa-solid fa-receipt"></i> Reçu de transaction</h3>
                <div class="receipt-row">
                    <span>Référence Commande :</span>
                    <strong><?php echo htmlspecialchars($commande['reference_commande']); ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Montant réglé :</span>
                    <strong class="price-highlight"><?php echo number_format($commande['montant_total_usd'], 2); ?> $</strong>
                </div>
                <div class="receipt-row">
                    <span>Moyen de paiement :</span>
                    <span><?php echo htmlspecialchars($commande['mode_paiement'] ?? 'Mobile Money'); ?> (Mode Test)</span>
                </div>
                <div class="receipt-row">
                    <span>Statut de la commande :</span>
                    <span class="badge-status success-badge">Payée - En attente d'exportation</span>
                </div>
            </div>

            <div class="onapac-notice">
                <i class="fa-solid fa-circle-info"></i>
                <p>L'ONAPAC a enregistré votre transaction. Vos lots certifiés sont mis de côté. Nos inspecteurs phytosanitaires vont préparer vos documents de douane officiels.</p>
            </div>

            <div class="actions-wrapper">
                <a href="mes_commandes.php" class="btn-action btn-primary">
                    <i class="fa-solid fa-box"></i> Suivre ma commande
                </a>
                <a href="produits.php" class="btn-action btn-secondary">
                    Continuer sur le catalogue
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="result-card error-card">
            <i class="fa-solid fa-circle-xmark status-icon error"></i>
            <h2>Transaction échouée</h2>
            <p>La transaction de paiement en ligne a été refusée ou annulée par l'opérateur.</p>
            
            <div class="actions-wrapper">
                <a href="passer_commande.php" class="btn-action btn-primary">Réessayer le paiement</a>
                <a href="panier.php" class="btn-action btn-secondary">Retourner au panier</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
echo "</div>"; // Ferme le site-container ouvert par le header
?>
</body>
</html>