<script src="../../publique/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../publique/vendor/fonts/fonts.js"></script>
<script>
// Menu latéral sur téléphone : ouverture par le bouton, fermeture par le bouton,
// le fond sombre, la touche Échap ou le choix d'un lien
(function () {
    const bouton = document.querySelector('.mrri-menu-btn');
    const menu = document.getElementById('mrri-sidebar');
    const fond = document.querySelector('.mrri-sidebar-overlay');
    if (!bouton || !menu || !fond) {
        return;
    }

    function basculer(ouvrir) {
        menu.classList.toggle('show', ouvrir);
        fond.hidden = !ouvrir;
        document.body.classList.toggle('menu-ouvert', ouvrir);
        bouton.setAttribute('aria-expanded', ouvrir ? 'true' : 'false');
        bouton.setAttribute('aria-label', ouvrir ? 'Fermer le menu' : 'Ouvrir le menu');
    }

    bouton.addEventListener('click', function () {
        basculer(!menu.classList.contains('show'));
    });
    fond.addEventListener('click', function () {
        basculer(false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && menu.classList.contains('show')) {
            basculer(false);
            bouton.focus();
        }
    });
    menu.querySelectorAll('a').forEach(function (lien) {
        lien.addEventListener('click', function () {
            basculer(false);
        });
    });
    // Retour sur grand écran : le menu redevient fixe, on retire l'état « ouvert »
    window.matchMedia('(min-width: 769px)').addEventListener('change', function (e) {
        if (e.matches) {
            basculer(false);
        }
    });
})();
</script>

</body>
</html>