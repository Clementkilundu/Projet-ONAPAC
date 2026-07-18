<?php
session_start();
// connexion.php
require_once 'bdd/db.php';
//require_once 'inc/header.php'; // Notre header commun (gère déjà session_start)

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$message_error = "";
$message_success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['mot_de_passe'];

    if (empty($email) || empty($password)) {
        $message_error = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Recherche de l'utilisateur (on s'assure qu'il s'agit d'un acheteur, id_role = 3)
            $stmt = $pdo->prepare("SELECT u.*, r.nom_role 
                                   FROM utilisateurs u
                                   INNER JOIN roles r ON u.id_role = r.id_role
                                   WHERE u.email = :email AND u.id_role = 3");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Vérification du mot de passe (gère les hashs créés lors de l'inscription)
            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Initialisation des variables de session
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_entreprise'] = $user['nom_entreprise'];
                $_SESSION['user_role'] = $user['id_role'];

                $message_success = "Connexion réussie ! Redirection en cours...";
                // Redirection vers le tableau de bord de l'acheteur après 1.5 seconde (géré en JS ou PHP)
                header("refresh:1.5;url=index.php");
            } else {
                $message_error = "Identifiants incorrects ou compte non autorisé.";
            }
        } catch (PDOException $e) {
            $message_error = "Une erreur technique est survenue : " . $e->getMessage();
        }
    }
}
?>

<link rel="stylesheet" href="css/connexion.css">

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <h2>Espace Connexion</h2>
            <p>Accédez à vos commandes, certificats ONAPAC et suivi de livraison</p>
        </div>

        <div class="login-body">
            
            <?php if (!empty($message_error)): ?>
                <div class="alert alert-danger" id="php-alert">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $message_error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($message_success)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $message_success; ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-danger hidden" id="js-alert">
                <i class="fa-solid fa-circle-xmark"></i> <span id="js-alert-text"></span>
            </div>

            <form action="" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Adresse Email Professionnelle</label>
                    <div class="input-container">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Ex: acheteur@kivu.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe</label>
                    <div class="input-container">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" placeholder="Saisissez votre mot de passe" required>
                        <button type="button" id="togglePassword" class="btn-toggle-pwd" title="Afficher/Masquer">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Se connecter
                </button>
            </form>

            <div class="register-redirect">
                Nouveau partenaire ? <a href="inscription.php">Créer un compte acheteur</a>
            </div>
        </div>
    </div>
</div>

<script src="js/connexion.js"></script>

<?php 
// Fermeture de la div globale "site-container" ouverte dans header.php
echo "</div>"; 
?>
</body>
</html>