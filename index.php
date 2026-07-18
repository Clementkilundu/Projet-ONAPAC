<?php
// index.php
session_start();
require_once 'bdd/db.php';
require_once 'inc/header.php'; // Notre header commun

try {
    // 1. Récupérer les 3 derniers produits ajoutés pour les mettre en avant
    $stmt = $pdo->query("SELECT p.*, c.nom_categorie 
                         FROM produits p 
                         INNER JOIN categories c ON p.id_categorie = c.id_categorie 
                         ORDER BY p.id_produit DESC 
                         LIMIT 3");
    $produits_phares = $stmt->fetchAll();

    // 2. Récupérer quelques statistiques rapides
    $count_cats = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $total_stock = $pdo->query("SELECT SUM(stock_disponible) FROM produits")->fetchColumn();
} catch (PDOException $e) {
    die("Erreur de chargement des données d'accueil : " . $e->getMessage());
}
?>

<link rel="stylesheet" href="css/index.css">

<div class="carousel-container">
    <div class="carousel-slide active" style="background-image: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=1200&q=80');">
        <div class="carousel-content">
            <span class="carousel-badge">Certification ONAPAC</span>
            <h2>Café d'Exception du Kivu</h2>
            <p>Découvrez nos lots de Café Arabica et Robusta rigoureusement sélectionnés, analysés en laboratoire et certifiés conformes pour l'exportation internationale.</p>
            <a href="produits.php?cat=1" class="btn-carousel">Voir les lots de Café</a>
        </div>
    </div>

    <div class="carousel-slide" style="background-image: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('https://images.unsplash.com/photo-1587132137056-bfbf0166836e?auto=format&fit=crop&w=1200&q=80');">
        <div class="carousel-content">
            <span class="carousel-badge">Qualité Premium</span>
            <h2>Fèves de Cacao de l'Ituri</h2>
            <p>Des fèves de cacao fermentées et séchées au soleil selon des normes strictes, garantissant des arômes intenses et une traçabilité complète.</p>
            <a href="produits.php?cat=2" class="btn-carousel">Découvrir le Cacao</a>
        </div>
    </div>

    <div class="carousel-slide" style="background-image: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('https://images.unsplash.com/photo-1608797178974-15b35a61d121?auto=format&fit=crop&w=1200&q=80');">
        <div class="carousel-content">
            <span class="carousel-badge">Régulation & Transparence</span>
            <h2>Portail Exportateurs Sécurisé</h2>
            <p>Créez votre espace acheteur, suivez l'avancement de vos analyses de qualité en direct et obtenez vos certificats phytosanitaires et de contrôle ONAPAC.</p>
            <a href="inscription.php" class="btn-carousel btn-secondary">Créer un compte</a>
        </div>
    </div>

    <button class="carousel-prev" id="prevBtn"><i class="fa-solid fa-chevron-left"></i></button>
    <button class="carousel-next" id="nextBtn"><i class="fa-solid fa-chevron-right"></i></button>

    <div class="carousel-dots">
        <span class="dot active" data-index="0"></span>
        <span class="dot" data-index="1"></span>
        <span class="dot" data-index="2"></span>
    </div>
</div>

<section class="benefits-section">
    <div class="section-header">
        <h2>Garantie de Qualité & Régulation</h2>
        <p>L'Office National des Produits Agricoles du Congo (ONAPAC) assure la conformité de chaque gramme exporté.</p>
    </div>
    <div class="benefits-grid">
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fa-solid fa-microscope"></i></div>
            <h3>Analyses Physico-chimiques</h3>
            <p>Chaque lot subit des examens rigoureux dans nos laboratoires pour mesurer le taux d'humidité, le calibrage et déceler d'éventuels défauts.</p>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fa-solid fa-file-shield"></i></div>
            <h3>Certification Officielle</h3>
            <p>Nous délivrons les certificats indispensables pour vos démarches d'exportation, attestant de la provenance et du grade de qualité exact des produits.</p>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fa-solid fa-scale-balanced"></i></div>
            <h3>Prix Régulés & Équitables</h3>
            <p>Nous appliquons les mercuriales officielles et veillons à la transparence des transactions commerciales entre les coopératives locales et les acheteurs internationaux.</p>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fa-solid fa-seedling"></i>
            <h3><?php echo htmlspecialchars($count_cats); ?></h3>
            <p>Filières Agricoles Régulées</p>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-boxes-stacked"></i>
            <h3><?php echo number_format($total_stock, 0, ',', ' '); ?> Kg</h3>
            <p>Volume de Stock Disponible</p>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-award"></i>
            <h3>100 %</h3>
            <p>Produits Certifiés Conformes</p>
        </div>
    </div>
</section>

<section class="featured-section">
    <div class="section-header">
        <h2>Derniers Lots Agricoles Enregistrés</h2>
        <p>Accédez aux analyses de laboratoire récentes et initiez vos commandes de matières premières certifiées</p>
    </div>

    <div class="products-grid">
        <?php if (empty($produits_phares)): ?>
            <p class="no-products">Aucun lot agricole n'est actuellement disponible.</p>
        <?php else: ?>
            <?php foreach ($produits_phares as $prod): 
                // Choix de l'image d'illustration selon la catégorie pour dynamiser le visuel
                $image_url = 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80'; // Par défaut (Café)
                if (strtolower($prod['nom_categorie']) === 'cacao') {
                    $image_url = 'https://images.unsplash.com/photo-1587132137056-bfbf0166836e?auto=format&fit=crop&w=600&q=80';
                } elseif (strtolower($prod['nom_categorie']) === 'pyrethe' || strtolower($prod['nom_categorie']) === 'plantes à parfum') {
                    $image_url = 'https://images.unsplash.com/photo-1608797178974-15b35a61d121?auto=format&fit=crop&w=600&q=80';
                }
            ?>
                <div class="product-card">
                    <div class="product-image-container">
                        <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($prod['nom_produit']); ?>" class="product-card-img">
                        <div class="card-badge"><?php echo htmlspecialchars($prod['nom_categorie']); ?></div>
                    </div>
                    <div class="card-body">
                        <h3 class="product-title"><?php echo htmlspecialchars($prod['nom_produit']); ?></h3>
                        <p class="product-meta">
                            <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($prod['origine_provenance']); ?></span>
                            <span><i class="fa-solid fa-tags"></i> <?php echo htmlspecialchars($prod['grade_qualite'] ?? 'Standard'); ?></span>
                        </p>
                        <p class="product-desc">
                            <?php 
                                $desc = htmlspecialchars($prod['description']);
                                echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc; 
                            ?>
                        </p>
                    </div>
                    <div class="card-footer">
                        <div class="price-box">
                            <span class="price"><?php echo number_format($prod['prix_unitaire_usd'], 2); ?> $</span>
                            <span class="unit">/ <?php echo htmlspecialchars($prod['unite_mesure']); ?></span>
                        </div>
                        <a href="detail_produit.php?id=<?php echo $prod['id_produit']; ?>" class="btn-view">Détails du Lot</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="view-all-container">
        <a href="produits.php" class="btn-all">Découvrir tout le catalogue <i class="fa-solid fa-arrow-right"></i></a>
    </div>
</section>

<section class="how-it-works">
    <div class="section-header">
        <h2>Le Processus d'Achat & d'Exportation</h2>
        <p>Un parcours transparent et sécurisé, encadré par les normes réglementaires de la RDC.</p>
    </div>
    <div class="steps-container">
        <div class="step-card">
            <div class="step-number">1</div>
            <h4>Création de compte</h4>
            <p>Inscrivez votre structure d'import/export en fournissant vos informations d'entreprise (RCCM).</p>
        </div>
        <div class="step-card">
            <div class="step-number">2</div>
            <h4>Choix & Réservation</h4>
            <p>Sélectionnez vos lots certifiés et lancez une demande d'option d'achat directement depuis votre panier.</p>
        </div>
        <div class="step-card">
            <div class="step-number">3</div>
            <h4>Paiement & Contrôle</h4>
            <p>Procédez au règlement sécurisé basé sur les mercuriales officielles et obtenez vos certificats de contrôle.</p>
        </div>
        <div class="step-card">
            <div class="step-number">4</div>
            <h4>Suivi d'expédition</h4>
            <p>Suivez l'acheminement, le chargement et l'expédition de vos produits agricoles en toute confiance.</p>
        </div>
    </div>
</section>

<script src="js/index.js"></script>

<?php 
// Fermeture de la div globale "site-container" ouverte dans header.php
echo "</div>"; 
?>
</body>
</html>