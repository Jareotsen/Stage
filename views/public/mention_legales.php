<?php
declare(strict_types=1);

$annee = date('Y');
$dateMiseAJour = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Mentions légales de la plateforme universitaire de consultation des informations relatives aux structures relevant du MRRI du Gabon.">

    <title>Mentions légales | Plateforme de consultation MRRI</title>

    <style>
        :root {
            --bleu: #17365d;
            --bleu-clair: #eaf1f8;
            --vert: #287747;
            --or: #c5a34a;
            --texte: #273444;
            --texte-secondaire: #657386;
            --fond: #f4f6f9;
            --bordure: #e1e7ef;
            --blanc: #ffffff;
            --rayon: 14px;
            --ombre: 0 8px 30px rgba(23, 54, 93, 0.07);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 25px;
        }

        body {
            margin: 0;
            background: var(--fond);
            color: var(--texte);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15px;
            line-height: 1.75;
        }

        a {
            color: var(--vert);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        a:focus-visible {
            outline: 3px solid var(--or);
            outline-offset: 4px;
        }

        .topbar {
            height: 6px;
            background: linear-gradient(
                to right,
                var(--vert) 0 33.33%,
                var(--or) 33.33% 66.66%,
                var(--bleu) 66.66% 100%
            );
        }

        .hero {
            background: var(--bleu);
            color: var(--blanc);
            padding: 55px 20px 65px;
        }

        .hero-inner {
            max-width: 1000px;
            margin: 0 auto;
        }

        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #d6e2f0;
        }

        .breadcrumb a {
            color: var(--blanc);
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 12px;
            color: #e7d39a;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .hero h1 {
            max-width: 750px;
            margin: 0 0 12px;
            font-size: clamp(30px, 5vw, 44px);
            line-height: 1.2;
            letter-spacing: -0.8px;
        }

        .hero p {
            max-width: 680px;
            margin: 0;
            color: #e1e9f3;
            font-size: 16px;
        }

        .hero-line {
            width: 65px;
            height: 4px;
            margin-top: 25px;
            background: var(--or);
            border-radius: 10px;
        }

        .container {
            width: min(1000px, calc(100% - 36px));
            margin: -25px auto 50px;
            position: relative;
        }

        .update-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 22px;
            padding: 16px 22px;
            background: var(--blanc);
            border: 1px solid var(--bordure);
            border-radius: var(--rayon);
            box-shadow: var(--ombre);
        }

        .update-label {
            color: var(--texte-secondaire);
            font-size: 13px;
        }

        .update-date {
            color: var(--bleu);
            font-weight: 700;
        }

        .project-badge {
            padding: 6px 12px;
            background: var(--bleu-clair);
            color: var(--bleu);
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
        }

        .layout {
            display: grid;
            grid-template-columns: 245px minmax(0, 1fr);
            align-items: start;
            gap: 22px;
        }

        .sidebar,
        .content {
            background: var(--blanc);
            border: 1px solid var(--bordure);
            border-radius: var(--rayon);
            box-shadow: var(--ombre);
        }

        .sidebar {
            position: sticky;
            top: 20px;
            padding: 22px 16px;
        }

        .sidebar h2 {
            margin: 0 0 15px;
            padding: 0 8px;
            color: var(--bleu);
            font-size: 15px;
        }

        .sidebar ol {
            margin: 0;
            padding: 0;
            list-style: none;
            counter-reset: navigation;
        }

        .sidebar li {
            counter-increment: navigation;
        }

        .sidebar a {
            display: flex;
            gap: 10px;
            padding: 9px 8px;
            color: var(--texte-secondaire);
            font-size: 13px;
            line-height: 1.5;
            border-radius: 7px;
        }

        .sidebar a::before {
            content: counter(navigation, decimal-leading-zero);
            color: var(--vert);
            font-size: 11px;
            font-weight: 700;
        }

        .sidebar a:hover {
            background: var(--bleu-clair);
            color: var(--bleu);
            text-decoration: none;
        }

        .content {
            padding: 30px;
        }

        .intro {
            margin-bottom: 28px;
            padding: 18px 20px;
            background: #f1f7f3;
            border-left: 4px solid var(--vert);
            border-radius: 0 9px 9px 0;
        }

        .intro p {
            margin: 0;
        }

        .section {
            padding: 25px 0;
            border-bottom: 1px solid var(--bordure);
            scroll-margin-top: 20px;
        }

        .section:first-of-type {
            padding-top: 0;
        }

        .section:last-child {
            padding-bottom: 0;
            border-bottom: none;
        }

        .section h2 {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 0 0 15px;
            color: var(--bleu);
            font-size: 20px;
            line-height: 1.4;
        }

        .section-number {
            display: inline-flex;
            flex: 0 0 32px;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            background: var(--bleu-clair);
            color: var(--bleu);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .section p {
            margin: 10px 0;
        }

        .section ul {
            padding-left: 22px;
        }

        .section li {
            margin-bottom: 7px;
        }

        .info-box {
            margin-top: 16px;
            padding: 16px 18px;
            background: #f7f8fa;
            border: 1px solid var(--bordure);
            border-radius: 9px;
        }

        .info-box p:first-child {
            margin-top: 0;
        }

        .info-box p:last-child {
            margin-bottom: 0;
        }

        .info-label {
            color: var(--bleu);
            font-weight: 700;
        }

        .notice {
            margin-top: 18px;
            padding: 16px 18px;
            background: #fff8e8;
            border: 1px solid #f0dfb4;
            border-radius: 9px;
            color: #6b5420;
            font-size: 13px;
        }

        .footer {
            padding: 28px 20px;
            background: #122a47;
            color: #d7e0ec;
            text-align: center;
        }

        .footer p {
            margin: 5px 0;
            font-size: 13px;
        }

        .footer strong {
            color: var(--blanc);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-top: 15px;
        }

        .footer-links a {
            color: var(--blanc);
            font-size: 13px;
        }

        @media (max-width: 800px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }

            .sidebar ol {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 3px 12px;
            }

            .content {
                padding: 25px;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 38px 18px 50px;
            }

            .container {
                width: calc(100% - 24px);
                margin-top: -18px;
            }

            .sidebar,
            .content {
                padding: 19px;
            }

            .sidebar ol {
                grid-template-columns: 1fr;
            }

            .section h2 {
                font-size: 18px;
            }

            .update-card {
                padding: 14px;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .topbar,
            .sidebar,
            .footer-links {
                display: none;
            }

            .hero {
                padding: 20px;
                background: #fff;
                color: #000;
            }

            .hero p,
            .breadcrumb {
                color: #333;
            }

            .container {
                width: 100%;
                margin: 20px auto;
            }

            .layout {
                display: block;
            }

            .content,
            .update-card {
                box-shadow: none;
            }

            .section {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

<div class="topbar" aria-hidden="true"></div>

<header class="hero">
    <div class="hero-inner">

        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="accueil.php">Accueil</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Mentions légales</span>
        </nav>

        <span class="eyebrow">Plateforme de consultation</span>

        <h1>Mentions légales</h1>

        <p>
            Informations relatives au projet, à son fonctionnement
            et aux règles applicables à son utilisation.
        </p>

        <div class="hero-line" aria-hidden="true"></div>

    </div>
</header>

<main class="container">

    <div class="update-card">

        <div>
            <div class="update-label">Dernière mise à jour</div>
            <div class="update-date">
                <?= htmlspecialchars($dateMiseAJour, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <span class="project-badge">
            Projet universitaire
        </span>

    </div>

    <div class="layout">

        <aside class="sidebar" aria-label="Navigation dans les mentions légales">

            <h2>Sommaire</h2>

            <ol>
                <li><a href="#presentation">Présentation du projet</a></li>
                <li><a href="#editeur">Éditeur et conception</a></li>
                <li><a href="#hebergement">Hébergement</a></li>
                <li><a href="#objet">Objet de la plateforme</a></li>
                <li><a href="#propriete">Propriété intellectuelle</a></li>
                <li><a href="#donnees">Données personnelles</a></li>
                <li><a href="#securite">Sécurité et confidentialité</a></li>
                <li><a href="#responsabilite">Disponibilité et responsabilité</a></li>
                <li><a href="#droit">Droit applicable</a></li>
            </ol>

        </aside>

        <article class="content">

            <div class="intro">
                <p>
                    Cette page présente les informations légales relatives
                    à la plateforme de consultation développée dans le cadre
                    d'un stage académique au Ministère des Réformes et des
                    Relations avec les Institutions (MRRI) du Gabon.
                    Le projet est actuellement en développement dans un
                    environnement local.
                </p>
            </div>

            <section class="section" id="presentation">

                <h2>
                    <span class="section-number">01</span>
                    Présentation du projet
                </h2>

                <p>
                    La plateforme est réalisée dans le cadre d'un projet
                    universitaire en informatique, à l'occasion d'un stage
                    académique au sein du Ministère des Réformes et des
                    Relations avec les Institutions de la République gabonaise.
                </p>

                <p>
                    Elle vise à faciliter l'accès aux informations relatives
                    aux structures référencées, notamment leurs missions,
                    leurs coordonnées, leurs responsables et leurs actualités.
                </p>

                <p>
                    Selon les fonctionnalités développées, elle peut également
                    permettre la consultation de documents institutionnels
                    et la participation à des consultations publiques.
                </p>

            </section>

            <section class="section" id="editeur">

                <h2>
                    <span class="section-number">02</span>
                    Éditeur et conception
                </h2>

                <div class="info-box">
                    <p>
                        <span class="info-label">Organisme d'accueil :</span><br>
                        Ministère des Réformes et des Relations avec les
                        Institutions (MRRI) du Gabon.
                    </p>

                    <p>
                        <span class="info-label">Nature du projet :</span><br>
                        Projet universitaire réalisé dans le cadre d'un stage
                        académique en informatique.
                    </p>

                    <p>
                        <span class="info-label">Conception et développement :</span><br>
                        Réalisés dans le cadre du stage académique.
                    </p>

                    <p>
                        <span class="info-label">Responsable officiel de la publication :</span><br>
                        À confirmer par l'administration compétente.
                    </p>
                </div>

                <p>
                    La réalisation du projet au sein du ministère ne signifie
                    pas, à elle seule, que la plateforme a été officiellement
                    mise en service ou que sa publication a fait l'objet d'une
                    validation institutionnelle définitive.
                </p>

            </section>

            <section class="section" id="hebergement">

                <h2>
                    <span class="section-number">03</span>
                    Hébergement
                </h2>

                <p>
                    La plateforme fonctionne actuellement dans un environnement
                    local de développement utilisant WampServer.
                </p>

                <p>
                    À ce stade, aucun hébergement public n'est déclaré pour
                    cette version de développement.
                </p>

                <p>
                    Les informations relatives à l'hébergement seront précisées
                    lorsque la solution technique de déploiement aura été définie.
                </p>

            </section>

            <section class="section" id="objet">

                <h2>
                    <span class="section-number">04</span>
                    Objet de la plateforme
                </h2>

                <p>
                    La plateforme a pour objectif de faciliter la consultation
                    des informations institutionnelles relatives aux structures
                    enregistrées dans le système.
                </p>

                <p>Les informations susceptibles d'être présentées comprennent :</p>

                <ul>
                    <li>les missions et les coordonnées des structures ;</li>
                    <li>les informations relatives à leurs responsables ;</li>
                    <li>les actualités institutionnelles ;</li>
                    <li>les documents mis à la disposition du public ;</li>
                    <li>les consultations publiques proposées sur la plateforme.</li>
                </ul>

                <p>
                    Les informations affichées sont destinées à faciliter
                    l'accès à l'information. Elles ne remplacent pas les
                    procédures administratives officielles.
                </p>

            </section>

            <section class="section" id="propriete">

                <h2>
                    <span class="section-number">05</span>
                    Propriété intellectuelle
                </h2>

                <p>
                    Les éléments originaux développés dans le cadre du projet,
                    notamment l'interface, l'organisation graphique, les textes
                    et les développements informatiques, sont soumis aux règles
                    applicables en matière de propriété intellectuelle.
                </p>

                <p>
                    Les logos, armoiries, photographies, documents et contenus
                    appartenant au ministère ou à des tiers restent soumis
                    aux droits qui leur sont applicables.
                </p>

                <p>
                    Toute reproduction ou réutilisation d'un contenu protégé
                    doit respecter la législation en vigueur et, lorsque cela
                    est nécessaire, faire l'objet d'une autorisation préalable.
                </p>

            </section>

            <section class="section" id="donnees">

                <h2>
                    <span class="section-number">06</span>
                    Données personnelles
                </h2>

                <p>
                    Dans le cadre de la fonctionnalité de consultation publique,
                    une adresse électronique peut être utilisée pour vérifier
                    les contributions soumises par les participants.
                </p>

                <p>
                    Les modalités de traitement dépendent du fonctionnement
                    effectif de cette fonctionnalité, notamment de l'utilisation
                    de l'adresse électronique pour la vérification, de son
                    éventuel enregistrement et de sa durée de conservation.
                </p>

                <p>
                    Les données personnelles doivent être traitées conformément
                    à la législation gabonaise applicable, notamment aux textes
                    relatifs à la protection des données à caractère personnel.
                    Le cadre juridique précis et les éventuelles formalités
                    applicables devront être confirmés avant toute mise en
                    service officielle.
                </p>

                <div class="notice">
                    <strong>Information importante :</strong>
                    les modalités définitives de collecte, d'utilisation,
                    de conservation et de suppression des données, ainsi que
                    les modalités d'exercice des droits des personnes concernées,
                    devront être précisées dans la politique de confidentialité.
                </div>

            </section>

            <section class="section" id="securite">

                <h2>
                    <span class="section-number">07</span>
                    Sécurité et confidentialité
                </h2>

                <p>
                    Des mesures techniques sont mises en œuvre pendant le
                    développement afin de limiter les accès non autorisés
                    et de protéger les informations traitées par la plateforme.
                </p>

                <p>
                    Les utilisateurs disposant d'un compte doivent préserver
                    la confidentialité de leurs identifiants.
                </p>

                <p>
                    Les mesures de sécurité et les modalités de gestion des
                    accès devront être adaptées à l'environnement définitif
                    avant tout déploiement public.
                </p>

            </section>

            <section class="section" id="responsabilite">

                <h2>
                    <span class="section-number">08</span>
                    Disponibilité et responsabilité
                </h2>

                <p>
                    La plateforme étant en cours de développement, certaines
                    fonctionnalités peuvent être incomplètes, indisponibles
                    ou susceptibles d'être modifiées.
                </p>

                <p>
                    Des erreurs, des interruptions de service ou des opérations
                    de maintenance peuvent également survenir.
                </p>

                <p>
                    Les informations destinées à une démarche administrative
                    doivent être vérifiées auprès des services compétents.
                </p>

                <p>
                    Les conditions définitives d'utilisation et de responsabilité
                    seront précisées après validation du projet par l'administration
                    concernée.
                </p>

            </section>

            <section class="section" id="droit">

                <h2>
                    <span class="section-number">09</span>
                    Droit applicable
                </h2>

                <p>
                    Le projet est développé dans le contexte administratif
                    et juridique de la République gabonaise.
                </p>

                <p>
                    Les règles applicables à son éventuelle exploitation officielle,
                    ainsi que les obligations qui en découlent, devront être
                    confirmées par les services compétents avant sa mise en service.
                </p>

            </section>

        </article>

    </div>

</main>

<footer class="footer">

    <p>
        &copy; <?= htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') ?>
        — <strong>Plateforme de consultation</strong>
    </p>

    <p>
        Projet universitaire réalisé dans le cadre d'un stage académique au MRRI du Gabon.
    </p>

    <nav class="footer-links" aria-label="Liens complémentaires">
        <a href="accueil.php">Accueil</a>
        <a href="politique_confidentialite.php">Politique de confidentialité</a>
    </nav>

</footer>

</body>
</html>
```
