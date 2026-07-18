<?php
// connexion_admin.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si l'utilisateur est déjà connecté en tant qu'admin ou agent, on le redirige directement vers l'administration
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && 
    (strtolower($_SESSION['role']) === 'Administrateur' || strtolower($_SESSION['role']) === 'Agent_ONAPAC')) {
    header('Location: admin.php');
    exit();
}

require_once 'bdd/db.php'; // Ajuste le chemin vers ton fichier de connexion à la base de données

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['mot_de_passe']);

    if (!empty($email) && !empty($password)) {
        try {
            // On récupère l'utilisateur et son rôle associé
            $stmt = $pdo->prepare("
                SELECT u.*, r.nom_role 
                FROM utilisateurs u
                INNER JOIN roles r ON u.id_role = r.id_role
                WHERE u.email = :email
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // COMPARAISON EN TEXTE CLAIR (sans password_verify)
            if ($user && $password === $user['mot_de_passe']) {
                
                // Récupération du rôle en minuscules pour éviter les soucis de casse
                $role = strtolower($user['nom_role']);
                
                // Vérification stricte des rôles : Administrateur ou Agent_ONAPAC
                if ($role === 'administrateur' || $role === 'agent_onapac') {
                    // Stockage des informations nécessaires en session
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['role'] = $user['nom_role']; // Stocke 'Administrateur' ou 'Agent_ONAPAC'
                    $_SESSION['user_entreprise'] = $user['nom_entreprise'] ?? 'ONAPAC';

                    // Redirection vers le back-office
                    header('Location: admin.php');
                    exit();
                } else {
                    $erreur = "Accès refusé. Cette interface est réservée au personnel autorisé de l'ONAPAC.";
                }
            } else {
                $erreur = "Identifiants incorrects ou compte inexistant.";
            }
        } catch (PDOException $e) {
            $erreur = "Une erreur est survenue lors de la connexion : " . $e->getMessage();
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONAPAC - Connexion Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css"> <style>
        body {
            background: #f4f6f8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-admin-container {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            padding: 40px 30px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-top: 5px solid #1e5631; /* Vert ONAPAC */
            box-sizing: border-box;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 3rem;
            color: #1e5631;
            margin-bottom: 15px;
        }

        .login-header h2 {
            margin: 0;
            color: #2d3748;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .login-header p {
            color: #718096;
            margin: 5px 0 0 0;
            font-size: 0.9rem;
        }

        .alert-error {
            background-color: #fff5f5;
            color: #e53e3e;
            border: 1px solid #fed7d7;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            color: #a0aec0;
            font-size: 1rem;
        }

        .input-with-icon input {
            width: 100%;
            padding: 12px 15px 12px 42px;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            outline: none;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .input-with-icon input:focus {
            border-color: #1e5631;
            box-shadow: 0 0 0 3px rgba(30, 86, 49, 0.15);
        }

        .btn-login {
            background-color: #1e5631;
            color: #ffffff;
            border: none;
            width: 100%;
            padding: 14px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s;
            margin-top: 10px;
        }

        .btn-login:hover {
            background-color: #174425;
        }

        .back-to-site {
            text-align: center;
            margin-top: 25px;
        }

        .back-to-site a {
            color: #718096;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
        }

        .back-to-site a:hover {
            color: #1e5631;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-admin-container">
        <div class="login-header">
            <i class="fa-solid fa-user-lock"></i>
            <h2>Espace Administratif</h2>
            <p>Portail d'Administration ONAPAC</p>
        </div>

        <?php if (!empty($erreur)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($erreur); ?></span>
            </div>
        <?php endif; ?>

        <form action="connexion_admin.php" method="POST">
            <div class="form-group">
                <label for="email">Adresse email professionnelle</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="nom@onapac.cd" required autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-key"></i>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fa-solid fa-right-to-bracket"></i> Se connecter
            </button>
        </form>

        <div class="back-to-site">
            <a href="../index.php"><i class="fa-solid fa-arrow-left-long"></i> Retourner sur le site public</a>
        </div>
    </div>

</body>
</html>