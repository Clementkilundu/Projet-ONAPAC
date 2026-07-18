<?php
// dashboard_acheteur.php
session_start();
require_once 'bdd/db.php';

// Protection : l'utilisateur doit être connecté et avoir le rôle d'Acheteur (id_role = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header('Location: connexion.php');
    exit();
}

$id_utilisateur = $_SESSION['user_id'];

try {
    // 1. Récupérer les informations de profil de l'acheteur
    $stmt_user = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = :id");
    $stmt_user->execute([':id' => $id_utilisateur]);
    $acheteur = $stmt_user->fetch();

    // 2. Récupérer les statistiques rapides de l'acheteur
    // Nombre total de commandes
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE id_acheteur = :id");
    $stmt_count->execute([':id' => $id_utilisateur]);
    $total_commandes = $stmt_count->fetchColumn();

    // Dépenses totales (uniquement les commandes validées/payées)
    $stmt_sum = $pdo->prepare("SELECT SUM(montant_total_usd) FROM commandes WHERE id_acheteur = :id AND statut_commande != 'En attente'");
    $stmt_sum->execute([':id' => $id_utilisateur]);
    $total_depense = $stmt_sum->fetchColumn() ?? 0;

    // 3. Récupérer la liste de toutes les commandes de l'acheteur (de la plus récente à la plus ancienne)
    $stmt_orders = $pdo->prepare("SELECT c.*, p.statut_paiement, l.statut_livraison 
                                  FROM commandes c
                                  LEFT JOIN paiements p ON c.id_commande = p.id_commande
                                  LEFT JOIN livraisons l ON c.id_commande = l.id_commande
                                  WHERE c.id_acheteur = :id 
                                  ORDER BY c.date_commande DESC");
    $stmt_orders->execute([':id' => $id_utilisateur]);
    $commandes = $stmt_orders->fetchAll();

} catch (PDOException $e) {
    $erreur = "Erreur technique : " . $e->getMessage();
}

require_once 'inc/header.php';
?>

<link rel="stylesheet" href="css/dashboard_acheteur.css">

<div class="dashboard-container">
    <div class="dashboard-sidebar">
        <div class="user-profile-card">
            <div class="avatar-icon"><i class="fa-solid fa-user-tie"></i></div>
            <h3><?php echo htmlspecialchars($acheteur['prenom'] . ' ' . $acheteur['nom']); ?></h3>
            <p class="company-tag"><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($acheteur['nom_entreprise'] ?? 'Exportateur Indépendant'); ?></p>
            <p class="rccm-tag">RCCM : <?php echo htmlspecialchars($acheteur['rccm'] ?? 'Non fourni'); ?></p>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard_acheteur.php" class="active"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
            <a href="produits.php"><i class="fa-solid fa-seedling"></i> Catalogue FOB</a>
            <a href="panier.php"><i class="fa-solid fa-basket-shopping"></i> Mon Panier</a>
            <a href="deconnexion.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Se déconnecter</a>
        </nav>
    </div>

    <div class="dashboard-content">
        <div class="welcome-header">
            <h2>Ravi de vous revoir, <?php echo htmlspecialchars($acheteur['prenom']); ?> !</h2>
            <p>Suivez l'état d'analyse phytosanitaire et d'exportation de vos lots de café ou de cacao certifiés par l'ONAPAC.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fa-solid fa-box-archive text-green"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $total_commandes; ?></span>
                    <span class="stat-label">Commandes passées</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-dollar-sign text-orange"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_depense, 2); ?> $</span>
                    <span class="stat-label">Frais FOB & Certifications</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-award text-blue"></i>
                <div class="stat-info">
                    <span class="stat-value">ONAPAC</span>
                    <span class="stat-label">Régie de contrôle officiel</span>
                </div>
            </div>
        </div>

        <div class="orders-section">
            <div class="section-title-bar">
                <h3><i class="fa-solid fa-list-check"></i> Historique de vos demandes de certification</h3>
            </div>

            <?php if (empty($commandes)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <p>Vous n'avez pas encore initié de commande d'exportation.</p>
                    <a href="produits.php" class="btn-primary-fob">Parcourir le catalogue</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Date</th>
                                <th>Montant Total (USD)</th>
                                <th>Statut Commande</th>
                                <th>Paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $cmd): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cmd['reference_commande']); ?></strong></td>
                                    <td><?php echo date("d/m/Y", strtotime($cmd['date_commande'])); ?></td>
                                    <td class="price-cell"><?php echo number_format($cmd['montant_total_usd'], 2); ?> $</td>
                                    <td>
                                        <span class="badge-status cmd-<?php echo strtolower(str_replace(' ', '-', $cmd['statut_commande'])); ?>">
                                            <?php echo htmlspecialchars($cmd['statut_commande']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status pay-<?php echo strtolower(str_replace(' ', '-', $cmd['statut_paiement'] ?? 'En attente')); ?>">
                                            <?php echo htmlspecialchars($cmd['statut_paiement'] ?? 'Non initié'); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="detail_commande.php?id=<?php echo $cmd['id_commande']; ?>" class="btn-mini btn-view" title="Détails">
                                            <i class="fa-solid fa-eye"></i> Voir
                                        </a>
                                        <?php if ($cmd['statut_commande'] !== 'En attente'): ?>
                                            <a href="telecharger_facture.php?id=<?php echo $cmd['id_commande']; ?>" target="_blank" class="btn-mini btn-pdf" title="Facture PDF">
                                                <i class="fa-solid fa-file-pdf"></i> Facture
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>