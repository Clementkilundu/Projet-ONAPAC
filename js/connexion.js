// js/connexion.js

document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("loginForm");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("mot_de_passe");
    const togglePasswordBtn = document.getElementById("togglePassword");
    const eyeIcon = document.getElementById("eyeIcon");
    const jsAlert = document.getElementById("js-alert");
    const jsAlertText = document.getElementById("js-alert-text");
    const phpAlert = document.getElementById("php-alert");

    // 1. Gestion du masquage/affichage du mot de passe
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener("click", function () {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        });
    }

    // 2. Validation dynamique avant soumission
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            let errors = [];
            const emailValue = emailInput.value.trim();
            const passwordValue = passwordInput.value;

            // Masquer l'ancienne alerte PHP si l'utilisateur retente une soumission
            if (phpAlert) {
                phpAlert.classList.add("hidden");
            }

            // Validation de l'email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailValue === "") {
                errors.push("L'adresse email est requise.");
            } else if (!emailRegex.test(emailValue)) {
                errors.push("Le format de l'adresse email n'est pas valide.");
            }

            // Validation du mot de passe
            if (passwordValue === "") {
                errors.push("Le mot de passe est requis.");
            }

            // S'il y a des erreurs, on bloque l'envoi et on affiche l'alerte JS
            if (errors.length > 0) {
                e.preventDefault();
                jsAlertText.textContent = errors.join(" ");
                jsAlert.classList.remove("hidden");
                
                // Animation douce d'apparition de l'erreur
                jsAlert.style.opacity = "0";
                jsAlert.style.transition = "opacity 0.3s ease";
                setTimeout(() => {
                    jsAlert.style.opacity = "1";
                }, 50);
            } else {
                jsAlert.classList.add("hidden");
            }
        });
    }
});