<?php
// generer_rapport_commandes.php
session_start();
require_once 'bdd/db.php'; // On utilise le même chemin de connexion
require_once '../libs/fpdf19/fpdf.php'; // Assure-toi que ton dossier fpdf est bien accessible

// Classe personnalisée FPDF
class ONAPAC_Rapport_PDF extends FPDF {
    private $titre_rapport;
    private $periode_texte;

    public function setRapportDetails($titre, $periode) {
        $this->titre_rapport = $titre;
        $this->periode_texte = $periode;
    }

    // En-tête de page institutionnel
    function Header() {
        if (file_exists('img/logo-onapac.png')) {
            $this->Image('img/logo-onapac.png', 10, 10, 24);
            $this->SetX(36);
        } else {
            $this->SetX(10);
        }
        
        // Cartouche officielle
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(30, 86, 49); // Vert ONAPAC (#1E5631)
        $this->Cell(0, 5, 'OFFICE NATIONAL DES PRODUITS AGRICOLES DU CONGO', 0, 1, 'L');
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(113, 128, 150);
        $this->SetX($this->GetX() + (file_exists('img/logo-onapac.png') ? 26 : 0));
        $this->Cell(0, 4, 'Direction Provinciale du Sud-Kivu / Bukavu', 0, 1, 'L');
        
        $this->Ln(8);
        
        // Ligne de séparation verte
        $this->SetDrawColor(30, 86, 49);
        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(6);
        
        // Titre du Rapport
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(45, 55, 72);
        $this->Cell(0, 8, utf8_decode($this->titre_rapport), 0, 1, 'C');
        
        // Période
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(113, 128, 150);
        $this->Cell(0, 6, utf8_decode($this->periode_texte), 0, 1, 'C');
        $this->Ln(6);
    }

    // Pied de page
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(113, 128, 150);
        
        // Ligne de pied de page
        $this->SetDrawColor(226, 232, 240);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY() - 2, 200, $this->GetY() - 2);
        
        // Texte institutionnel
        $this->Cell(100, 10, utf8_decode('Portail ONAPAC - Document officiel de suivi interne'), 0, 0, 'L');
        $this->Cell(90, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'R');
    }
}

// 1. Récupération et filtrage de la période choisie
$periode = $_GET['periode'] ?? 'tous';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

$where_clause = "WHERE 1=1";
$params = [];
$periode_texte = "Toutes les commandes enregistrées sur la plateforme";

if ($periode === 'mois_en_cours') {
    $where_clause .= " AND MONTH(c.date_commande) = MONTH(CURRENT_DATE()) AND YEAR(c.date_commande) = YEAR(CURRENT_DATE())";
    $periode_texte = "Période : " . date('F Y');
} elseif ($periode === 'mois_dernier') {
    $where_clause .= " AND MONTH(c.date_commande) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(c.date_commande) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
    $periode_texte = "Période : Mois précédent";
} elseif ($periode === 'personnalise' && !empty($date_debut) && !empty($date_fin)) {
    $where_clause .= " AND c.date_commande BETWEEN :date_debut AND :date_fin";
    $params[':date_debut'] = $date_debut . " 00:00:00";
    $params[':date_fin'] = $date_fin . " 23:59:59";
    $periode_texte = "Période du : " . date('d/m/Y', strtotime($date_debut)) . " au " . date('d/m/Y', strtotime($date_fin));
}

// 2. Requête SQL calquée exactement sur ta base "onapac_db" (utilisation d'id_acheteur et de table paiements)
try {
    $query = "SELECT c.*, u.nom, u.prenom, u.nom_entreprise, p.statut_paiement 
              FROM commandes c 
              INNER JOIN utilisateurs u ON c.id_acheteur = u.id_utilisateur
              LEFT JOIN paiements p ON c.id_commande = p.id_commande
              $where_clause 
              ORDER BY c.date_commande DESC";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

// 3. Initialisation et configuration du PDF
$pdf = new ONAPAC_Rapport_PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->setRapportDetails("Bilan Synthetique des Commandes", $periode_texte);
$pdf->AddPage();

// En-tête du tableau
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(30, 86, 49); // Vert ONAPAC
$pdf->SetTextColor(255, 255, 255); // Texte Blanc

// Tailles des colonnes
$col_ref = 35;
$col_date = 30;
$col_acheteur = 65;
$col_paiement = 30;
$col_montant = 30;

$pdf->Cell($col_ref, 8, utf8_decode('Référence'), 1, 0, 'C', true);
$pdf->Cell($col_date, 8, 'Date de Commande', 1, 0, 'C', true);
$pdf->Cell($col_acheteur, 8, utf8_decode('Exportateur / Entreprise'), 1, 0, 'L', true);
$pdf->Cell($col_paiement, 8, 'Paiement', 1, 0, 'C', true);
$pdf->Cell($col_montant, 8, 'Montant (USD)', 1, 1, 'R', true);

// Données du tableau
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(45, 55, 72);
$total_cumule = 0;
$nb_commandes = 0;
$fill = false;

foreach ($commandes as $cmd) {
    $pdf->SetFillColor(247, 250, 252); // Effet zèbre
    
    $ref = $cmd['reference_commande'];
    $date_formatted = date('d/m/Y H:i', strtotime($cmd['date_commande']));
    
    // Déterminer la structure / l'acheteur
    $nom_structure = $cmd['nom_entreprise'] ?: trim($cmd['prenom'] . ' ' . $cmd['nom']);
    
    // Statut du paiement
    $statut_pay = $cmd['statut_paiement'] ?: 'Non Initié';
    
    $montant = floatval($cmd['montant_total_usd']);
    
    $pdf->Cell($col_ref, 8, $ref, 'B', 0, 'C', $fill);
    $pdf->Cell($col_date, 8, $date_formatted, 'B', 0, 'C', $fill);
    $pdf->Cell($col_acheteur, 8, utf8_decode($nom_structure), 'B', 0, 'L', $fill);
    $pdf->Cell($col_paiement, 8, utf8_decode($statut_pay), 'B', 0, 'C', $fill);
    $pdf->Cell($col_montant, 8, number_format($montant, 2, '.', ' ') . ' $', 'B', 1, 'R', $fill);
    
    $total_cumule += $montant;
    $nb_commandes++;
    $fill = !$fill;
}

// Bilan global au bas du tableau
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(45, 55, 72);
$pdf->Cell(100, 8, utf8_decode("Volume total d'activité : " . $nb_commandes . " commande(s)"), 0, 0, 'L');
$pdf->SetTextColor(30, 86, 49);
$pdf->Cell(90, 8, "Total Facture : " . number_format($total_cumule, 2, '.', ' ') . " USD", 0, 1, 'R');

// Envoi au navigateur
$pdf->Output('I', 'ONAPAC_Rapport_Commandes_' . date('Y-m-d') . '.pdf');