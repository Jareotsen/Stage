<?php
session_start();
require '../../config/database.php';

$actuStmt = $db->query(
    "SELECT actualites.id_actu, actualites.titre, actualites.contenu, actualites.date_publication, structure.nom_struc
     FROM actualites
     JOIN structure ON actualites.id_structure = structure.id_struc
     ORDER BY actualites.date_publication DESC"
);
$actualites = $actuStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupère la première photo de chaque actualité
foreach ($actualites as &$actualite) {
    $photoStmt = $db->prepare(
        "SELECT Photos_actu_url FROM actualites_photos WHERE id_actualites = :id ORDER BY Position LIMIT 1"
    );
    $photoStmt->execute([':id' => $actualite['id_actu']]);
    $actualite['image'] = $photoStmt->fetchColumn(); // false si aucune photo
}
unset($actualite);

// Formate une date SQL (YYYY-MM-DD) en français, ex : "12 juin 2024"
function formaterDateFr(string $dateSql): string {
    $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $timestamp = strtotime($dateSql);
    return date('d', $timestamp) . ' ' . $mois[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

require '../../includes/header.php';
?>

<style>
/* Présentation inspirée du portail éditorial fourni en référence */
.actualite-page{max-width:1440px;margin:0 auto;padding:36px 28px 76px;color:#152b49}
.actualite-hero{margin:0 0 22px;padding:0;background:transparent;box-shadow:none;border:0;overflow:visible;color:#145ca8}
.actualite-hero:before,.actualite-hero:after{display:none}
.actualite-hero-inner{max-width:none}
.actualite-kicker{display:none}
.actualite-page .actualite-hero h1{margin:0;color:#1262ad;font-size:clamp(2.3rem,5vw,3.6rem);font-weight:500;letter-spacing:-.045em;line-height:1.08}
.actualite-hero p{max-width:800px;margin:12px 0 0;color:#52677d;font-size:1rem;line-height:1.65}
.actualite-toolbar{display:flex;justify-content:space-between;align-items:center;gap:18px;margin:0 0 20px;border-bottom:1px solid #dce5ee;padding-bottom:15px}
.actualite-tabs{display:flex;flex-wrap:wrap;gap:8px}
.actualite-tab{display:inline-flex;align-items:center;justify-content:center;min-height:35px;padding:8px 16px;border:1px solid #d9e2eb;border-radius:999px;background:#fff;color:#42617f;font-size:.78rem;font-weight:650;text-decoration:none}
.actualite-tab.is-active{background:#1262ad;border-color:#1262ad;color:#fff}
.actualite-all{color:#285b80;font-size:.8rem;font-weight:750;text-decoration:none;border-bottom:1px solid #8aafc8;padding-bottom:3px;white-space:nowrap}
.actualite-feature-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);grid-template-rows: minmax(165px, 1fr) minmax(165px, 1fr);gap:12px;min-height:470px}
.actualite-card{position:relative;min-width:0;display:flex;flex-direction:column;overflow:hidden;border:0;border-radius:0;background:#dceaf6;box-shadow:none;transition:transform .2s ease,box-shadow .2s ease}
.actualite-card:hover{transform:translateY(-2px);border-color:transparent;box-shadow:0 8px 22px rgba(15,48,80,.12)}
.actualite-card.featured{grid-column:1;grid-row:1 / span 2;min-height:470px;background:#dceaf6}
.actualite-card.side-large{grid-column:2;grid-row:1}
.actualite-card.side-small{min-height:165px}
.actualite-card.side-small:nth-of-type(3){grid-column:2;grid-row:2}
.actualite-card.side-small:nth-of-type(4){display:none}
.actualite-card .actualite-media{position:absolute;inset:0;height:auto;overflow:hidden;background:#d8e7f4}
.actualite-card img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
.actualite-card:hover img{transform:scale(1.025)}
.actualite-card.featured:after{content:"";position:absolute;inset:35% 0 0;background:linear-gradient(180deg,rgba(8,25,45,0) 0%,rgba(8,25,45,.84) 100%);pointer-events:none}
.actualite-no-image{display:flex;width:100%;height:100%;align-items:center;justify-content:center;background:linear-gradient(135deg,#dceaf6,#c9deef);color:#3b6c98}
.actualite-no-image svg{width:52px;height:52px;opacity:.75}
.actualite-content{position:relative;z-index:1;display:flex;flex:1;flex-direction:column;align-items:flex-start;justify-content:flex-end;padding:20px;background:transparent}
.actualite-card.featured .actualite-content{padding:25px;color:#fff}
.actualite-card.side-large .actualite-content,.actualite-card.side-small .actualite-content{justify-content:space-between;padding:18px;background:rgba(217,232,245,.93)}
.actualite-meta{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:12px;color:#66829d;font-size:.67rem;font-weight:750;line-height:1.4;text-transform:uppercase;letter-spacing:.035em}
.actualite-meta .meta-dot{display:none}
.actualite-source{color:inherit}
.actualite-card.featured .actualite-meta{color:rgba(255,255,255,.88)}
.actualite-card h2{margin:0 0 10px;color:#164e82;font-size:1.18rem;line-height:1.35;font-weight:650;overflow-wrap:anywhere}
.actualite-card.featured h2{margin:0;color:#fff;font-size:clamp(1.35rem,2.1vw,2rem);line-height:1.22;font-weight:600}
.actualite-card.side-small h2{font-size:.95rem;line-height:1.35;margin-top:auto}
.actualite-card p{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;line-clamp:3;overflow:hidden;margin:0 0 15px;color:#536e88;font-size:.88rem;line-height:1.6}
.actualite-card.featured p{color:rgba(255,255,255,.86);margin-top:10px;margin-bottom:0}
.actualite-link{display:inline-flex;align-items:center;gap:8px;margin-top:12px;padding:0;border:0;width:auto;color:#17598f;font-weight:750;font-size:.8rem;text-decoration:none}
.actualite-card.featured .actualite-link{color:#fff}
.actualite-link span{transition:transform .2s ease}
.actualite-link:hover{color:#0b7a49}
.actualite-card.featured .actualite-link:hover{color:#d9f3e5}
.actualite-link:hover span{transform:translateX(4px)}
.actualite-extra-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:12px}
.actualite-empty{padding:46px 24px;text-align:center;border:1px solid #dce5ee;background:#f6f9fc;border-radius:4px}
.actualite-empty-icon{width:58px;height:58px;display:grid;place-items:center;margin:0 auto 14px;border-radius:50%;background:#e2edf7;color:#1262ad}
.actualite-empty h2{margin:0 0 8px;font-size:1.3rem}
.actualite-empty p{margin:0;color:#667b90}
.actualite-section-head{display:flex;align-items:end;justify-content:space-between;gap:18px;margin:28px 0 16px}
.actualite-section-head h2{margin:0;font-size:1.35rem;color:#164e82}
.actualite-section-head p{margin:5px 0 0;color:#667b90;font-size:.9rem}
@media(max-width:900px){.actualite-page{padding:28px 20px 56px}.actualite-feature-grid{grid-template-columns:minmax(0,1fr) minmax(0,1fr);min-height:420px}.actualite-card.featured{min-height:420px}.actualite-extra-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.actualite-card.featured h2{font-size:1.45rem}}
@media(max-width:640px){.actualite-page{padding:24px 14px 44px}.actualite-page .actualite-hero h1{font-size:2.35rem}.actualite-toolbar{align-items:flex-start;flex-direction:column}.actualite-tabs{gap:6px}.actualite-tab{padding:7px 12px;font-size:.74rem}.actualite-feature-grid{display:flex;flex-direction:column;min-height:0}.actualite-card.featured{min-height:360px}.actualite-card.side-large{min-height:220px}.actualite-card.side-small{min-height:180px}.actualite-card.side-small:nth-of-type(4){display:flex}.actualite-extra-grid{grid-template-columns:1fr}.actualite-card.featured h2{font-size:1.45rem}.actualite-card.side-large .actualite-content,.actualite-card.side-small .actualite-content{min-height:180px}}
@media(prefers-reduced-motion:reduce){.actualite-card,.actualite-card img,.actualite-link span{transition:none}}
</style>

<main class="actualite-page">
    <header class="actualite-hero">
        <div class="actualite-hero-inner">
            <h1>Dernières actualités</h1>
            <p>Les informations et annonces officielles </p>
        </div>
    </header>

   

    <?php if (empty($actualites)): ?>
        <section class="actualite-empty" aria-live="polite">
            <div class="actualite-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21z"/><path d="M4 5.5V21M8 7h8M8 11h8"/></svg>
            </div>
            <h2>Aucune actualité pour le moment</h2>
            <p>Les nouvelles publications des structures apparaîtront ici dès leur mise en ligne.</p>
        </section>
    <?php else: ?>
        <section class="actualite-feature-grid" aria-label="Dernières actualités">
            <?php foreach ($actualites as $index => $actualite): ?>
                <?php
                    $cardClass = $index === 0 ? 'featured' : ($index === 1 ? 'side-large' : ($index <= 3 ? 'side-small' : 'extra'));
                    $isExtra = $index > 3;
                ?>
                <?php if ($isExtra && $index === 4): ?><div class="actualite-extra-grid" aria-label="Autres actualités"><?php endif; ?>
                <article class="actualite-card <?= $cardClass ?>">
                    <div class="actualite-media">
                        <?php if (!empty($actualite['image'])): ?>
                            <img src="../../uploads/photos/<?= htmlspecialchars($actualite['image'], ENT_QUOTES, 'UTF-8') ?>" alt="Illustration de l’actualité : <?= htmlspecialchars($actualite['titre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                        <?php else: ?>
                            <div class="actualite-no-image" aria-label="Aucune image disponible">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-7h6v7M9 10h.01M15 10h.01"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="actualite-content">
                        <div class="actualite-meta">
                            <time datetime="<?= htmlspecialchars(date('Y-m-d', strtotime($actualite['date_publication'])), ENT_QUOTES, 'UTF-8') ?>"><?= formaterDateFr($actualite['date_publication']) ?></time>
                            <span><?= htmlspecialchars($actualite['nom_struc'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <h2><?= htmlspecialchars($actualite['titre'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if ($index === 0): ?>
                            <p><?= htmlspecialchars(mb_strimwidth(strip_tags($actualite['contenu']), 0, 190, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <a class="actualite-link" href="actualite_detail.php?id=<?= (int) $actualite['id_actu'] ?>">Lire l’actualité <span aria-hidden="true">→</span></a>
                    </div>
                </article>
                <?php if ($index === count($actualites) - 1 && count($actualites) > 4): ?></div><?php endif; ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<?php require '../../includes/footer.php'; ?>