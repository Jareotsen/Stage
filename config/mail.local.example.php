<?php
// Modèle de configuration SMTP pour la vérification des citoyens.
// 1. Copier ce fichier en config/mail.local.php (ce fichier-là n'est pas versionné).
// 2. Garder UN seul des blocs ci-dessous et le compléter.
// 3. Tester : php config/tester_smtp.php votre.adresse@exemple.com

// --- Option A : Gmail (envoi réel, fonctionne depuis WAMP en local) ---
// Prérequis : validation en 2 étapes activée sur le compte Google, puis créer un
// « mot de passe d'application » sur https://myaccount.google.com/apppasswords
// (le mot de passe habituel du compte est refusé par Gmail).
return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => 'adresse.expediteur@gmail.com',
    'password'   => 'xxxx xxxx xxxx xxxx', // mot de passe d'application
    'from'       => 'adresse.expediteur@gmail.com', // doit être la même adresse que username
    'from_name'  => 'Consultations citoyennes MRRI',
];

// --- Option B : Mailpit (tests locaux, aucun email ne sort de la machine) ---
// Lancer mailpit.exe, puis lire les emails sur http://localhost:8025
// return [
//     'host'       => 'localhost',
//     'port'       => 1025,
//     'encryption' => 'none',
//     'username'   => '',
//     'password'   => '',
//     'from'       => 'no-reply@mrri.local',
//     'from_name'  => 'Consultations citoyennes MRRI',
// ];

// --- Option C : production (serveur SMTP du ministère ou fournisseur transactionnel) ---
// Mêmes clés, avec les paramètres fournis par l'hébergeur. Les variables
// d'environnement MRRI_SMTP_HOST, MRRI_SMTP_PORT, ... remplacent ce fichier si définies.
