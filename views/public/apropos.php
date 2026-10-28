<?php
session_start();
require '../../config/database.php';

/* Chiffres affichés par les compteurs animés */
$chiffres = [
    'structures' => (int) $db->query("SELECT COUNT(*) FROM structure")->fetchColumn(),
    'actualites' => (int) $db->query("SELECT COUNT(*) FROM actualites")->fetchColumn(),
    'documents' => (int) $db->query("SELECT COUNT(*) FROM documents WHERE est_public = 1")->fetchColumn(),
    'consultations' => (int) $db->query(
        "SELECT COUNT(*) FROM consultations
         WHERE statut = 'ouverte' AND (date_fermeture IS NULL OR date_fermeture >= NOW())"
    )->fetchColumn(),
];

// Titre découpé lettre par lettre pour l'animation d'entrée (les espaces restent des espaces)
function lettres(string $texte): string {
    $html = '';
    foreach (mb_str_split($texte) as $i => $lettre) {
        $html .= $lettre === ' '
            ? ' '
            : '<span class="ap-lettre" style="--i:' . $i . '">' . htmlspecialchars($lettre, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    return $html;
}

// Citation découpée mot par mot pour la révélation au défilement
function mots(string $texte): string {
    $html = '';
    foreach (preg_split('/\s+/u', $texte) as $i => $mot) {
        $html .= '<span class="ap-mot" data-mot="' . $i . '">' . htmlspecialchars($mot, ENT_QUOTES, 'UTF-8') . '</span> ';
    }
    return $html;
}

$etapes = [
    ['bi-telephone', 'Appeler quelqu’un', 'Premier numéro composé, premier espoir.'],
    ['bi-telephone-forward', '…qui appelait quelqu’un', 'La chaîne de solidarité administrative s’active.'],
    ['bi-question-circle', '…qui demandait à quelqu’un', 'Le dossier gagne en notoriété.'],
    ['bi-door-open', '…qui n’était pas là', 'Bureau vide, café encore chaud.'],
    ['bi-folder2-open', 'Parti chercher le classeur', 'Le fameux classeur. Celui de 2014.'],
    ['bi-airplane', '…lui-même parti en mission', 'Le classeur, lui, voyage plus que nous.'],
];

$personas = [
    ['bi-briefcase', 'Agents du ministère', 'Ceux qui savent où sont les vrais documents.'],
    ['bi-pin-map', 'Points focaux', 'Ceux qui savent qui a les documents.'],
    ['bi-shield-lock', 'Super administrateurs', 'Ceux qui savent tout (ou font semblant).'],
    ['bi-people', 'Citoyens', 'Ceux qui veulent comprendre une structure sans lire 200 pages de PDF.'],
];

require '../../includes/header.php';
?>

<style>
/* =========================================================
   À PROPOS — page vitrine animée
   Tout passe par transform / opacity (fluide, sans recalcul de mise en page)
   et s'arrête si l'utilisateur a demandé moins d'animations.
   ========================================================= */
.ap { overflow-x: clip; color: var(--acc-encre); }
.ap-section { position: relative; padding: clamp(4.5rem, 10vw, 7.5rem) 0; }
.ap-surtitre { display: inline-block; margin-bottom: .8rem; color: var(--acc-vert-fonce); font-size: .76rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
.ap-h2 { margin: 0 0 1rem; color: var(--acc-encre); font-family: var(--font-title); font-size: clamp(2rem, 4.4vw, 3.3rem); font-weight: 600; line-height: 1.08; }
.ap-intro { max-width: 58ch; margin: 0; color: var(--acc-gris); font-size: 1.08rem; line-height: 1.75; }

/* Apparition au défilement */
[data-reveal] { opacity: 0; transform: translateY(40px); transition: opacity .9s cubic-bezier(.2,.7,.2,1), transform .9s cubic-bezier(.2,.7,.2,1); transition-delay: var(--delai, 0s); }
[data-reveal="gauche"] { transform: translateX(-60px); }
[data-reveal="droite"] { transform: translateX(60px); }
[data-reveal="zoom"] { transform: scale(.85); }
[data-reveal].visible { opacity: 1; transform: none; }

/* ---------- 1. HERO ---------- */
.ap-hero { position: relative; display: flex; align-items: center; min-height: min(92vh, 860px); overflow: hidden; background: radial-gradient(120% 90% at 80% 10%, #16305C 0%, var(--acc-nuit) 55%, #050E1F 100%); color: #fff; isolation: isolate; }
.ap-hero-grille { position: absolute; inset: 0; z-index: -2; background-image: linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px); background-size: 56px 56px; mask-image: radial-gradient(70% 60% at 60% 40%, #000 30%, transparent 100%); transform: translate3d(calc(var(--mx, 0) * -12px), calc(var(--my, 0) * -12px), 0); transition: transform .4s ease-out; }
.ap-hero .container { position: relative; z-index: 2; padding-bottom: 6rem; }
.ap-hero .ap-surtitre { color: var(--acc-jaune); opacity: 0; animation: apFondu .8s .2s forwards; }
.ap-titre { margin: 0 0 1.4rem; color: #fff; font-family: var(--font-title); font-size: clamp(2.8rem, 8.5vw, 6.6rem); font-weight: 600; line-height: .98; letter-spacing: -.02em; }
.ap-lettre { display: inline-block; opacity: 0; transform: translateY(.7em) rotate(8deg); animation: apLettre .9s cubic-bezier(.2,.8,.2,1.2) forwards; animation-delay: calc(.35s + var(--i) * .045s); }
.ap-titre .ap-accent { color: var(--acc-jaune); }
.ap-hero-texte { max-width: 54ch; margin: 0 0 2.2rem; color: rgba(255,255,255,.8); font-size: clamp(1.05rem, 1.6vw, 1.22rem); line-height: 1.7; opacity: 0; animation: apMonte 1s 1.2s forwards; }
.ap-hero-actions { display: flex; flex-wrap: wrap; gap: .9rem; opacity: 0; animation: apMonte 1s 1.45s forwards; }
.ap-bouton { position: relative; display: inline-flex; align-items: center; gap: .6rem; padding: .95rem 1.5rem; overflow: hidden; border: 2px solid currentColor; border-radius: 999px; color: #fff; font-weight: 700; transition: color .3s ease, transform .3s ease; isolation: isolate; }
.ap-bouton::before { content: ""; position: absolute; inset: 0; z-index: -1; background: var(--acc-jaune); transform: scaleX(0); transform-origin: right; transition: transform .45s cubic-bezier(.7,0,.2,1); }
.ap-bouton:hover { color: var(--acc-nuit); }
.ap-bouton:hover::before { transform: scaleX(1); transform-origin: left; }
.ap-bouton.plein { border-color: var(--acc-jaune); background: var(--acc-jaune); color: var(--acc-nuit); }
.ap-bouton.plein::before { background: #fff; }
.ap-bouton span { transition: transform .3s ease; }
.ap-bouton:hover span { transform: translateX(4px); }

/* Documents qui flottent dans le hero (parallaxe à la souris) */
.ap-flottants { position: absolute; inset: 0; z-index: -1; pointer-events: none; }
.ap-flottant { position: absolute; top: var(--y); left: var(--x); color: rgba(255,255,255,.14); font-size: var(--t, 2rem); transform: translate3d(calc(var(--mx, 0) * var(--p) * 1px), calc(var(--my, 0) * var(--p) * 1px), 0); transition: transform .6s ease-out; }
.ap-flottant i { display: block; animation: apFlotte var(--d, 9s) ease-in-out infinite alternate; }

/* Vagues aux couleurs du drapeau */
.ap-vagues { position: absolute; left: 0; right: 0; bottom: -1px; z-index: 1; height: clamp(90px, 13vw, 170px); }
.ap-vagues svg { position: absolute; bottom: 0; width: 200%; height: 100%; }
.ap-vague-1 { animation: apVague 18s linear infinite; }
.ap-vague-2 { animation: apVague 13s linear infinite reverse; }
.ap-vague-3 { animation: apVague 24s linear infinite; }
.ap-defiler { position: absolute; left: 50%; bottom: clamp(100px, 14vw, 180px); z-index: 2; display: flex; flex-direction: column; align-items: center; gap: .5rem; color: rgba(255,255,255,.6); font-size: .7rem; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; transform: translateX(-50%); opacity: 0; animation: apFondu 1s 2s forwards; }
.ap-souris { width: 22px; height: 34px; border: 2px solid currentColor; border-radius: 12px; }
.ap-souris::after { content: ""; display: block; width: 4px; height: 7px; margin: 6px auto 0; border-radius: 2px; background: var(--acc-jaune); animation: apMolette 1.6s ease-in-out infinite; }

/* ---------- 2. AVANT : la quête du classeur ---------- */
.ap-avant { background: #fff; }
.ap-chrono { position: relative; max-width: 980px; margin: 4rem auto 0; }
.ap-chrono-ligne { position: absolute; top: 0; bottom: 0; left: 50%; width: 3px; border-radius: 3px; background: var(--acc-ligne); transform: translateX(-50%); }
.ap-chrono-ligne::after { content: ""; position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(var(--acc-vert), var(--acc-jaune), var(--acc-bleu)); transform: scaleY(var(--progression, 0)); transform-origin: top; }
.ap-etape { position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: 5rem; margin-bottom: 2.2rem; }
.ap-etape-carte { grid-column: 1; justify-self: end; max-width: 380px; padding: 1.3rem 1.5rem; border: 1px solid var(--acc-ligne); border-radius: 14px; background: #fff; box-shadow: 0 10px 30px rgba(10,26,51,.06); }
.ap-etape:nth-child(even) .ap-etape-carte { grid-column: 2; justify-self: start; }
.ap-etape-carte strong { display: block; margin-bottom: .2rem; font-family: var(--font-title); font-size: 1.25rem; font-weight: 600; }
.ap-etape-carte p { margin: 0; color: var(--acc-gris); font-size: .92rem; }
.ap-etape-pastille { position: absolute; top: 50%; left: 50%; z-index: 1; display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border: 3px solid #fff; border-radius: 50%; background: var(--acc-nuit); color: #fff; font-size: 1.2rem; box-shadow: 0 0 0 1px var(--acc-ligne); transform: translate(-50%, -50%) scale(.4); transition: transform .6s cubic-bezier(.3,1.6,.5,1), background .4s ease; }
.ap-etape.visible .ap-etape-pastille { transform: translate(-50%, -50%) scale(1); }
.ap-etape:nth-child(5) .ap-etape-pastille { background: var(--acc-jaune); color: var(--acc-nuit); }
.ap-etape:nth-child(5).visible .ap-etape-pastille i { animation: apSecoue .5s .6s 3; }
.ap-tampon-zone { display: flex; justify-content: center; margin-top: 3rem; }
.ap-tampon { padding: .7rem 1.6rem; border: 4px solid #C4161C; border-radius: 10px; color: #C4161C; font-family: var(--font-title); font-size: clamp(1.4rem, 3vw, 2.2rem); font-weight: 700; letter-spacing: .08em; text-transform: uppercase; opacity: 0; transform: scale(2.6) rotate(-14deg); }
.ap-tampon.visible { animation: apTampon .55s cubic-bezier(.5,0,.7,1.4) forwards; }
.ap-tampon-legende { margin-top: 1.2rem; color: var(--acc-gris); text-align: center; }

/* ---------- 3. MISSION : cartes inclinables ---------- */
.ap-mission { background: var(--acc-fond); }
.ap-cartes { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1.4rem; margin-top: 3rem; perspective: 900px; }
.ap-carte { position: relative; padding: 2rem 1.6rem 1.8rem; overflow: hidden; border-radius: 18px; background: #fff; box-shadow: 0 12px 30px rgba(10,26,51,.07); transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)); transform-style: preserve-3d; transition: transform .25s ease-out, box-shadow .3s ease; }
.ap-carte:hover { box-shadow: 0 24px 50px rgba(10,26,51,.14); }
.ap-carte::after { content: ""; position: absolute; inset: 0; background: radial-gradient(260px circle at var(--lx, 50%) var(--ly, 50%), rgba(252,209,22,.18), transparent 60%); opacity: 0; transition: opacity .3s ease; pointer-events: none; }
.ap-carte:hover::after { opacity: 1; }
.ap-carte-num { position: absolute; top: .6rem; right: 1rem; color: var(--acc-fond); font-family: var(--font-title); font-size: 4.5rem; font-weight: 700; line-height: 1; transform: translateZ(10px); }
.ap-carte svg { position: relative; width: 52px; height: 52px; margin-bottom: 1.2rem; color: var(--acc-vert); transform: translateZ(40px); }
/* Tracé progressif de l'icône quand la carte apparaît */
.ap-carte svg * { stroke-dasharray: 160; stroke-dashoffset: 160; }
.ap-carte.visible svg * { animation: apTrace 1.6s cubic-bezier(.6,0,.2,1) calc(var(--delai, 0s) + .3s) forwards; }
.ap-carte h3 { position: relative; margin: 0 0 .5rem; color: var(--acc-encre); font-family: var(--font-title); font-size: 1.4rem; font-weight: 600; transform: translateZ(30px); }
.ap-carte p { position: relative; margin: 0; color: var(--acc-gris); font-size: .92rem; line-height: 1.6; transform: translateZ(20px); }

/* ---------- 4. CHIFFRES ---------- */
.ap-chiffres { background: var(--acc-nuit); color: #fff; }
.ap-chiffres-grille { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
.ap-chiffre { padding: 1.5rem 1rem; border-left: 1px solid rgba(255,255,255,.15); }
.ap-chiffre strong { display: block; font-family: var(--font-title); font-size: clamp(2.8rem, 6vw, 4.6rem); font-weight: 600; line-height: 1; font-variant-numeric: tabular-nums; }
.ap-chiffre:nth-child(1) strong { color: var(--acc-vert); }
.ap-chiffre:nth-child(2) strong { color: var(--acc-jaune); }
.ap-chiffre:nth-child(3) strong { color: #6FA3E6; }
.ap-chiffre span { display: block; margin-top: .6rem; color: rgba(255,255,255,.7); font-size: .9rem; }

/* ---------- 5. QUI : orbite ---------- */
.ap-qui { background: #fff; }
.ap-qui-grille { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: 3rem; align-items: center; }
.ap-orbite { position: relative; width: min(100%, 520px); aspect-ratio: 1; margin-inline: auto; }
.ap-orbite-anneau { position: absolute; inset: 9%; border: 2px dashed var(--acc-ligne); border-radius: 50%; }
.ap-orbite-centre { position: absolute; inset: 31%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; border-radius: 50%; background: var(--acc-nuit); color: #fff; text-align: center; box-shadow: 0 0 0 12px rgba(10,26,51,.06), 0 0 0 26px rgba(10,26,51,.03); }
.ap-orbite-centre strong { font-family: var(--font-title); font-size: clamp(1rem, 2.2vw, 1.3rem); font-weight: 600; line-height: 1.2; }
.ap-orbite-centre small { margin-top: .4rem; color: rgba(255,255,255,.72); font-size: clamp(.7rem, 1.4vw, .8rem); line-height: 1.4; transition: opacity .25s ease; }
.ap-orbite-piste { position: absolute; inset: 9%; container-type: size; animation: apTourne 36s linear infinite; }
.ap-orbite:hover .ap-orbite-piste, .ap-orbite:focus-within .ap-orbite-piste,
.ap-orbite:hover .ap-persona-corps, .ap-orbite:focus-within .ap-persona-corps { animation-play-state: paused; }
/* Chaque profil est posé sur l'anneau : rayon = moitié de la piste (unité de conteneur cqw) */
.ap-persona { position: absolute; top: 50%; left: 50%; width: 0; height: 0; transform: rotate(var(--a)) translate(50cqw) rotate(calc(var(--a) * -1)); }
.ap-persona-corps { position: absolute; display: flex; flex-direction: column; align-items: center; gap: .35rem; width: 120px; transform: translate(-50%, -50%); animation: apTourne 36s linear infinite reverse; }
.ap-persona button { display: flex; align-items: center; justify-content: center; width: 64px; height: 64px; border: 3px solid #fff; border-radius: 50%; background: var(--acc-jaune); color: var(--acc-nuit); font-size: 1.5rem; box-shadow: 0 10px 24px rgba(10,26,51,.18); cursor: pointer; transition: transform .3s cubic-bezier(.3,1.6,.5,1), background .3s ease; }
.ap-persona:nth-child(2) button { background: var(--acc-vert); color: #fff; }
.ap-persona:nth-child(3) button { background: var(--acc-bleu); color: #fff; }
.ap-persona:nth-child(4) button { background: #fff; color: var(--acc-nuit); }
.ap-persona button:hover, .ap-persona button:focus-visible, .ap-persona button.actif { transform: scale(1.18); }
.ap-persona-nom { padding: .15rem .55rem; border-radius: 999px; background: rgba(255,255,255,.92); font-size: .74rem; font-weight: 700; white-space: nowrap; }
.ap-qui-liste { margin: 2rem 0 0; padding: 0; list-style: none; }
.ap-qui-liste li { display: flex; gap: .9rem; padding: .9rem 0; border-top: 1px solid var(--acc-ligne); }
.ap-qui-liste i { color: var(--acc-vert-fonce); font-size: 1.2rem; }
.ap-qui-liste strong { display: block; }
.ap-qui-liste span { color: var(--acc-gris); font-size: .92rem; }

/* ---------- 6. AVANT / MAINTENANT : comparateur ---------- */
.ap-compare-section { background: var(--acc-fond); }
.ap-compare { position: relative; height: clamp(360px, 50vw, 480px); margin-top: 3rem; overflow: hidden; border-radius: 22px; box-shadow: 0 30px 60px rgba(10,26,51,.15); user-select: none; --pos: 50%; }
.ap-panneau { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; padding: clamp(1.4rem, 3vw, 2.4rem); }
.ap-panneau h3 { margin: 0; color: inherit; font-family: var(--font-title); font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 600; }
.ap-panneau p { margin: .3rem 0 0; font-size: .95rem; }
.ap-panneau.avant { background: repeating-linear-gradient(-45deg, #EDE6D6 0 14px, #E6DDC9 14px 28px); color: #4A3F2A; }
.ap-panneau.maintenant { background: linear-gradient(135deg, var(--acc-nuit), #16305C); color: #fff; align-items: flex-end; text-align: right; clip-path: inset(0 0 0 var(--pos)); }
.ap-papier { position: absolute; top: var(--y); left: var(--x); padding: .55rem .8rem; border-radius: 4px; background: #FFFDF6; box-shadow: 0 6px 14px rgba(74,63,42,.18); color: #6B5D40; font-family: ui-monospace, monospace; font-size: .74rem; transform: rotate(var(--r)); animation: apPapier 3.2s ease-in-out infinite alternate; animation-delay: var(--d); }
.ap-tuile { position: absolute; top: var(--y); right: var(--x); display: flex; align-items: center; gap: .55rem; padding: .7rem .9rem; border: 1px solid rgba(255,255,255,.15); border-radius: 12px; background: rgba(255,255,255,.07); backdrop-filter: blur(6px); font-size: .82rem; font-weight: 600; animation: apFlotte 5s ease-in-out infinite alternate; animation-delay: var(--d); }
.ap-tuile i { color: var(--acc-vert); }
.ap-poignee { position: absolute; top: 0; bottom: 0; left: var(--pos); z-index: 3; width: 3px; background: #fff; transform: translateX(-50%); pointer-events: none; }
.ap-poignee::after { content: "⟷"; position: absolute; top: 50%; left: 50%; display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 50%; background: #fff; color: var(--acc-nuit); font-size: 1.3rem; font-weight: 700; box-shadow: 0 8px 22px rgba(0,0,0,.25); transform: translate(-50%, -50%); }
.ap-compare input { position: absolute; inset: 0; z-index: 4; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: ew-resize; }
.ap-compare input:focus-visible + .ap-poignee::after { outline: 3px solid var(--acc-jaune); outline-offset: 3px; }

/* ---------- 7. PHILOSOPHIE : mots révélés au défilement ---------- */
.ap-philo { background: #fff; }
.ap-citation { max-width: 22ch; margin: 0 auto; font-family: var(--font-title); font-size: clamp(2rem, 5.4vw, 4.2rem); font-weight: 600; line-height: 1.15; text-align: center; }
.ap-mot { color: #D5DAE2; transition: color .35s ease; }
.ap-mot.lu { color: var(--acc-encre); }
.ap-mot.lu:nth-last-child(-n+3) { color: var(--acc-vert-fonce); }
.ap-citation-auteur { margin-top: 1.6rem; color: var(--acc-gris); text-align: center; font-style: italic; }

/* ---------- 8. MERCI ---------- */
.ap-merci { overflow: hidden; background: radial-gradient(90% 120% at 50% 100%, #16305C, var(--acc-nuit)); color: #fff; text-align: center; }
.ap-merci canvas { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.ap-merci .container { position: relative; z-index: 1; }
.ap-merci .ap-h2 { color: #fff; font-size: clamp(2.4rem, 6vw, 4.6rem); }
.ap-merci p { max-width: 52ch; margin: 0 auto 2.2rem; color: rgba(255,255,255,.78); font-size: 1.1rem; line-height: 1.7; }
.ap-merci .ap-hero-actions { justify-content: center; opacity: 1; animation: none; }
.ap-signature { margin-top: 3rem; color: rgba(255,255,255,.5); font-size: .85rem; }

/* ---------- Animations ---------- */
@keyframes apLettre { to { opacity: 1; transform: none; } }
@keyframes apFondu { to { opacity: 1; } }
@keyframes apMonte { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }
@keyframes apFlotte { from { transform: translateY(0) rotate(0deg); } to { transform: translateY(-22px) rotate(6deg); } }
@keyframes apVague { from { transform: translateX(0); } to { transform: translateX(-50%); } }
@keyframes apMolette { 0% { opacity: 0; transform: translateY(0); } 40% { opacity: 1; } 100% { opacity: 0; transform: translateY(10px); } }
@keyframes apSecoue { 0%, 100% { transform: rotate(0); } 25% { transform: rotate(-14deg); } 75% { transform: rotate(14deg); } }
@keyframes apTampon { 0% { opacity: 0; transform: scale(2.6) rotate(-14deg); } 70% { opacity: 1; transform: scale(.92) rotate(-8deg); } 100% { opacity: 1; transform: scale(1) rotate(-8deg); } }
@keyframes apTourne { to { transform: rotate(360deg); } }
@keyframes apTrace { to { stroke-dashoffset: 0; } }
@keyframes apPapier { from { transform: rotate(var(--r)) translateY(0); } to { transform: rotate(calc(var(--r) * -1)) translateY(-8px); } }

/* ---------- Adaptation mobile ---------- */
@media (max-width: 991.98px) {
  .ap-cartes { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .ap-chiffres-grille { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .ap-qui-grille { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
  .ap-chrono-ligne { left: 26px; }
  .ap-etape { grid-template-columns: 1fr; gap: 0; padding-left: 70px; }
  .ap-etape-carte, .ap-etape:nth-child(even) .ap-etape-carte { grid-column: 1; justify-self: stretch; max-width: none; }
  .ap-etape-pastille { left: 26px; width: 44px; height: 44px; font-size: 1rem; }
  .ap-orbite { display: none; }
  .ap-flottant:nth-child(n+5) { display: none; }
}
@media (max-width: 575.98px) {
  .ap-cartes, .ap-chiffres-grille { grid-template-columns: 1fr; }
  .ap-papier:nth-child(n+4), .ap-tuile:nth-child(n+4) { display: none; }
}

/* Moins d'animations demandées : tout est affiché directement, sans mouvement */
@media (prefers-reduced-motion: reduce) {
  .ap *, .ap *::before, .ap *::after { animation: none !important; transition: none !important; }
  [data-reveal], .ap-lettre, .ap-hero-texte, .ap-hero-actions, .ap-hero .ap-surtitre, .ap-defiler, .ap-tampon { opacity: 1 !important; transform: none !important; }
  .ap-tampon { transform: rotate(-8deg) !important; }
  .ap-carte svg * { stroke-dashoffset: 0 !important; }
  .ap-chrono-ligne::after { transform: none !important; }
  .ap-mot { color: var(--acc-encre) !important; }
}
</style>

<div class="ap">

  <!-- ================= 1. HERO ================= -->
  <section class="ap-hero" id="ap-hero" aria-labelledby="ap-titre">
    <div class="ap-hero-grille" aria-hidden="true"></div>
    <div class="ap-flottants" aria-hidden="true">
      <?php
      $flottants = [
          ['bi-file-earmark-text', '12%', '8%', '2.4rem', 30, '8s'], ['bi-folder2', '22%', '78%', '3rem', -40, '10s'],
          ['bi-bank', '68%', '86%', '2.6rem', 25, '9s'], ['bi-chat-square-text', '74%', '12%', '2rem', -25, '7s'],
          ['bi-geo-alt', '40%', '92%', '1.8rem', 50, '11s'], ['bi-journal-text', '58%', '62%', '2.2rem', -55, '8.5s'],
          ['bi-search', '16%', '52%', '1.6rem', 35, '12s'], ['bi-megaphone', '84%', '48%', '2rem', -30, '9.5s'],
      ];
      foreach ($flottants as [$icone, $y, $x, $taille, $parallaxe, $duree]): ?>
        <span class="ap-flottant" style="--y:<?= $y ?>;--x:<?= $x ?>;--t:<?= $taille ?>;--p:<?= $parallaxe ?>;--d:<?= $duree ?>"><i class="bi <?= $icone ?>"></i></span>
      <?php endforeach; ?>
    </div>

    <div class="container">
      <span class="ap-surtitre">Plateforme de consultation publique · MRRI</span>
      <h1 class="ap-titre" id="ap-titre" aria-label="À propos de la plateforme">
        <span aria-hidden="true"><?= lettres('À propos de la') ?><br><span class="ap-accent"><?= lettres('plateforme') ?></span></span>
      </h1>
      <p class="ap-hero-texte">
        Bienvenue sur la plateforme du Ministère de la Réforme et des Relations avec les Institutions.
        Oui, le nom est long. Oui, il y a «&nbsp;Réforme&nbsp;» dedans. Mais promis, on est gentils.
      </p>
      <div class="ap-hero-actions">
        <a href="#ap-avant" class="ap-bouton plein">Découvrir l'histoire <span aria-hidden="true">↓</span></a>
        <a href="catalogue.php" class="ap-bouton">Explorer les structures <span aria-hidden="true">→</span></a>
      </div>
    </div>

    <div class="ap-defiler" aria-hidden="true"><span class="ap-souris"></span>Défiler</div>

    <div class="ap-vagues" aria-hidden="true">
      <svg class="ap-vague-1" viewBox="0 0 2880 160" preserveAspectRatio="none"><path fill="#009E60" fill-opacity=".85" d="M0 90 C 240 40 480 140 720 90 S 1200 40 1440 90 S 1920 140 2160 90 S 2640 40 2880 90 V160 H0Z"/></svg>
      <svg class="ap-vague-2" viewBox="0 0 2880 160" preserveAspectRatio="none"><path fill="#FCD116" fill-opacity=".9" d="M0 110 C 240 70 480 150 720 110 S 1200 70 1440 110 S 1920 150 2160 110 S 2640 70 2880 110 V160 H0Z"/></svg>
      <svg class="ap-vague-3" viewBox="0 0 2880 160" preserveAspectRatio="none"><path fill="#3A75C4" d="M0 130 C 240 105 480 155 720 130 S 1200 105 1440 130 S 1920 155 2160 130 S 2640 105 2880 130 V160 H0Z"/></svg>
    </div>
  </section>

  <!-- ================= 2. AVANT ================= -->
  <section class="ap-section ap-avant" id="ap-avant" aria-labelledby="ap-avant-titre">
    <div class="container">
      <div class="text-center" data-reveal>
        <span class="ap-surtitre">Il était une fois…</span>
        <h2 class="ap-h2" id="ap-avant-titre">La grande quête du classeur</h2>
        <p class="ap-intro mx-auto">Avant, pour trouver une information sur une structure sous tutelle, il fallait :</p>
      </div>

      <div class="ap-chrono" id="ap-chrono">
        <div class="ap-chrono-ligne" aria-hidden="true"></div>
        <ol class="list-unstyled m-0">
          <?php foreach ($etapes as $i => [$icone, $titre, $texte]): ?>
            <li class="ap-etape" data-reveal="<?= $i % 2 ? 'droite' : 'gauche' ?>">
              <div class="ap-etape-carte">
                <strong><?= htmlspecialchars($titre) ?></strong>
                <p><?= htmlspecialchars($texte) ?></p>
              </div>
              <span class="ap-etape-pastille" aria-hidden="true"><i class="bi <?= $icone ?>"></i></span>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>

      <div class="ap-tampon-zone">
        <div class="ap-tampon" id="ap-tampon">Modernisé</div>
      </div>
      <p class="ap-tampon-legende">Bref. On a décidé de moderniser tout ça.</p>
    </div>
  </section>

  <!-- ================= 3. MISSION ================= -->
  <section class="ap-section ap-mission" id="ap-mission" aria-labelledby="ap-mission-titre">
    <div class="container">
      <div data-reveal>
        <span class="ap-surtitre">Notre mission</span>
        <h2 class="ap-h2" id="ap-mission-titre">Quatre verbes, zéro classeur perdu</h2>
        <p class="ap-intro">Pour que plus personne ne dise «&nbsp;je n'ai pas trouvé le document&nbsp;» alors qu'il était juste dans le mauvais classeur depuis 2014.</p>
      </div>
      <div class="ap-cartes">
        <?php
        $missions = [
            ['Centraliser', 'Toutes les informations des structures, au même endroit.', '<path d="M12 3v6M12 15v6M3 12h6M15 12h6"/><circle cx="12" cy="12" r="3"/>'],
            ['Organiser', 'Des fiches claires, classées par catégorie et à jour.', '<rect x="3" y="4" width="18" height="5" rx="1.5"/><rect x="3" y="15" width="18" height="5" rx="1.5"/><path d="M7 6.5h4M7 17.5h4"/>'],
            ['Faciliter', 'Une recherche simple et une carte pour tout retrouver.', '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>'],
            ['Faire participer', 'Des consultations ouvertes où chaque citoyen peut donner son avis.', '<path d="M4 5h16v10H9l-5 4z"/><path d="M8 9h8M8 12h5"/>'],
        ];
        foreach ($missions as $i => [$titre, $texte, $svg]): ?>
          <article class="ap-carte" data-reveal style="--delai: <?= $i * .12 ?>s">
            <span class="ap-carte-num" aria-hidden="true">0<?= $i + 1 ?></span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $svg ?></svg>
            <h3><?= $titre ?></h3>
            <p><?= $texte ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ================= 4. CHIFFRES ================= -->
  <section class="ap-section ap-chiffres" aria-label="La plateforme en chiffres">
    <div class="container">
      <div class="ap-chiffres-grille">
        <?php
        $libelles = [
            'structures' => 'Institutions et A.A.I référencées',
            'actualites' => 'Actualités publiées',
            'documents' => 'Documents publics',
            'consultations' => 'Consultation(s) ouverte(s)',
        ];
        foreach ($libelles as $cle => $libelle): ?>
          <div class="ap-chiffre" data-reveal>
            <strong data-compteur="<?= $chiffres[$cle] ?>"><?= $chiffres[$cle] ?></strong>
            <span><?= $libelle ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ================= 5. QUI ================= -->
  <section class="ap-section ap-qui" aria-labelledby="ap-qui-titre">
    <div class="container ap-qui-grille">
      <div data-reveal="gauche">
        <span class="ap-surtitre">Qui l'utilise ?</span>
        <h2 class="ap-h2" id="ap-qui-titre">Quatre profils, une seule plateforme</h2>
        <p class="ap-intro">Survolez ou sélectionnez un profil pour découvrir son super-pouvoir.</p>
        <ul class="ap-qui-liste">
          <?php foreach ($personas as [$icone, $nom, $role]): ?>
            <li><i class="bi <?= $icone ?>" aria-hidden="true"></i><div><strong><?= $nom ?></strong><span><?= $role ?></span></div></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="ap-orbite" data-reveal="zoom" aria-hidden="true">
        <div class="ap-orbite-anneau"></div>
        <div class="ap-orbite-centre">
          <strong id="ap-orbite-titre">La plateforme</strong>
          <small id="ap-orbite-texte">Un point de rencontre entre l'administration et les citoyens.</small>
        </div>
        <div class="ap-orbite-piste">
          <?php foreach ($personas as $i => [$icone, $nom, $role]): ?>
            <div class="ap-persona" style="--a: <?= $i * 90 - 45 ?>deg">
              <div class="ap-persona-corps">
                <button type="button" tabindex="-1" data-titre="<?= htmlspecialchars($nom) ?>" data-texte="<?= htmlspecialchars($role) ?>"><i class="bi <?= $icone ?>"></i></button>
                <span class="ap-persona-nom"><?= $nom ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= 6. AVANT / MAINTENANT ================= -->
  <section class="ap-section ap-compare-section" aria-labelledby="ap-compare-titre">
    <div class="container">
      <div class="text-center" data-reveal>
        <span class="ap-surtitre">Pourquoi c'est mieux maintenant ?</span>
        <h2 class="ap-h2" id="ap-compare-titre">De la tortue à la fusée</h2>
        <p class="ap-intro mx-auto">Faites glisser la poignée pour comparer.</p>
      </div>

      <div class="ap-compare" id="ap-compare" data-reveal="zoom">
        <div class="ap-panneau avant">
          <?php
          $papiers = [['brouillon_final_v3_definitif.docx', '10%', '6%', '-6deg', '0s'], ['classeur_2014 (copie).pdf', '26%', '30%', '4deg', '.4s'],
                      ['NE PAS JETER.xlsx', '8%', '44%', '-3deg', '.8s'], ['scan_flou_page2.jpg', '40%', '10%', '7deg', '.2s'],
                      ['note_au_collegue_en_conge.txt', '52%', '34%', '-5deg', '.6s']];
          foreach ($papiers as [$nom, $y, $x, $r, $d]): ?>
            <span class="ap-papier" style="--y:<?= $y ?>;--x:<?= $x ?>;--r:<?= $r ?>;--d:<?= $d ?>" aria-hidden="true"><?= $nom ?></span>
          <?php endforeach; ?>
          <h3>🐢 Avant</h3>
          <p>« Le classeur est chez le collègue qui est en congé. »</p>
        </div>
        <div class="ap-panneau maintenant">
          <?php
          $tuiles = [['bi-check2-circle', 'Tout est en ligne', '10%', '6%', '0s'], ['bi-check2-circle', 'Tout est centralisé', '26%', '28%', '.6s'],
                     ['bi-check2-circle', 'Tout est sécurisé', '42%', '8%', '1.2s'], ['bi-check2-circle', 'Accessible 24 h / 24', '58%', '30%', '.3s']];
          foreach ($tuiles as [$icone, $texte, $y, $x, $d]): ?>
            <span class="ap-tuile" style="--y:<?= $y ?>;--x:<?= $x ?>;--d:<?= $d ?>" aria-hidden="true"><i class="bi <?= $icone ?>"></i><?= $texte ?></span>
          <?php endforeach; ?>
          <h3>Maintenant 🚀</h3>
          <p>Une recherche, une fiche, et c'est réglé.</p>
        </div>
        <input type="range" min="0" max="100" value="50" id="ap-compare-curseur" aria-label="Comparer avant et maintenant">
        <div class="ap-poignee" aria-hidden="true"></div>
      </div>
    </div>
  </section>

  <!-- ================= 7. PHILOSOPHIE ================= -->
  <section class="ap-section ap-philo" id="ap-philo" aria-labelledby="ap-philo-titre">
    <div class="container">
      <h2 class="visually-hidden" id="ap-philo-titre">Notre philosophie</h2>
      <p class="text-center"><span class="ap-surtitre">Notre philosophie</span></p>
      <blockquote class="ap-citation" id="ap-citation">« <?= mots('Si une information existe quelque part, elle doit être trouvable ici.') ?>»</blockquote>
      <p class="ap-citation-auteur">— Quelqu'un au MRRI, probablement.</p>
    </div>
  </section>

  <!-- ================= 8. MERCI ================= -->
  <section class="ap-section ap-merci" id="ap-merci" aria-labelledby="ap-merci-titre">
    <canvas id="ap-confettis" aria-hidden="true"></canvas>
    <div class="container" data-reveal>
      <span class="ap-surtitre" style="color: var(--acc-jaune)">Merci</span>
      <h2 class="ap-h2" id="ap-merci-titre">Merci d'utiliser la plateforme</h2>
      <p>Vous contribuez à un Gabon plus organisé, plus moderne, et avec beaucoup moins de classeurs perdus.</p>
      <div class="ap-hero-actions">
        <a href="consultations.php" class="ap-bouton plein">Participer à une consultation <span aria-hidden="true">→</span></a>
        <a href="actualite.php" class="ap-bouton">Lire les actualités <span aria-hidden="true">→</span></a>
      </div>
      <p class="ap-signature">Conçu et développé avec beaucoup de code, de café et un stagiaire motivé.</p>
    </div>
  </section>

</div>

<script>
(function () {
    const calme = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* 1. Apparition des éléments au défilement */
    const aReveler = document.querySelectorAll('[data-reveal]');
    if (calme || !('IntersectionObserver' in window)) {
        aReveler.forEach(function (el) { el.classList.add('visible'); });
    } else {
        const observateur = new IntersectionObserver(function (entrees) {
            entrees.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('visible'); observateur.unobserve(e.target); }
            });
        }, { threshold: .18, rootMargin: '0px 0px -40px 0px' });
        aReveler.forEach(function (el) { observateur.observe(el); });
    }

    /* 2. Parallaxe du hero à la souris */
    const hero = document.getElementById('ap-hero');
    if (hero && !calme) {
        hero.addEventListener('pointermove', function (e) {
            const r = hero.getBoundingClientRect();
            hero.style.setProperty('--mx', ((e.clientX - r.left) / r.width - .5).toFixed(3));
            hero.style.setProperty('--my', ((e.clientY - r.top) / r.height - .5).toFixed(3));
        });
        hero.addEventListener('pointerleave', function () { hero.style.setProperty('--mx', 0); hero.style.setProperty('--my', 0); });
    }

    /* 3. Ligne du temps qui se dessine + tampon + citation : liés au défilement */
    const chrono = document.getElementById('ap-chrono');
    const tampon = document.getElementById('ap-tampon');
    const citation = document.getElementById('ap-citation');
    const motsCitation = citation ? Array.from(citation.querySelectorAll('.ap-mot')) : [];
    let enAttente = false;

    function progression(el, debut, fin) {
        const r = el.getBoundingClientRect();
        const h = window.innerHeight;
        return Math.min(1, Math.max(0, (h * debut - r.top) / (r.height + h * (debut - fin))));
    }

    function auDefilement() {
        enAttente = false;
        if (chrono) chrono.style.setProperty('--progression', progression(chrono, .6, .4).toFixed(3));
        if (tampon && !tampon.classList.contains('visible') && tampon.getBoundingClientRect().top < window.innerHeight * .8) {
            tampon.classList.add('visible');
        }
        if (motsCitation.length) {
            const p = progression(citation, .85, .35);
            const nb = Math.round(p * motsCitation.length);
            motsCitation.forEach(function (m, i) { m.classList.toggle('lu', i < nb); });
        }
    }
    if (!calme) {
        window.addEventListener('scroll', function () {
            if (!enAttente) { enAttente = true; requestAnimationFrame(auDefilement); }
        }, { passive: true });
        auDefilement();
    }

    /* 4. Compteurs animés */
    const compteurs = document.querySelectorAll('[data-compteur]');
    if (!calme && 'IntersectionObserver' in window) {
        const obsCompteurs = new IntersectionObserver(function (entrees) {
            entrees.forEach(function (e) {
                if (!e.isIntersecting) return;
                obsCompteurs.unobserve(e.target);
                const cible = parseInt(e.target.dataset.compteur, 10) || 0;
                const debut = performance.now();
                (function etape(t) {
                    const x = Math.min(1, (t - debut) / 1600);
                    e.target.textContent = Math.round(cible * (1 - Math.pow(1 - x, 3)));
                    if (x < 1) requestAnimationFrame(etape);
                })(debut);
            });
        }, { threshold: .6 });
        compteurs.forEach(function (c) { c.textContent = '0'; obsCompteurs.observe(c); });
    }

    /* 5. Cartes qui s'inclinent sous le pointeur */
    if (!calme) {
        document.querySelectorAll('.ap-carte').forEach(function (carte) {
            carte.addEventListener('pointermove', function (e) {
                const r = carte.getBoundingClientRect();
                const x = (e.clientX - r.left) / r.width;
                const y = (e.clientY - r.top) / r.height;
                carte.style.setProperty('--ry', ((x - .5) * 14).toFixed(2) + 'deg');
                carte.style.setProperty('--rx', ((.5 - y) * 14).toFixed(2) + 'deg');
                carte.style.setProperty('--lx', (x * 100).toFixed(1) + '%');
                carte.style.setProperty('--ly', (y * 100).toFixed(1) + '%');
            });
            carte.addEventListener('pointerleave', function () {
                carte.style.setProperty('--rx', '0deg');
                carte.style.setProperty('--ry', '0deg');
            });
        });
    }

    /* 6. Orbite : le centre présente le profil survolé */
    const titreOrbite = document.getElementById('ap-orbite-titre');
    const texteOrbite = document.getElementById('ap-orbite-texte');
    if (titreOrbite) {
        const defautTitre = titreOrbite.textContent;
        const defautTexte = texteOrbite.textContent;
        document.querySelectorAll('.ap-persona button').forEach(function (b) {
            b.addEventListener('pointerenter', function () {
                titreOrbite.textContent = b.dataset.titre;
                texteOrbite.textContent = b.dataset.texte;
                b.classList.add('actif');
            });
            b.addEventListener('pointerleave', function () {
                titreOrbite.textContent = defautTitre;
                texteOrbite.textContent = defautTexte;
                b.classList.remove('actif');
            });
        });
    }

    /* 7. Comparateur avant / maintenant (souris, tactile et clavier via le curseur natif) */
    const compare = document.getElementById('ap-compare');
    const curseur = document.getElementById('ap-compare-curseur');
    if (compare && curseur) {
        curseur.addEventListener('input', function () { compare.style.setProperty('--pos', curseur.value + '%'); });
    }

    /* 8. Confettis aux couleurs du Gabon à l'arrivée sur « Merci » */
    const merci = document.getElementById('ap-merci');
    const toile = document.getElementById('ap-confettis');
    if (merci && toile && !calme && 'IntersectionObserver' in window) {
        const ctx = toile.getContext('2d');
        const couleurs = ['#009E60', '#FCD116', '#3A75C4', '#FFFFFF'];
        let confettis = [];

        function lancer() {
            const ratio = window.devicePixelRatio || 1;
            toile.width = toile.offsetWidth * ratio;
            toile.height = toile.offsetHeight * ratio;
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            const l = toile.offsetWidth;
            confettis = Array.from({ length: 160 }, function () {
                return {
                    x: l / 2 + (Math.random() - .5) * l * .3, y: toile.offsetHeight * .62,
                    vx: (Math.random() - .5) * 14, vy: -Math.random() * 15 - 6,
                    t: Math.random() * 8 + 5, r: Math.random() * Math.PI, vr: (Math.random() - .5) * .3,
                    c: couleurs[Math.floor(Math.random() * couleurs.length)], vie: 1
                };
            });
            requestAnimationFrame(dessiner);
        }

        function dessiner() {
            ctx.clearRect(0, 0, toile.offsetWidth, toile.offsetHeight);
            confettis.forEach(function (p) {
                p.vy += .32; p.vx *= .985; p.x += p.vx; p.y += p.vy; p.r += p.vr; p.vie -= .006;
                ctx.save();
                ctx.globalAlpha = Math.max(0, p.vie);
                ctx.translate(p.x, p.y);
                ctx.rotate(p.r);
                ctx.fillStyle = p.c;
                ctx.fillRect(-p.t / 2, -p.t / 4, p.t, p.t / 2);
                ctx.restore();
            });
            confettis = confettis.filter(function (p) { return p.vie > 0 && p.y < toile.offsetHeight + 40; });
            if (confettis.length) requestAnimationFrame(dessiner);
        }

        const obsMerci = new IntersectionObserver(function (entrees) {
            if (entrees[0].isIntersecting) { obsMerci.disconnect(); setTimeout(lancer, 300); }
        }, { threshold: .55 });
        obsMerci.observe(merci);
    }
})();
</script>

<?php require '../../includes/footer.php'; ?>
