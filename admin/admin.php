<?php
// admin.php
// 1. Initialisation de la session et restriction d'accès
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : Remplacement par les noms exacts de tes rôles
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    (strtolower($_SESSION['role']) !== 'administrateur' && strtolower($_SESSION['role']) !== 'agent_onapac')) {
    
    // Si l'utilisateur n'est pas autorisé, redirection immédiate vers la page de connexion
    header('Location: connexion_admin.php');
    exit();
}

// 2. Inclusion de la connexion à la base de données
require_once 'bdd/db.php'; 

// Initialisation des variables
$totalUsers = 0;
$totalProduits = 0;
$totalMessages = 0;
$totalCommandes = 0;
$sommeStock = 0;
$totalRevenus = 0;

$produits = [];
$users = [];
$categories_list = [];
$messages_list = [];
$commandes_list = [];
$dernieres_commandes = [];
$derniers_messages = [];

try {
    // 3. Récupération des statistiques réelles depuis ton fichier SQL
    $totalProduits  = $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();
    $totalUsers     = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    $totalMessages  = $pdo->query("SELECT COUNT(*) FROM messages_contact WHERE est_lu = 0")->fetchColumn();
    $totalCommandes = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
    
    // Calcul du volume total disponible (Conversion en Tonnes)
    $stmtStock  = $pdo->query("SELECT SUM(stock_disponible) FROM produits");
    $resultStock = $stmtStock->fetchColumn();
    $sommeStock = $resultStock ? floatval($resultStock) / 1000 : 0;

    // Calcul du chiffre d'affaires total (Commandes)
    $totalRevenus = $pdo->query("SELECT SUM(montant_total_usd) FROM commandes")->fetchColumn() ?? 0;

    // 4. Récupération des produits avec catégories
    $sqlProd = "SELECT p.*, c.nom_categorie 
                FROM produits p 
                INNER JOIN categories c ON p.id_categorie = c.id_categorie 
                ORDER BY p.id_produit DESC";
    $produits = $pdo->query($sqlProd)->fetchAll();

    // 5. Récupération des utilisateurs avec rôles
    $sqlUser = "SELECT u.*, r.nom_role 
                FROM utilisateurs u 
                INNER JOIN roles r ON u.id_role = r.id_role 
                ORDER BY u.id_utilisateur DESC";
    $users = $pdo->query($sqlUser)->fetchAll();

    // 6. Récupération des catégories
    $categories_list = $pdo->query("SELECT * FROM categories ORDER BY id_categorie ASC")->fetchAll();

    // 7. Récupération des messages de contact
    $messages_list = $pdo->query("SELECT * FROM messages_contact ORDER BY date_envoi DESC")->fetchAll();

    // 8. Récupération des commandes alignée sur la structure ONAPAC_DB
    $sqlCommandes = "SELECT c.*, u.nom, u.prenom, u.nom_entreprise,
                            p.montant_paye_usd, p.statut_paiement, p.mode_paiement,
                            l.statut_livraison, l.adresse_livraison, l.numero_suivi
                     FROM commandes c
                     INNER JOIN utilisateurs u ON c.id_acheteur = u.id_utilisateur
                     LEFT JOIN paiements p ON c.id_commande = p.id_commande
                     LEFT JOIN livraisons l ON c.id_commande = l.id_commande
                     ORDER BY c.date_commande DESC";
    $commandes_list = $pdo->query($sqlCommandes)->fetchAll();

    // 9. Récupération des 5 dernières commandes pour le Dashboard
    $sqlRecentCmd = "SELECT c.*, u.nom, u.prenom, u.nom_entreprise, p.statut_paiement
                     FROM commandes c
                     INNER JOIN utilisateurs u ON c.id_acheteur = u.id_utilisateur
                     LEFT JOIN paiements p ON c.id_commande = p.id_commande
                     ORDER BY c.date_commande DESC LIMIT 5";
    $dernieres_commandes = $pdo->query($sqlRecentCmd)->fetchAll();

    // 10. Récupération des 3 derniers messages non lus pour le Dashboard
    $derniers_messages = $pdo->query("SELECT * FROM messages_contact WHERE est_lu = 0 ORDER BY date_envoi DESC LIMIT 3")->fetchAll();

} catch (PDOException $e) {
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; margin:20px; border-radius:5px; font-family:monospace; z-index:9999; position:relative;'>";
    echo "<strong>Erreur Base de données :</strong> " . $e->getMessage();
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONAPAC - Administration Intégrale</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        /* RESET GLOBAL - Suppression absolue de toutes les marges parasites */
        * {
            box-sizing: border-box;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100%;
            height: 100%;
            background-color: #f7fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* CORRECTIFS DESIGN NAVBAR & POSITIONNEMENT */
        .admin-navbar {
            position: fixed; /* Fixe la navbar en haut */
            top: 0;
            left: 0;
            width: 100%;
            height: 60px; /* Hauteur fixe de la navbar */
            margin: 0 !important;
            border-radius: 0 !important;
            z-index: 1000; /* Reste toujours au-dessus */
        }

        .admin-container {
            display: flex;
            align-items: stretch;
            min-height: calc(100vh - 60px); /* Prend tout le reste de l'écran */
            margin-top: 60px !important; /* Pousse tout le contenu en dessous de la navbar (60px) */
            padding: 0 !important;
            background-color: #f7fafc;
        }
        
        .admin-sidebar {
            min-width: 260px;
            background: #1e293b;
            margin: 0;
            padding-bottom: 30px;
            /* S'assure que la sidebar s'adapte à la hauteur restante */
            position: sticky;
            top: 60px;
            height: calc(100vh - 60px);
            overflow-y: auto;
        }

        .admin-content {
            flex: 1;
            margin: 0; 
            padding: 30px;
            overflow-y: auto;
        }

        /* BADGES ET DESIGN */
        .badge-status { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; color: #fff; }
        .bg-success { background-color: #2e7d32; }
        .bg-warning { background-color: #f57c00; }
        .bg-danger { background-color: #d32f2f; }
        .bg-info { background-color: #0288d1; }
        
        .details-box { background: #f9f9f9; padding: 10px; margin-top: 5px; border-radius: 4px; font-size: 0.9rem; border-left: 3px solid #1e5631; }

        .admin-profile-top {
            display: flex;
            align-items: center; 
            gap: 15px;
        }

        .btn-exit-admin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px; 
            padding: 0 15px;
            box-sizing: border-box;
        }

        /* NOUVEAUX ÉLÉMENTS DE DASHBOARD INTEGRÉS */
        .dashboard-grid-widgets {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-top: 25px;
        }

        @media (max-width: 1024px) {
            .dashboard-grid-widgets {
                grid-template-columns: 1fr;
            }
        }

        .dashboard-widget {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .dashboard-widget h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.15rem;
            color: #2d3748;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-actions-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 10px;
        }

        .btn-quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            color: #4a5568;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-quick-action i {
            font-size: 1.4rem;
            color: #1e5631;
            margin-bottom: 8px;
        }

        .btn-quick-action:hover {
            background: #eaf2ed;
            border-color: #1e5631;
            color: #1e5631;
            transform: translateY(-2px);
        }

        .mini-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .mini-list-item {
            padding: 10px 0;
            border-bottom: 1px solid #edf2f7;
            font-size: 0.9rem;
        }

        .mini-list-item:last-child {
            border-bottom: none;
        }

        /* Rapports Styles */
        .card-rapport {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            margin-top: 20px;
        }
        .card-rapport h3 {
            color: #1e5631;
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }
        .form-group input, .form-group select {
            padding: 10px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            outline: none;
            font-size: 0.95rem;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #1e5631;
        }
        .btn-generate {
            background-color: #1e5631;
            color: #ffffff;
            border: none;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s;
        }
        .btn-generate:hover {
            background-color: #174425;
        }
    </style>
</head>
<body>

    <nav class="admin-navbar">
        <div class="admin-nav-container">
            <div class="admin-logo">
                <i class="fa-solid fa-unlock-keyhole"></i> ONAPAC <span>Back-Office</span>
            </div>
            <div class="admin-profile-top">
                <span><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['role']); ?> : <?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></span>
                <a href="../deconnexion.php" class="btn-exit-admin"><i class="fa-solid fa-right-from-bracket"></i> Quitter</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        
        <aside class="admin-sidebar">
            <ul class="admin-menu">
                <li><button class="admin-menu-btn active" onclick="switchAdminTab('stats')"><i class="fa-solid fa-chart-line"></i> Vue d'ensemble</button></li>
                <li><button class="admin-menu-btn" onclick="switchAdminTab('ajouter-produit')"><i class="fa-solid fa-plus"></i> Ajouter un Lot</button></li>
                <li><button class="admin-menu-btn" onclick="switchAdminTab('liste-produits')"><i class="fa-solid fa-boxes-stacked"></i> Lots au Catalogue</button></li>
                <li><button class="admin-menu-btn" onclick="switchAdminTab('users')"><i class="fa-solid fa-users"></i> Gestion des Users</button></li>
                <li><button class="admin-menu-btn" onclick="switchAdminTab('categories')"><i class="fa-solid fa-tags"></i> Catégories</button></li>
                <li><button class="admin-menu-btn" onclick="switchAdminTab('commandes')"><i class="fa-solid fa-cart-shopping"></i> Commandes & Suivi</button></li>
                <li><button class="admin-menu-btn" onclick="switchAdminTab('messages')"><i class="fa-solid fa-envelope"></i> Messages (<?php echo $totalMessages; ?>)</button></li>
                <li><button class="admin-menu-btn" onclick="switchAdminTab('rapports')"><i class="fa-solid fa-file-pdf"></i> Rapports PDF</button></li>
            </ul>
        </aside>

        <main class="admin-content">

            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] === 'success'): ?>
                    <div style="background: #eaf2ed; color: #1e5631; border: 1.5px solid #1e5631; padding: 15px; border-radius: 6px; margin-bottom: 25px; font-weight: 600;">
                        <i class="fa-solid fa-circle-check"></i> Action traitée avec succès dans la base de données !
                    </div>
                <?php elseif ($_GET['status'] === 'deleted'): ?>
                    <div style="background: #fbe9e7; color: #c62828; border: 1.5px solid #c62828; padding: 15px; border-radius: 6px; margin-bottom: 25px; font-weight: 600;">
                        <i class="fa-solid fa-trash-can"></i> Élément retiré définitivement.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div id="admin-tab-stats" class="admin-tab-content active">
                <h2>Tableau de bord analytique</h2>
                
                <div class="stats-cards">
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-users"></i></div><div class="stat-info"><h3><?php echo $totalUsers; ?></h3><p>Opérateurs</p></div></div>
                    <div class="stat-card success"><div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div><div class="stat-info"><h3><?php echo $totalProduits; ?></h3><p>Lots Catalogue</p></div></div>
                    <div class="stat-card info-blue" style="border-left: 4px solid #007bff;"><div class="stat-icon" style="color: #007bff;"><i class="fa-solid fa-weight-hanging"></i></div><div class="stat-info"><h3><?php echo number_format($sommeStock, 1); ?> T</h3><p>Volume (Tonnes)</p></div></div>
                    <div class="stat-card warning"><div class="stat-icon"><i class="fa-solid fa-receipt"></i></div><div class="stat-info"><h3><?php echo $totalCommandes; ?></h3><p>Commandes</p></div></div>
                    <div class="stat-card purple" style="border-left: 4px solid #9c27b0;"><div class="stat-icon" style="color: #9c27b0;"><i class="fa-solid fa-envelope-open-text"></i></div><div class="stat-info"><h3><?php echo $totalMessages; ?></h3><p>Non Lus</p></div></div>
                    <div class="stat-card" style="border-left: 4px solid #2e7d32;"><div class="stat-icon" style="color: #2e7d32;"><i class="fa-solid fa-sack-dollar"></i></div><div class="stat-info"><h3><?php echo number_format($totalRevenus, 2); ?> $</h3><p>CA Global</p></div></div>
                </div>

                <div class="dashboard-grid-widgets">
                    <div class="dashboard-widget">
                        <h3><i class="fa-solid fa-list-check"></i> Transactions Récentes</h3>
                        <div class="admin-table-responsive" style="box-shadow: none; border: none; margin: 0;">
                            <table class="admin-table" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Opérateur</th>
                                        <th>Montant</th>
                                        <th>Paiement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dernieres_commandes)): ?>
                                        <tr><td colspan="4" style="text-align: center; color: #888; padding: 15px;">Aucune commande récente.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($dernieres_commandes as $cmd): ?>
                                            <tr>
                                                <td><small><?php echo date('d/m/Y H:i', strtotime($cmd['date_commande'])); ?></small></td>
                                                <td><strong><?php echo htmlspecialchars($cmd['nom_entreprise'] ?? ($cmd['nom'] . ' ' . $cmd['prenom'])); ?></strong></td>
                                                <td><strong><?php echo number_format($cmd['montant_total_usd'], 2); ?> $</strong></td>
                                                <td>
                                                    <?php 
                                                    $st = strtolower($cmd['statut_paiement'] ?? 'en attente');
                                                    $cl = ($st === 'confirmé') ? 'bg-success' : 'bg-warning';
                                                    ?>
                                                    <span class="badge-status <?php echo $cl; ?>" style="font-size: 0.75rem; padding: 2px 6px;"><?php echo htmlspecialchars($cmd['statut_paiement'] ?? 'En attente'); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="dashboard-widget">
                            <h3><i class="fa-solid fa-bolt"></i> Actions Rapides</h3>
                            <div class="quick-actions-container">
                                <a class="btn-quick-action" onclick="switchAdminTab('ajouter-produit')">
                                    <i class="fa-solid fa-plus-circle"></i>
                                    Nouveau Lot
                                </a>
                                <a class="btn-quick-action" onclick="switchAdminTab('rapports')">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                    Rapports PDF
                                </a>
                                <a class="btn-quick-action" onclick="switchAdminTab('messages')">
                                    <i class="fa-solid fa-envelope"></i>
                                    Boîte (<?php echo $totalMessages; ?>)
                                </a>
                            </div>
                        </div>

                        <div class="dashboard-widget">
                            <h3><i class="fa-solid fa-bell"></i> Messages Urgents (Non lus)</h3>
                            <ul class="mini-list">
                                <?php if (empty($derniers_messages)): ?>
                                    <li class="mini-list-item" style="color: #888; text-align: center;">Aucun nouveau message.</li>
                                <?php else: ?>
                                    <?php foreach($derniers_messages as $m): ?>
                                        <li class="mini-list-item">
                                            <strong><?php echo htmlspecialchars($m['nom_expediteur']); ?></strong> : 
                                            <span style="color: #555;"><?php echo htmlspecialchars(substr($m['message'], 0, 45)) . '...'; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                    <li style="text-align: center; margin-top: 10px; list-style: none;">
                                        <button class="btn-generate" onclick="switchAdminTab('messages')" style="padding: 6px 12px; font-size: 0.8rem;">Consulter la boîte</button>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div id="admin-tab-ajouter-produit" class="admin-tab-content">
                <h2>Publier un nouveau lot agricole</h2>
                <form class="admin-form" action="admin_action.php" method="POST" enctype="multipart/form-data">
                    <div class="input-group"><label for="p-titre">Dénomination du Produit</label><input type="text" id="p-titre" name="titre" required></div>
                    <div class="form-row-2">
                        <div class="input-group">
                            <label for="p-cat">Catégorie</label>
                            <select id="p-cat" name="categorie" required>
                                <?php foreach($categories_list as $c): ?>
                                    <option value="<?php echo $c['id_categorie']; ?>"><?php echo htmlspecialchars($c['nom_categorie']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group"><label for="p-origine">Provenance</label><input type="text" id="p-origine" name="origine" required></div>
                    </div>
                    <div class="form-row-2">
                        <div class="input-group"><label for="p-prix">Prix (USD / Kg)</label><input type="number" step="0.01" id="p-prix" name="prix" required></div>
                        <div class="input-group"><label for="p-image">Image du lot</label><input type="file" id="p-image" name="image" accept="image/*" required></div>
                    </div>
                    <div class="input-group"><label for="p-desc">Description</label><textarea id="p-desc" name="description" rows="4" required></textarea></div>
                    <button type="submit" class="btn-admin-submit"><i class="fa-solid fa-cloud-arrow-up"></i> Enregistrer le produit</button>
                </form>
            </div>

            <div id="admin-tab-liste-produits" class="admin-tab-content">
                <h2>Lots au Catalogue</h2>
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>Grade / Image</th><th>Dénomination</th><th>Catégorie</th><th>Origine</th><th>Prix</th><th style="text-align: center;">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($produits as $row): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($row['grade_qualite'] ?? 'Standard'); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($row['nom_produit']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nom_categorie']); ?></td>
                                    <td><?php echo htmlspecialchars($row['origine_provenance']); ?></td>
                                    <td><?php echo number_format($row['prix_unitaire_usd'], 2); ?> $</td>
                                    <td style="text-align: center;">
                                        <a href="modifier_produit.php?id=<?php echo $row['id_produit']; ?>" class="btn-action edit" style="background:#007bff; color:#fff; padding:6px 10px; border-radius:4px;"><i class="fa-solid fa-pen"></i></a>
                                        <a href="supprimer_produit.php?id=<?php echo $row['id_produit']; ?>" class="btn-action delete" style="background:#dc3545; color:#fff; padding:6px 10px; border-radius:4px;" onclick="return confirm('Supprimer ce lot ?');"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="admin-tab-users" class="admin-tab-content">
                <h2>Registre des Opérateurs Économiques</h2>
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>Structure</th><th>Email</th><th>Téléphone</th><th>RCCM</th><th>Rôle</th></tr></thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($u['nom'] . ' ' . $u['prenom']); ?></strong><br><small><?php echo htmlspecialchars($u['nom_entreprise'] ?? '-'); ?></small></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['telephone'] ?? '-'); ?></td>
                                    <td><code><?php echo htmlspecialchars($u['rccm'] ?? 'Interne'); ?></code></td>
                                    <td><span class="badge-role"><?php echo htmlspecialchars($u['nom_role']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="admin-tab-categories" class="admin-tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Gestion des Catégories de Produits</h2>
                    <form action="ajouter_categorie.php" method="POST" style="display:flex; gap:10px; background:#fff; padding:10px; border-radius:6px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                        <input type="text" name="nom_categorie" placeholder="Nouvelle Catégorie (ex: Thé)" required style="padding:8px; border:1px solid #ccc; border-radius:4px;">
                        <button type="submit" style="background:#1e5631; color:#fff; border:none; padding:8px 12px; border-radius:4px; font-weight:600; cursor:pointer;"><i class="fa-solid fa-plus"></i> Créer</button>
                    </form>
                </div>
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>ID</th><th>Nom de la Catégorie</th><th>Description</th><th style="text-align: center;">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($categories_list as $cat): ?>
                                <tr>
                                    <td><code>#<?php echo $cat['id_categorie']; ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($cat['nom_categorie']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($cat['description'] ?? 'Aucune description fournie.'); ?></td>
                                    <td style="text-align: center;">
                                        <a href="supprimer_categorie.php?id=<?php echo $cat['id_categorie']; ?>" class="btn-action delete" style="background:#dc3545; color:#fff; padding:6px 10px; border-radius:4px;" onclick="return confirm('Supprimer cette catégorie ?');"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="admin-tab-commandes" class="admin-tab-content">
                <h2>Suivi Centralisé des Commandes & Flux Logistiques</h2>
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Réf / Date</th>
                                <th>Opérateur (Acheteur)</th>
                                <th>Total Facturé</th>
                                <th>Paiement</th>
                                <th>Livraison</th>
                                <th style="text-align: center;">Actions CRUD</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($commandes_list)): ?>
                                <tr><td colspan="6" style="text-align:center; padding:20px; color:#888;">Aucune commande enregistrée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($commandes_list as $com): ?>
                                    <tr>
                                        <td>
                                            <strong><code><?php echo htmlspecialchars($com['reference_commande']); ?></code></strong><br>
                                            <small><?php echo $com['date_commande']; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($com['nom'] . ' ' . $com['prenom']); ?></strong><br>
                                            <small style="color:#555;"><?php echo htmlspecialchars($com['nom_entreprise'] ?? 'Particulier'); ?></small>
                                        </td>
                                        <td><strong><?php echo number_format($com['montant_total_usd'], 2); ?> $</strong></td>
                                        <td>
                                            <?php 
                                            $p_statut = strtolower($com['statut_paiement'] ?? 'en attente de vérification');
                                            $p_class = ($p_statut === 'confirmé') ? 'bg-success' : (($p_statut === 'échoué') ? 'bg-danger' : 'bg-warning');
                                            ?>
                                            <span class="badge-status <?php echo $p_class; ?>">
                                                <?php echo htmlspecialchars($com['statut_paiement'] ?? 'Non Initié'); ?>
                                            </span><br>
                                            <small style="font-size:0.8rem; color:#666;"><?php echo htmlspecialchars($com['mode_paiement'] ?? '-'); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $l_statut = strtolower($com['statut_livraison'] ?? 'en préparation');
                                            $l_class = ($l_statut === 'livrée') ? 'bg-success' : (($l_statut === 'en transit') ? 'bg-info' : 'bg-warning');
                                            ?>
                                            <span class="badge-status <?php echo $l_class; ?>">
                                                <?php echo htmlspecialchars($com['statut_livraison'] ?? 'En préparation'); ?>
                                            </span><br>
                                            <small style="font-size:0.75rem; font-family:monospace;">Suivi: <?php echo htmlspecialchars($com['numero_suivi'] ?? 'Aucun'); ?></small>
                                        </td>
                                        <td style="text-align: center;">
                                            <!-- Bouton Modifier -->
                                            <a href="modifier_commande.php?id=<?php echo $com['id_commande']; ?>" class="btn-action edit" style="background:#f57c00; color:#fff; padding:6px 10px; border-radius:4px;" title="Mettre à jour les statuts logistiques"><i class="fa-solid fa-truck-ramp-box"></i></a>
                                            
                                            <!-- AJOUT DU BOUTON IMPRIMER BON DE LIVRAISON -->
                                            <a href="generer_bon_livraison.php?id=<?php echo $com['id_commande']; ?>" target="_blank" class="btn-action print" style="background:#0288d1; color:#fff; padding:6px 10px; border-radius:4px; margin: 0 2px;" title="Imprimer le Bon de Livraison"><i class="fa-solid fa-print"></i></a>
                                            
                                            <!-- Bouton Supprimer -->
                                            <a href="supprimer_commande.php?id=<?php echo $com['id_commande']; ?>" class="btn-action delete" style="background:#dc3545; color:#fff; padding:6px 10px; border-radius:4px;" title="Supprimer la commande" onclick="return confirm('Supprimer cette commande ?');"><i class="fa-solid fa-trash"></i></a>
                                            
                                            <div class="details-box" style="text-align: left; max-width: 250px; margin: 5px auto 0 auto;">
                                                <strong><i class="fa-solid fa-list-check"></i> Articles :</strong><br>
                                                <?php 
                                                    $stmtLignes = $pdo->prepare("SELECT lc.*, p.nom_produit 
                                                                                FROM lignes_commande lc 
                                                                                INNER JOIN produits p ON lc.id_produit = p.id_produit 
                                                                                WHERE lc.id_commande = :id");
                                                    $stmtLignes->execute([':id' => $com['id_commande']]);
                                                    $lignes = $stmtLignes->fetchAll();
                                                    foreach($lignes as $l) {
                                                        echo "- " . htmlspecialchars($l['nom_produit']) . " (" . floatval($l['quantite_commandee']) . " x " . number_format($l['prix_applique_usd'],2) . "$)<br>";
                                                    }
                                                ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="admin-tab-messages" class="admin-tab-content">
                <h2>Boîte de réception - Messages Utilisateurs</h2>
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>Expéditeur</th><th>Sujet</th><th>Message</th><th>Date d'envoi</th><th style="text-align: center;">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($messages_list as $msg): ?>
                                <tr style="<?php echo $msg['est_lu'] == 0 ? 'background: #f1f8e9; font-weight:bold;' : ''; ?>">
                                    <td><strong><?php echo htmlspecialchars($msg['nom_expediteur']); ?></strong><br><small><?php echo htmlspecialchars($msg['email_expediteur']); ?></small></td>
                                    <td><?php echo htmlspecialchars($msg['sujet']); ?></td>
                                    <td><p style="max-width:300px; white-space: nowrap; overflow:hidden; text-overflow:ellipsis; margin:0;"><?php echo htmlspecialchars($msg['message']); ?></p></td>
                                    <td><small><?php echo $msg['date_envoi']; ?></small></td>
                                    <td style="text-align: center;">
                                        <a href="supprimer_message.php?id=<?php echo $msg['id_message']; ?>" class="btn-action delete" style="background:#dc3545; color:#fff; padding:6px 10px; border-radius:4px;" onclick="return confirm('Supprimer ce message ?');"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="admin-tab-rapports" class="admin-tab-content">
                <h2><i class="fa-solid fa-file-pdf"></i> Centre de Rapports et Statistiques</h2>
                
                <div class="card-rapport">
                    <h3><i class="fa-solid fa-cart-shopping"></i> Rapport Analytique des Commandes</h3>
                    <p style="color: #718096; margin-bottom: 25px; font-size: 0.95rem;">
                        Exportez un état complet des commandes d'exportation enregistrées sur le portail ONAPAC. Ce rapport calcule les totaux cumulés et liste les acheteurs.
                    </p>
                    
                    <form action="generer_rapport_commandes.php" method="GET" target="_blank">
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="periode">Sélectionner la Période</label>
                                <select id="periode" name="periode" onchange="toggleDateInputs(this.value)">
                                    <option value="tous">Historique complet</option>
                                    <option value="mois_en_cours">Mois en cours</option>
                                    <option value="mois_dernier">Mois dernier</option>
                                    <option value="personnalise">Plage de dates personnalisée</option>
                                </select>
                            </div>

                            <div class="form-group" id="group-date-debut" style="display: none;">
                                <label for="date_debut">Du (Date de début)</label>
                                <input type="date" id="date_debut" name="date_debut">
                            </div>

                            <div class="form-group" id="group-date-fin" style="display: none;">
                                <label for="date_fin">Au (Date de fin)</label>
                                <input type="date" id="date_fin" name="date_fin">
                            </div>
                        </div>

                        <button type="submit" class="btn-generate">
                            <i class="fa-solid fa-file-export"></i> Exporter au Format PDF
                        </button>
                    </form>
                </div>

                <div class="card-rapport" style="margin-top: 30px; border-top: 3px solid #ffd700;">
                    <h3><i class="fa-solid fa-boxes-stacked"></i> Rapport d'Inventaire et de Stock Agricole</h3>
                    <p style="color: #718096; margin-bottom: 25px; font-size: 0.95rem;">
                        Générez un état physique et financier des stocks de matières premières (café, cacao, thé, etc.) actuellement enregistrés dans les entrepôts de l'Office.
                    </p>
    
                    <form action="generer_rapport_stocks.php" method="GET" target="_blank">
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="id_categorie">Filtrer par Catégorie</label>
                                <select id="id_categorie" name="id_categorie">
                                    <option value="toutes">Toutes les catégories</option>
                                    <?php foreach($categories_list as $cat): ?>
                                        <option value="<?php echo $cat['id_categorie']; ?>"><?php echo htmlspecialchars($cat['nom_categorie']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                
                            <div class="form-group">
                                <label for="tri">Trier par</label>
                                <select id="tri" name="tri">
                                    <option value="nom">Nom du produit (A-Z)</option>
                                    <option value="stock_desc">Stock disponible (Décroissant)</option>
                                    <option value="prix_desc">Prix unitaire (Décroissant)</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-generate" style="background-color: #1e5631;">
                            <i class="fa-solid fa-file-export"></i> Générer l'Inventaire PDF
                        </button>
                    </form>
                </div>

                <div class="card-rapport" style="margin-top: 30px; border-top: 3px solid #0288d1;">
                    <h3><i class="fa-solid fa-truck-fast"></i> Rapport de Livraison & Suivi Logistique</h3>
                    <p style="color: #718096; margin-bottom: 25px; font-size: 0.95rem;">
                        Générez un état d'avancement des expéditions et des flux logistiques. Utile pour contrôler les commandes en transit et s'assurer du respect des délais de livraison.
                    </p>
        
                    <form action="generer_rapport_logistique.php" method="GET" target="_blank">
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="statut_livraison">Statut des expéditions</label>
                                <select id="statut_livraison" name="statut_livraison">
                                    <option value="tous">Tous les statuts</option>
                                    <option value="en préparation">En préparation</option>
                                    <option value="en transit">En transit</option>
                                    <option value="livrée">Livrée</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="tri_logistique">Trier les expéditions par</label>
                                <select id="tri_logistique" name="tri_logistique">
                                    <option value="date_desc">Plus récentes d'abord</option>
                                    <option value="destination">Destination (A-Z)</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-generate" style="background-color: #0288d1;">
                            <i class="fa-solid fa-file-export"></i> Générer le Rapport Logistique
                        </button>
                    </form>
                </div>

                <div class="card-rapport" style="margin-top: 30px; border-top: 3px solid #9c27b0;">
                    <h3><i class="fa-solid fa-address-card"></i> Rapport d'Activité Exportateur (Fiche Client)</h3>
                    <p style="color: #718096; margin-bottom: 25px; font-size: 0.95rem;">
                        Générez une fiche récapitulative contenant l'historique complet des transactions, des volumes commandés et de la situation financière pour un opérateur économique spécifique.
                    </p>
    
                    <form action="generer_rapport_client.php" method="GET" target="_blank">
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="id_acheteur">Sélectionner l'Opérateur Économique</label>
                                <select id="id_acheteur" name="id_acheteur" required>
                                    <option value="" disabled selected>-- Choisir un opérateur --</option>
                                    <?php foreach($users as $u): ?>
                                        <option value="<?php echo $u['id_utilisateur']; ?>">
                                            <?php 
                                            $label = !empty($u['nom_entreprise']) ? $u['nom_entreprise'] . " (" . $u['nom'] . ")" : $u['nom'] . " " . $u['prenom'];
                                            echo htmlspecialchars($label); 
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-generate" style="background-color: #9c27b0;">
                            <i class="fa-solid fa-file-export"></i> Exporter la Fiche Partenaire
                        </button>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script src="../js/admin.js"></script>
    <script>
    function toggleDateInputs(value) {
        const startGroup = document.getElementById('group-date-debut');
        const endGroup = document.getElementById('group-date-fin');
        
        if (value === 'personnalise') {
            startGroup.style.display = 'flex';
            endGroup.style.display = 'flex';
            document.getElementById('date_debut').required = true;
            document.getElementById('date_fin').required = true;
        } else {
            startGroup.style.display = 'none';
            endGroup.style.display = 'none';
            document.getElementById('date_debut').required = false;
            document.getElementById('date_fin').required = false;
        }
    }
    </script>
</body>
</html>