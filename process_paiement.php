<?php
// process_paiement.php
session_start();
require_once 'bdd/db.php';

// Protection d'accès
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour initier un paiement.";
    header('Location: connexion.php');
    exit();
}

$id_utilisateur = $_SESSION['user_id'];

// Vérification de la soumission du formulaire de commande
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['adresse_livraison']) || empty($_POST['telephone'])) {
    $_SESSION['error'] = "Veuillez renseigner toutes les informations de livraison.";
    header('Location: passer_commande.php');
    exit();
}

// Nettoyage des coordonnées d'expédition
$adresse_livraison = htmlspecialchars(trim($_POST['adresse_livraison'])) . ', ' . 
                     htmlspecialchars(trim($_POST['ville'])) . ' - ' . 
                     htmlspecialchars(trim($_POST['pays']));
$telephone_contact = htmlspecialchars(trim($_POST['telephone']));
$mode_paiement = htmlspecialchars(trim($_POST['mode_paiement']));

try {
    // 1. Récupérer et valider le panier de l'utilisateur
    $stmt = $pdo->prepare("SELECT p.quantite, prod.id_produit, prod.nom_produit, 
                                  prod.prix_unitaire_usd, prod.unite_mesure, prod.stock_disponible
                           FROM paniers p
                           INNER JOIN produits prod ON p.id_produit = prod.id_produit
                           WHERE p.id_utilisateur = :user_id");
    $stmt->execute([':user_id' => $id_utilisateur]);
    $items_panier = $stmt->fetchAll();

    if (empty($items_panier)) {
        throw new Exception("Votre panier est vide.");
    }

    // 2. Calcul du montant de la commande (Mercuriale FOB + 1% frais ONAPAC)
    $sous_total = 0;
    foreach ($items_panier as $item) {
        $sous_total += $item['prix_unitaire_usd'] * $item['quantite'];
    }
    $frais_onapac = $sous_total * 0.01;
    $total_general = $sous_total + $frais_onapac;

    // 3. Génération d'une référence de commande unique
    $reference_commande = "CMD-" . date("Ymd") . "-" . rand(1000, 9999);

    // Début de la transaction pour garantir l'intégrité de la BDD
    $pdo->beginTransaction();

    // 4. Insertion de la commande (Statut initial : 'En attente')
    $stmt_cmd = $pdo->prepare("INSERT INTO commandes (id_acheteur, reference_commande, montant_total_usd, statut_commande, date_commande) 
                               VALUES (:id_acheteur, :ref, :total, 'En attente', NOW())");
    $stmt_cmd->execute([
        ':id_acheteur' => $id_utilisateur,
        ':ref' => $reference_commande,
        ':total' => $total_general
    ]);
    $id_commande = $pdo->lastInsertId();

    // 5. Insertion des lignes de commande & Décrémentation préventive des stocks
    $stmt_ligne = $pdo->prepare("INSERT INTO lignes_commande (id_commande, id_produit, quantite_commandee, prix_applique_usd) 
                                 VALUES (:id_cmd, :id_prod, :qty, :prix)");
    
    $stmt_stock = $pdo->prepare("UPDATE produits SET stock_disponible = stock_disponible - :qty WHERE id_produit = :id_prod");

    foreach ($items_panier as $item) {
        // Validation que le stock n'a pas expiré entre-temps
        if ($item['quantite'] > $item['stock_disponible']) {
            throw new Exception("Le stock disponible pour " . $item['nom_produit'] . " est insuffisant.");
        }

        // Écriture de la ligne
        $stmt_ligne->execute([
            ':id_cmd' => $id_commande,
            ':id_prod' => $item['id_produit'],
            ':qty' => $item['quantite'],
            ':prix' => $item['prix_unitaire_usd']
        ]);

        // Mise à jour physique du stock
        $stmt_stock->execute([
            ':qty' => $item['quantite'],
            ':id_prod' => $item['id_produit']
        ]);
    }

    // 6. Enregistrement des pré-requis logistiques dans la table 'livraisons'
    $stmt_liv = $pdo->prepare("INSERT INTO livraisons (id_commande, statut_livraison, adresse_livraison) 
                               VALUES (:id_cmd, 'En préparation', :adresse)");
    $stmt_liv->execute([
        ':id_cmd' => $id_commande,
        ':adresse' => $adresse_livraison
    ]);

    // 7. Initialisation de la ligne de paiement (avec référence pour éviter l'erreur de doublon)
    $stmt_pay = $pdo->prepare("INSERT INTO paiements (id_commande, reference_transaction, montant_paye_usd, statut_paiement, mode_paiement, date_paiement) 
                               VALUES (:id_cmd, :ref_trans, :montant, 'En attente de vérification', :mode, NOW())");
    $stmt_pay->execute([
        ':id_cmd' => $id_commande,
        ':ref_trans' => $reference_commande, // Renseigne la clé unique
        ':montant' => $total_general,
        ':mode' => ($mode_paiement === 'maishapay_card' ? 'Carte Bancaire' : 'Mobile Money')
    ]);

    // 8. Vider le panier de l'utilisateur
    $stmt_clear = $pdo->prepare("DELETE FROM paniers WHERE id_utilisateur = :user_id");
    $stmt_clear->execute([':user_id' => $id_utilisateur]);

    // Validation définitive de toutes les écritures SQL
    $pdo->commit();

    // ================= CONFIGURATION MAISHAPAY =================
    $public_key = "MP-SBPK-C20pwK0jZKl6L.GsP\$yAUzjGoqyve3X1zbZBzi/ex/3t7wcLW2yo.7NMhv4CGpUB\$.ta\$y6jluUiQtucxNyiYSg0Y0z1u\$Z\$KE0Xyi2/K7mi0OnaG1n6vuC\$";
    $secret_key = "MP-SBSK-lhzezlqwrU\$YIlpHUP\$yv/X0O\$nsT\$Hm8c/r2nD0zou9mU11I\$Jb\$blCnrD9hWHNBWwP5fWAO/kWLE2Z7z2rFjya3zVWyWM1sH3JLVh1ZxQp/LK4J69QBYPI";

    // URLs de retour de ton site local
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $callback_url = $protocol . $host . "/onapac/retour_paiement.php?ref=" . $reference_commande;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // On annule tout en cas de pépin
    }
    $_SESSION['error'] = "Erreur de traitement de commande : " . $e->getMessage();
    header('Location: panier.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ONAPAC - Redirection vers MaishaPay</title>
    <style>
        body {
            background-color: #f4f6f5;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .redirect-box {
            text-align: center;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border-top: 5px solid #1e5631;
            max-width: 450px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1e5631;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 { color: #1e5631; margin-bottom: 10px; }
        p { color: #666; font-size: 0.95rem; line-height: 1.4; }
    </style>
</head>
<body>

    <div class="redirect-box">
        <h2>Initialisation du paiement sécurisé...</h2>
        <div class="spinner"></div>
        <p>Veuillez patienter, nous vous redirigeons vers la plateforme de test <strong>MaishaPay</strong> pour finaliser votre commande.</p>
        
        <form id="maishapayForm" action="https://marchand.maishapay.online/payment/vers1.0/merchant/checkout" method="POST">
            <input type="hidden" name="gatewayMode" value="0">
            <input type="hidden" name="publicApiKey" value="<?php echo htmlspecialchars($public_key); ?>">
            <input type="hidden" name="secretApiKey" value="<?php echo htmlspecialchars($secret_key); ?>">
            <input type="hidden" name="montant" value="<?php echo $total_general; ?>">
            <input type="hidden" name="devise" value="USD">
            <input type="hidden" name="transactionReference" value="<?php echo htmlspecialchars($reference_commande); ?>">
            <input type="hidden" name="callbackUrl" value="<?php echo htmlspecialchars($callback_url); ?>">
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                document.getElementById("maishapayForm").submit();
            }, 1500);
        });
    </script>

</body>
</html>