<?php
declare(strict_types=1);

session_start();
require '../../config/database.php';

$idBrut = $_GET['id'] ?? null;
$idStructure = null;
$erreurId = false;

if ($idBrut !== null) {
    if (!is_string($idBrut) || !ctype_digit($idBrut) || (int) $idBrut < 1) {
        $erreurId = true;
    } else {
        $idStructure = (int) $idBrut;
    }
}

$structures = [];
$structureSelectionnee = null;
if (!$erreurId) {
    $sql = "SELECT id_struc, nom_struc, adresse, latitude, longitude
            FROM structure";
    if ($idStructure !== null) {
        $sql .= " WHERE id_struc = :id";
    }
    $sql .= " ORDER BY nom_struc";
    $structureStmt = $db->prepare($sql);
    $structureStmt->execute($idStructure !== null ? [':id' => $idStructure] : []);

    foreach ($structureStmt->fetchAll(PDO::FETCH_ASSOC) as $structure) {
        if ($idStructure !== null) {
            $structureSelectionnee = $structure;
        }

        if (!is_numeric($structure['latitude']) || !is_numeric($structure['longitude'])) {
            continue;
        }
        $latitude = (float) $structure['latitude'];
        $longitude = (float) $structure['longitude'];
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            continue;
        }

        $structures[] = [
            'id' => (int) $structure['id_struc'],
            'nom' => (string) $structure['nom_struc'],
            'adresse' => (string) ($structure['adresse'] ?? ''),
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }
}

$positionDemandeeSansCoordonnees = $idStructure !== null
    && $structureSelectionnee !== null
    && count($structures) === 0;
$donneesCarte = json_encode(
    $structures,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
);
$h = static fn($valeur): string => htmlspecialchars((string) ($valeur ?? ''), ENT_QUOTES, 'UTF-8');

require '../../includes/header.php';
?>

<main class="localisation-page">
    <section class="localisation-hero">
        <div class="container">
            <nav class="localisation-fil" aria-label="Fil d'Ariane">
                <a href="accueil.php">Accueil</a>
                <span aria-hidden="true">/</span>
                <?php if ($structureSelectionnee): ?>
                    <a href="fiche.php?id=<?= (int) $structureSelectionnee['id_struc'] ?>">Fiche de la structure</a>
                    <span aria-hidden="true">/</span>
                <?php endif; ?>
                <span aria-current="page">Localisation</span>
            </nav>
            <span class="localisation-kicker">Institutions et services publics</span>
            <h1><?= $structureSelectionnee ? 'Localiser une structure' : 'Localiser les structures' ?></h1>
            <p>
                <?= $structureSelectionnee
                    ? 'Retrouvez la position géographique de ' . $h($structureSelectionnee['nom_struc']) . '.'
                    : 'Explorez la carte pour repérer les institutions et autorités référencées.' ?>
            </p>
        </div>
    </section>

    <section class="container localisation-contenu">
        <?php if ($erreurId || ($idStructure !== null && !$structureSelectionnee)): ?>
            <div class="localisation-message" role="status">
                <span class="localisation-message-icone" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                <div>
                    <h2>Structure introuvable</h2>
                    <p>La structure demandée n’existe pas ou le lien de localisation est invalide.</p>
                    <a class="localisation-bouton" href="catalogue.php">Voir les structures</a>
                </div>
            </div>
        <?php elseif ($positionDemandeeSansCoordonnees): ?>
            <div class="localisation-message" role="status">
                <span class="localisation-message-icone" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                <div>
                    <h2>Position non renseignée</h2>
                    <p>
                        Les coordonnées géographiques de
                        <strong><?= $h($structureSelectionnee['nom_struc']) ?></strong>
                        ne sont pas encore disponibles.
                    </p>
                    <?php if (!empty($structureSelectionnee['adresse'])): ?>
                        <p class="localisation-adresse"><i class="bi bi-signpost-2" aria-hidden="true"></i> <?= $h($structureSelectionnee['adresse']) ?></p>
                    <?php endif; ?>
                    <a class="localisation-bouton secondaire" href="fiche.php?id=<?= (int) $structureSelectionnee['id_struc'] ?>">Retour à la fiche</a>
                </div>
            </div>
        <?php else: ?>
            <div class="localisation-entete">
                <div>
                    <span class="localisation-surtitre">Carte interactive</span>
                    <h2><?= $structureSelectionnee ? $h($structureSelectionnee['nom_struc']) : 'Structures géolocalisées' ?></h2>
                    <p>
                        <?= count($structures) ?> position<?= count($structures) > 1 ? 's' : '' ?> affichée<?= count($structures) > 1 ? 's' : '' ?>.
                        Sélectionnez un repère pour consulter les informations.
                    </p>
                </div>
                <?php if ($structureSelectionnee): ?>
                    <a class="localisation-bouton secondaire" href="fiche.php?id=<?= (int) $structureSelectionnee['id_struc'] ?>">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour à la fiche
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($structures)): ?>
                <div class="localisation-message" role="status">
                    <span class="localisation-message-icone" aria-hidden="true"><i class="bi bi-map"></i></span>
                    <div>
                        <h2>Aucune position disponible</h2>
                        <p>Aucune structure ne possède actuellement des coordonnées géographiques valides.</p>
                        <a class="localisation-bouton" href="catalogue.php">Parcourir les structures</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="localisation-carte">
                    <div id="map" role="region" aria-label="Carte interactive des structures" aria-describedby="carte-aide"></div>
                    <div class="localisation-carte-pied" id="carte-aide">
                        <span><i class="bi bi-info-circle" aria-hidden="true"></i> Les repères utilisent les coordonnées enregistrées pour chaque structure.</span>
                        <span class="localisation-attribution">Cartographie © OpenStreetMap</span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<?php if (!empty($structures)): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            const structures = <?= $donneesCarte ?>;
            const mapElement = document.getElementById('map');
            if (!mapElement || typeof L === 'undefined') {
                return;
            }

            const map = L.map(mapElement, { scrollWheelZoom: false });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            const bounds = [];
            structures.forEach(function (structure) {
                const point = [structure.latitude, structure.longitude];
                bounds.push(point);

                const popup = document.createElement('div');
                popup.className = 'localisation-popup';
                const titre = document.createElement('strong');
                titre.textContent = structure.nom;
                popup.appendChild(titre);
                if (structure.adresse) {
                    const adresse = document.createElement('p');
                    adresse.textContent = structure.adresse;
                    popup.appendChild(adresse);
                }
                const fiche = document.createElement('a');
                fiche.href = 'fiche.php?id=' + encodeURIComponent(structure.id);
                fiche.textContent = 'Consulter la fiche';
                popup.appendChild(fiche);

                L.marker(point).addTo(map).bindPopup(popup);
            });

            if (bounds.length === 1) {
                map.setView(bounds[0], 15);
            } else {
                map.fitBounds(bounds, { padding: [36, 36], maxZoom: 15 });
            }
        }());
    </script>
<?php endif; ?>

<?php require '../../includes/footer.php'; ?>
