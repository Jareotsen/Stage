<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentions légales | Plateforme de consultation</title>
    <style>
        :root { --primary: #1f4f68; --text: #24313a; --muted: #64727b; --light: #f4f7f8; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--text); font-family: Arial, sans-serif; line-height: 1.65; background: var(--light); }
        header { padding: 2.5rem 1.25rem; color: white; background: var(--primary); }
        header div, main { max-width: 960px; margin: auto; }
        h1 { margin: 0 0 .4rem; font-size: clamp(1.8rem, 4vw, 2.6rem); }
        header p { margin: 0; opacity: .9; }
        main { margin-top: 2rem; margin-bottom: 2rem; padding: 2rem; background: white; box-shadow: 0 2px 12px #17304212; }
        h2 { margin-top: 2rem; padding-bottom: .35rem; color: var(--primary); border-bottom: 1px solid #dbe3e6; }
        h2:first-child { margin-top: 0; }
        p { margin: .7rem 0; }
        a { color: var(--primary); }
        .notice { padding: 1rem; background: #eef6f8; border-left: 4px solid var(--primary); }
        footer { padding: 1.5rem; color: var(--muted); text-align: center; font-size: .9rem; }
        @media (max-width: 600px) { main { margin: 1rem 0; padding: 1.25rem; } }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Mentions légales</h1>
            <p>Informations légales de la plateforme de consultation</p>
        </div>
    </header>

    <main>
        <p class="notice"><strong>Dernière mise à jour :</strong> <?= htmlspecialchars(date('d/m/Y'), ENT_QUOTES, 'UTF-8') ?></p>

        <h2>1. Éditeur du site</h2>
        <p>Le présent site est édité par <strong>[Nom de l’entreprise ou du professionnel]</strong>,<br>
            [forme juridique] au capital de [montant] €, immatriculé(e) au [RCS/RNE] sous le numéro [numéro],<br>
            dont le siège social est situé [adresse complète].</p>
        <p><strong>Responsable de la publication :</strong> [Nom et fonction]<br>
            <strong>Contact :</strong> <a href="mailto:contact@exemple.fr">contact@exemple.fr</a><br>
            <strong>Téléphone :</strong> [numéro de téléphone]</p>

        <h2>2. Hébergement</h2>
        <p>Le site est hébergé par <strong>[Nom de l’hébergeur]</strong>,<br>
            [forme juridique], situé [adresse complète],<br>
            joignable au [numéro] et à l’adresse [adresse e-mail].</p>

        <h2>3. Objet de la plateforme</h2>
        <p>La plateforme permet de mettre en relation des utilisateurs avec des professionnels pour organiser des consultations. Les informations publiées sont fournies à titre indicatif et ne remplacent pas les conseils personnalisés d’un professionnel compétent.</p>
        <p>Chaque utilisateur demeure responsable des informations communiquées et de l’usage qu’il fait du service.</p>

        <h2>4. Propriété intellectuelle</h2>
        <p>L’ensemble des éléments présents sur ce site (textes, images, logo, graphismes, logiciels et structure) est protégé par les règles applicables en matière de propriété intellectuelle. Toute reproduction, représentation ou adaptation, totale ou partielle, sans autorisation préalable est interdite.</p>

        <h2>5. Données personnelles</h2>
        <p>Les données personnelles sont traitées conformément à la réglementation applicable, notamment au règlement général sur la protection des données (RGPD). Pour connaître les finalités, les durées de conservation et vos droits (accès, rectification, effacement, opposition et portabilité), consultez notre <a href="politique_confidentialite.php">politique de confidentialité</a>.</p>
        <p>Pour exercer vos droits, écrivez à <a href="mailto:contact@exemple.fr">contact@exemple.fr</a>. Vous pouvez également introduire une réclamation auprès de la CNIL.</p>

        <h2>6. Cookies</h2>
        <p>Le site peut utiliser des cookies nécessaires à son fonctionnement et, sous réserve de votre consentement, des cookies de mesure d’audience ou de personnalisation. Vous pouvez modifier vos préférences à tout moment depuis les paramètres de votre navigateur ou le module de gestion du consentement.</p>

        <h2>7. Responsabilité</h2>
        <p>L’éditeur s’efforce d’assurer l’exactitude et la disponibilité des informations publiées, sans pouvoir garantir qu’elles soient exemptes d’erreurs ou accessibles sans interruption. L’utilisateur est invité à vérifier les informations avant toute décision.</p>

        <h2>8. Droit applicable</h2>
        <p>Les présentes mentions légales sont soumises au droit français. En cas de litige, les juridictions compétentes sont celles désignées par les règles de procédure applicables.</p>
    </main>

    <footer>&copy; <?= date('Y') ?> [Nom de l’entreprise] — Tous droits réservés.</footer>
</body>
</html>