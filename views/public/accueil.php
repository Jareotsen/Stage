<?php
session_start();
require '../../config/database.php';
require_once '../../includes/dates.php';

/* =========================================================
   ACTUALITÉS RÉCENTES (avec leur première photo)
   ========================================================= */
$actu = $db->query("
    SELECT a.id_actu, a.titre, a.contenu, a.date_publication, s.nom_struc,
           (SELECT p.Photos_actu_url
              FROM actualites_photos p
             WHERE p.id_actualites = a.id_actu
             ORDER BY p.Position
             LIMIT 1) AS photo
    FROM actualites a
    JOIN structure s ON a.id_structure = s.id_struc
    ORDER BY a.date_publication DESC, a.id_actu DESC
    LIMIT 3
");
$actualites = $actu->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   CATÉGORIES "FEUILLES" (raccourcis sous la recherche)
   Les catégories intermédiaires sans structure directe sont exclues.
   ========================================================= */
$categories = $db->query("
    SELECT id_cat, nom_cat
    FROM categories
    WHERE id_cat NOT IN (
        SELECT DISTINCT parent_id FROM categories WHERE parent_id IS NOT NULL
    )
    ORDER BY nom_cat
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   APERÇU DES STRUCTURES
   ========================================================= */
$structuresListe = $db->query("
    SELECT structure.id_struc, structure.nom_struc, structure.sigle,
           structure.adresse, categories.nom_cat
    FROM structure
    JOIN categories ON structure.id_cat = categories.id_cat
    ORDER BY structure.nom_struc ASC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   CHIFFRES CLÉS
   ========================================================= */
$totalStructures = (int) $db->query("SELECT COUNT(*) FROM structure")->fetchColumn();
$totalActualites = (int) $db->query("SELECT COUNT(*) FROM actualites")->fetchColumn();
$totalConsultations = (int) $db->query("
    SELECT COUNT(*) FROM consultations
    WHERE statut = 'ouverte' AND (date_fermeture IS NULL OR date_fermeture >= NOW())
")->fetchColumn();

function h(?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES, 'UTF-8');
}

// Les noms de catégories sont stockés en majuscules : on les rend plus lisibles.
function libelleCategorie(string $nom): string {
    return mb_convert_case(mb_strtolower($nom, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

require '../../includes/header.php';
?>
<style>
/* Composants communs (couleurs, titres, cartes) : publique/css/portail.css */

/* ---------- Recherche ---------- */
.acc-recherche { padding: 2.6rem 0; border-bottom: 1px solid var(--acc-ligne); background: #fff; }
.acc-recherche-grille { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.4fr); gap: 2.5rem; align-items: center; }
.acc-recherche h2 { margin: 0 0 .35rem; color: var(--acc-encre); font-family: var(--font-title); font-size: clamp(1.4rem, 2.4vw, 1.8rem); font-weight: 600; }
.acc-recherche-intro { margin: 0; color: var(--acc-gris); font-size: .92rem; }
.acc-recherche-form { position: relative; display: flex; }
.acc-raccourcis { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: .9rem; color: var(--acc-gris); font-size: .8rem; }
.acc-raccourcis a { padding: .35rem .75rem; border: 1px solid var(--acc-ligne); border-radius: 999px; color: var(--acc-encre); font-weight: 600; transition: border-color .2s ease, color .2s ease; }
.acc-raccourcis a:hover { border-color: var(--acc-vert); color: var(--acc-vert-fonce); }
.acc-resultats { position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 30; max-height: 360px; overflow-y: auto; padding: .6rem; border: 1px solid var(--acc-ligne); border-radius: 6px; background: #fff; box-shadow: 0 18px 40px rgba(10,26,51,.16); }
.acc-resultats:empty { display: none; }
.acc-resultats .border { border-color: var(--acc-ligne) !important; }

/* ---------- Mot du Ministre + chiffres clés ---------- */
/* Bandeau sombre en haut, carte de citation à cheval dessus, portrait rond qui déborde à gauche */
.acc-ministre { padding: 2.75rem 0 clamp(3.5rem, 7vw, 5rem); background: linear-gradient(var(--acc-nuit) 0 130px, #fff 130px); }
.acc-ministre-carte { position: relative; max-width: 1080px; margin: 0 auto; padding: 1.9rem 2.2rem 1.7rem 250px; min-height: 165px; border: 1px solid #D5DBE5; border-radius: 14px; background: #F9FAFB; box-shadow: 0 6px 18px rgba(10,26,51,.10); }
.acc-ministre-photo { position: absolute; top: 50%; left: 30px; width: 200px; height: 200px; margin: 0; border-radius: 50%; overflow: hidden; background: #E4E7EC; box-shadow: 0 10px 28px rgba(10,26,51,.22); transform: translateY(-50%); }
.acc-ministre-photo img { width: 100%; height: 100%; object-fit: cover; object-position: top; }
.acc-citation { margin: 0; }
.acc-citation blockquote { margin: 0; color: var(--acc-encre); font-size: clamp(1rem, 1.6vw, 1.15rem); font-weight: 600; line-height: 1.55; }
.acc-guillemet { color: var(--acc-vert); font-family: Georgia, serif; font-size: 1.9em; font-weight: 700; line-height: 0; vertical-align: -.3em; }
.acc-guillemet.ouvrant { margin-right: .3rem; }
.acc-guillemet.fermant { margin-left: .2rem; }
.acc-citation-trait { width: 50px; height: 2px; margin: 1rem 0 .9rem; background: var(--acc-vert); }
.acc-citation figcaption { color: var(--acc-encre); font-size: .88rem; font-weight: 600; line-height: 1.5; }
.acc-citation figcaption span { display: block; color: var(--acc-gris); font-weight: 500; }
@media (max-width: 767.98px) {
  .acc-ministre { background: linear-gradient(var(--acc-nuit) 0 110px, #fff 110px); padding-top: 1.5rem; }
  .acc-ministre-carte { padding: 120px 1.4rem 1.5rem; text-align: center; }
  .acc-ministre-photo { top: -90px; left: 50%; width: 180px; height: 180px; transform: translateX(-50%); }
  .acc-citation-trait { margin-inline: auto; }
  .acc-ministre-carte { margin-top: 90px; }
}
.acc-chiffres { display: grid; grid-template-columns: repeat(3, 1fr); max-width: 980px; margin: 2.8rem auto 0; border-top: 1px solid var(--acc-ligne); }
.acc-chiffre { padding: 1.4rem 1rem 0 0; }
.acc-chiffre + .acc-chiffre { padding-left: 1.2rem; border-left: 1px solid var(--acc-ligne); }
.acc-chiffre strong { display: block; color: var(--acc-encre); font-family: var(--font-title); font-size: clamp(2rem, 3.6vw, 2.8rem); font-weight: 600; line-height: 1; }
.acc-chiffre span { display: block; margin-top: .45rem; color: var(--acc-gris); font-size: .84rem; }

/* ---------- Accès rapides ---------- */
.acc-acces { background: var(--acc-fond); }
.acc-acces-grille { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
.acc-tuile { position: relative; display: flex; flex-direction: column; min-height: 210px; padding: 1.6rem 1.5rem 1.4rem; border: 1px solid var(--acc-ligne); border-radius: 4px; background: #fff; color: var(--acc-encre); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.acc-tuile:hover { transform: translateY(-4px); border-color: #C8D2E1; box-shadow: 0 16px 32px rgba(10,26,51,.09); color: var(--acc-encre); }
.acc-tuile-icone { display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; margin-bottom: 1.2rem; border-radius: 50%; background: #E8F0FA; color: var(--acc-bleu-fonce); transition: background .2s ease, color .2s ease; }
.acc-tuile:hover .acc-tuile-icone { background: var(--acc-bleu); color: #fff; }
.acc-tuile h3 { margin: 0 0 .45rem; font-family: var(--font-title); font-size: 1.2rem; font-weight: 600; }
.acc-tuile p { flex: 1; margin: 0 0 1.1rem; color: var(--acc-gris); font-size: .86rem; line-height: 1.55; }
.acc-tuile-fleche { display: inline-flex; align-items: center; gap: .4rem; color: var(--acc-bleu-fonce); font-size: .85rem; font-weight: 700; }
.acc-tuile-fleche span { transition: transform .2s ease; }
.acc-tuile:hover .acc-tuile-fleche span { transform: translateX(4px); }
.acc-tuile-badge { position: absolute; top: 1.4rem; right: 1.3rem; padding: .25rem .6rem; border-radius: 999px; background: #E3F5EC; color: var(--acc-vert-fonce); font-size: .72rem; font-weight: 800; }

/* ---------- Actualités : une à la une + deux secondaires ---------- */
.acc-actus { background: var(--acc-fond); }
.acc-actus-grille { display: grid; grid-template-columns: minmax(0, 7fr) minmax(0, 5fr); gap: 1.75rem; }
.acc-actus-cote { display: grid; gap: 1.75rem; align-content: start; }

/* ---------- Appel à participation ---------- */
.acc-appel { position: relative; overflow: hidden; padding: clamp(3rem, 6vw, 4.5rem) 0; background: var(--acc-nuit); color: #fff; }
.acc-appel::before { content: ""; position: absolute; top: -140px; right: -100px; width: 420px; height: 420px; border: 1px solid rgba(255,255,255,.08); border-radius: 50%; }
.acc-appel-grille { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 2rem; }
.acc-appel .acc-surtitre { color: var(--acc-jaune); }
.acc-appel h2 { max-width: 24ch; margin: 0 0 .6rem; color: #fff; font-family: var(--font-title); font-size: clamp(1.6rem, 3vw, 2.3rem); font-weight: 600; line-height: 1.2; }
.acc-appel p { max-width: 56ch; margin: 0; color: rgba(255,255,255,.75); }
.acc-bouton-clair { display: inline-flex; flex-shrink: 0; align-items: center; gap: .6rem; padding: .95rem 1.5rem; border-radius: 4px; background: var(--acc-jaune); color: var(--acc-nuit); font-weight: 800; transition: background .2s ease, transform .2s ease; }
.acc-bouton-clair:hover { background: #fff; color: var(--acc-nuit); transform: translateY(-2px); }


@media (max-width: 991.98px) {
  .acc-recherche-grille, .acc-actus-grille { grid-template-columns: 1fr; }
  .acc-acces-grille { grid-template-columns: repeat(2, 1fr); }
  .acc-appel-grille { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 575.98px) {
  .acc-acces-grille { grid-template-columns: 1fr; }
  .acc-tuile { min-height: 0; }
  .acc-chiffre + .acc-chiffre { padding-left: .8rem; }
  .acc-chiffre span { font-size: .76rem; }
}
@media (prefers-reduced-motion: reduce) {
  .acc-tuile, .acc-bouton-clair { transition: none; }
}
</style>

<?php require '../../includes/hero_actualites.php'; ?>

<!-- =========================================================
     RECHERCHE
     ========================================================= -->
<section class="acc-recherche" aria-labelledby="titre-recherche">
  <div class="container acc-recherche-grille">
    <div>
      <h2 id="titre-recherche">Rechercher une information</h2>
      <p class="acc-recherche-intro">Trouvez une institution par son nom, son sigle ou un mot-clé.</p>
    </div>
    <div>
      <form action="catalogue.php" method="get" class="acc-recherche-form acc-champ" role="search">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <label for="search" class="visually-hidden">Rechercher une structure</label>
        <input id="search" type="text" name="q" placeholder="Nom de la structure, sigle, mot-clé…" autocomplete="off" aria-controls="results">
        <button type="submit">Rechercher</button>
        <div id="results" class="acc-resultats" aria-live="polite"></div>
      </form>
      <div class="acc-raccourcis">
        <span>Parcourir :</span>
        <?php foreach ($categories as $cat): ?>
          <a href="catalogue.php?cat=<?= (int) $cat['id_cat'] ?>"><?= h(libelleCategorie($cat['nom_cat'])) ?></a>
        <?php endforeach; ?>
        <a href="catalogue.php">Filtres avancés</a>
      </div>
    </div>
  </div>
</section>

<!-- =========================================================
     MOT DU MINISTRE + CHIFFRES CLÉS
     ========================================================= -->
<section class="acc-ministre" aria-labelledby="titre-ministre">
  <div class="container">
    <h2 class="visually-hidden" id="titre-ministre">Le mot du Ministre</h2>
    <div class="acc-ministre-carte">
      <figure class="acc-ministre-photo">
        <img src="../../publique/image/minis2.jpeg" alt="Aimé Martial Massamba, Ministre de la Réforme et des Relations avec les Institutions">
      </figure>
      <figure class="acc-citation">
        <blockquote><span class="acc-guillemet ouvrant" aria-hidden="true">“</span>Les réformes sont un processus continu, pas une fin en soi.<span class="acc-guillemet fermant" aria-hidden="true">”</span></blockquote>
        <div class="acc-citation-trait" aria-hidden="true"></div>
        <figcaption>
          Aimé Martial Massamba, Ministre de la Réforme et des Relations avec les Institutions
          <span>11 octobre 2026</span>
        </figcaption>
      </figure>
    </div>

    <div class="acc-chiffres">
      <div class="acc-chiffre"><strong><?= $totalStructures ?></strong><span>Institutions et A.A.I référencées</span></div>
      <div class="acc-chiffre"><strong><?= $totalActualites ?></strong><span>Actualités publiées</span></div>
      <div class="acc-chiffre"><strong><?= $totalConsultations ?></strong><span>Consultation(s) ouverte(s)</span></div>
    </div>
  </div>
</section>

<!-- =========================================================
     ACCÈS RAPIDES
     ========================================================= -->
<section class="acc-section acc-acces" aria-labelledby="titre-acces">
  <div class="container">
    <div class="acc-entete">
      <div>
        <span class="acc-surtitre">Accès rapides</span>
        <h2 id="titre-acces">Ce que vous pouvez faire ici</h2>
      </div>
    </div>
    <div class="acc-acces-grille">
      <a href="catalogue.php" class="acc-tuile">
        <span class="acc-tuile-icone" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 5l9 4.5z"/><path d="M6 12v6M10 12v6M14 12v6M18 12v6M4.5 12h15M4 18h16M3 21h18"/></svg>
        </span>
        <h3>I.C &amp; A.A.I</h3>
        <p>Le répertoire des Institutions constitutionnelles et des Autorités administratives indépendantes.</p>
        <span class="acc-tuile-fleche">Explorer les institutions</span>
      </a>
      <a href="consultations.php" class="acc-tuile">
        <?php if ($totalConsultations > 0): ?><span class="acc-tuile-badge"><?= $totalConsultations ?> ouverte<?= $totalConsultations > 1 ? 's' : '' ?></span><?php endif; ?>
        <span class="acc-tuile-icone" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4h1.5A1.5 1.5 0 0 1 18 5.5V10"/><path d="M9 4H7.5A1.5 1.5 0 0 0 6 5.5v14A1.5 1.5 0 0 0 7.5 21H12"/><rect x="9" y="2.5" width="6" height="3" rx="1"/><path d="m8.5 10 1 1 2-2M13 10h2m-6.5 4.5 1 1 2-2"/><path d="M15 13.5h5.5a1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1H18l-2 2v-2h-1a1 1 0 0 1-1-1v-3.5a1 1 0 0 1 1-1z"/></svg>
        </span>
        <h3>Consultations</h3>
        <p>Donnez votre avis sur les projets soumis à la participation citoyenne.</p>
        <span class="acc-tuile-fleche">Participer</span>
      </a>
      <a href="actualite.php" class="acc-tuile">
        <span class="acc-tuile-icone" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h11A1.5 1.5 0 0 1 18 5.5V19a2 2 0 0 0 2 2H6a2 2 0 0 1-2-2z"/><path d="M18 9h2v10a2 2 0 0 1-2 2M8 8h6M8 12h6M8 16h3"/></svg>
        </span>
        <h3>Actualités</h3>
        <p>Les dernières informations publiées par les structures sous tutelle.</p>
        <span class="acc-tuile-fleche">Découvrir les actualités</span>
      </a>
      <a href="https://relations-institutions.gouv.ga/" class="acc-tuile" target="_blank" rel="noopener">
        <span class="acc-tuile-icone" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5.5"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg>
        </span>
        <h3>À propos du MRRI</h3>
        <p>Missions et organisation du Ministère, sur son site officiel.</p>
        <span class="acc-tuile-fleche">En savoir plus <span aria-hidden="true">↗</span></span>
      </a>
    </div>
  </div>
</section>

<!-- =========================================================
     STRUCTURES
     ========================================================= -->
<section class="acc-section" aria-labelledby="titre-structures">
  <div class="container">
    <div class="acc-entete">
      <div>
        <span class="acc-surtitre">Répertoire</span>
        <h2 id="titre-structures">Institutions constitutionnelles et A.A.I</h2>
        <p>Explorez les structures enregistrées dans la plateforme.</p>
      </div>
      <a href="catalogue.php" class="acc-lien-fleche">Voir le catalogue</a>
    </div>

    <?php if (empty($structuresListe)): ?>
      <div class="acc-vide">Aucune structure trouvée.</div>
    <?php else: ?>
      <div class="acc-structures-grille">
        <?php foreach ($structuresListe as $s): ?>
          <a href="fiche.php?id=<?= (int) $s['id_struc'] ?>" class="acc-structure">
            <div class="acc-structure-tete">
              <span class="acc-structure-icone" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.5v3"/><path d="M12 2.5h3l-.75 1 .75 1h-3"/><path d="M3 9.5 12 5.5l9 4z"/><path d="M4 9.5h16"/><path d="M6 12v6M10 12v6M14 12v6M18 12v6"/><path d="M4.5 12h15M4 18h16M3 21h18"/></svg>
              </span>
              <div>
                <?php if (!empty($s['sigle'])): ?><div class="acc-structure-sigle"><?= h($s['sigle']) ?></div><?php endif; ?>
                <div class="acc-structure-cat"><?= h(libelleCategorie($s['nom_cat'])) ?></div>
              </div>
            </div>
            <h3><?= h($s['nom_struc']) ?></h3>
            <p class="acc-structure-adresse">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span><?= h($s['adresse'] ?: 'Adresse non renseignée') ?></span>
            </p>
            <span class="acc-fleche">Voir la fiche</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- =========================================================
     ACTUALITÉS
     ========================================================= -->
<section class="acc-section acc-actus" aria-labelledby="titre-actus">
  <div class="container">
    <div class="acc-entete">
      <div>
        <span class="acc-surtitre">Actualités</span>
        <h2 id="titre-actus">Actualités récentes</h2>
        <p>Les dernières informations publiées sur la plateforme.</p>
      </div>
      <a href="actualite.php" class="acc-lien-fleche">Toutes les actualités</a>
    </div>

    <?php if (empty($actualites)): ?>
      <div class="acc-vide">Aucune actualité publiée pour le moment.</div>
    <?php else: ?>
      <?php
        // Rendu d'une carte d'actualité ; la première est mise en avant
        $carteActu = function (array $a, string $variante) {
            $image = !empty($a['photo']) ? '../../uploads/photos/' . h($a['photo']) : '../../publique/image/GA.jpeg';
            ?>
            <a href="actualite_detail.php?id=<?= (int) $a['id_actu'] ?>" class="acc-actu <?= $variante ?>">
              <div class="acc-actu-media"><img src="<?= $image ?>" alt="" loading="lazy"></div>
              <div class="acc-actu-corps">
                <div class="acc-actu-meta">
                  <span class="acc-actu-source"><?= h($a['nom_struc']) ?></span>
                  <span class="acc-actu-date"><?= h(formaterDateFr($a['date_publication'])) ?></span>
                </div>
                <h3><?= h($a['titre']) ?></h3>
                <?php if ($variante === 'une' && !empty($a['contenu'])): ?>
                  <p class="acc-actu-extrait"><?= h(mb_strimwidth(strip_tags($a['contenu']), 0, 220, '…')) ?></p>
                <?php endif; ?>
              </div>
            </a>
            <?php
        };
      ?>
      <div class="acc-actus-grille">
        <?php $carteActu($actualites[0], 'une'); ?>
        <?php if (count($actualites) > 1): ?>
          <div class="acc-actus-cote">
            <?php foreach (array_slice($actualites, 1) as $a): ?>
              <?php $carteActu($a, 'secondaire'); ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- =========================================================
     APPEL À PARTICIPATION
     ========================================================= -->
<section class="acc-appel" aria-labelledby="titre-appel">
  <div class="container acc-appel-grille">
    <div>
      <span class="acc-surtitre">Participation citoyenne</span>
      <h2 id="titre-appel">Votre avis compte dans la réforme de l'action publique</h2>
      <p>
        <?= $totalConsultations > 0
            ? $totalConsultations . ' consultation' . ($totalConsultations > 1 ? 's sont ouvertes' : ' est ouverte') . ' en ce moment. Prenez part aux échanges avant leur clôture.'
            : 'Aucune consultation n\'est ouverte pour le moment. Revenez bientôt pour participer.' ?>
      </p>
    </div>
    <a href="consultations.php" class="acc-bouton-clair">Voir les consultations</a>
  </div>
</section>

<?php require '../../includes/footer.php'; ?>

<!-- =========================================================
     RECHERCHE DYNAMIQUE
     ========================================================= -->
<script>
(function () {
    const searchInput = document.getElementById('search');
    const results = document.getElementById('results');
    if (!searchInput || !results) return;

    let delai = null;

    searchInput.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(delai);

        if (q.length < 1) {
            results.innerHTML = '';
            return;
        }

        // Attend une courte pause dans la frappe avant d'interroger le serveur
        delai = setTimeout(function () {
            fetch('search_structures.php?q=' + encodeURIComponent(q))
                .then(response => response.text())
                .then(data => { results.innerHTML = data; })
                .catch(error => console.error('Erreur de recherche :', error));
        }, 200);
    });

    // Ferme la liste avec Échap ou en cliquant ailleurs
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') results.innerHTML = '';
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.acc-recherche-form')) results.innerHTML = '';
    });
})();
</script>
