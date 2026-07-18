<?php
// ajouter_categorie.php
require_once 'bdd/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_categorie'])) {
    $nom = htmlspecialchars(trim($_POST['nom_categorie']));

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (nom_categorie) VALUES (:nom)");
        $stmt->execute([':nom' => $nom]);
        header('Location: admin.php?status=success');
        exit();
    } catch (PDOException $e) {
        die("Erreur lors de l'ajout de la catégorie : " . $e->getMessage());
    }
}
header('Location: admin.php');
exit();