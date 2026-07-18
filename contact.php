<?php
// contact.php
session_start();
require_once 'bdd/db.php';

// Variables pour pré-remplir si l'utilisateur est connecté
$nom_defaut = '';
$email_defaut = '';

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT nom, prenom, email FROM utilisateurs WHERE id_utilisateur = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $nom_defaut = $user['prenom'] . ' ' . $user['nom'];
            $email_defaut = $user['email'];
        }
    } catch (PDOException $e) {
        // Erreur silencieuse
    }
}

// Traitement du formulaire
$message_succes = '';
$message_erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        $message_erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_erreur = "L'adresse email saisie n'est pas valide.";
    } else {
        try {
            // Insertion avec "nom_expediteur" et "email_expediteur" conformes à ton schéma
            $stmt_insert = $pdo->prepare("INSERT INTO messages_contact (nom_expediteur, email_expediteur, sujet, message) VALUES (:nom, :email, :sujet, :message)");
            $stmt_insert->execute([
                ':nom' => $nom,
                ':email' => $email,
                ':sujet' => $sujet,
                ':message' => $message
            ]);

            $message_succes = "Votre message a été enregistré et envoyé avec succès à la direction de l'ONAPAC ! Notre équipe vous répondra dans les plus brefs délais.";
            
            // Réinitialisation des variables pour vider le formulaire
            $sujet = '';
            $message = '';
            
        } catch (PDOException $e) {
            $message_erreur = "Une erreur technique est survenue lors de l'envoi : " . $e->getMessage();
        }
    }
}

require_once 'inc/header.php';
?>

<link rel="stylesheet" href="css/contact.css">

<div class="contact-hero">
    <div class="hero-overlay">
        <h1>Contactez l'ONAPAC</h1>
        <p>Une question sur la certification de vos lots, un paiement ou une procédure d'exportation ? Nos équipes à Bukavu sont à votre écoute.</p>
    </div>
</div>

<div class="contact-container">
    <div class="contact-grid">
        
        <!-- Formulaire -->
        <div class="contact-form-card">
            <h2>Envoyez-nous un message</h2>
            <p class="form-subtitle">Remplissez ce formulaire et un agent de l'Office vous recontactera sous 24h ouvrées.</p>

            <?php if (!empty($message_succes)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($message_succes); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($message_erreur)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($message_erreur); ?>
                </div>
            <?php endif; ?>

            <form action="contact.php" method="POST" class="contact-form">
                <div class="form-group-row">
                    <div class="form-group">
                        <label for="nom">Nom complet <span class="required">*</span></label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? $nom_defaut); ?>" required placeholder="Ex: Jean Mukendi">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Adresse Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $email_defaut); ?>" required placeholder="Ex: jean.mukendi@example.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="sujet">Sujet du message <span class="required">*</span></label>
                    <select id="sujet" name="sujet" required>
                        <option value="" disabled selected>Choisissez le motif de votre contact...</option>
                        <option value="Certification" <?php echo (isset($_POST['sujet']) && $_POST['sujet'] === 'Certification') ? 'selected' : ''; ?>>Analyse & Certification phytosanitaire</option>
                        <option value="Facturation" <?php echo (isset($_POST['sujet']) && $_POST['sujet'] === 'Facturation') ? 'selected' : ''; ?>>Problème de paiement ou Facture</option>
                        <option value="Technique" <?php echo (isset($_POST['sujet']) && $_POST['sujet'] === 'Technique') ? 'selected' : ''; ?>>Assistance technique sur la plateforme</option>
                        <option value="Partenariat" <?php echo (isset($_POST['sujet']) && $_POST['sujet'] === 'Partenariat') ? 'selected' : ''; ?>>Demande d'agrément exportateur</option>
                        <option value="Autre" <?php echo (isset($_POST['sujet']) && $_POST['sujet'] === 'Autre') ? 'selected' : ''; ?>>Autre demande</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">Votre message <span class="required">*</span></label>
                    <textarea id="message" name="message" rows="6" required placeholder="Décrivez en détail votre demande ou spécifiez la référence de votre commande..."><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit-contact">
                    <i class="fa-solid fa-paper-plane"></i> Envoyer le message
                </button>
            </form>
        </div>

        <!-- Coordonnées physiques -->
        <div class="contact-info-column">
            <div class="info-card">
                <h3>Nos Bureaux</h3>
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="info-text">
                        <h4>Adresse Physique</h4>
                        <p>Avenue de la Résistance, Commune d'Ibanda<br>Bukavu, Sud-Kivu, République Démocratique du Congo</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="info-text">
                        <h4>Téléphone</h4>
                        <p>+243 (0) 81 234 56 78<br>+243 (0) 99 876 54 32</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="info-text">
                        <h4>E-mail Officiel</h4>
                        <p>contact@onapac-kivu.cd<br>direction.provinciale@onapac-kivu.cd</p>
                    </div>
                </div>
            </div>

            <div class="info-card hours-card">
                <h3><i class="fa-solid fa-clock"></i> Heures de Réception</h3>
                <table class="hours-table">
                    <tr>
                        <td>Lundi - Vendredi :</td>
                        <td class="hours-val">08h00 - 16h30</td>
                    </tr>
                    <tr>
                        <td>Samedi :</td>
                        <td class="hours-val">08h00 - 12h00</td>
                    </tr>
                    <tr>
                        <td>Dimanche :</td>
                        <td class="hours-val text-muted-status">Fermé</td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
</div>