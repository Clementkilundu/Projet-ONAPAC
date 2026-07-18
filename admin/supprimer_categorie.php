<?php
// supprimer_categorie.php
require_once 'bdd/db.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id_categorie = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php?status=deleted');
        exit();
    } catch (PDOException $e) {
        die("Erreur SQL lors de la suppression de la catégorie : " . $e->getMessage());
    }
}
header('Location: admin.php');
exit();