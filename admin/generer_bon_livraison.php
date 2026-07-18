<?php
// generer_bon_livraison.php

// 1. Sécurité et restrictions d'accès
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    (strtolower($_SESSION['role']) !== 'administrateur' && strtolower($_SESSION['role']) !== 'agent_onapac')) {
    header('Location: connexion_admin.php');
    exit();
}

// Vérification du paramètre ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Erreur : Identifiant de la commande manquant.");
}

$id_commande = intval($_GET['id']);

// 2. Connexion à la BDD et récupération des données
require_once 'bdd/db.php';

try {
    $sqlCmd = "SELECT c.*, u.nom, u.prenom, u.nom_entreprise, u.email, u.telephone,
                      l.adresse_livraison, l.statut_livraison
               FROM commandes c
               INNER JOIN utilisateurs u ON c.id_acheteur = u.id_utilisateur
               LEFT JOIN livraisons l ON c.id_commande = l.id_commande
               WHERE c.id_commande = :id";
    
    $stmt = $pdo->prepare($sqlCmd);
    $stmt->execute([':id' => $id_commande]);
    $commande = $stmt->fetch();

    if (!$commande) {
        die("Erreur : Commande introuvable.");
    }

    $sqlLignes = "SELECT lc.quantite_commandee, p.nom_produit, cat.nom_categorie
                  FROM lignes_commande lc
                  INNER JOIN produits p ON lc.id_produit = p.id_produit
                  INNER JOIN categories cat ON p.id_categorie = cat.id_categorie
                  WHERE lc.id_commande = :id";
    $stmtLignes = $pdo->prepare($sqlLignes);
    $stmtLignes->execute([':id' => $id_commande]);
    $articles = $stmtLignes->fetchAll();

} catch (PDOException $e) {
    die("Erreur Base de données : " . $e->getMessage());
}

// 3. Inclusion de FPDF
require_once '../libs/fpdf19/fpdf.php'; 

class PDF_BonLivraison extends FPDF {
    // En-tête officiel de l'ONAPAC corrigé
    function Header() {
        // Titre Institutionnel (Largeur réduite à 115 pour laisser de la place à droite)
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(30, 86, 49); // Vert ONAPAC
        $this->Cell(115, 7, utf8_decode('OFFICE NATIONAL DES PRODUITS AGRICOLES'), 0, 0, 'L');
        
        // Alignement parfait à droite
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(75, 7, utf8_decode('REPUBLIQUE DEMOCRATIQUE DU CONGO'), 0, 1, 'R');
        
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(115, 5, utf8_decode('RDC - Portefeuille de l\'Etat'), 0, 0, 'L');
        $this->Cell(75, 5, utf8_decode('Fait le : ' . date('d/m/Y à H:i')), 0, 1, 'R');
        
        // Ligne de séparation colorée
        $this->SetDrawColor(30, 86, 49);
        $this->SetLineWidth(0.8);
        $this->Line(10, 24, 200, 24);
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, utf8_decode('Document officiel ONAPAC - Le bon signé vaut décharge juridique pour classement sécurisé.'), 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// 4. Instanciation et construction du PDF
$pdf = new PDF_BonLivraison('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

// --- TITRE DU DOCUMENT ---
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetFillColor(240, 244, 241); 
$pdf->Cell(0, 12, 'BON DE LIVRAISON : ' . utf8_decode($commande['reference_commande']), 1, 1, 'C', true);
$pdf->Ln(5);

// --- BLOCS D'INFORMATIONS ---
$x = $pdf->GetX();
$y = $pdf->GetY();

// Bloc Émetteur (Gauche)
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(90, 5, utf8_decode('Émetteur logistique :'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);
$pdf->Cell(90, 5, utf8_decode('Direction Générale - Service Expéditions'), 0, 1, 'L');
$pdf->Cell(90, 5, utf8_decode('ONAPAC - Secteur Agricole et Exportations'), 0, 1, 'L');
$pdf->Cell(90, 5, 'Bukavu, Sud-Kivu, RDC', 0, 1, 'L');

// Repositionnement pour le Bloc Destinataire (Droite)
$pdf->SetXY($x + 100, $y);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(90, 5, 'Destinataire (Acheteur) :', 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);

$entreprise = !empty($commande['nom_entreprise']) ? $commande['nom_entreprise'] : 'Particulier / Opérateur';
$pdf->SetX($x + 100);
$pdf->Cell(90, 5, utf8_decode('Entreprise : ' . $entreprise), 0, 1, 'L');

$pdf->SetX($x + 100);
$pdf->Cell(90, 5, utf8_decode('Responsable : ' . $commande['prenom'] . ' ' . $commande['nom']), 0, 1, 'L');

$pdf->SetX($x + 100);
$pdf->Cell(90, 5, utf8_decode('Tél : ' . ($commande['telephone'] ?? 'Non fourni')), 0, 1, 'L');

$adresse = !empty($commande['adresse_livraison']) ? $commande['adresse_livraison'] : 'À récupérer à l\'entrepôt';
$pdf->SetX($x + 100);
$pdf->MultiCell(90, 4, utf8_decode('Lieu de Livraison : ' . $adresse), 0, 'L');

$pdf->Ln(8);

// --- TABLEAU DES MARCHANDISES ---
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(30, 86, 49); 
$pdf->SetTextColor(255, 255, 255); 

$pdf->Cell(80, 8, utf8_decode('Désignation du Lot / Produit'), 1, 0, 'L', true);
$pdf->Cell(60, 8, utf8_decode('Catégorie'), 1, 0, 'L', true);
$pdf->Cell(50, 8, utf8_decode('Quantité Émise (Poids)'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);

if (empty($articles)) {
    $pdf->Cell(190, 8, 'Aucun article lié à cette commande.', 1, 1, 'C');
} else {
    foreach ($articles as $art) {
        $pdf->Cell(80, 8, utf8_decode($art['nom_produit']), 1, 0, 'L');
        $pdf->Cell(60, 8, utf8_decode($art['nom_categorie']), 1, 0, 'L');
        $pdf->Cell(50, 8, number_format($art['quantite_commandee'], 1) . ' Kg', 1, 1, 'C');
    }
}

$pdf->Ln(15);

// --- BLOC DE SIGNATURES ---
$pdf->SetFont('Arial', 'B', 10);
$current_y = $pdf->GetY();

$pdf->SetXY(10, $current_y);
$pdf->Cell(90, 5, utf8_decode('Pour l\'ONAPAC (Visa du Livreur) :'), 0, 1, 'L');
$pdf->SetFont('Arial', 'I', 8.5);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(90, 4, 'Nom du chauffeur : ..................................', 0, 1, 'L');
$pdf->Cell(90, 4, 'Date et Heure : ........................................', 0, 1, 'L');

$pdf->SetXY(110, $current_y);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(90, 5, utf8_decode('Pour le Client (Réceptionnaire) :'), 0, 1, 'L');
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(120, 30, 30); 
$pdf->SetX(110);
$pdf->Cell(90, 4, utf8_decode('Précéder de la mention manuscrite "Reçu conforme"'), 0, 1, 'L');
$pdf->SetTextColor(100, 100, 100);
$pdf->SetX(110);
$pdf->Cell(90, 4, 'Nom du signataire : ...................................', 0, 1, 'L');
$pdf->SetX(110);
$pdf->Cell(90, 4, 'Date et Signature : ', 0, 1, 'L');

$pdf->Output('I', 'Bon_de_livraison_' . $commande['reference_commande'] . '.pdf');