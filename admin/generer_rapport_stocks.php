<?php
// generer_rapport_stocks.php
session_start();
require_once 'bdd/db.php'; 
require_once '../libs/fpdf19/fpdf.php';

// Classe FPDF personnalisée pour l'en-tête et pied de page ONAPAC
class ONAPAC_Stock_PDF extends FPDF {
    private $categorie_nom;

    public function setCategorieFiltre($nom) {
        $this->categorie_nom = $nom;
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
        $this->Cell(0, 8, utf8_decode("État d'Inventaire et Valorisation des Stocks"), 0, 1, 'C');
        
        // Filtre appliqué
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(113, 128, 150);
        $this->Cell(0, 6, utf8_decode("Catégorie : " . $this->categorie_nom), 0, 1, 'C');
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
        
        // Texte
        $this->Cell(100, 10, utf8_decode('Portail ONAPAC - Inventaire physique des entrepôts'), 0, 0, 'L');
        $this->Cell(90, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'R');
    }
}

// 1. Récupération des filtres
$id_categorie = $_GET['id_categorie'] ?? 'toutes';
$tri = $_GET['tri'] ?? 'nom';

$where_clause = "WHERE 1=1";
$params = [];
$categorie_nom = "Toutes les catégories";

if ($id_categorie !== 'toutes') {
    $where_clause .= " AND p.id_categorie = :id_cat";
    $params[':id_cat'] = $id_categorie;
    
    // Récupérer le nom de la catégorie pour l'en-tête
    $stmtCat = $pdo->prepare("SELECT nom_categorie FROM categories WHERE id_categorie = :id");
    $stmtCat->execute([':id' => $id_categorie]);
    $catRow = $stmtCat->fetch();
    if ($catRow) {
        $categorie_nom = $catRow['nom_categorie'];
    }
}

// Définition de l'ordre de tri
$order_by = "p.nom_produit ASC";
if ($tri === 'stock_desc') {
    $order_by = "p.stock_disponible DESC";
} elseif ($tri === 'prix_desc') {
    $order_by = "p.prix_unitaire_usd DESC";
}

// 2. Requête SQL d'extraction
try {
    $query = "SELECT p.*, c.nom_categorie 
              FROM produits p 
              INNER JOIN categories c ON p.id_categorie = c.id_categorie
              $where_clause 
              ORDER BY $order_by";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

// 3. Initialisation du PDF
$pdf = new ONAPAC_Stock_PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->setCategorieFiltre($categorie_nom);
$pdf->AddPage();

// En-tête du tableau
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(30, 86, 49); // Vert ONAPAC
$pdf->SetTextColor(255, 255, 255); // Texte Blanc

$col_nom = 60;
$col_cat = 30;
$col_prix = 30;
$col_stock = 35;
$col_valeur = 35;

$pdf->Cell($col_nom, 8, utf8_decode('Dénomination du lot'), 1, 0, 'L', true);
$pdf->Cell($col_cat, 8, utf8_decode('Catégorie'), 1, 0, 'L', true);
$pdf->Cell($col_prix, 8, 'Prix (USD/Kg)', 1, 0, 'R', true);
$pdf->Cell($col_stock, 8, 'Stock Disponible', 1, 0, 'R', true);
$pdf->Cell($col_valeur, 8, 'Valeur Estimée', 1, 1, 'R', true);

// Données
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(45, 55, 72);
$total_lots = 0;
$total_poids_kg = 0;
$total_valeur_usd = 0;
$fill = false;

foreach ($produits as $row) {
    $pdf->SetFillColor(247, 250, 252);
    
    $nom = $row['nom_produit'];
    $cat = $row['nom_categorie'];
    $prix = floatval($row['prix_unitaire_usd']);
    $stock = floatval($row['stock_disponible']); // stock en Kg dans ton SQL
    
    // Calcul de la valeur financière du lot (Prix au Kg * quantité de stock au Kg)
    $valeur_lot = $prix * $stock;
    
    // Formatage de l'affichage du stock (si >= 1000 Kg on l'affiche aussi en Tonnes pour la clarté)
    if ($stock >= 1000) {
        $stock_texte = number_format($stock, 0, '.', ' ') . " Kg (" . number_format(($stock / 1000), 1, '.', ' ') . " T)";
    } else {
        $stock_texte = number_format($stock, 0, '.', ' ') . " Kg";
    }
    
    $pdf->Cell($col_nom, 8, utf8_decode($nom), 'B', 0, 'L', $fill);
    $pdf->Cell($col_cat, 8, utf8_decode($cat), 'B', 0, 'L', $fill);
    $pdf->Cell($col_prix, 8, number_format($prix, 2, '.', ' ') . ' $', 'B', 0, 'R', $fill);
    $pdf->Cell($col_stock, 8, $stock_texte, 'B', 0, 'R', $fill);
    $pdf->Cell($col_valeur, 8, number_format($valeur_lot, 2, '.', ' ') . ' $', 'B', 1, 'R', $fill);
    
    $total_lots++;
    $total_poids_kg += $stock;
    $total_valeur_usd += $valeur_lot;
    $fill = !$fill;
}

// Résumé d'inventaire financier
$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(45, 55, 72);
$pdf->Cell(95, 8, utf8_decode("Nombre de lots audités : " . $total_lots), 0, 0, 'L');
$pdf->Cell(95, 8, utf8_decode("Poids cumulé : " . number_format($total_poids_kg / 1000, 2, '.', ' ') . " Tonnes"), 0, 1, 'R');

$pdf->SetTextColor(30, 86, 49);
$pdf->Cell(190, 8, utf8_decode("Valeur financière globale de l'inventaire : " . number_format($total_valeur_usd, 2, '.', ' ') . " USD"), 0, 1, 'R');

// Envoi au navigateur
$pdf->Output('I', 'ONAPAC_Inventaire_Stocks_' . date('Y-m-d') . '.pdf');