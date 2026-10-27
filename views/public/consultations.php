<?php
session_start();
require '../../config/database.php';
require_once '../../includes/dates.php';
require_once '../../includes/consultation_helpers.php';

/* =========================================================
   CONSULTATIONS OUVERTES UNIQUEMENT
   (statut 'ouverte' et date de clôture non dépassée)
   ========================================================= */
$conditionOuverte = "c.statut = 'ouverte' AND (c.date_fermeture IS NULL OR c.date_fermeture >= NOW())";

$search = isset($_GET['q']) ? trim($_GET['q']) : "";
$type = isset($_GET['type']) && in_array($_GET['type'], ['generale', 'structure'], true) ? $_GET['type'] : '';
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'recent';
$parPage = 9;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

$conditions = [$conditionOuverte];
$parametres = [];

if ($search !== "") {
    $conditions[] = "(c.titre LIKE :q OR c.contenu LIKE :q OR s.nom_struc LIKE :q OR s.sigle LIKE :q)";
    $parametres[':q'] = "%$search%";
}

// Nombre de consultations par type (recherche appliquée, type ignoré) pour la colonne de filtres
$typesStmt = $db->prepare(
    "SELECT SUM(c.id_structure IS NULL), SUM(c.id_structure IS NOT NULL)
     FROM consultations c LEFT JOIN structure s ON c.id_structure = s.id_struc
     WHERE " . implode(" AND ", $conditions)
);
$typesStmt->execute($parametres);
[$nbGenerales, $nbStructures] = array_map('intval', $typesStmt->fetch(PDO::FETCH_NUM));

if ($type === 'generale') {
    $conditions[] = "c.id_structure IS NULL";
} elseif ($type === 'structure') {
    $conditions[] = "c.id_structure IS NOT NULL";
}
$whereSQL = " WHERE " . implode(" AND ", $conditions);
// Les consultations sans date de clôture passent en dernier lors du tri par échéance
$ordreSQL = $tri === 'cloture'
    ? "c.date_fermeture IS NULL, c.date_fermeture ASC"
    : "c.date_ouverture DESC";

// LEFT JOIN : une consultation générale n'a pas de structure (id_structure NULL)
$compteStmt = $db->prepare("SELECT COUNT(*) FROM consultations c LEFT JOIN structure s ON c.id_structure = s.id_struc" . $whereSQL);
$compteStmt->execute($parametres);
$totalConsultations = (int) $compteStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalConsultations / $parPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $parPage;

$consultationStmt = $db->prepare(
    "SELECT c.id_consultation, c.titre, c.contenu, c.date_ouverture, c.date_fermeture,
            c.id_structure, s.nom_struc, s.sigle
     FROM consultations c
     LEFT JOIN structure s ON c.id_structure = s.id_struc"
     . $whereSQL .
    " ORDER BY $ordreSQL
     LIMIT $parPage OFFSET $offset"
);
$consultationStmt->execute($parametres);
$consultations = $consultationStmt->fetchAll(PDO::FETCH_ASSOC);

// Conserve les filtres lors de la navigation entre les pages.
function lienConsultations(array $modifications = []): string {
    $params = [
        'q' => isset($_GET['q']) ? trim((string) $_GET['q']) : '',
        'type' => isset($_GET['type']) ? (string) $_GET['type'] : '',
        'tri' => isset($_GET['tri']) ? (string) $_GET['tri'] : 'recent',
        'page' => isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1,
    ];
    foreach ($modifications as $cle => $valeur) {
        $params[$cle] = $valeur;
    }
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($params);
}

// Nombre de jours avant la clôture, ou null si la consultation n'a pas d'échéance.
function joursRestants(?string $dateFermeture): ?int {
    if (empty($dateFermeture)) {
        return null;
    }
    $fin = strtotime(date('Y-m-d', strtotime($dateFermeture)));
    $aujourdhui = strtotime(date('Y-m-d'));
    return max(0, (int) round(($fin - $aujourdhui) / 86400));
}

