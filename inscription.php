<?php
// inscription.php
require_once 'bdd/db.php';
//require_once 'inc/header.php'; // On inclut notre header commun et dynamique !

$message_success = "";
$message_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupération et nettoyage des données de la BDD
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $email = htmlspecialchars(trim($_POST['email']));
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $nom_entreprise = htmlspecialchars(trim($_POST['nom_entreprise']));
    $rccm = htmlspecialchars(trim($_POST['rccm']));
    $password = $_POST['mot_de_passe'];
    $password_confirm = $_POST['mot_de_passe_confirme'];

    // 2. Validations de base
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $message_error = "Veuillez remplir tous les champs obligatoires (Nom, Prénom, Email, Mot de passe).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_error = "L'adresse email saisie n'est pas valide.";
    } elseif ($password !== $password_confirm) {
        $message_error = "Les deux mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $message_error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            // 3. Vérification si l'email existe déjà
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = :email");
            $stmtCheck->execute([':email' => $email]);
            if ($stmtCheck->fetchColumn() > 0) {
                $message_error = "Cette adresse email est déjà associée à un compte.";
            } else {
                // 4. Hachage sécurisé du mot de passe
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // 5. Insertion en base de données avec id_role = 3 (Acheteur)
                $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, nom_entreprise, rccm, id_role) 
                        VALUES (:nom, :prenom, :email, :mdp, :tel, :entreprise, :rccm, 3)";
                
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':email' => $email,
                    ':mdp' => $password_hash,
                    ':tel' => !empty($telephone) ? $telephone : null,
                    ':entreprise' => !empty($nom_entreprise) ? $nom_entreprise : null,
                    ':rccm' => !empty($rccm) ? $rccm : null
                ]);

                $message_success = "Votre compte acheteur a été créé avec succès ! Vous pouvez maintenant vous connecter.";
            }
        } catch (PDOException $e) {
            $message_error = "Une erreur technique est survenue : " . $e->getMessage();
        }
    }
}
?>

<style>
    /* Styles spécifiques pour le formulaire d'inscription */
    .register-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
        background: #f4f6f5;
    }
    .register-card {
        background: #ffffff;
        width: 100%;
        max-width: 750px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border-top: 5px solid #1e5631;
    }
    .register-header {
        background: linear-gradient(135deg, #1e5631 0%, #2e7d32 100%);
        color: #fff;
        padding: 30px;
        text-align: center;
    }
    .register-header h2 {
        margin: 0;
        font-size: 1.8rem;
    }
    .register-header p {
        margin: 10px 0 0 0;
        opacity: 0.9;
        font-size: 0.95rem;
    }
    .register-body {
        padding: 40px;
    }
    .form-section-title {
        font-size: 1.1rem;
        color: #1e5631;
        border-bottom: 2px solid #eaf2ed;
        padding-bottom: 8px;
        margin-bottom: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 25px;
    }
    @media (max-width: 600px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #444;
    }
    .form-group label span {
        color: #d32f2f;
    }
    .form-control {
        padding: 10px 14px;
        border: 1.5px solid #cccccc;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }
    .form-control:focus {
        border-color: #1e5631;
        outline: none;
    }
    .alert {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 25px;
        font-weight: 600;
        font-size: 0.95rem;
    }
    .alert-danger {
        background: #fbe9e7;
        color: #c62828;
        border: 1px solid #ffccbc;
    }
    .alert-success {
        background: #eaf2ed;
        color: #1e5631;
        border: 1px solid #c8e6c9;
    }
    .btn-register {
        background: #1e5631;
        color: white;
        border: none;
        padding: 14px;
        border-radius: 6px;
        font-size: 1.05rem;
        font-weight: bold;
        cursor: pointer;
        width: 100%;
        transition: background 0.2s;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
    .btn-register:hover {
        background: #153e22;
    }
    .login-redirect {
        text-align: center;
        margin-top: 25px;
        font-size: 0.95rem;
        color: #666;
    }
    .login-redirect a {
        color: #1e5631;
        text-decoration: none;
        font-weight: bold;
    }
    .login-redirect a:hover {
        text-decoration: underline;
    }
</style>

<div class="register-wrapper">
    <div class="register-card">
        
        <div class="register-header">
            <h2>Créer un Espace Acheteur</h2>
            <p>Devenez partenaire certifié de l'ONAPAC pour négocier et exporter nos produits agricoles</p>
        </div>

        <div class="register-body">
            
            <?php if (!empty($message_error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $message_error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($message_success)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $message_success; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                
                <div class="form-section-title">
                    <i class="fa-solid fa-user-tie"></i> Informations du Représentant
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom">Nom <span>*</span></label>
                        <input type="text" id="nom" name="nom" class="form-control" placeholder="Ex: Smith" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom <span>*</span></label>
                        <input type="text" id="prenom" name="prenom" class="form-control" placeholder="Ex: John" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="email">Adresse Email Professionnelle <span>*</span></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Ex: j.smith@entreprise.com" required>
                    </div>
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" placeholder="Ex: +243820000000">
                    </div>
                </div>

                <div class="form-section-title">
                    <i class="fa-solid fa-building"></i> Informations de la Société (Acheteur / Exportateur)
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom_entreprise">Nom de l'entreprise / Coopérative</label>
                        <input type="text" id="nom_entreprise" name="nom_entreprise" class="form-control" placeholder="Ex: Kivu Coffee Export SRL">
                    </div>
                    <div class="form-group">
                        <label for="rccm">Numéro RCCM</label>
                        <input type="text" id="rccm" name="rccm" class="form-control" placeholder="Ex: CD/BKV/RCCM/26-B-0450">
                    </div>
                </div>

                <div class="form-section-title">
                    <i class="fa-solid fa-lock"></i> Sécurité du compte
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe <span>*</span></label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="mot_de_passe_confirme">Confirmer le mot de passe <span>*</span></label>
                        <input type="password" id="mot_de_passe_confirme" name="mot_de_passe_confirme" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    <i class="fa-solid fa-user-plus"></i> Créer mon compte
                </button>

            </form>

            <div class="login-redirect">
                Déjà inscrit ? <a href="connexion.php">Connectez-vous ici</a>
            </div>

        </div>
    </div>
</div>

<?php 
// Nous fermons la div "site-container" ouverte dans le header.php
echo "</div>"; 
?>
</body>
</html>