<?php
// inc/header.php
// Sécurité : On démarre la session uniquement si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Styles de base pour un header ONAPAC moderne et propre */
        .main-header {
            background-color: #1e5631;
            color: #ffffff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-logo a {
            color: #fff;
            text-decoration: none;
            font-size: 1.4rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header-logo span {
            color: #ffd700;
            /* Touche dorée pour la certification */
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
        }
        .header-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-link {
            color: #eaf2ed;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .nav-link:hover {
            color: #ffd700;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .btn-logout {
            color: #ff8a80;
            text-decoration: none;
            font-weight: bold;
            margin-left: 5px;
        }
        .btn-logout:hover {
            color: #ff5252;
        }
        .badge-cart {
            background: #ffd700;
            color: #1e5631;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: bold;
            vertical-align: top;
            margin-left: 2px;
        }

        /* BOUTON BURGER (Masqué sur PC) */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: #ffffff;
            font-size: 1.5rem;
            cursor: pointer;
            outline: none;
            padding: 5px;
            transition: transform 0.2s ease;
        }

        /* --- STYLES RESPONSIVES (MOBILE & TABLETTE) --- */
        @media (max-width: 992px) {
            .main-header {
                padding: 15px 20px;
                position: relative; /* Pour caler le menu déroulant absolu */
            }

            .menu-toggle {
                display: block; /* On affiche le burger */
            }

            .header-nav {
                position: absolute;
                top: 100%; /* Se déroule juste en dessous du header */
                left: 0;
                right: 0;
                background-color: #1e5631;
                flex-direction: column;
                gap: 0;
                padding: 0;
                max-height: 0; /* Masqué par défaut */
                overflow: hidden;
                transition: max-height 0.3s ease-in-out;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }

            /* Classe active gérée par le JavaScript */
            .header-nav.active {
                max-height: 500px; /* Hauteur suffisante pour contenir le menu */
            }

            /* Liens de navigation en mobile */
            .nav-link {
                width: 100%;
                text-align: left;
                padding: 15px 25px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                box-sizing: border-box;
            }

            .nav-link:hover {
                background-color: rgba(255, 255, 255, 0.05);
            }

            /* Menu utilisateur connecté en mobile */
            .user-menu {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
                background: rgba(0, 0, 0, 0.15);
                border-radius: 0;
                padding: 15px 25px;
                gap: 12px;
                box-sizing: border-box;
            }

            .user-menu > span {
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                width: 100%;
                padding-bottom: 8px;
            }

            /* Nettoyage des barres de séparation "|" inutiles en vertical */
            .user-menu-separator {
                display: none;
            }
        }
    </style>
</head>
<body>

<header class="main-header">
    <div class="header-logo">
        <a href="index.php">
            <i class="fa-solid fa-leaf"></i>
            <div>
                ONAPAC
                <span>Portail Acheteurs</span>
            </div>
        </a>
    </div>

    <button class="menu-toggle" id="menu-burger-btn" aria-label="Ouvrir le menu">
        <i class="fa-solid fa-bars"></i>
    </button>

    <nav class="header-nav" id="main-navigation">
        <a href="index.php" class="nav-link"><i class="fa-solid fa-house"></i> Accueil</a>
        <a href="produits.php" class="nav-link"><i class="fa-solid fa-wheat-stalk"></i> Catalogue</a>
        <a href="contact.php" class="nav-link"><i class="fa-solid fa-paper-plane"></i> Contact</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-menu">
                <span>
                    <i class="fa-solid fa-building"></i> 
                    <strong><?php echo htmlspecialchars($_SESSION['user_entreprise'] ?? $_SESSION['user_nom']); ?></strong>
                </span>
                <span class="user-menu-separator">|</span>
                <a href="dashboard_acheteur.php" class="nav-link" style="font-weight: 600; padding: 0;"><i class="fa-solid fa-chart-pie"></i> Mon Espace</a>
                <span class="user-menu-separator">|</span>
                <a href="panier.php" class="nav-link" style="padding: 0;">
                    <i class="fa-solid fa-basket-shopping"></i> Panier
                    <?php if (isset($_SESSION['panier']) && count($_SESSION['panier']) > 0): ?>
                        <span class="badge-cart"><?php echo count($_SESSION['panier']); ?></span>
                    <?php endif; ?>
                </a>
                <span class="user-menu-separator">|</span>
                <a href="deconnexion.php" class="btn-logout" title="Se déconnecter"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
            </div>
        <?php else: ?>
            <a href="connexion.php" class="nav-link" style="background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: 4px; margin: 10px 25px;"><i class="fa-solid fa-user-lock"></i> Connexion / Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const burgerBtn = document.getElementById('menu-burger-btn');
    const navMenu = document.getElementById('main-navigation');

    if (burgerBtn && navMenu) {
        burgerBtn.addEventListener('click', function() {
            // Alterne l'affichage du menu
            navMenu.classList.toggle('active');
            
            // Change l'icône de burger en croix (fa-bars -> fa-xmark)
            const icon = burgerBtn.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.className = 'fa-solid fa-xmark';
            } else {
                icon.className = 'fa-solid fa-bars';
            }
        });
    }
});
</script>

<div class="site-container" style="min-height: calc(100vh - 80px); padding-bottom: 40px;">