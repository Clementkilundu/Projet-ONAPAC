// js/detail_produit.js

document.addEventListener("DOMContentLoaded", function () {
    // 1. Éléments pour le sélecteur de quantité et calcul dynamique de prix
    const qtyInput = document.getElementById("quantite");
    const qtyPlusBtn = document.getElementById("qtyPlus");
    const qtyMinusBtn = document.getElementById("qtyMinus");
    const totalPriceEl = document.getElementById("totalPrice");
    const stockMaxContainer = document.getElementById("stockMax");

    const pricePerUnit = parseFloat(totalPriceEl.getAttribute("data-price"));
    const maxStock = parseInt(stockMaxContainer.getAttribute("data-stock"));

    // Met à jour l'estimation du prix total
    function updateTotalPrice() {
        let currentQty = parseInt(qtyInput.value);
        if (isNaN(currentQty) || currentQty < 1) {
            currentQty = 1;
        } else if (currentQty > maxStock) {
            currentQty = maxStock;
        }
        qtyInput.value = currentQty;

        const total = currentQty * pricePerUnit;
        totalPriceEl.textContent = total.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " $";
    }

    // Bouton Plus
    if (qtyPlusBtn) {
        qtyPlusBtn.addEventListener("click", function () {
            let val = parseInt(qtyInput.value);
            if (val < maxStock) {
                qtyInput.value = val + 1;
                updateTotalPrice();
            }
        });
    }

    // Bouton Moins
    if (qtyMinusBtn) {
        qtyMinusBtn.addEventListener("click", function () {
            let val = parseInt(qtyInput.value);
            if (val > 1) {
                qtyInput.value = val - 1;
                updateTotalPrice();
            }
        });
    }

    // Input change direct
    if (qtyInput) {
        qtyInput.addEventListener("input", function () {
            updateTotalPrice();
        });
    }

    // Initialisation du calcul au démarrage de la page
    updateTotalPrice();


    // 2. Gestion des Onglets en bas de page
    const tabButtons = document.querySelectorAll(".tab-btn");
    const tabPanels = document.querySelectorAll(".tab-panel");

    tabButtons.forEach(button => {
        button.addEventListener("click", function () {
            // Retirer l'état actif de tous les boutons et panels
            tabButtons.forEach(btn => btn.classList.remove("active"));
            tabPanels.forEach(panel => panel.classList.remove("active"));

            // Ajouter l'état actif sur l'élément cliqué
            this.classList.add("active");
            const targetTabId = this.getAttribute("data-tab");
            const targetPanel = document.getElementById(targetTabId);
            
            if (targetPanel) {
                targetPanel.classList.add("active");
            }
        });
    });
});