<?php
// supprimer_message.php
require_once 'bdd/db.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM messages_contact WHERE id_message = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php?status=deleted');
        exit();
    } catch (PDOException $e) {
        die("Erreur SQL lors de la suppression du message : " . $e->getMessage());
    }
}
header('Location: admin.php');
exit();