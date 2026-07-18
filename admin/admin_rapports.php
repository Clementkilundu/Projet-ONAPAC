<?php
// admin_rapports.php
session_start();
require_once 'bdd/db.php';

// Sécurité : Vérifie ici si l'utilisateur est bien un administrateur
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     header('Location: connexion.php');
//     exit();
// }

// 'inc/header.php';
?>

<style>
    .rapports-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
        font-family: 'Segoe UI', sans-serif;
    }
    .rapports-title {
        color: #1e5631;
        border-bottom: 3px solid #ffd700;
        padding-bottom: 10px;
        margin-bottom: 30px;
    }
    .grid-rapports {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
    }
    .card-rapport {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .card-rapport h3 {
        color: #1e5631;
        margin-top: 0;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-group-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .form-group label {
        font-weight: 600;
        color: #2d3748;
        font-size: 0.9rem;
    }
    .form-group input, .form-group select {
        padding: 10px 12px;
        border: 1px solid #cbd5e0;
        border-radius: 5px;
        outline: none;
        font-size: 0.95rem;
    }
    .form-group input:focus, .form-group select:focus {
        border-color: #1e5631;
    }
    .btn-generate {
        background-color: #1e5631;
        color: #ffffff;
        border: none;
        padding: 12px 25px;
        font-size: 1rem;
        font-weight: bold;
        border-radius: 5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: background 0.2s;
    }
    .btn-generate:hover {
        background-color: #174425;
    }
</style>

<div class="rapports-container">
    <h2 class="rapports-title"><i class="fa-solid fa-file-pdf"></i> Centre de Rapports - ONAPAC</h2>
    
    <div class="grid-rapports">
        <!-- Bloc : Rapport des Commandes -->
        <div class="card-rapport">
            <h3><i class="fa-solid fa-cart-shopping"></i> Rapport des Commandes d'Exportation</h3>
            <p style="color: #718096; margin-bottom: 25px; font-size: 0.95rem;">
                Générez un bilan au format PDF des commandes passées sur la plateforme. Le rapport contient l'historique complet, les volumes commandés et les statistiques financières.
            </p>
            
            <form action="generer_rapport_commandes.php" method="GET" target="_blank">
                <div class="form-group-row">
                    <!-- Filtre de Période -->
                    <div class="form-group">
                        <label for="periode">Période pré-définie</label>
                        <select id="periode" name="periode" onchange="toggleDateInputs(this.value)">
                            <option value="tous">Toutes les commandes</option>
                            <option value="mois_en_cours">Mois en cours</option>
                            <option value="mois_dernier">Mois dernier</option>
                            <option value="personnalise">Dates personnalisées...</option>
                        </select>
                    </div>

                    <!-- Date de début (masquée par défaut) -->
                    <div class="form-group date-input" id="group-date-debut" style="display: none;">
                        <label for="date_debut">Date de début</label>
                        <input type="date" id="date_debut" name="date_debut">
                    </div>

                    <!-- Date de fin (masquée par défaut) -->
                    <div class="form-group date-input" id="group-date-fin" style="display: none;">
                        <label for="date_fin">Date de fin</label>
                        <input type="date" id="date_fin" name="date_fin">
                    </div>
                </div>

                <button type="submit" class="btn-generate">
                    <i class="fa-solid fa-file-export"></i> Générer le PDF
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Script simple pour afficher/masquer dynamiquement les sélecteurs de dates
function toggleDateInputs(value) {
    const startGroup = document.getElementById('group-date-debut');
    const endGroup = document.getElementById('group-date-fin');
    
    if (value === 'personnalise') {
        startGroup.style.display = 'flex';
        endGroup.style.display = 'flex';
        document.getElementById('date_debut').required = true;
        document.getElementById('date_fin').required = true;
    } else {
        startGroup.style.display = 'none';
        endGroup.style.display = 'none';
        document.getElementById('date_debut').required = false;
        document.getElementById('date_fin').required = false;
    }
}
</script>

<?php require_once 'inc/footer.php'; ?>