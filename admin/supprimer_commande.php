<?php
// supprimer_commande.php
require_once 'bdd/db.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // En vertu des contraintes ONAPAC_DB (ON DELETE CASCADE),
        // supprimer la ligne dans 'commandes' va nettoyer automatiquement livraisons et paiements.
        $stmt = $pdo->prepare("DELETE FROM commandes WHERE id_commande = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php?status=deleted');
        exit();
    } catch (PDOException $e) {
        die("Erreur lors de la suppression de la commande : " . $e->getMessage());
    }
}
header('Location: admin.php');
exit();