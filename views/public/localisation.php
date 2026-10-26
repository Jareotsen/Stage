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

if ($idBrut === null) {
    $marqueurs = [];
    $sansPosition = 0;
    $toutesStmt = $db->query("SELECT id_struc, nom_struc, sigle, adresse, latitude, longitude FROM structure ORDER BY nom_struc");
    foreach ($toutesStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $latS = is_numeric($s['latitude']) ? (float) $s['latitude'] : null;
        $lngS = is_numeric($s['longitude']) ? (float) $s['longitude'] : null;
        if ($latS === null || $lngS === null || $latS < -90 || $latS > 90 || $lngS < -180 || $lngS > 180) {
            $sansPosition++;
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
    ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        #carte-structures { height: 560px; width: 100%; border-radius: var(--radius-md); border: 1px solid var(--mrri-border); z-index: 1; }
        @media (max-width: 700px) { #carte-structures { height: 400px; } }
    </style>

    <main class="container my-4">
        <h1 class="h3 mb-1">Localisation des structures</h1>
        <p class="text-secondary mb-3">
            <?= count($marqueurs) ?> structure(s) positionnée(s) sur la carte.
            Cliquez sur un repère pour accéder à la fiche.
            <?php if ($sansPosition > 0): ?>
                <br><span class="small"><?= $sansPosition ?> structure(s) n’ont pas encore de position renseignée.</span>
            <?php endif; ?>
        </p>

        <?php if (empty($marqueurs)): ?>
            <div class="alert alert-light border text-center">Aucune structure n’a encore de position géographique renseignée.</div>
        <?php else: ?>
            <div id="carte-structures"></div>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            <script>
                // Données encodées en JSON par PHP, popups construits avec le DOM (pas de HTML injecté)
                const structures = <?= json_encode($marqueurs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                const carte = L.map('carte-structures');
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(carte);

                const limites = [];
                structures.forEach(function (s) {
                    const contenu = document.createElement('div');
                    const titre = document.createElement('strong');
                    titre.textContent = s.sigle ? s.sigle + ' — ' + s.nom : s.nom;
                    contenu.appendChild(titre);
                    if (s.adresse) {
                        contenu.appendChild(document.createElement('br'));
                        contenu.appendChild(document.createTextNode(s.adresse));
                    }
                    contenu.appendChild(document.createElement('br'));
                    const lien = document.createElement('a');
                    lien.href = 'fiche.php?id=' + s.id;
                    lien.textContent = 'Voir la fiche';
                    contenu.appendChild(lien);

                    L.marker([s.lat, s.lng]).addTo(carte).bindPopup(contenu);
                    limites.push([s.lat, s.lng]);
                });

                if (limites.length === 1) {
                    carte.setView(limites[0], 15);
                } else {
                    carte.fitBounds(limites, { padding: [40, 40], maxZoom: 15 });
                }
            </script>
        <?php endif; ?>
    </main>
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
?>

<!-- Leaflet (cartographie OpenStreetMap, gratuit, sans clé API) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

<style>
    #carte-structure {
        height: 520px;
        width: 100%;
        border-radius: var(--radius-md);
        border: 1px solid var(--mrri-border);
        z-index: 1;
    }
    @media (max-width: 700px) {
        #carte-structure { height: 360px; }
    }
</style>

<?php if ($structure): ?>
<main class="container my-4">
    <p class="small">
        <a href="catalogue.php" class="link-secondary">Structures</a> /
        <a href="fiche.php?id=<?= (int) $structure['id_struc'] ?>" class="link-secondary"><?= htmlspecialchars($structure['nom_struc']) ?></a> /
        Localisation
    </p>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($structure['nom_struc']) ?></h1>
            <?php if (!empty($structure['sigle'])): ?>
                <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($structure['sigle']) ?></span>
            <?php endif; ?>
            <p class="small text-secondary mt-2 mb-0">
                <?= htmlspecialchars($structure['adresse'] ?? 'Adresse non renseignée') ?>
            </p>
        </div>
        <a href="fiche.php?id=<?= (int) $structure['id_struc'] ?>" class="btn btn-outline-secondary btn-sm">
            ← Retour à la fiche
        </a>
    </div>

    <?php if ($coordonneesValides): ?>
        <div id="carte-structure"></div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <p class="small text-secondary mb-0">
                Latitude : <strong><?= htmlspecialchars((string) $lat) ?></strong> —
                Longitude : <strong><?= htmlspecialchars((string) $lng) ?></strong>
            </p>
            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($lat . ',' . $lng) ?>"
               target="_blank" rel="noopener" class="btn btn-mrri btn-sm">
                Obtenir l'itinéraire
            </a>
        </div>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            // Les valeurs viennent de PHP, encodées en JSON (pas d'injection possible)
            const lat = <?= json_encode($lat) ?>;
            const lng = <?= json_encode($lng) ?>;
            const nom = <?= json_encode($structure['nom_struc']) ?>;
            const adresse = <?= json_encode($structure['adresse'] ?? '') ?>;

            const carte = L.map('carte-structure').setView([lat, lng], 16);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(carte);

            // Contenu du popup construit avec le DOM (textContent) pour éviter tout HTML injecté
            const contenu = document.createElement('div');
            const titre = document.createElement('strong');
            titre.textContent = nom;
            contenu.appendChild(titre);
            if (adresse) {
                contenu.appendChild(document.createElement('br'));
                contenu.appendChild(document.createTextNode(adresse));
            }

            L.marker([lat, lng]).addTo(carte).bindPopup(contenu).openPopup();
        </script>
    <?php else: ?>
        <div class="alert alert-light border text-center">
            Les coordonnées géographiques de cette structure ne sont pas encore renseignées.
        </div>
    <?php endif; ?>
</main>
<?php else: ?>
    <div class="container my-5">
        <div class="alert alert-light border text-center">Structure introuvable.</div>
    </div>
<?php endif; ?>

<?php require '../../includes/footer.php'; ?>