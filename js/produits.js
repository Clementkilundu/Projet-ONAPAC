// js/produits.js

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const filterButtons = document.querySelectorAll(".filter-btn");
    const productCards = document.querySelectorAll(".product-card");

    let activeCategory = "all";
    let searchQuery = "";

    // Fonction de filtrage globale
    function filterProducts() {
        productCards.forEach(card => {
            const cardCategory = card.getAttribute("data-category");
            const cardName = card.getAttribute("data-name");
            const cardOrigin = card.getAttribute("data-origin");

            // Critère Catégorie
            const matchCategory = (activeCategory === "all" || cardCategory === activeCategory);
            
            // Critère Recherche
            const matchSearch = (cardName.includes(searchQuery) || cardOrigin.includes(searchQuery));

            if (matchCategory && matchSearch) {
                card.classList.remove("hidden");
            } else {
                card.classList.add("hidden");
            }
        });
    }

    // Événement pour la saisie de texte
    if (searchInput) {
        searchInput.addEventListener("input", function (e) {
            searchQuery = e.target.value.toLowerCase().trim();
            filterProducts();
        });
    }

    // Événement pour les clics de filtre par bouton
    filterButtons.forEach(button => {
        button.addEventListener("click", function () {
            // Mise à jour de l'état graphique des boutons
            filterButtons.forEach(btn => btn.classList.remove("active"));
            this.classList.add("active");

            // Mise à jour de la catégorie active
            activeCategory = this.getAttribute("data-category");
            filterProducts();
        });
    });
});