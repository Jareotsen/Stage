CREATE TABLE IF NOT EXISTS citoyens (
    email VARCHAR(190) NOT NULL,
    genre ENUM('femme', 'homme') NOT NULL,
    tranche_age ENUM('moins_18', '18_24', '25_34', '35_44', '45_54', '55_plus') NOT NULL,
    secteur_activite ENUM('secteur_public', 'secteur_prive', 'independant', 'etudiant', 'sans_emploi', 'autre') NOT NULL,
    consentement_statistiques_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    email_verifie_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS verifications_citoyens (
    email VARCHAR(190) NOT NULL,
    genre ENUM('femme', 'homme') NOT NULL,
    tranche_age ENUM('moins_18', '18_24', '25_34', '35_44', '45_54', '55_plus') NOT NULL,
    secteur_activite ENUM('secteur_public', 'secteur_prive', 'independant', 'etudiant', 'sans_emploi', 'autre') NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expire_le DATETIME NOT NULL,
    envoye_le DATETIME NOT NULL,
    tentatives TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
