<?php
session_start();
require '../../config/database.php';

// 1. Sans identifiant (lien « Localisation » du menu) : carte de toutes les structures.
//    Avec un identifiant invalide : retour au catalogue.
$idBrut = $_GET['id'] ?? null;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idBrut !== null && !$id) {
    header('Location: catalogue.php');
    exit;
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

function h(?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES, 'UTF-8');
}

/* Styles des cartes, communs aux deux affichages */
function stylesCarte(): void { ?>
<style>
/* Localisation : les composants communs sont dans publique/css/portail.css */
.loc-carte { width: 100%; height: 640px; border: 1px solid var(--acc-ligne); border-radius: 4px; background: var(--acc-fond); z-index: 1; }
.loc-repere { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border: 3px solid #fff; border-radius: 50% 50% 50% 0; background: var(--acc-nuit); box-shadow: 0 4px 12px rgba(10,26,51,.35); transform: rotate(-45deg); }
.loc-repere span { color: #fff; font-size: .72rem; font-weight: 800; transform: rotate(45deg); }
.loc-repere.actif { background: var(--acc-vert); }
.leaflet-popup-content { margin: .9rem 1rem; color: var(--acc-encre); font-family: var(--font-body); font-size: .85rem; line-height: 1.5; }
.leaflet-popup-content strong { display: block; font-family: var(--font-title); font-size: .98rem; font-weight: 600; }
.leaflet-popup-content a { color: var(--acc-bleu-fonce); font-weight: 700; }
.leaflet-popup-content hr { margin: .6rem 0; border-color: var(--acc-ligne); opacity: 1; }
.leaflet-popup-content-wrapper { border-radius: 4px; }
@media (max-width: 991.98px) { .loc-carte { height: 460px; } }
</style>
<?php }

/* Script Leaflet + repère personnalisé (pastille bleu nuit numérotée, verte quand elle est active) */
function scriptsCarte(): void { ?>
<script src="../../publique/vendor/leaflet/leaflet.js"></script>
<script>
function iconeRepere(texte, actif) {
    const span = document.createElement('span');
    span.textContent = texte;
    return L.divIcon({
        className: '',
        html: '<div class="loc-repere' + (actif ? ' actif' : '') + '">' + span.outerHTML + '</div>',
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -32]
    });
}
function fondDeCarte(carte) {
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(carte);
    // Le zoom à la molette ne s'active qu'après un clic sur la carte (évite de bloquer le défilement)
    carte.on('click', function () { carte.scrollWheelZoom.enable(); });
}
</script>
<?php }

if ($idBrut === null) {
    $marqueurs = [];
    $sansPosition = [];
    $toutesStmt = $db->query("SELECT id_struc, nom_struc, sigle, adresse, latitude, longitude FROM structure ORDER BY nom_struc");
    foreach ($toutesStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $latS = is_numeric($s['latitude']) ? (float) $s['latitude'] : null;
        $lngS = is_numeric($s['longitude']) ? (float) $s['longitude'] : null;
        if ($latS === null || $lngS === null || $latS < -90 || $latS > 90 || $lngS < -180 || $lngS > 180) {
            $sansPosition[] = $s;
            continue;
        }
        $marqueurs[] = [
            'id' => (int) $s['id_struc'],
            'nom' => (string) $s['nom_struc'],
            'sigle' => (string) ($s['sigle'] ?? ''),
            'adresse' => (string) ($s['adresse'] ?? ''),
            'lat' => $latS,
            'lng' => $lngS,
        ];
    }

    require '../../includes/header.php';
    stylesCarte();
    ?>
    <style>
    .loc-grille { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 1.5rem; align-items: start; }
    .loc-panneau { display: flex; flex-direction: column; height: 640px; border: 1px solid var(--acc-ligne); border-radius: 4px; background: #fff; }
    .loc-panneau-tete { padding: 1.1rem 1.1rem 1rem; border-bottom: 1px solid var(--acc-ligne); }
    .loc-panneau-tete h2 { margin: 0 0 .8rem; color: var(--acc-encre); font-size: .8rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .loc-panneau-tete .acc-champ input { height: 44px; border-right: 2px solid var(--acc-encre); border-radius: 4px; font-size: .88rem; }
    .loc-panneau-tete .acc-champ input:focus { border-color: var(--acc-bleu); }
    .loc-liste { flex: 1; margin: 0; padding: 0; overflow-y: auto; list-style: none; }
    .loc-liste li + li { border-top: 1px solid var(--acc-ligne); }
    .loc-item { display: flex; gap: .8rem; width: 100%; padding: .85rem 1.1rem; border: 0; border-left: 3px solid transparent; background: none; color: var(--acc-encre); font: inherit; text-align: left; cursor: pointer; transition: background .2s ease; }
    .loc-item:hover { background: var(--acc-fond); }
    .loc-item.actif { border-left-color: var(--acc-vert); background: #E9F6EF; }
    .loc-item-num { display: flex; flex: 0 0 26px; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: var(--acc-nuit); color: #fff; font-size: .7rem; font-weight: 800; }
    .loc-item.actif .loc-item-num { background: var(--acc-vert); }
    .loc-item-sigle { display: block; color: var(--acc-vert-fonce); font-size: .7rem; font-weight: 800; letter-spacing: .08em; }
    .loc-item-nom { display: block; font-size: .86rem; font-weight: 600; line-height: 1.35; }
    .loc-item-adresse { display: block; margin-top: .15rem; color: var(--acc-gris); font-size: .76rem; line-height: 1.4; }
    .loc-aucun { margin: 0; padding: 1.2rem 1.1rem; color: var(--acc-gris); font-size: .86rem; font-style: italic; }
    .loc-sans-position { margin: 0; padding: .9rem 1.1rem; border-top: 1px solid var(--acc-ligne); background: var(--acc-fond); color: var(--acc-gris); font-size: .78rem; line-height: 1.5; }
    .loc-sans-position a { color: var(--acc-bleu-fonce); font-weight: 700; }
    @media (max-width: 991.98px) {
      .loc-grille { grid-template-columns: 1fr; }
      .loc-panneau { order: 2; height: auto; max-height: 520px; }
    }
    </style>

    <header class="acc-bandeau">
      <img class="acc-bandeau-image" src="../../publique/image/GA.jpeg" alt="">
      <div class="container">
        <ol class="acc-fil" aria-label="Fil d'Ariane">
          <li><a href="accueil.php">Accueil</a></li>
          <li aria-current="page">Localisation</li>
        </ol>
        <span class="acc-surtitre">Carte des institutions</span>
        <h1>Localisation des structures</h1>
        <p><?= count($marqueurs) ?> structure<?= count($marqueurs) > 1 ? 's' : '' ?> positionnée<?= count($marqueurs) > 1 ? 's' : '' ?> sur la carte de Libreville et ses environs. Sélectionnez-en une pour la situer et accéder à sa fiche.</p>
      </div>
    </header>

    <main class="acc-section">
      <div class="container">
        <?php if (empty($marqueurs)): ?>
          <div class="acc-vide">
            <h2>Aucune position renseignée</h2>
            <p>Aucune structure n'a encore de position géographique. <a href="catalogue.php">Voir le catalogue</a></p>
          </div>
        <?php else: ?>
          <div class="loc-grille">
            <aside class="loc-panneau" aria-label="Liste des structures">
              <div class="loc-panneau-tete">
                <h2>Structures</h2>
                <div class="acc-champ">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                  <label for="loc-filtre" class="visually-hidden">Filtrer les structures</label>
                  <input type="search" id="loc-filtre" placeholder="Filtrer par nom ou sigle…" autocomplete="off">
                </div>
              </div>
              <ul class="loc-liste" id="loc-liste"></ul>
              <p class="loc-aucun" id="loc-aucun" hidden>Aucune structure ne correspond.</p>
              <?php if ($sansPosition): ?>
                <p class="loc-sans-position">
                  Sans position renseignée :
                  <?php foreach ($sansPosition as $i => $s): ?><?= $i ? ', ' : '' ?><a href="fiche.php?id=<?= (int) $s['id_struc'] ?>"><?= h($s['sigle'] ?: $s['nom_struc']) ?></a><?php endforeach; ?>.
                </p>
              <?php endif; ?>
            </aside>

            <div id="carte-structures" class="loc-carte" role="region" aria-label="Carte des structures"></div>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <?php if (!empty($marqueurs)): ?>
      <?php scriptsCarte(); ?>
      <script>
      (function () {
          // Données encodées en JSON par PHP, contenus construits avec le DOM (pas de HTML injecté)
          const structures = <?= json_encode($marqueurs, $jsonFlags) ?>;
          const carte = L.map('carte-structures', { scrollWheelZoom: false });
          fondDeCarte(carte);

          // Plusieurs structures peuvent partager les mêmes coordonnées :
          // elles sont regroupées dans un seul repère pour rester toutes cliquables.
          const groupes = new Map();
          structures.forEach(function (s) {
              const cle = s.lat + ',' + s.lng;
              if (!groupes.has(cle)) groupes.set(cle, []);
              groupes.get(cle).push(s);
          });

          const liste = document.getElementById('loc-liste');
          const elements = [];
          const limites = [];
          let numero = 0;
          let actif = null;

          function activer(groupe) {
              if (actif) {
                  actif.marqueur.setIcon(iconeRepere(actif.numero, false));
                  actif.boutons.forEach(function (b) { b.classList.remove('actif'); });
              }
              actif = groupe;
              groupe.marqueur.setIcon(iconeRepere(groupe.numero, true));
              groupe.boutons.forEach(function (b) { b.classList.add('actif'); });
          }

          groupes.forEach(function (membres) {
              numero++;
              const groupe = { numero: String(numero), boutons: [] };

              const contenu = document.createElement('div');
              membres.forEach(function (s, i) {
                  if (i > 0) contenu.appendChild(document.createElement('hr'));
                  const titre = document.createElement('strong');
                  titre.textContent = s.sigle ? s.sigle + ' — ' + s.nom : s.nom;
                  contenu.appendChild(titre);
                  if (s.adresse) contenu.appendChild(document.createTextNode(s.adresse));
                  contenu.appendChild(document.createElement('br'));
                  const lien = document.createElement('a');
                  lien.href = 'fiche.php?id=' + s.id;
                  lien.textContent = 'Voir la fiche';
                  contenu.appendChild(lien);
              });

              const position = [membres[0].lat, membres[0].lng];
              groupe.marqueur = L.marker(position, {
                      icon: iconeRepere(groupe.numero, false),
                      title: membres.map(function (s) { return s.sigle || s.nom; }).join(', ')
                  })
                  .addTo(carte)
                  .bindPopup(contenu)
                  .on('click', function () { activer(groupe); });
              limites.push(position);

              // Une entrée de liste par structure, reliée au repère de son groupe
              membres.forEach(function (s) {
                  const li = document.createElement('li');
                  const bouton = document.createElement('button');
                  bouton.type = 'button';
                  bouton.className = 'loc-item';
                  const num = document.createElement('span');
                  num.className = 'loc-item-num';
                  num.textContent = groupe.numero;
                  const texte = document.createElement('span');
                  if (s.sigle) {
                      const sigle = document.createElement('span');
                      sigle.className = 'loc-item-sigle';
                      sigle.textContent = s.sigle;
                      texte.appendChild(sigle);
                  }
                  const nom = document.createElement('span');
                  nom.className = 'loc-item-nom';
                  nom.textContent = s.nom;
                  texte.appendChild(nom);
                  if (s.adresse) {
                      const adresse = document.createElement('span');
                      adresse.className = 'loc-item-adresse';
                      adresse.textContent = s.adresse;
                      texte.appendChild(adresse);
                  }
                  bouton.append(num, texte);
                  bouton.addEventListener('click', function () {
                      activer(groupe);
                      carte.flyTo(position, 16, { duration: .8 });
                      carte.once('moveend', function () { groupe.marqueur.openPopup(); });
                  });
                  li.appendChild(bouton);
                  liste.appendChild(li);
                  groupe.boutons.push(bouton);
                  elements.push({ li: li, texte: (s.nom + ' ' + s.sigle + ' ' + s.adresse).toLowerCase() });
              });
          });

          if (limites.length === 1) {
              carte.setView(limites[0], 15);
          } else {
              carte.fitBounds(limites, { padding: [40, 40], maxZoom: 15 });
          }

          // Filtre de la liste
          const filtre = document.getElementById('loc-filtre');
          const aucun = document.getElementById('loc-aucun');
          filtre.addEventListener('input', function () {
              const q = this.value.trim().toLowerCase();
              let visibles = 0;
              elements.forEach(function (e) {
                  const ok = q === '' || e.texte.indexOf(q) !== -1;
                  e.li.hidden = !ok;
                  if (ok) visibles++;
              });
              aucun.hidden = visibles > 0;
          });
      })();
      </script>
    <?php endif; ?>
    <?php
    require '../../includes/footer.php';
    exit;
}

// 2. Récupération de la structure (colonnes latitude / longitude requises dans la table `structure`)
$stmt = $db->prepare(
    "SELECT id_struc, nom_struc, sigle, adresse, latitude, longitude
     FROM structure
     WHERE id_struc = :id"
);
$stmt->execute([':id' => $id]);
$structure = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Les coordonnées sont-elles exploitables ?
$coordonneesValides = false;
$lat = $lng = null;

if ($structure && is_numeric($structure['latitude'] ?? null) && is_numeric($structure['longitude'] ?? null)) {
    $lat = (float) $structure['latitude'];
    $lng = (float) $structure['longitude'];
    $coordonneesValides = ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180);
}

require '../../includes/header.php';
stylesCarte();
?>

<?php if ($structure): ?>
<style>
.loc-detail { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1.5rem; align-items: start; }
.loc-detail .loc-carte { height: 560px; }
.loc-infos { display: grid; gap: 1.5rem; }
.loc-infos-carte { padding: 1.5rem; border: 1px solid var(--acc-ligne); border-radius: 4px; background: #fff; }
.loc-infos-carte h2 { margin: 0 0 1rem; color: var(--acc-encre); font-size: .8rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.loc-infos-carte dl { margin: 0; }
.loc-infos-carte dt { color: var(--acc-gris); font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
.loc-infos-carte dd { margin: .2rem 0 1rem; color: var(--acc-encre); font-size: .92rem; font-weight: 600; line-height: 1.5; overflow-wrap: anywhere; }
.loc-infos-carte dd:last-child { margin-bottom: 0; }
.loc-gps { font-family: ui-monospace, monospace; font-size: .85rem !important; }
.loc-boutons { display: grid; gap: .6rem; }
.loc-bouton { display: flex; align-items: center; justify-content: center; gap: .5rem; padding: .8rem 1rem; border: 2px solid var(--acc-encre); border-radius: 4px; color: var(--acc-encre); font-size: .88rem; font-weight: 700; transition: background .2s ease, color .2s ease; }
.loc-bouton:hover { background: var(--acc-encre); color: #fff; }
@media (max-width: 991.98px) {
  .loc-detail { grid-template-columns: 1fr; }
  .loc-detail .loc-carte { height: 420px; }
}
</style>

<header class="acc-bandeau">
  <img class="acc-bandeau-image" src="../../publique/image/GA.jpeg" alt="">
  <div class="container">
    <ol class="acc-fil" aria-label="Fil d'Ariane">
      <li><a href="accueil.php">Accueil</a></li>
      <li><a href="localisation.php">Localisation</a></li>
      <li aria-current="page"><?= h($structure['sigle'] ?: $structure['nom_struc']) ?></li>
    </ol>
    <span class="acc-surtitre">Localisation</span>
    <h1><?= h($structure['nom_struc']) ?></h1>
    <p><?= h($structure['adresse'] ?: 'Adresse non renseignée') ?></p>
  </div>
</header>

<main class="acc-section">
  <div class="container">
    <?php if ($coordonneesValides): ?>
      <div class="loc-detail">
        <div id="carte-structure" class="loc-carte" role="region" aria-label="Carte de <?= h($structure['nom_struc']) ?>"></div>

        <aside class="loc-infos" aria-label="Informations de localisation">
          <div class="loc-infos-carte">
            <h2>Adresse</h2>
            <dl>
              <dt>Adresse postale</dt>
              <dd><?= h($structure['adresse'] ?: 'Non renseignée') ?></dd>
              <dt>Coordonnées GPS</dt>
              <dd class="loc-gps"><?= number_format($lat, 5) ?>, <?= number_format($lng, 5) ?></dd>
            </dl>
          </div>
          <div class="loc-boutons">
            <a href="fiche.php?id=<?= (int) $structure['id_struc'] ?>" class="loc-bouton">← Retour à la fiche</a>
            <a href="localisation.php" class="loc-bouton">Voir toutes les structures</a>
          </div>
        </aside>
      </div>

      <?php scriptsCarte(); ?>
      <script>
      (function () {
          // Les valeurs viennent de PHP, encodées en JSON (pas d'injection possible)
          const lat = <?= json_encode($lat) ?>;
          const lng = <?= json_encode($lng) ?>;
          const nom = <?= json_encode($structure['nom_struc'], $jsonFlags) ?>;
          const adresse = <?= json_encode($structure['adresse'] ?? '', $jsonFlags) ?>;

          const carte = L.map('carte-structure', { scrollWheelZoom: false }).setView([lat, lng], 16);
          fondDeCarte(carte);

          // Contenu du popup construit avec le DOM (textContent) pour éviter tout HTML injecté
          const contenu = document.createElement('div');
          const titre = document.createElement('strong');
          titre.textContent = nom;
          contenu.appendChild(titre);
          if (adresse) contenu.appendChild(document.createTextNode(adresse));

          L.marker([lat, lng], { icon: iconeRepere('', true), title: nom }).addTo(carte).bindPopup(contenu).openPopup();
      })();
      </script>
    <?php else: ?>
      <div class="acc-vide">
        <h2>Position non renseignée</h2>
        <p>Les coordonnées géographiques de cette structure ne sont pas encore renseignées.
          <a href="fiche.php?id=<?= (int) $structure['id_struc'] ?>">Retour à la fiche</a> ·
          <a href="localisation.php">Voir toutes les structures</a></p>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php else: ?>
  <main class="acc-section">
    <div class="container">
      <div class="acc-vide">
        <h2>Structure introuvable</h2>
        <p>Cette structure n'existe pas ou a été retirée. <a href="localisation.php">Voir la carte de toutes les structures</a></p>
      </div>
    </div>
  </main>
<?php endif; ?>

<?php require '../../includes/footer.php'; ?>
