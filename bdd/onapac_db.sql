-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2026 at 11:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `onapac_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id_categorie` int(11) NOT NULL,
  `nom_categorie` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id_categorie`, `nom_categorie`, `description`) VALUES
(1, 'Café', 'Café Arabica et Robusta certifiés.'),
(2, 'Cacao', 'Fèves de cacao prêtes pour l\'exportation.'),
(3, 'Plantes à Parfum', 'Produits de spécialité (Quinquina, Papaye, etc.).'),
(4, 'plante médecinale', NULL),
(5, 'thé', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `certificats_qualite`
--

CREATE TABLE `certificats_qualite` (
  `id_certificat` int(11) NOT NULL,
  `numero_certificat` varchar(50) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `date_emission` timestamp NOT NULL DEFAULT current_timestamp(),
  `resultat_analyse` text NOT NULL,
  `decision` enum('Approuvé','Refusé') NOT NULL DEFAULT 'Approuvé',
  `id_agent_onapac` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commandes`
--

CREATE TABLE `commandes` (
  `id_commande` int(11) NOT NULL,
  `reference_commande` varchar(50) NOT NULL,
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  `montant_total_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut_commande` enum('En attente','Validée','En cours d''analyse','Certifiée OK','Rejetée','Prête pour expédition','Livrée') NOT NULL DEFAULT 'En attente',
  `id_acheteur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commandes`
--

INSERT INTO `commandes` (`id_commande`, `reference_commande`, `date_commande`, `montant_total_usd`, `statut_commande`, `id_acheteur`) VALUES
(6, 'CMD-20260714-5760', '2026-07-14 15:07:17', 9.09, 'Validée', 5),
(7, 'CMD-20260714-9499', '2026-07-14 16:08:04', 5.25, 'Validée', 4),
(8, 'CMD-20260720-8705', '2026-07-20 16:36:41', 9.09, 'En attente', 4),
(9, 'CMD-20260721-7702', '2026-07-21 07:25:06', 979.70, 'Livrée', 6);

-- --------------------------------------------------------

--
-- Table structure for table `lignes_commande`
--

CREATE TABLE `lignes_commande` (
  `id_ligne` int(11) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite_commandee` decimal(12,2) NOT NULL,
  `prix_applique_usd` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lignes_commande`
--

INSERT INTO `lignes_commande` (`id_ligne`, `id_commande`, `id_produit`, `quantite_commandee`, `prix_applique_usd`) VALUES
(8, 6, 1, 2.00, 4.50),
(9, 7, 2, 1.00, 5.20),
(10, 8, 1, 2.00, 4.50),
(11, 9, 1, 100.00, 4.50),
(12, 9, 2, 100.00, 5.20);

-- --------------------------------------------------------

--
-- Table structure for table `livraisons`
--

CREATE TABLE `livraisons` (
  `id_livraison` int(11) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `adresse_livraison` text NOT NULL,
  `societe_transport` varchar(150) DEFAULT NULL,
  `numero_suivi` varchar(100) DEFAULT NULL,
  `statut_livraison` enum('En préparation','En transit','Disponible au point de retrait','Livrée') NOT NULL DEFAULT 'En préparation',
  `date_expedition` datetime DEFAULT NULL,
  `date_livraison_effective` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `livraisons`
--

INSERT INTO `livraisons` (`id_livraison`, `id_commande`, `adresse_livraison`, `societe_transport`, `numero_suivi`, `statut_livraison`, `date_expedition`, `date_livraison_effective`) VALUES
(6, 6, 'Port de bukavu, Bukavu - RDC', NULL, 'N-SUIVI-123', 'Livrée', NULL, NULL),
(7, 7, 'Port de kalundu, Uvira - RDC', NULL, '', 'Livrée', NULL, NULL),
(8, 8, 'GOMA, GOMA - RDC', NULL, NULL, 'En préparation', NULL, NULL),
(9, 9, 'Maison dada chez baba chingazi, Bukavu - RDC', NULL, 'N-SUIVI-124', 'Livrée', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages_contact`
--

CREATE TABLE `messages_contact` (
  `id_message` int(11) NOT NULL,
  `nom_expediteur` varchar(100) NOT NULL,
  `email_expediteur` varchar(150) NOT NULL,
  `sujet` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp(),
  `est_lu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages_contact`
--

INSERT INTO `messages_contact` (`id_message`, `nom_expediteur`, `email_expediteur`, `sujet`, `message`, `date_envoi`, `est_lu`) VALUES
(3, 'baraka Farijika', 'barakakabagaya52@gmail.com', 'Partenariat', 'Bonjour, commentr je peux reconnu comme exportateur ?', '2026-07-21 08:06:48', 0);

-- --------------------------------------------------------

--
-- Table structure for table `paiements`
--

CREATE TABLE `paiements` (
  `id_paiement` int(11) NOT NULL,
  `reference_transaction` varchar(100) NOT NULL,
  `mode_paiement` enum('Virement Bancaire','Mobile Money','Chèque Certifié') NOT NULL,
  `montant_paye_usd` decimal(12,2) NOT NULL,
  `date_paiement` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut_paiement` enum('En attente de vérification','Confirmé','Échoué') NOT NULL DEFAULT 'En attente de vérification',
  `id_commande` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `paiements`
--

INSERT INTO `paiements` (`id_paiement`, `reference_transaction`, `mode_paiement`, `montant_paye_usd`, `date_paiement`, `statut_paiement`, `id_commande`) VALUES
(6, 'CMD-20260714-5760', 'Mobile Money', 9.09, '2026-07-14 15:08:38', 'Confirmé', 6),
(7, 'CMD-20260714-9499', 'Mobile Money', 5.25, '2026-07-14 16:10:24', 'Confirmé', 7),
(8, 'CMD-20260720-8705', 'Mobile Money', 9.09, '2026-07-20 16:36:41', 'En attente de vérification', 8),
(9, 'CMD-20260721-7702', 'Mobile Money', 979.70, '2026-07-21 07:34:10', 'Confirmé', 9);

-- --------------------------------------------------------

--
-- Table structure for table `paniers`
--

CREATE TABLE `paniers` (
  `id_panier` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` decimal(12,2) NOT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `paniers`
--

INSERT INTO `paniers` (`id_panier`, `id_utilisateur`, `id_produit`, `quantite`, `date_ajout`) VALUES
(11, 1, 2, 2.00, '2026-07-21 08:59:34');

-- --------------------------------------------------------

--
-- Table structure for table `produits`
--

CREATE TABLE `produits` (
  `id_produit` int(11) NOT NULL,
  `nom_produit` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `prix_unitaire_usd` decimal(10,2) NOT NULL,
  `unite_mesure` varchar(20) NOT NULL DEFAULT 'Kg',
  `stock_disponible` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grade_qualite` varchar(50) DEFAULT NULL,
  `origine_provenance` varchar(100) DEFAULT NULL,
  `id_categorie` int(11) NOT NULL,
  `date_enregistrement` timestamp NOT NULL DEFAULT current_timestamp(),
  `img_url` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produits`
--

INSERT INTO `produits` (`id_produit`, `nom_produit`, `description`, `prix_unitaire_usd`, `unite_mesure`, `stock_disponible`, `grade_qualite`, `origine_provenance`, `id_categorie`, `date_enregistrement`, `img_url`) VALUES
(1, 'Café Arabica Kivu K3', 'Café lavé de haute altitude.', 4.50, 'Kg', 24894.00, 'K3 Standard', 'Nord-Kivu (Beni)', 1, '2026-07-08 17:30:04', 'uploads/1784625405_6a5f38fd185d8.jpg'),
(2, 'Fèves de Cacao Fermentées', 'Qualité supérieure séchée au soleil.', 5.20, 'Kg', 14896.00, 'Premium', 'Ituri (Mambasa)', 2, '2026-07-08 17:30:04', 'uploads/1784623566_6a5f31ce69a66.jpg'),
(3, 'Café Robusta', 'un café bon pour la santé', 8.00, 'Kg', 1000.00, 'uploads/1784620994_6a5f27c297e0a.jpg', 'kalehe/sud-Kivu', 1, '2026-07-21 08:00:21', 'uploads/1784623231_6a5f307f463f4.webp'),
(4, 'thé kivu cahi', 'un thé pas comme les autres', 5.00, 'Kg', 1000.00, 'Grade A', 'mwenga / sud-kivu', 5, '2026-07-21 09:22:10', 'uploads/1784625730_6a5f3a42d8873.png');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id_role` int(11) NOT NULL,
  `nom_role` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id_role`, `nom_role`, `description`) VALUES
(1, 'Administrateur', 'Gestion technique globale et audit.'),
(2, 'Agent_ONAPAC', 'Contrôle qualité, validation logistique et émission des certificats.'),
(3, 'Acheteur', 'Exportateurs, coopératives agricoles et clients internationaux.');

-- --------------------------------------------------------

--
-- Table structure for table `tokens_authentification`
--

CREATE TABLE `tokens_authentification` (
  `id_token` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `type_token` enum('Refresh','Reset_Password') NOT NULL DEFAULT 'Refresh',
  `date_expiration` datetime NOT NULL,
  `est_valide` tinyint(1) DEFAULT 1,
  `id_utilisateur` int(11) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `nom_entreprise` varchar(150) DEFAULT NULL,
  `rccm` varchar(50) DEFAULT NULL,
  `id_role` int(11) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `nom_entreprise`, `rccm`, `id_role`, `date_creation`) VALUES
(1, 'Kambale', 'Jean', 'admin@onapac.cd', 'AdminHash123!', '+243810000001', 'ONAPAC', NULL, 1, '2026-07-08 17:30:04'),
(2, 'Mbuyi', 'Sarah', 's.mbuyi@onapac.cd', 'AgentHash2026', '+243990000002', 'ONAPAC', NULL, 2, '2026-07-08 17:30:04'),
(3, 'Smith', 'John', 'j.smith@kivuexport.com', 'ClientHash2026', '+243820000003', 'Kivu Coffee Export SRL', 'CD/BKV/RCCM/26-B-0450', 3, '2026-07-08 17:30:04'),
(4, 'kilundu', 'clement', 'kilunduclement@gmail.com', '$2y$10$Fh.AAxeWsd.W34AMCNoDOuXR5SvqymRxe6fIjl1jW.F6kv1aSvtVK', '+243995802729', 'Bantu code', 'CD/BKV/RCCM/26-B-0450', 3, '2026-07-14 12:56:12'),
(5, 'kakusu', 'John', 'johnshekinah@gmail.com', '$2y$10$.DjLmCPViFGteJhk6lk10.6fJFkX//p1kmMCs6pBY4isxF3SmXeFy', '+243995802728', 'INERA-MULUNGU', 'CD/BKV/RCCM/28-B-0420', 3, '2026-07-14 14:51:07'),
(6, 'Farijika', 'baraka', 'barakakabagaya52@gmail.com', '$2y$10$KVoEPklt8uJtBCjXIntIUeWt0X7jzs7zKaI0V67yXPkFjdekrUt5W', '+243974772103', 'Baraka food', 'CD/BKV/RCCM/30-C-0530', 3, '2026-07-21 07:10:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_categorie`),
  ADD UNIQUE KEY `uq_nom_categorie` (`nom_categorie`);

--
-- Indexes for table `certificats_qualite`
--
ALTER TABLE `certificats_qualite`
  ADD PRIMARY KEY (`id_certificat`),
  ADD UNIQUE KEY `uq_numero_certificat` (`numero_certificat`),
  ADD UNIQUE KEY `uq_id_commande_certificat` (`id_commande`),
  ADD KEY `fk_certificats_agents` (`id_agent_onapac`);

--
-- Indexes for table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id_commande`),
  ADD UNIQUE KEY `uq_reference_commande` (`reference_commande`),
  ADD KEY `fk_commandes_acheteurs` (`id_acheteur`);

--
-- Indexes for table `lignes_commande`
--
ALTER TABLE `lignes_commande`
  ADD PRIMARY KEY (`id_ligne`),
  ADD KEY `fk_lignes_commande_commandes` (`id_commande`),
  ADD KEY `fk_lignes_commande_produits` (`id_produit`);

--
-- Indexes for table `livraisons`
--
ALTER TABLE `livraisons`
  ADD PRIMARY KEY (`id_livraison`),
  ADD UNIQUE KEY `uq_id_commande_livraison` (`id_commande`);

--
-- Indexes for table `messages_contact`
--
ALTER TABLE `messages_contact`
  ADD PRIMARY KEY (`id_message`);

--
-- Indexes for table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id_paiement`),
  ADD UNIQUE KEY `uq_ref_transaction` (`reference_transaction`),
  ADD KEY `fk_paiements_commandes` (`id_commande`);

--
-- Indexes for table `paniers`
--
ALTER TABLE `paniers`
  ADD PRIMARY KEY (`id_panier`),
  ADD KEY `fk_paniers_utilisateurs` (`id_utilisateur`),
  ADD KEY `fk_paniers_produits` (`id_produit`);

--
-- Indexes for table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id_produit`),
  ADD KEY `fk_produits_categories` (`id_categorie`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `uq_nom_role` (`nom_role`);

--
-- Indexes for table `tokens_authentification`
--
ALTER TABLE `tokens_authentification`
  ADD PRIMARY KEY (`id_token`),
  ADD KEY `fk_tokens_utilisateurs` (`id_utilisateur`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `uq_email_utilisateur` (`email`),
  ADD KEY `fk_utilisateurs_roles` (`id_role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `certificats_qualite`
--
ALTER TABLE `certificats_qualite`
  MODIFY `id_certificat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `lignes_commande`
--
ALTER TABLE `lignes_commande`
  MODIFY `id_ligne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `livraisons`
--
ALTER TABLE `livraisons`
  MODIFY `id_livraison` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `messages_contact`
--
ALTER TABLE `messages_contact`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id_paiement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `paniers`
--
ALTER TABLE `paniers`
  MODIFY `id_panier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `produits`
--
ALTER TABLE `produits`
  MODIFY `id_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_role` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tokens_authentification`
--
ALTER TABLE `tokens_authentification`
  MODIFY `id_token` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificats_qualite`
--
ALTER TABLE `certificats_qualite`
  ADD CONSTRAINT `fk_certificats_agents` FOREIGN KEY (`id_agent_onapac`) REFERENCES `utilisateurs` (`id_utilisateur`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_certificats_commandes` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE;

--
-- Constraints for table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `fk_commandes_acheteurs` FOREIGN KEY (`id_acheteur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON UPDATE CASCADE;

--
-- Constraints for table `lignes_commande`
--
ALTER TABLE `lignes_commande`
  ADD CONSTRAINT `fk_lignes_commande_commandes` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lignes_commande_produits` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON UPDATE CASCADE;

--
-- Constraints for table `livraisons`
--
ALTER TABLE `livraisons`
  ADD CONSTRAINT `fk_livraisons_commandes` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE;

--
-- Constraints for table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `fk_paiements_commandes` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE;

--
-- Constraints for table `paniers`
--
ALTER TABLE `paniers`
  ADD CONSTRAINT `fk_paniers_produits` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_paniers_utilisateurs` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `fk_produits_categories` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id_categorie`) ON UPDATE CASCADE;

--
-- Constraints for table `tokens_authentification`
--
ALTER TABLE `tokens_authentification`
  ADD CONSTRAINT `fk_tokens_utilisateurs` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_utilisateurs_roles` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
