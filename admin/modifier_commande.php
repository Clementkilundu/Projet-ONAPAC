<?php
// modifier_commande.php
require_once 'bdd/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: admin.php');
    exit();
}

$id_commande = intval($_GET['id']);

try {
    // Sélection croisée avec les vrais noms de colonnes SQL
    $stmt = $pdo->prepare("SELECT c.*, p.statut_paiement, l.statut_livraison, l.numero_suivi
                           FROM commandes c
                           LEFT JOIN paiements p ON c.id_commande = p.id_commande
                           LEFT JOIN livraisons l ON c.id_commande = l.id_commande
                           WHERE c.id_commande = :id");
    $stmt->execute([':id' => $id_commande]);
    $commande = $stmt->fetch();

    if (!$commande) { die("Commande introuvable en base."); }

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statut_commande = $_POST['statut_commande'];
    $statut_paiement = $_POST['statut_paiement'];
    $statut_livraison = $_POST['statut_livraison'];
    $numero_suivi = htmlspecialchars($_POST['numero_suivi']);

    try {
        $pdo->beginTransaction();

        // 1. Table commandes (statut_commande)
        $stmt1 = $pdo->prepare("UPDATE commandes SET statut_commande = :st WHERE id_commande = :id");
        $stmt1->execute([':st' => $statut_commande, ':id' => $id_commande]);

        // 2. Table paiements (statut_paiement)
        $stmt2 = $pdo->prepare("UPDATE paiements SET statut_paiement = :st WHERE id_commande = :id");
        $stmt2->execute([':st' => $statut_paiement, ':id' => $id_commande]);

        // 3. Table livraisons (statut_livraison et numero_suivi)
        $stmt3 = $pdo->prepare("UPDATE livraisons SET statut_livraison = :st, numero_suivi = :num WHERE id_commande = :id");
        $stmt3->execute([':st' => $statut_livraison, ':num' => $numero_suivi, ':id' => $id_commande]);

        $pdo->commit();
        header('Location: admin.php?status=success');
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur de mise à jour des flux : " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ONAPAC - Suivi Logistique Commande</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body style="background:#f4f6f5; padding: 40px; font-family: sans-serif;">

    <div style="max-width: 600px; margin: 0 auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.05); border-top: 4px solid #f57c00;">
        <h2>Traitement Logistique <?php echo htmlspecialchars($commande['reference_commande']); ?></h2>
        
        <form action="" method="POST" class="admin-form" style="box-shadow:none; padding:0; background:transparent;">
            
            <div style="margin-bottom:15px;">
                <label style="font-weight:bold;">État de la commande (Table Commandes)</label>
                <select name="statut_commande" style="width:100%; padding:10px; margin-top:5px; border-radius:4px; border:1px solid #ccc;">
                    <option value="En attente" <?php echo ($commande['statut_commande'] === 'En attente') ? 'selected' : ''; ?>>En attente</option>
                    <option value="Validée" <?php echo ($commande['statut_commande'] === 'Validée') ? 'selected' : ''; ?>>Validée</option>
                    <option value="Certifiée OK" <?php echo ($commande['statut_commande'] === 'Certifiée OK') ? 'selected' : ''; ?>>Certifiée OK</option>
                    <option value="Livrée" <?php echo ($commande['statut_commande'] === 'Livrée') ? 'selected' : ''; ?>>Livrée</option>
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label style="font-weight:bold;">Statut du Paiement</label>
                <select name="statut_paiement" style="width:100%; padding:10px; margin-top:5px; border-radius:4px; border:1px solid #ccc;">
                    <option value="En attente de vérification" <?php echo ($commande['statut_paiement'] === 'En attente de vérification') ? 'selected' : ''; ?>>En attente de vérification</option>
                    <option value="Confirmé" <?php echo ($commande['statut_paiement'] === 'Confirmé') ? 'selected' : ''; ?>>Confirmé</option>
                    <option value="Échoué" <?php echo ($commande['statut_paiement'] === 'Échoué') ? 'selected' : ''; ?>>Échoué</option>
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label style="font-weight:bold;">État de la Livraison</label>
                <select name="statut_livraison" style="width:100%; padding:10px; margin-top:5px; border-radius:4px; border:1px solid #ccc;">
                    <option value="En préparation" <?php echo ($commande['statut_livraison'] === 'En préparation') ? 'selected' : ''; ?>>En préparation</option>
                    <option value="En transit" <?php echo ($commande['statut_livraison'] === 'En transit') ? 'selected' : ''; ?>>En transit</option>
                    <option value="Livrée" <?php echo ($commande['statut_livraison'] === 'Livrée') ? 'selected' : ''; ?>>Livrée à destination</option>
                </select>
            </div>

            <div style="margin-bottom:20px;">
                <label style="font-weight:bold;">Numéro de suivi douane / fret</label>
                <input type="text" name="numero_suivi" value="<?php echo htmlspecialchars($commande['numero_suivi'] ?? ''); ?>" placeholder="Ex: N-SUIVI-123" style="width:100%; padding:10px; margin-top:5px; border-radius:4px; border:1px solid #ccc;">
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" style="background:#f57c00; color:#fff; border:none; padding:12px 20px; border-radius:4px; font-weight:bold; cursor:pointer;">Enregistrer</button>
                <a href="admin.php" style="background:#666; color:#fff; padding:12px 20px; text-decoration:none; border-radius:4px;">Retour</a>
            </div>
        </form>
    </div>

</body>
</html>