$libellesTypes = ['' => 'Toutes les consultations', 'generale' => 'Consultations générales', 'structure' => 'Liées à une structure'];
$moisCourts = [1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

require '../../includes/header.php';
?>

<style>
/* Styles propres aux consultations : les composants communs sont dans publique/css/portail.css */
.cons-mise-en-page { display: grid; grid-template-columns: 290px minmax(0, 1fr); gap: clamp(2rem, 4vw, 3.5rem); align-items: start; }
.cons-filtres { position: sticky; top: 100px; display: grid; gap: 2rem; }
.cons-filtres h2 { margin: 0 0 .9rem; padding-bottom: .6rem; border-bottom: 2px solid var(--acc-encre); color: var(--acc-encre); font-size: .8rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.cons-filtres .acc-champ input { height: 48px; font-size: .9rem; }
.cons-filtres .acc-champ button { height: 48px; padding: 0 1rem; }
.cons-liste-types { margin: 0; padding: 0; list-style: none; }
.cons-type { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: .6rem .75rem; border-left: 3px solid transparent; color: var(--acc-encre); font-size: .88rem; font-weight: 600; transition: background .2s ease; }
.cons-type:hover { background: var(--acc-fond); color: var(--acc-encre); }
.cons-type.actif { border-left-color: var(--acc-vert); background: #E9F6EF; color: var(--acc-vert-fonce); }
.cons-type .nb { min-width: 26px; padding: .1rem .45rem; border-radius: 999px; background: var(--acc-fond); color: var(--acc-gris); font-size: .74rem; font-weight: 700; text-align: center; }
.cons-type.actif .nb { background: var(--acc-vert); color: #fff; }
.cons-etapes { padding: 1.3rem 1.2rem; border-radius: 4px; background: var(--acc-nuit); color: rgba(255,255,255,.8); }
.cons-etapes ol { margin: 0; padding: 0; list-style: none; counter-reset: etape; }
.cons-etapes strong { display: block; margin-bottom: .9rem; color: #fff; font-family: var(--font-title); font-size: 1.05rem; font-weight: 600; }
.cons-etapes li { position: relative; padding-left: 2.2rem; font-size: .84rem; line-height: 1.5; counter-increment: etape; }
.cons-etapes li + li { margin-top: .8rem; }
.cons-etapes li::before { content: counter(etape); position: absolute; top: -.05rem; left: 0; display: flex; align-items: center; justify-content: center; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: var(--acc-jaune); color: var(--acc-nuit); font-size: .75rem; font-weight: 800; }

/* Une consultation = une ligne : échéance à gauche, contenu au centre */
.cons-liste { display: grid; gap: 1.25rem; }
.cons-item { display: grid; grid-template-columns: 110px minmax(0, 1fr); border: 1px solid var(--acc-ligne); border-radius: 4px; background: #fff; color: var(--acc-encre); transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
.cons-item:hover { transform: translateY(-3px); border-color: #C8D2E1; box-shadow: 0 16px 32px rgba(10,26,51,.09); color: var(--acc-encre); }
.cons-echeance { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.2rem .6rem; border-right: 1px solid var(--acc-ligne); background: var(--acc-fond); text-align: center; }
.cons-echeance small { color: var(--acc-gris); font-size: .66rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
.cons-echeance strong { margin: .2rem 0 .1rem; color: var(--acc-encre); font-family: var(--font-title); font-size: 2.2rem; font-weight: 600; line-height: 1; }
.cons-echeance span { color: var(--acc-gris); font-size: .8rem; font-weight: 600; }
.cons-echeance.sans strong { font-size: 1.6rem; }
.cons-corps { display: flex; flex-direction: column; padding: 1.4rem 1.6rem 1.35rem; }
.cons-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem .7rem; margin-bottom: .7rem; }
.cons-type-badge { padding: .22rem .6rem; border-radius: 2px; font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
.cons-type-badge.generale { background: #E8F0FA; color: var(--acc-bleu-fonce); }
.cons-type-badge.structure { background: #E3F5EC; color: var(--acc-vert-fonce); }
.cons-restant { display: inline-flex; align-items: center; gap: .35rem; color: var(--acc-gris); font-size: .76rem; font-weight: 700; }
.cons-restant::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: var(--acc-vert); }
.cons-restant.urgent { color: #B42318; }
.cons-restant.urgent::before { background: #D92D20; }
.cons-item h3 { margin: 0 0 .55rem; font-family: var(--font-title); font-size: 1.2rem; font-weight: 600; line-height: 1.35; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; transition: color .2s ease; }
.cons-item:hover h3 { color: var(--acc-bleu-fonce); }
.cons-extrait { margin: 0 0 1.1rem; color: var(--acc-gris); font-size: .9rem; line-height: 1.6; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
.cons-pied { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem 1rem; margin-top: auto; padding-top: .9rem; border-top: 1px solid var(--acc-ligne); color: var(--acc-gris); font-size: .8rem; }
.cons-pied .acc-fleche { font-size: .88rem; }

@media (max-width: 991.98px) {
  .cons-mise-en-page { grid-template-columns: 1fr; }
  .cons-filtres { position: static; }
  .cons-etapes { display: none; }
}
@media (max-width: 575.98px) {
  .cons-item { grid-template-columns: 1fr; }
  .cons-echeance { flex-direction: row; justify-content: flex-start; gap: .5rem; padding: .7rem 1.2rem; border-right: 0; border-bottom: 1px solid var(--acc-ligne); }
  .cons-echeance strong { margin: 0; font-size: 1.1rem; }
  .cons-corps { padding: 1.1rem 1.2rem; }
}
@media (prefers-reduced-motion: reduce) { .cons-item { transition: none; } }
</style>

<header class="acc-bandeau">
  <img class="acc-bandeau-image" src="../../publique/image/GA3.jpeg" alt="">
  <div class="container">
    <ol class="acc-fil" aria-label="Fil d'Ariane">
      <li><a href="accueil.php">Accueil</a></li>
      <li aria-current="page">Consultations</li>
    </ol>
    <span class="acc-surtitre">Participation citoyenne</span>
    <h1>Consultations publiques</h1>
    <p>Donnez votre avis sur les projets soumis par le Ministère, les Institutions constitutionnelles et les A.A.I avant leur date de clôture.</p>
  </div>
</header>

<main class="acc-section">
  <div class="container cons-mise-en-page">

    <aside class="cons-filtres" aria-label="Filtres des consultations">
      <div>
        <h2>Rechercher</h2>
        <form action="" method="get" class="acc-champ" role="search">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
          <label for="q" class="visually-hidden">Rechercher une consultation</label>
          <input type="text" id="q" name="q" placeholder="Titre, sujet, structure…" value="<?= echapper($search) ?>">
          <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= echapper($type) ?>"><?php endif; ?>
          <?php if ($tri !== 'recent'): ?><input type="hidden" name="tri" value="<?= echapper($tri) ?>"><?php endif; ?>
          <button type="submit">OK</button>
        </form>
      </div>

      <nav aria-label="Types de consultation">
        <h2>Type</h2>
        <ul class="cons-liste-types">
          <?php foreach (['' => $nbGenerales + $nbStructures, 'generale' => $nbGenerales, 'structure' => $nbStructures] as $cle => $nb): ?>
            <li>
              <a href="<?= echapper(lienConsultations(['type' => $cle, 'page' => 1])) ?>" class="cons-type <?= $type === $cle ? 'actif' : '' ?>" <?= $type === $cle ? 'aria-current="true"' : '' ?>>
                <?= $libellesTypes[$cle] ?> <span class="nb"><?= $nb ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div class="cons-etapes">
        <strong>Comment participer ?</strong>
        <ol>
          <li>Choisissez une consultation ouverte.</li>
          <li>Lisez le sujet et les documents associés.</li>
          <li>Envoyez votre contribution avant la clôture.</li>
        </ol>
      </div>
    </aside>

    <div>
      <div class="acc-barre">
        <div>
          <strong><?= $totalConsultations ?></strong> consultation<?= $totalConsultations > 1 ? 's' : '' ?> ouverte<?= $totalConsultations > 1 ? 's' : '' ?>
          <?php if ($type !== ''): ?>
            <span class="acc-filtre-actif"><?= $libellesTypes[$type] ?>
              <a href="<?= echapper(lienConsultations(['type' => '', 'page' => 1])) ?>" aria-label="Retirer le filtre de type">×</a></span>
          <?php endif; ?>
          <?php if ($search !== ''): ?>
            <span class="acc-filtre-actif">« <?= echapper($search) ?> »
              <a href="<?= echapper(lienConsultations(['q' => '', 'page' => 1])) ?>" aria-label="Retirer la recherche">×</a></span>
          <?php endif; ?>
        </div>
        <form action="" method="get">
          <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= echapper($search) ?>"><?php endif; ?>
          <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= echapper($type) ?>"><?php endif; ?>
          <label>Trier par
            <select name="tri" onchange="this.form.submit()">
              <option value="recent" <?= $tri !== 'cloture' ? 'selected' : '' ?>>Plus récentes</option>
              <option value="cloture" <?= $tri === 'cloture' ? 'selected' : '' ?>>Clôture la plus proche</option>
            </select>
          </label>
        </form>
      </div>

      <?php if (empty($consultations)): ?>
        <div class="acc-vide">
          <h2>Aucune consultation ouverte</h2>
          <p><?= ($search !== '' || $type !== '') ? 'Essayez avec un autre mot-clé ou un autre type de consultation.' : 'Revenez bientôt : de nouvelles consultations seront publiées prochainement.' ?></p>
        </div>
      <?php else: ?>
        <div class="cons-liste">
          <?php foreach ($consultations as $consultation): ?>
            <?php
              $estGenerale = empty($consultation['id_structure']);
              $jours = joursRestants($consultation['date_fermeture']);
              $fin = !empty($consultation['date_fermeture']) ? strtotime($consultation['date_fermeture']) : null;
            ?>
            <a href="consultation_detail.php?id=<?= (int) $consultation['id_consultation'] ?>" class="cons-item">
              <?php if ($fin): ?>
                <div class="cons-echeance" aria-label="Clôture le <?= formaterDateFr($consultation['date_fermeture']) ?>">
                  <small>Clôture</small>
                  <strong><?= date('d', $fin) ?></strong>
                  <span><?= $moisCourts[(int) date('n', $fin)] ?> <?= date('Y', $fin) ?></span>
                </div>
              <?php else: ?>
                <div class="cons-echeance sans">
                  <small>Clôture</small>
                  <strong>—</strong>
                  <span>Non définie</span>
                </div>
              <?php endif; ?>

              <div class="cons-corps">
                <div class="cons-meta">
                  <span class="cons-type-badge <?= $estGenerale ? 'generale' : 'structure' ?>" title="<?= $estGenerale ? 'Consultation générale' : echapper($consultation['nom_struc']) ?>">
                    <?= $estGenerale ? 'Consultation générale' : echapper($consultation['sigle'] ?: $consultation['nom_struc']) ?>
                  </span>
                  <?php if ($jours === 0): ?>
                    <span class="cons-restant urgent">Dernier jour</span>
                  <?php elseif ($jours !== null): ?>
                    <span class="cons-restant <?= $jours <= 7 ? 'urgent' : '' ?>"><?= $jours ?> jour<?= $jours > 1 ? 's' : '' ?> restant<?= $jours > 1 ? 's' : '' ?></span>
                  <?php endif; ?>
                </div>
                <h3><?= echapper($consultation['titre']) ?></h3>
                <p class="cons-extrait"><?= echapper(mb_strimwidth(strip_tags($consultation['contenu'] ?? ''), 0, 240, '…')) ?></p>
                <div class="cons-pied">
                  <span>Ouverte le <?= formaterDateFr($consultation['date_ouverture']) ?><?= !$estGenerale ? ' · ' . echapper($consultation['nom_struc']) : '' ?></span>
                  <span class="acc-fleche">Participer <span aria-hidden="true">→</span></span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav class="acc-pagination" aria-label="Pagination des consultations">
            <a href="<?= echapper(lienConsultations(['page' => max(1, $page - 1)])) ?>" class="<?= $page <= 1 ? 'desactive' : '' ?>" aria-label="Page précédente">‹</a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <?php if ($i === $page): ?>
                <span class="actif" aria-current="page"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= echapper(lienConsultations(['page' => $i])) ?>" aria-label="Page <?= $i ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            <a href="<?= echapper(lienConsultations(['page' => min($totalPages, $page + 1)])) ?>" class="<?= $page >= $totalPages ? 'desactive' : '' ?>" aria-label="Page suivante">›</a>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php require '../../includes/footer.php'; ?>
