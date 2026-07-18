<?php
// generer_rapport_logistique.php
session_start();
require_once 'bdd/db.php'; 
require_once '../libs/fpdf19/fpdf.php';

class ONAPAC_Logistique_PDF extends FPDF {
    private $statut_filtre;

    public function setFiltre($statut) {
        $this->statut_filtre = $statut;
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
        $this->Cell(0, 8, utf8_decode("Rapport de Suivi des Flux Logistiques et Livraisons"), 0, 1, 'C');
        
        // Sous-titre filtre
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(113, 128, 150);
        $this->Cell(0, 6, utf8_decode("Statut ciblé : " . ucfirst($this->statut_filtre)), 0, 1, 'C');
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(113, 128, 150);
        
        $this->SetDrawColor(226, 232, 240);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY() - 2, 200, $this->GetY() - 2);
        
        $this->Cell(100, 10, utf8_decode('Portail ONAPAC - Département Exportations & Logistique'), 0, 0, 'L');
        $this->Cell(90, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'R');
    }
}

// 1. Gestion des filtres
$statut_livraison = $_GET['statut_livraison'] ?? 'tous';
$tri = $_GET['tri_logistique'] ?? 'date_desc';

$where_clause = "WHERE l.id_livraison IS NOT NULL"; // On s'assure d'avoir une livraison associée
$params = [];
$statut_texte = "Tous les flux logistiques";

if ($statut_livraison !== 'tous') {
    $where_clause .= " AND LOWER(l.statut_livraison) = :statut";
    $params[':statut'] = strtolower($statut_livraison);
    $statut_texte = $statut_livraison;
}

$order_by = "c.date_commande DESC";
if ($tri === 'destination') {
    $order_by = "l.adresse_livraison ASC";
}

// 2. Requête SQL d'extraction alignée sur ONAPAC_DB
try {
    $query = "SELECT l.*, c.reference_commande, c.date_commande, u.nom_entreprise, u.nom, u.prenom 
              FROM livraisons l
              INNER JOIN commandes c ON l.id_commande = c.id_commande
              INNER JOIN utilisateurs u ON c.id_acheteur = u.id_utilisateur
              $where_clause
              ORDER BY $order_by";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $livraisons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

// 3. Configuration PDF
$pdf = new ONAPAC_Logistique_PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->setFiltre($statut_texte);
$pdf->AddPage();

// En-tête de tableau
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(2, 136, 209); // Bleu Logistique (#0288d1)
$pdf->SetTextColor(255, 255, 255);

$col_ref = 30;
$col_entreprise = 45;
$col_adresse = 55;
$col_suivi = 30;
$col_statut = 30;

$pdf->Cell($col_ref, 8, utf8_decode('Réf. Commande'), 1, 0, 'C', true);
$pdf->Cell($col_entreprise, 8, 'Opérateur', 1, 0, 'L', true);
$pdf->Cell($col_adresse, 8, 'Destination / Adresse', 1, 0, 'L', true);
$pdf->Cell($col_suivi, 8, 'N° Suivi', 1, 0, 'C', true);
$pdf->Cell($col_statut, 8, 'Statut', 1, 1, 'C', true);

// Données
$pdf->SetFont('Arial', '', 8.5);
$pdf->SetTextColor(45, 55, 72);
$fill = false;
$totaux = ['en préparation' => 0, 'en transit' => 0, 'livrée' => 0];

foreach ($livraisons as $row) {
    $pdf->SetFillColor(247, 250, 252);
    
    $ref = $row['reference_commande'];
    $entreprise = $row['nom_entreprise'] ?: trim($row['prenom'] . ' ' . $row['nom']);
    $adresse = $row['adresse_livraison'] ?: 'Non spécifiée';
    $suivi = $row['numero_suivi'] ?: 'Non assigné';
    $statut = $row['statut_livraison'] ?: 'En préparation';
    
    // Comptabilisation pour le bilan final
    $statut_key = strtolower($statut);
    if (array_key_exists($statut_key, $totaux)) {
        $totaux[$statut_key]++;
    } else {
        $totaux['en préparation']++; // Par défaut
    }
    
    $pdf->Cell($col_ref, 8, $ref, 'B', 0, 'C', $fill);
    $pdf->Cell($col_entreprise, 8, utf8_decode(strlen($entreprise) > 22 ? substr($entreprise, 0, 20) . '..' : $entreprise), 'B', 0, 'L', $fill);
    $pdf->Cell($col_adresse, 8, utf8_decode(strlen($adresse) > 28 ? substr($adresse, 0, 26) . '..' : $adresse), 'B', 0, 'L', $fill);
    $pdf->Cell($col_suivi, 8, $suivi, 'B', 0, 'C', $fill);
    $pdf->Cell($col_statut, 8, utf8_decode(ucfirst($statut)), 'B', 1, 'C', $fill);
    
    $fill = !$fill;
}

// Bilan global logistique
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(45, 55, 72);
$pdf->Cell(0, 6, utf8_decode("Synthèse des flux d'expédition :"), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);

$pdf->Cell(60, 6, utf8_decode("- Colis en préparation : " . $totaux['en préparation']), 0, 0, 'L');
$pdf->Cell(60, 6, utf8_decode("- Colis en transit : " . $totaux['en transit']), 0, 0, 'L');
$pdf->Cell(60, 6, utf8_decode("- Livraisons finalisées : " . $totaux['livrée']), 0, 1, 'L');

// Envoi
$pdf->Output('I', 'ONAPAC_Suivi_Logistique_' . date('Y-m-d') . '.pdf');