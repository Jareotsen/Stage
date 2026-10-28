-- Limitation des essais de mot de passe sur la page de connexion.
-- Une ligne par échec ; les lignes de plus de 24 h sont purgées par connexion.php.

CREATE TABLE IF NOT EXISTS tentatives_connexion (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip VARCHAR(45) NOT NULL,
    username VARCHAR(50) NOT NULL,
    tente_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tentatives_ip (ip, tente_le),
    KEY idx_tentatives_username (username, tente_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
