// js/panier.js

document.addEventListener("DOMContentLoaded", function () {
    const cartCards = document.querySelectorAll(".cart-item-card");

    cartCards.forEach(card => {
        const minusBtn = card.querySelector(".qty-btn-minus");
        const plusBtn = card.querySelector(".qty-btn-plus");
        const qtyInput = card.querySelector(".qty-input");
        const form = card.querySelector(".qty-form");

        const maxStock = parseInt(qtyInput.getAttribute("data-stock"));

        // Gestion du bouton Moins
        if (minusBtn) {
            minusBtn.addEventListener("click", function () {
                let currentVal = parseInt(qtyInput.value);
                if (currentVal > 1) {
                    qtyInput.value = currentVal - 1;
                    // Soumission automatique pour enregistrer en BDD
                    form.submit();
                }
            });
        }

        // Gestion du bouton Plus
        if (plusBtn) {
            plusBtn.addEventListener("click", function () {
                let currentVal = parseInt(qtyInput.value);
                if (currentVal < maxStock) {
                    qtyInput.value = currentVal + 1;
                    // Soumission automatique pour enregistrer en BDD
                    form.submit();
                } else {
                    alert("Désolé, la quantité demandée atteint la limite du stock disponible.");
                }
            });
        }

        // Événement en cas de saisie manuelle dans l'input
        qtyInput.addEventListener("change", function () {
            let val = parseInt(this.value);
            if (isNaN(val) || val < 1) {
                this.value = 1;
            } else if (val > maxStock) {
                this.value = maxStock;
                alert("Quantité ajustée au stock maximal disponible (" + maxStock + ").");
            }
            form.submit();
        });
    });
});