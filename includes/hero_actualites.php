<?php
/*
 * Hero de la page d'accueil : défilé plein écran des actualités récentes.
 *  - la photo de l'actualité occupe toute la largeur, le texte est posé dessus
 *  - en bas, un onglet par actualité permet de passer de l'une à l'autre
 * Inclus par accueil.php (qui fournit déjà la connexion $db).
 */
require_once __DIR__ . '/dates.php';

// 4 actualités les plus récentes, avec leur première photo (sous-requête)
$heroStmt = $db->query("
    SELECT a.id_actu, a.titre, a.date_publication, s.nom_struc, s.sigle,
           (SELECT p.Photos_actu_url
              FROM actualites_photos p
             WHERE p.id_actualites = a.id_actu
             ORDER BY p.Position
             LIMIT 1) AS photo
    FROM actualites a
    JOIN structure s ON a.id_structure = s.id_struc
    ORDER BY a.date_publication DESC, a.id_actu DESC
    LIMIT 4
");
$actualitesHero = $heroStmt->fetchAll(PDO::FETCH_ASSOC);

// Visuel de repli quand une actualité n'a pas de photo
$heroImageDefaut = '../../publique/image/GA.jpeg';
?>
<style>
.acc-hero { position: relative; min-height: clamp(460px, 72vh, 640px); overflow: hidden; background: var(--acc-nuit); color: #fff; isolation: isolate; }
.acc-hero-slide { position: absolute; inset: 0; display: flex; align-items: flex-end; opacity: 0; visibility: hidden; transition: opacity .7s ease, visibility .7s; }
.acc-hero-slide.active { opacity: 1; visibility: visible; }
.acc-hero-slide img { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; transform: scale(1.04); transition: transform 7s ease; }
.acc-hero-slide.active img { transform: scale(1); }
.acc-hero-slide::after { content: ""; position: absolute; inset: 0; z-index: -1; background: linear-gradient(90deg, rgba(6,18,38,.92) 0%, rgba(6,18,38,.68) 45%, rgba(6,18,38,.15) 100%), linear-gradient(0deg, rgba(6,18,38,.85) 0%, rgba(6,18,38,0) 45%); }
.acc-hero-contenu { width: 100%; padding-bottom: 8.5rem; }
.acc-hero-kicker { display: inline-flex; align-items: center; gap: .6rem; margin-bottom: 1rem; color: var(--acc-jaune); font-size: .76rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
.acc-hero-titre { max-width: 21ch; margin: 0 0 1.1rem; color: #fff; font-family: var(--font-title); font-size: clamp(2rem, 4.6vw, 3.6rem); font-weight: 600; line-height: 1.08; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; }
.acc-hero-source { margin: 0 0 1.8rem; color: rgba(255,255,255,.78); font-size: .95rem; }
.acc-hero-lien { display: inline-flex; align-items: center; gap: .6rem; padding: .85rem 1.4rem; border: 2px solid #fff; border-radius: 4px; color: #fff; font-weight: 700; font-size: .92rem; transition: background .2s ease, color .2s ease; }
.acc-hero-lien:hover { background: #fff; color: var(--acc-nuit); }
.acc-hero-lien span { transition: transform .2s ease; }
.acc-hero-lien:hover span { transform: translateX(4px); }

/* Onglets des actualités en bas du hero */
.acc-hero-onglets { position: absolute; left: 0; right: 0; bottom: 0; z-index: 3; border-top: 1px solid rgba(255,255,255,.18); background: rgba(6,18,38,.55); backdrop-filter: blur(6px); }
.acc-hero-onglets .container { display: grid; grid-template-columns: repeat(var(--nb-onglets, 4), 1fr); }
.acc-hero-onglet { position: relative; display: flex; gap: .8rem; padding: 1.1rem 1.1rem 1.2rem 0; border: 0; background: none; color: rgba(255,255,255,.62); font: inherit; text-align: left; cursor: pointer; transition: color .2s ease; }
.acc-hero-onglet + .acc-hero-onglet { padding-left: 1.1rem; border-left: 1px solid rgba(255,255,255,.14); }
.acc-hero-onglet:hover, .acc-hero-onglet.active { color: #fff; }
.acc-hero-onglet::before { content: ""; position: absolute; top: -1px; left: 0; width: 0; height: 3px; background: var(--acc-jaune); }
.acc-hero-onglet.active::before { animation: accProgression 6s linear forwards; }
.acc-hero.en-pause .acc-hero-onglet.active::before { animation-play-state: paused; }
.acc-hero-onglet-num { color: var(--acc-jaune); font-family: var(--font-title); font-size: 1.05rem; font-weight: 600; line-height: 1.3; }
.acc-hero-onglet-titre { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; font-size: .82rem; font-weight: 600; line-height: 1.4; }
@keyframes accProgression { from { width: 0; } to { width: 100%; } }

.acc-hero-vide { display: flex; align-items: center; }
.acc-hero-vide .acc-hero-contenu { padding: 5rem 0; }

@media (max-width: 767.98px) {
  .acc-hero-contenu { padding-bottom: 5.5rem; }
  .acc-hero-onglets .container { display: flex; gap: .5rem; justify-content: flex-start; padding-block: 1rem; }
  .acc-hero-onglet, .acc-hero-onglet + .acc-hero-onglet { width: 30px; height: 4px; padding: 0; border: 0; border-radius: 4px; background: rgba(255,255,255,.35); }
  .acc-hero-onglet.active { background: var(--acc-jaune); }
  .acc-hero-onglet::before, .acc-hero-onglet-num, .acc-hero-onglet-titre { display: none; }
}
@media (prefers-reduced-motion: reduce) {
  .acc-hero-slide, .acc-hero-slide img { transition: none; }
  .acc-hero-onglet.active::before { animation: none; width: 100%; }
}
</style>

<?php if (empty($actualitesHero)): ?>

<section class="acc-hero acc-hero-vide" aria-label="Bienvenue">
  <div class="acc-hero-slide active">
    <img src="<?= $heroImageDefaut ?>" alt="">
    <div class="container acc-hero-contenu">
      <span class="acc-hero-kicker">Ministère de la Réforme et des Relations avec les Institutions</span>
      <h1 class="acc-hero-titre">Plateforme de consultation des structures sous tutelle du MRRI</h1>
      <a href="catalogue.php" class="acc-hero-lien">Consulter les structures</a>
    </div>
  </div>
</section>

<?php else: ?>

<section class="acc-hero" aria-roledescription="carrousel" aria-label="Actualités à la une">
  <h1 class="visually-hidden">Ministère de la Réforme et des Relations avec les Institutions</h1>

  <?php foreach ($actualitesHero as $i => $a): ?>
    <article class="acc-hero-slide <?= $i === 0 ? 'active' : '' ?>" id="acc-slide-<?= $i ?>" aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
      <img src="<?= !empty($a['photo']) ? '../../uploads/photos/' . htmlspecialchars($a['photo']) : $heroImageDefaut ?>" alt="">
      <div class="container acc-hero-contenu">
        <span class="acc-hero-kicker">À la une · <?= htmlspecialchars(formaterDateFr($a['date_publication'])) ?></span>
        <h2 class="acc-hero-titre"><?= htmlspecialchars($a['titre']) ?></h2>
        <p class="acc-hero-source"><?= htmlspecialchars($a['nom_struc']) ?></p>
        <a href="actualite_detail.php?id=<?= (int) $a['id_actu'] ?>" class="acc-hero-lien" tabindex="<?= $i === 0 ? '0' : '-1' ?>">
          Lire l'actualité
        </a>
      </div>
    </article>
  <?php endforeach; ?>

  <?php if (count($actualitesHero) > 1): ?>
    <div class="acc-hero-onglets" style="--nb-onglets: <?= count($actualitesHero) ?>">
      <div class="container" role="group" aria-label="Choisir une actualité">
        <?php foreach ($actualitesHero as $i => $a): ?>
          <button type="button" class="acc-hero-onglet <?= $i === 0 ? 'active' : '' ?>" aria-controls="acc-slide-<?= $i ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>">
            <span class="acc-hero-onglet-num"><?= sprintf('%02d', $i + 1) ?></span>
            <span class="acc-hero-onglet-titre"><?= htmlspecialchars($a['titre']) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<script>
(function () {
    const hero    = document.querySelector('.acc-hero');
    const slides  = Array.from(document.querySelectorAll('.acc-hero-slide'));
    const onglets = Array.from(document.querySelectorAll('.acc-hero-onglet'));
    if (!hero || slides.length < 2) return;

    const DUREE = 6000;
    let courant = 0;
    let minuteur = null;

    function afficher(n) {
        slides[courant].classList.remove('active');
        slides[courant].setAttribute('aria-hidden', 'true');
        slides[courant].querySelector('.acc-hero-lien').tabIndex = -1;
        onglets[courant].classList.remove('active');
        onglets[courant].setAttribute('aria-current', 'false');

        courant = (n + slides.length) % slides.length;

        slides[courant].classList.add('active');
        slides[courant].setAttribute('aria-hidden', 'false');
        slides[courant].querySelector('.acc-hero-lien').tabIndex = 0;
        onglets[courant].setAttribute('aria-current', 'true');
        relancerProgression();
    }

    // Repart de zéro sur la barre de progression de l'onglet actif
    function relancerProgression() {
        onglets[courant].classList.remove('active');
        void onglets[courant].offsetWidth;
        onglets[courant].classList.add('active');
    }

    function demarrer() {
        // Pas de défilement automatique si l'utilisateur a demandé moins d'animations
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        arreter();
        hero.classList.remove('en-pause');
        relancerProgression();
        minuteur = setInterval(function () { afficher(courant + 1); }, DUREE);
    }

    function arreter() {
        if (minuteur) { clearInterval(minuteur); minuteur = null; }
        hero.classList.add('en-pause');
    }

    onglets.forEach(function (onglet, n) {
        onglet.addEventListener('click', function () { afficher(n); demarrer(); });
    });

    // Pause quand on survole ou qu'on navigue au clavier dans le hero
    hero.addEventListener('mouseenter', arreter);
    hero.addEventListener('mouseleave', demarrer);
    hero.addEventListener('focusin', arreter);
    hero.addEventListener('focusout', demarrer);

    demarrer();
})();
</script>

<?php endif; ?>
