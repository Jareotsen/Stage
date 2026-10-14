<?php
/*
 * Hero de la page d'accueil : défilé des actualités récentes.
 *  - à gauche : le titre de l'actualité et un lien vers sa page
 *  - à droite : sa première photo (ou un visuel de remplacement)
 * Inclus par accueil.php (qui fournit déjà la connexion $db).
 * Les classes .mrri-hero, .mrri-hero-grid, .mrri-hero-texte, .mrri-hero-image-col,
 * .mrri-hero-accent et .mrri-hero-tagline restent définies dans accueil.php.
 */
require_once __DIR__ . '/dates.php';

// 5 actualités les plus récentes, avec leur première photo (sous-requête)
$heroStmt = $db->query("
    SELECT a.id_actu, a.titre, a.date_publication, s.nom_struc,
           (SELECT p.Photos_actu_url
              FROM actualites_photos p
             WHERE p.id_actualites = a.id_actu
             ORDER BY p.Position
             LIMIT 1) AS photo
    FROM actualites a
    JOIN structure s ON a.id_structure = s.id_struc
    ORDER BY a.date_publication DESC, a.id_actu DESC
    LIMIT 5
");
$actualitesHero = $heroStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    .hero-slide { display: none; }
    .hero-slide.active { display: block; animation: heroFondu .6s ease; }
    @keyframes heroFondu { from { opacity: 0; } to { opacity: 1; } }

    .hero-kicker { display: inline-block; margin-bottom: .9rem; color: var(--mrri-or-light, #F7EACB); font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .hero-titre { color: #fff; font-size: clamp(1.7rem, 3.4vw, 2.5rem); line-height: 1.2; margin-bottom: 1.6rem;
                  display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 4; line-clamp: 4; overflow: hidden; }
    .hero-lien { align-self: flex-start; display: inline-flex; align-items: center; gap: 8px; padding: .7rem 1.3rem; border-radius: 999px; background: var(--mrri-or, #C9972C); color: #fff; font-weight: 700; font-size: .9rem; }
    .hero-lien:hover { background: #fff; color: var(--mrri-bleu-nuit, #0B1D4D); }
    .hero-placeholder { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 4rem; background: linear-gradient(135deg, var(--mrri-bleu, #1B3FAE), var(--mrri-bleu-nuit, #0B1D4D)); }

    .hero-dots { position: absolute; left: 3rem; bottom: 1.5rem; z-index: 5; display: flex; gap: 8px; }
    .hero-dot { width: 11px; height: 11px; padding: 0; border: none; border-radius: 50%; background: rgba(255,255,255,.4); cursor: pointer; transition: width .2s ease, background .2s ease; }
    .hero-dot.active { width: 28px; border-radius: 6px; background: var(--mrri-or, #C9972C); }

    @media (max-width: 900px) {
        .hero-dots { left: 1.5rem; }
        .mrri-hero-texte { padding: 3rem 1.5rem; }
    }
</style>

<section class="mrri-hero" aria-label="Actualités à la une">

<?php if (empty($actualitesHero)): ?>

    <!-- Aucune actualité publiée : message d'accueil par défaut -->
    <div class="mrri-hero-grid" style="grid-template-columns: 1fr;">
        <div class="mrri-hero-texte">
            <div class="mrri-hero-accent"></div>
            <h1 class="hero-titre">Plateforme de consultation des structures sous tutelle du MRRI</h1>
            <a href="catalogue.php" class="hero-lien">Consulter les structures <span>→</span></a>
        </div>
    </div>

<?php else: ?>

    <?php foreach ($actualitesHero as $i => $a): ?>
        <article class="hero-slide <?= $i === 0 ? 'active' : '' ?>" aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
            <div class="mrri-hero-grid">

                <div class="mrri-hero-texte">
                    <div class="mrri-hero-accent"></div>
                    <span class="hero-kicker">
                        À la une · <?= htmlspecialchars(formaterDateFr($a['date_publication'])) ?>
                    </span>
                    <h2 class="hero-titre"><?= htmlspecialchars($a['titre']) ?></h2>
                    <a href="actualite_detail.php?id=<?= (int) $a['id_actu'] ?>" class="hero-lien">
                        Lire l'actualité <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="mrri-hero-image-col">
                    <?php if (!empty($a['photo'])): ?>
                        <img src="../../uploads/photos/<?= htmlspecialchars($a['photo']) ?>"
                             alt="<?= htmlspecialchars($a['titre']) ?>">
                    <?php else: ?>
                        <div class="hero-placeholder" aria-hidden="true">📰</div>
                    <?php endif; ?>
                    <div class="mrri-hero-tagline"><?= htmlspecialchars($a['nom_struc']) ?></div>
                </div>

            </div>
        </article>
    <?php endforeach; ?>

    <?php if (count($actualitesHero) > 1): ?>
        <div class="hero-dots" role="group" aria-label="Choisir une actualité">
            <?php foreach ($actualitesHero as $i => $a): ?>
                <button type="button" class="hero-dot <?= $i === 0 ? 'active' : '' ?>"
                        aria-label="Actualité <?= $i + 1 ?> sur <?= count($actualitesHero) ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

</section>

<script>
(function () {
    const hero   = document.querySelector('.mrri-hero');
    const slides = Array.from(document.querySelectorAll('.hero-slide'));
    const dots   = Array.from(document.querySelectorAll('.hero-dot'));
    if (!hero || slides.length < 2) return;

    let courant = 0;
    let minuteur = null;

    function afficher(n) {
        slides[courant].classList.remove('active');
        slides[courant].setAttribute('aria-hidden', 'true');
        dots[courant].classList.remove('active');

        courant = (n + slides.length) % slides.length;

        slides[courant].classList.add('active');
        slides[courant].setAttribute('aria-hidden', 'false');
        dots[courant].classList.add('active');
    }

    function demarrer() {
        // Pas de défilement automatique si l'utilisateur a demandé moins d'animations
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        arreter();
        minuteur = setInterval(function () { afficher(courant + 1); }, 6000);
    }

    function arreter() {
        if (minuteur) { clearInterval(minuteur); minuteur = null; }
    }

    dots.forEach(function (point, n) {
        point.addEventListener('click', function () { afficher(n); demarrer(); });
    });

    // Pause quand on survole ou qu'on navigue au clavier dans le hero
    hero.addEventListener('mouseenter', arreter);
    hero.addEventListener('mouseleave', demarrer);
    hero.addEventListener('focusin', arreter);
    hero.addEventListener('focusout', demarrer);

    demarrer();
})();
</script>