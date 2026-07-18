// js/index.js

document.addEventListener("DOMContentLoaded", function () {
    const slides = document.querySelectorAll(".carousel-slide");
    const dots = document.querySelectorAll(".dot");
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    
    let currentSlide = 0;
    let slideInterval;

    // Fonction pour afficher une slide spécifique
    function showSlide(index) {
        // Enlever la classe active sur toutes les slides et dots
        slides.forEach(slide => slide.classList.remove("active"));
        dots.forEach(dot => dot.classList.remove("active"));

        // Assurer une boucle circulaire
        if (index >= slides.length) {
            currentSlide = 0;
        } else if (index < 0) {
            currentSlide = slides.length - 1;
        } else {
            currentSlide = index;
        }

        // Ajouter la classe active sur la slide et le dot courants
        slides[currentSlide].classList.add("active");
        dots[currentSlide].classList.add("active");
    }

    // Fonctions de navigation
    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
    }

    // Démarrer la rotation automatique
    function startAutoSlide() {
        slideInterval = setInterval(nextSlide, 5000); // 5 secondes
    }

    // Réinitialiser le timer automatique sur action manuelle
    function resetAutoSlide() {
        clearInterval(slideInterval);
        startAutoSlide();
    }

    // Clics sur les boutons fléchés
    if (nextBtn) {
        nextBtn.addEventListener("click", function () {
            nextSlide();
            resetAutoSlide();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", function () {
            prevSlide();
            resetAutoSlide();
        });
    }

    // Clics sur les indicateurs (dots)
    dots.forEach((dot, index) => {
        dot.addEventListener("click", function () {
            showSlide(index);
            resetAutoSlide();
        });
    });

    // Démarrage initial
    startAutoSlide();
});