<?php
// generer_rapport_client.php
session_start();
require_once 'bdd/db.php'; 
require_once '../libs/fpdf19/fpdf.php';

class ONAPAC_Client_PDF extends FPDF {
    private $client_nom;

    public function setClientNom($nom) {
        $this->client_nom = $nom;
    }

    // En-tête institutionnel
    function Header() {
        if (file_exists('img/logo-onapac.png')) {
            $this->Image('img/logo-onapac.png', 10, 10, 24);
            $this->SetX(36);
        } else {
            $this->SetX(10);
        }
        
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(30, 86, 49); // Vert ONAPAC
        $this->Cell(0, 5, 'OFFICE NATIONAL DES PRODUITS AGRICOLES DU CONGO', 0, 1, 'L');
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(113, 128, 150);
        $this->SetX($this->GetX() + (file_exists('img/logo-onapac.png') ? 26 : 0));
        $this->Cell(0, 4, 'Direction Provinciale du Sud-Kivu / Bukavu', 0, 1, 'L');
        
        $this->Ln(8);
        $this->SetDrawColor(30, 86, 49);
        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(6);
        
        // Titre du Rapport
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(45, 55, 72);
        $this->Cell(0, 8, utf8_decode("Fiche d'Activité Partenaire"), 0, 1, 'C');
        
        // Nom du client ciblé
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(156, 39, 176); // Violet pour le profil
        $this->Cell(0, 6, utf8_decode($this->client_nom), 0, 1, 'C');
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(113, 128, 150);
        
        $this->SetDrawColor(226, 232, 240);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY() - 2, 200, $this->GetY() - 2);
        
        $this->Cell(100, 10, utf8_decode('Portail ONAPAC - Fiche de suivi Clientèle'), 0, 0, 'L');
        $this->Cell(90, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'R');
    }
}

// 1. Récupération de l'acheteur
$id_acheteur = $_GET['id_acheteur'] ?? null;

if (!$id_acheteur) {
    die("Erreur : Aucun opérateur économique sélectionné.");
}

try {
    // Récupérer les informations de l'acheteur
    $stmtUser = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = :id");
    $stmtUser->execute([':id' => $id_acheteur]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("Erreur : Opérateur introuvable.");
    }
    
    $nom_complet = !empty($user['nom_entreprise']) ? $user['nom_entreprise'] : $user['prenom'] . " " . $user['nom'];

    // Récupérer toutes les commandes de cet acheteur
    $stmtCmds = $pdo->prepare("SELECT c.*, p.statut_paiement 
                               FROM commandes c
                               LEFT JOIN paiements p ON c.id_commande = p.id_commande
                               WHERE c.id_acheteur = :id 
                               ORDER BY c.date_commande DESC");
    $stmtCmds->execute([':id' => $id_acheteur]);
    $commandes = $stmtCmds->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

// 2. Configuration PDF
$pdf = new ONAPAC_Client_PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->setClientNom($nom_complet);
$pdf->AddPage();

// Section : Coordonnées de l'opérateur
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(45, 55, 72);
$pdf->Cell(0, 6, utf8_decode("1. Informations Générales de l'Établissement"), 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 6, utf8_decode("Nom du Responsable : "), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, utf8_decode($user['prenom'] . " " . $user['nom']), 0, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 6, "Adresse Email : ", 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, $user['email'], 0, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 6, utf8_decode("Téléphone : "), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, $user['telephone'] ?: 'Non communiqué', 0, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 6, "N° d'enregistrement RCCM : ", 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, $user['rccm'] ?: 'Non spécifié (Particulier/Interne)', 0, 1, 'L');

$pdf->Ln(8);

// Section : Historique des transactions
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, utf8_decode("2. Historique complet des Commandes d'Exportation"), 0, 1, 'L');
$pdf->Ln(2);

// En-tête tableau
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(156, 39, 176); // Violet
$pdf->SetTextColor(255, 255, 255);

$col_ref = 40;
$col_date = 40;
$col_statut = 50;
$col_montant = 60;

$pdf->Cell($col_ref, 8, utf8_decode('Référence unique'), 1, 0, 'C', true);
$pdf->Cell($col_date, 8, 'Date', 1, 0, 'C', true);
$pdf->Cell($col_statut, 8, 'Statut de Paiement', 1, 0, 'C', true);
$pdf->Cell($col_montant, 8, 'Total Facturé (USD)', 1, 1, 'R', true);

// Données du tableau
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(45, 55, 72);
$total_depense = 0;
$nb_transactions = 0;
$fill = false;

if (empty($commandes)) {
    $pdf->Cell(190, 10, utf8_decode("Aucune transaction enregistrée pour le moment."), 1, 1, 'C');
} else {
    foreach ($commandes as $cmd) {
        $pdf->SetFillColor(247, 250, 252);
        
        $ref = $cmd['reference_commande'];
        $date_formatted = date('d/m/Y H:i', strtotime($cmd['date_commande']));
        $statut_p = $cmd['statut_paiement'] ?: 'En attente';
        $montant = floatval($cmd['montant_total_usd']);
        
        $pdf->Cell($col_ref, 8, $ref, 'B', 0, 'C', $fill);
        $pdf->Cell($col_date, 8, $date_formatted, 'B', 0, 'C', $fill);
        $pdf->Cell($col_statut, 8, utf8_decode(ucfirst($statut_p)), 'B', 0, 'C', $fill);
        $pdf->Cell($col_montant, 8, number_format($montant, 2, '.', ' ') . ' $', 'B', 1, 'R', $fill);
        
        $total_depense += $montant;
        $nb_transactions++;
        $fill = !$fill;
    }
}

// Section Bilan Final du Partenaire
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(45, 55, 72);
$pdf->Cell(0, 6, utf8_decode("3. Synthèse d'activité commerciale"), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);

$pdf->Cell(95, 6, utf8_decode("- Volume d'achat total : " . $nb_transactions . " commande(s)"), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(156, 39, 176);
$pdf->Cell(95, 6, "Volume d'Affaires Cumulé : " . number_format($total_depense, 2, '.', ' ') . " USD", 0, 1, 'R');

// Calcul du panier moyen si applicable
if ($nb_transactions > 0) {
    $panier_moyen = $total_depense / $nb_transactions;
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(113, 128, 150);
    $pdf->Cell(190, 6, "Panier d'achat moyen constaté : " . number_format($panier_moyen, 2, '.', ' ') . " USD / commande", 0, 1, 'R');
}

// Envoi
$pdf->Output('I', 'ONAPAC_Fiche_Client_' . str_replace(' ', '_', $nom_complet) . '.pdf');