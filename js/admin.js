function switchAdminTab(tabName) {
    // 1. Récupérer tous les conteneurs d'onglets et les boutons de la sidebar
    const tabs = document.querySelectorAll('.admin-tab-content');
    const buttons = document.querySelectorAll('.admin-menu-btn');

    // 2. Masquer tous les onglets
    tabs.forEach(tab => {
        tab.classList.remove('active');
    });

    // 3. Désactiver tous les boutons
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });

    // 4. Activer l'onglet ciblé
    const selectedTab = document.getElementById('admin-tab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    // 5. Mettre le bouton correspondant en surbrillance active
    buttons.forEach(btn => {
        if (btn.getAttribute('onclick').includes(tabName)) {
            btn.classList.add('active');
        }
    });
}