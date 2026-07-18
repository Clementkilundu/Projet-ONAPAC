<?php
// telecharger_facture.php
session_start();
require_once 'bdd/db.php';

// 1. Inclusion de la bibliothèque FPDF
if (file_exists('libs/fpdf19/fpdf.php')) {
    require_once 'libs/fpdf19/fpdf.php';
} else {
    die("Erreur : La bibliothèque FPDF est introuvable dans 'libs/fpdf.php'. Veuillez l'y installer pour générer les factures.");
}

// 2. Sécurité : l'utilisateur doit être connecté
if (!isset($_SESSION['user_id'])) {
    die("Accès refusé. Veuillez vous connecter.");
}

$id_utilisateur = $_SESSION['user_id'];

// 3. Récupération de l'ID de commande
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Identifiant de commande manquant.");
}

$id_commande = (int)$_GET['id'];

try {
    // 4. Récupérer les détails de la commande et les colonnes exactes de l'acheteur
    $stmt_cmd = $pdo->prepare("SELECT c.*, 
                                      CONCAT(u.prenom, ' ', u.nom) AS nom_complet, 
                                      u.email, 
                                      p.mode_paiement, 
                                      p.statut_paiement, 
                                      l.adresse_livraison
                               FROM commandes c
                               INNER JOIN utilisateurs u ON c.id_acheteur = u.id_utilisateur
                               LEFT JOIN paiements p ON c.id_commande = p.id_commande
                               LEFT JOIN livraisons l ON c.id_commande = l.id_commande
                               WHERE c.id_commande = :id_cmd AND c.id_acheteur = :user_id");
    $stmt_cmd->execute([
        ':id_cmd' => $id_commande,
        ':user_id' => $id_utilisateur
    ]);
    $commande = $stmt_cmd->fetch();

    if (!$commande) {
        die("Commande introuvable ou vous n'avez pas l'autorisation d'y accéder.");
    }

    if ($commande['statut_commande'] === 'En attente') {
        die("Cette commande n'a pas encore été réglée. Aucune facture disponible.");
    }

    // 5. Récupérer les lignes de produits associées à la commande
    $stmt_lignes = $pdo->prepare("SELECT lc.*, prod.nom_produit, prod.unite_mesure
                                  FROM lignes_commande lc
                                  INNER JOIN produits prod ON lc.id_produit = prod.id_produit
                                  WHERE lc.id_commande = :id_cmd");
    $stmt_lignes->execute([':id_cmd' => $id_commande]);
    $lignes = $stmt_lignes->fetchAll();

} catch (PDOException $e) {
    die("Erreur technique : " . $e->getMessage());
}

// ================= GÉNÉRATION DU PDF VIA FPDF =================

class ONAPAC_PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(30, 86, 49); // Vert ONAPAC
        $this->Cell(0, 8, utf8_decode("ONAPAC - OFFICE NATIONAL DES PRODUITS AGRICOLES DU CONGO"), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, utf8_decode("Direction Générale - Secteur Exportations du Kivu"), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode("République Démocratique du Congo"), 0, 1, 'C');
        
        // Ligne de séparation verte
        $this->SetDrawColor(30, 86, 49);
        $this->SetLineWidth(0.8);
        $this->Line(10, 32, 200, 32);
        $this->Ln(15); // Plus d'espace sous l'en-tête
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Line(10, 282, 200, 282);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Configuration de base du document
$pdf = new ONAPAC_PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(15, 20, 15);

// --- 1. Titre de la facture ---
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(33, 33, 33);
$pdf->Cell(0, 10, utf8_decode("FACTURE OFFICIELLE N° " . $commande['reference_commande']), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, "Date d'emission : " . date("d/m/Y H:i", strtotime($commande['date_commande'])), 0, 1, 'L');
$pdf->Ln(5);

// --- 2. Blocs Émetteur / Client (Alignement fixe et propre) ---
$y_depart = $pdf->GetY();

// Émetteur (ONAPAC) - Colonne de Gauche (X: 15)
$pdf->SetXY(15, $y_depart);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(30, 86, 49);
$pdf->Cell(85, 6, utf8_decode("ÉMETTEUR :"), 0, 1, 'L');
$pdf->SetX(15); // Repositionnement X de sécurité
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(85, 5, utf8_decode("Office National des Produits Agricoles"), 0, 1, 'L');
$pdf->SetX(15);
$pdf->Cell(85, 5, utf8_decode("Régie de certification à l'exportation"), 0, 1, 'L');
$pdf->SetX(15);
$pdf->Cell(85, 5, utf8_decode("Bukavu, Sud-Kivu, RDC"), 0, 1, 'L');

// Client (Acheteur) - Colonne de Droite (X: 110)
$pdf->SetXY(110, $y_depart);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(30, 86, 49);
$pdf->Cell(85, 6, utf8_decode("FACTURÉ À (EXPORTATEUR) :"), 0, 1, 'L');
$pdf->SetX(110); // Repositionnement X de sécurité
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(85, 5, utf8_decode($commande['nom_complet']), 0, 1, 'L');
$pdf->SetX(110);
$pdf->Cell(85, 5, utf8_decode("Email : " . $commande['email']), 0, 1, 'L');
$pdf->SetX(110);
$pdf->Cell(85, 5, utf8_decode("Adresse : " . ($commande['adresse_livraison'] ?? 'Non spécifiée')), 0, 1, 'L');

// CORRECTIF : On force la redescente globale du curseur sous les deux colonnes (Y à +30px)
$pdf->SetXY(15, $y_depart + 30);
$pdf->Ln(5);

// --- 3. Cadre Récapitulatif Paiement ---
$pdf->SetFillColor(245, 248, 246);
$pdf->Rect(15, $pdf->GetY(), 180, 20, 'F');
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(30, 86, 49);
$pdf->Cell(0, 6, utf8_decode("  INFORMATIONS DE RÈGLEMENT :"), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 5, utf8_decode("  Passerelle : MaishaPay (Mode Test) | Canal : " . ($commande['mode_paiement'] ?? 'Mobile Money')), 0, 1, 'L');
$pdf->Cell(0, 5, utf8_decode("  Statut du paiement : " . ($commande['statut_paiement'] === 'Confirmé' || $commande['statut_paiement'] === 'Validé' ? 'RÉGLÉ / CONFIRMÉ' : 'EN ATTENTE')), 0, 1, 'L');
$pdf->Ln(8);

// --- 4. Tableau des Lots Certifiés ---
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(30, 86, 49); // En-tête vert
$pdf->SetTextColor(255, 255, 255);

$pdf->Cell(90, 8, utf8_decode("Description du lot agricole certifié"), 1, 0, 'L', true);
$pdf->Cell(30, 8, utf8_decode("Prix Unitaire FOB"), 1, 0, 'R', true);
$pdf->Cell(25, 8, utf8_decode("Quantité"), 1, 0, 'C', true);
$pdf->Cell(35, 8, utf8_decode("Total (USD)"), 1, 1, 'R', true);

// Contenu du tableau
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(33, 33, 33);
$sous_total = 0;

foreach ($lignes as $ligne) {
    $prix_u = (float)$ligne['prix_applique_usd'];
    $quantite = (float)$ligne['quantite_commandee'];
    $total_ligne = $prix_u * $quantite;
    $sous_total += $total_ligne;

    $pdf->Cell(90, 8, utf8_decode($ligne['nom_produit']), 1, 0, 'L');
    $pdf->Cell(30, 8, number_format($prix_u, 2) . " $", 1, 0, 'R');
    $pdf->Cell(25, 8, number_format($quantite, 1) . " " . $ligne['unite_mesure'], 1, 0, 'C');
    $pdf->Cell(35, 8, number_format($total_ligne, 2) . " $", 1, 1, 'R');
}

// Totaux & Taxes ONAPAC (1%)
$frais_onapac = $sous_total * 0.01;
$total_general = $sous_total + $frais_onapac;

$pdf->SetFont('Arial', 'B', 9);

// Ligne de Sous-total
$pdf->Cell(145, 8, utf8_decode("Sous-total des lots FOB :"), 0, 0, 'R');
$pdf->Cell(35, 8, number_format($sous_total, 2) . " $", 1, 1, 'R');

// Ligne Frais Administrateur
$pdf->Cell(145, 8, utf8_decode("Frais de contrôle technique ONAPAC (1%) :"), 0, 0, 'R');
$pdf->Cell(35, 8, number_format($frais_onapac, 2) . " $", 1, 1, 'R');

// CORRECTIF DU TOTAL : On définit une couleur de fond spécifique et claire pour la cellule
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(30, 86, 49); // Vert
$pdf->SetFillColor(235, 245, 238); // Vert très clair pour faire ressortir le montant final

$pdf->Cell(145, 10, utf8_decode("MONTANT TOTAL ACQUITTÉ (USD) :"), 0, 0, 'R');
$pdf->Cell(35, 10, number_format($total_general, 2) . " $", 1, 1, 'R', true); // Le paramètre true applique le fond clair

$pdf->Ln(12);

// --- 5. Certification Légale ---
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 4, utf8_decode("Déclaration légale : Cette facture certifie que l'exportateur s'est acquitté de l'entièreté des frais dus à l'Office National des Produits Agricoles du Congo (ONAPAC) pour l'analyse phytosanitaire des lots listés ci-dessus. Ce reçu vaut pièce justificative pour l'obtention des documents douaniers de transit."), 0, 'L');

$pdf->Ln(8);

// Signature
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(30, 86, 49);
$pdf->Cell(110, 5, "", 0, 0, 'L');
$pdf->Cell(70, 5, utf8_decode("Pour l'Office National des Produits Agricoles,"), 0, 1, 'C');
$pdf->Cell(110, 5, "", 0, 0, 'L');
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(70, 5, utf8_decode("Service d'homologation des exportations"), 0, 1, 'C');

// Génération et sortie du document
$nom_fichier = "Facture_ONAPAC_" . $commande['reference_commande'] . ".pdf";
$pdf->Output('I', $nom_fichier); 
exit();