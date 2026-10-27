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

require '../../includes/header.php';
?>

<style>
/* Mise en page alignée sur catalogue.php : les composants partagés restent dans header.php. */
.consult-page { padding-bottom: 4rem; }
.consult-hero {
  position: relative; overflow: hidden; padding: clamp(2.5rem, 5vw, 4.4rem) 0 3.3rem;
  color: #fff; background: linear-gradient(118deg, #0B1D4D 0%, #173B91 68%, #176B58 100%);
}
.consult-hero::before, .consult-hero::after { content: ""; position: absolute; border: 1px solid rgba(255,255,255,.13); border-radius: 50%; pointer-events: none; }
.consult-hero::before { width: 360px; height: 360px; right: -90px; top: -190px; }
.consult-hero::after { width: 240px; height: 240px; right: 70px; bottom: -175px; }
.consult-hero .container { position: relative; z-index: 1; }
.consult-kicker { display: inline-flex; align-items: center; gap: .55rem; margin-bottom: .9rem; color: #F7EACB; font-size: .75rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
.consult-kicker::before { content: ""; width: 28px; height: 3px; border-radius: 5px; background: #C9972C; }
.consult-hero h1 { max-width: 760px; color: #fff; font-family: var(--font-title); font-size: clamp(2rem, 4vw, 3.15rem); line-height: 1.12; margin: 0 0 1rem; }
.consult-hero p { max-width: 680px; color: rgba(255,255,255,.82); font-size: 1rem; line-height: 1.8; margin: 0; }
.consult-stats { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1.5rem; }
.consult-stat { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem .85rem; border: 1px solid rgba(255,255,255,.22); border-radius: 999px; background: rgba(255,255,255,.08); color: #fff; font-size: .82rem; }
.consult-stat strong { color: #fff; font-size: .95rem; }
.consult-stat .pulse { width: 8px; height: 8px; border-radius: 50%; background: #5DD39E; box-shadow: 0 0 0 4px rgba(93,211,158,.22); }

.consult-tools { position: relative; z-index: 3; margin-top: -1.45rem; }
.consult-search-panel { padding: 1rem; border: 1px solid var(--mrri-border); border-radius: 16px; background: #fff; box-shadow: 0 14px 38px rgba(11,29,77,.12); }
.consult-search-form { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(190px, .45fr) auto; gap: .75rem; }
.consult-field { position: relative; min-width: 0; }
.consult-field .field-icon { position: absolute; top: 50%; left: 1rem; transform: translateY(-50%); color: #667085; pointer-events: none; }
.consult-field input, .consult-field select { width: 100%; min-height: 50px; border: 1px solid #D7DDE7; border-radius: 10px; background: #fff; color: var(--mrri-text); font: inherit; font-size: .9rem; }
.consult-field input { padding: .7rem 1rem .7rem 2.8rem; }
.consult-field select { padding: .7rem 2.3rem .7rem .9rem; }
.consult-field input:focus, .consult-field select:focus, .consult-sort select:focus { outline: 0; border-color: var(--mrri-vert); box-shadow: 0 0 0 3px rgba(15,138,75,.12); }
.consult-submit { display: inline-flex; align-items: center; justify-content: center; gap: .55rem; min-height: 50px; padding: .7rem 1.3rem; border: 1px solid var(--mrri-bleu); border-radius: 10px; background: var(--mrri-bleu); color: #fff; font: inherit; font-weight: 700; transition: background .18s, transform .18s; }
.consult-submit:hover { background: var(--mrri-bleu-dark); transform: translateY(-1px); }

.consult-content { padding-top: 2.1rem; }
.consult-section-title { margin: 0 0 .35rem; font-size: 1.35rem; }
.consult-section-intro { margin: 0; color: var(--mrri-text-light); font-size: .9rem; }
.consult-filters { display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; margin: 1.3rem 0 1.7rem; }
.consult-filter-label { margin-right: .25rem; color: #475467; font-size: .83rem; font-weight: 700; }
.consult-chip { display: inline-flex; align-items: center; gap: .45rem; min-height: 35px; padding: .45rem .85rem; border: 1px solid #DDE3EB; border-radius: 999px; background: #fff; color: #475467; font-size: .8rem; font-weight: 650; transition: all .18s; }
.consult-chip:hover { border-color: var(--mrri-vert); color: var(--mrri-vert-dark); }
.consult-chip.active { border-color: var(--mrri-vert); background: var(--mrri-vert); color: #fff; }
.consult-chip .pastille { width: 9px; height: 9px; border-radius: 3px; }
.consult-chip .pastille.generale { background: var(--mrri-bleu); }
.consult-chip .pastille.structure { background: var(--mrri-or); }
.consult-chip.active .pastille { background: #fff; }

.consult-results-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.consult-result-count { display: flex; align-items: center; gap: .65rem; color: var(--mrri-text-light); font-size: .88rem; }
.consult-result-count strong { color: var(--mrri-bleu-nuit); font-size: 1rem; }
.consult-result-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--mrri-vert); box-shadow: 0 0 0 4px #E5F5EC; }
.consult-sort { display: flex; align-items: center; gap: .55rem; color: var(--mrri-text-light); font-size: .82rem; }
.consult-sort select { min-height: 40px; max-width: 235px; padding: .45rem 2rem .45rem .7rem; border: 1px solid #D7DDE7; border-radius: 9px; background: #fff; color: var(--mrri-text); font: inherit; }

/* Comme le catalogue : l'espace de l'icône qui dépasse est réservé sur la colonne */
.consult-grille > .col { display: flex; flex-direction: column; padding-top: 2.2rem; }
.consult-carte { position: relative; display: flex; flex: 1 1 auto; flex-direction: column; min-height: 320px; padding: 0 1.35rem 1.25rem; border: 1px solid #E1E6EF; border-radius: 18px; background: #fff; box-shadow: 0 5px 18px rgba(11,29,77,.045); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.consult-carte:hover { transform: translateY(-4px); border-color: #B9C8E6; box-shadow: 0 14px 30px rgba(11,29,77,.10); }
.consult-carte.structure:hover { border-color: #E6D2A3; }
.consult-carte:hover .consult-icone { border-color: var(--mrri-bleu); background: var(--mrri-bleu); color: #fff; }
.consult-carte.structure:hover .consult-icone { border-color: #C9972C; background: #C9972C; color: #fff; }
.consult-carte-top { display: flex; align-items: flex-end; justify-content: space-between; gap: .75rem; margin: -2.2rem 0 1.1rem; }
.consult-icone { display: flex; flex: 0 0 74px; align-items: center; justify-content: center; width: 74px; height: 74px; border: 6px solid #D7E8FB; border-radius: 50%; background: #F9FBFF; color: var(--mrri-bleu); box-shadow: 0 7px 16px rgba(11,29,77,.13); transition: background .2s ease, border-color .2s ease, color .2s ease; }
.consult-carte.structure .consult-icone { border-color: #F3E4C2; background: #FFFCF5; color: #9A6F14; }
.consult-icone svg { width: 30px; height: 30px; }
.consult-echeance { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .65rem; border-radius: 999px; background: #E5F5EC; color: var(--mrri-vert-dark); font-size: .72rem; font-weight: 800; white-space: nowrap; }
.consult-echeance.urgent { background: #FDECEC; color: #B42318; }
.consult-echeance.libre { background: #F0F4FB; color: var(--mrri-bleu); }
.consult-badge { align-self: flex-start; max-width: 100%; margin-bottom: .7rem; padding: .25rem .65rem; overflow: hidden; border-radius: 999px; font-size: .7rem; font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
.consult-badge.generale { background: #E8EDFB; color: var(--mrri-bleu); }
.consult-badge.structure { background: #FBF3E1; color: #8A6311; }
.consult-carte h3 { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; overflow-wrap: anywhere; margin: 0 0 .55rem; color: var(--mrri-bleu-nuit); font-family: var(--font-title); font-size: 1.15rem; line-height: 1.35; }
.consult-carte .extrait { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; margin: 0 0 1rem; color: var(--mrri-text-light); font-size: .86rem; line-height: 1.6; }
.consult-dates { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin: auto 0 1rem; padding: .7rem .8rem; border-radius: 12px; background: #F7F9FC; }
.consult-dates div { display: flex; flex-direction: column; gap: .1rem; min-width: 0; }
.consult-dates span { color: #667085; font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
.consult-dates strong { color: var(--mrri-text); font-size: .8rem; font-weight: 700; }
.consult-carte .participer { display: inline-flex; align-items: center; gap: .45rem; padding-top: .8rem; border-top: 1px solid #EEF1F5; color: var(--mrri-bleu); font-size: .84rem; font-weight: 800; }
.consult-carte .participer span { transition: transform .18s ease; }
.consult-carte .participer:hover { color: var(--mrri-vert-dark); }
.consult-carte .participer:hover span { transform: translateX(4px); }

.consult-empty { padding: 3rem 1.25rem; border: 1px dashed #CDD5E1; border-radius: 16px; background: #fff; text-align: center; }
.consult-empty-icon { display: flex; align-items: center; justify-content: center; width: 58px; height: 58px; margin: 0 auto 1rem; border-radius: 16px; background: #F0F4FB; color: var(--mrri-bleu); }
.consult-empty h2 { margin-bottom: .4rem; font-size: 1.2rem; }
.consult-empty p { margin: 0; color: var(--mrri-text-light); font-size: .9rem; }

.pagination-ronde { position: relative; z-index: 1; display: flex; justify-content: center; flex-wrap: wrap; gap: .45rem; margin-top: 3rem; }
.pagination-ronde a, .pagination-ronde span { display: inline-flex; align-items: center; justify-content: center; min-width: 39px; height: 39px; padding: 0 .65rem; border: 1px solid #DDE3EB; border-radius: 10px; background: #fff; color: var(--mrri-text); font-size: .85rem; font-weight: 750; }
.pagination-ronde a:hover { border-color: var(--mrri-vert); color: var(--mrri-vert-dark); }
.pagination-ronde .actif { border-color: var(--mrri-bleu); background: var(--mrri-bleu); color: #fff; }
.pagination-ronde .desactive { opacity: .4; pointer-events: none; }

@media (max-width: 767.98px) {
  .consult-search-form { grid-template-columns: 1fr; }
  .consult-submit { width: 100%; }
  .consult-results-head { align-items: flex-start; flex-direction: column; }
  .consult-sort { width: 100%; justify-content: space-between; }
  .consult-sort select { max-width: 65%; }
  .consult-hero { padding-bottom: 2.8rem; }
  .consult-content { padding-top: 1.7rem; }
}
@media (prefers-reduced-motion: reduce) { .consult-carte, .consult-chip, .consult-submit { transition: none; } }
</style>

<div class="consult-page">
  <section class="consult-hero">
    <div class="container">
      <div class="consult-kicker">Participation citoyenne</div>
      <h1>Consultations publiques</h1>
      <p>Donnez votre avis sur les consultations ouvertes par le Ministère, les Institutions constitutionnelles et les A.A.I.</p>
      <div class="consult-stats" aria-label="Résumé des consultations">
        <span class="consult-stat"><span class="pulse" aria-hidden="true"></span><strong><?= $totalConsultations ?></strong> consultation(s) ouverte(s) selon vos critères</span>
        <span class="consult-stat">Votre voix compte</span>
      </div>
    </div>
  </section>

  <div class="container consult-tools">
    <div class="consult-search-panel">
      <form action="" method="get" class="consult-search-form" role="search" aria-label="Rechercher une consultation">
        <div class="consult-field">
          <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
          <input type="text" name="q" aria-label="Rechercher une consultation" placeholder="Titre, sujet ou structure…" value="<?= echapper($search) ?>">
        </div>
        <div class="consult-field">
          <select name="type" aria-label="Filtrer par type">
            <option value="">Tous les types</option>
            <option value="generale" <?= $type === 'generale' ? 'selected' : '' ?>>Consultations générales</option>
            <option value="structure" <?= $type === 'structure' ? 'selected' : '' ?>>Liées à une structure</option>
          </select>
        </div>
        <button type="submit" class="consult-submit">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
          Rechercher
        </button>
        <?php if ($search !== '' || $type !== ''): ?>
          <a href="consultations.php" class="small text-decoration-underline" style="grid-column:1/-1; color:var(--mrri-text-light);">Effacer les filtres</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <main class="container consult-content">
    <div>
      <h2 class="consult-section-title">Consultations en cours</h2>
      <p class="consult-section-intro">Choisissez une consultation, lisez le sujet et partagez votre contribution avant la date de clôture.</p>
    </div>

    <nav class="consult-filters" aria-label="Filtres rapides par type">
      <span class="consult-filter-label">Type :</span>
      <a href="<?= echapper(lienConsultations(['type' => '', 'page' => 1])) ?>" class="consult-chip <?= $type === '' ? 'active' : '' ?>">Toutes</a>
      <a href="<?= echapper(lienConsultations(['type' => 'generale', 'page' => 1])) ?>" class="consult-chip <?= $type === 'generale' ? 'active' : '' ?>"><span class="pastille generale" aria-hidden="true"></span>Générales</a>
      <a href="<?= echapper(lienConsultations(['type' => 'structure', 'page' => 1])) ?>" class="consult-chip <?= $type === 'structure' ? 'active' : '' ?>"><span class="pastille structure" aria-hidden="true"></span>Liées à une structure</a>
    </nav>

    <div class="consult-results-head">
      <div class="consult-result-count"><span class="consult-result-dot" aria-hidden="true"></span><span><strong><?= $totalConsultations ?></strong> consultation(s) trouvée(s)</span></div>
      <form action="" method="get" class="consult-sort">
        <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= echapper($search) ?>"><?php endif; ?>
        <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= echapper($type) ?>"><?php endif; ?>
        <label for="tri">Trier par</label>
        <select name="tri" id="tri" onchange="this.form.submit()">
          <option value="recent" <?= $tri !== 'cloture' ? 'selected' : '' ?>>Plus récentes</option>
          <option value="cloture" <?= $tri === 'cloture' ? 'selected' : '' ?>>Clôture la plus proche</option>
        </select>
      </form>
    </div>

    <?php if (empty($consultations)): ?>
      <div class="consult-empty">
        <div class="consult-empty-icon" aria-hidden="true"><svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
        <h2>Aucune consultation ouverte</h2>
        <p><?= ($search !== '' || $type !== '') ? 'Essayez avec un autre mot-clé ou un autre type de consultation.' : 'Revenez bientôt : de nouvelles consultations seront publiées prochainement.' ?></p>
      </div>
    <?php else: ?>
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 consult-grille">
        <?php foreach ($consultations as $consultation): ?>
          <?php
            $estGenerale = empty($consultation['id_structure']);
            $jours = joursRestants($consultation['date_fermeture']);
          ?>
          <div class="col">
            <article class="consult-carte <?= $estGenerale ? 'generale' : 'structure' ?>">
              <div class="consult-carte-top">
                <div class="consult-icone" aria-hidden="true">
                  <!-- Formulaire de consultation : questionnaire avec réponses cochées et bulle d'avis -->
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 4h1.5A1.5 1.5 0 0 1 18 5.5V10"/>
                    <path d="M9 4H7.5A1.5 1.5 0 0 0 6 5.5v14A1.5 1.5 0 0 0 7.5 21H12"/>
                    <rect x="9" y="2.5" width="6" height="3" rx="1"/>
                    <path d="m8.5 10 1 1 2-2M13 10h2"/>
                    <path d="m8.5 14.5 1 1 2-2"/>
                    <path d="M15 13.5h5.5a1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1H18l-2 2v-2h-1a1 1 0 0 1-1-1v-3.5a1 1 0 0 1 1-1z"/>
                  </svg>
                </div>
                <?php if ($jours === null): ?>
                  <span class="consult-echeance libre">Sans échéance</span>
                <?php elseif ($jours === 0): ?>
                  <span class="consult-echeance urgent">Dernier jour</span>
                <?php else: ?>
                  <span class="consult-echeance <?= $jours <= 7 ? 'urgent' : '' ?>"><?= $jours ?> jour<?= $jours > 1 ? 's' : '' ?> restant<?= $jours > 1 ? 's' : '' ?></span>
                <?php endif; ?>
              </div>

              <span class="consult-badge <?= $estGenerale ? 'generale' : 'structure' ?>" title="<?= $estGenerale ? 'Consultation générale' : echapper($consultation['nom_struc']) ?>">
                <?= $estGenerale ? 'Consultation générale' : echapper($consultation['sigle'] ?: $consultation['nom_struc']) ?>
              </span>

              <h3><?= echapper($consultation['titre']) ?></h3>
              <p class="extrait"><?= echapper(mb_strimwidth($consultation['contenu'] ?? '', 0, 220, '…')) ?></p>

              <div class="consult-dates">
                <div><span>Ouverte le</span><strong><?= formaterDateFr($consultation['date_ouverture']) ?></strong></div>
                <div><span>Clôture</span><strong><?= !empty($consultation['date_fermeture']) ? formaterDateFr($consultation['date_fermeture']) : 'Non définie' ?></strong></div>
              </div>

              <a href="consultation_detail.php?id=<?= (int) $consultation['id_consultation'] ?>" class="participer">Participer à la consultation <span aria-hidden="true">→</span></a>
            </article>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pagination-ronde" aria-label="Pagination des consultations">
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
  </main>
</div>

<?php require '../../includes/footer.php'; ?>
