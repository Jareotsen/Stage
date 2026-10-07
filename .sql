//engine =Innodb
CREATE DATABASE MRRI;
CREATE TABLE utilisateurs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(20) UNIQUE ,
  password VARCHAR (255) ,
  role ENUM ('super_admin', 'agent_ministere', 'point_focal')
 );
CREATE TABLE categories (
  id_cat INT PRIMARY KEY AUTO_INCREMENT,
  nom_cat VARCHAR(255) UNIQUE NOT NULL
);
CREATE TABLE structure (
  id_struc INT PRIMARY KEY AUTO_INCREMENT,
  nom_struc VARCHAR(255) NOT NULL,
  adresse VARCHAR(255),
  telephone VARCHAR(20),
  email VARCHAR(100),
  mission VARCHAR(255),
  site_web VARCHAR(255),
  date_creation DATE,
  directeur_general VARCHAR(200),
  id_cat INT NOT NULL,
  sigle VARCHAR(30),
  id_responsable INT,
  FOREIGN KEY (id_cat) REFERENCES categories(id_cat),
  FOREIGN KEY (id_responsable) REFERENCES utilisateurs(id)
);
CREATE TABLE structure_photos (
  id_photos INT PRIMARY KEY AUTO_INCREMENT,
  Photos_url VARCHAR(255),
  Position INT,
  id_structure INT,
  FOREIGN KEY (id_structure) REFERENCES structure(id_struc)
);
CREATE TABLE actualites (
  id_actu INT PRIMARY KEY AUTO_INCREMENT,
  contenu TEXT,
  titre VARCHAR(150),
  date_publication DATE,
  id_structure INT,
  FOREIGN KEY (id_structure) REFERENCES structure(id_struc)
);
CREATE TABLE actualites_photos (
  id_actulites_photos INT PRIMARY KEY AUTO_INCREMENT,
  Photos_actu_url VARCHAR(255),
  id_actualites INT,
  FOREIGN KEY (id_actualites) REFERENCES actualites(id_actu)
);
CREATE TABLE documents (
id_document INT PRIMARY KEY AUTO_INCREMENT ,
doc_url VARCHAR(255),
nom_affichage VARCHAR(225),
est_public BOOLEAN,
id_structure INT ,
FOREIGN KEY (id_structure) REFERENCES structure(id_struc)
);

CREATE TABLE demandes (
  id_demande INT PRIMARY KEY AUTO_INCREMENT,
  titre_theme VARCHAR(255) NOT NULL,
  contexte TEXT,
  problematique TEXT,
  objectifs TEXT,
  utilisateurs_concernes TEXT,
  contraintes TEXT,
  delai_souhaite VARCHAR(100),
  id_point_focal INT NOT NULL,
  statut VARCHAR(30) NOT NULL DEFAULT 'en_attente',
  reponse_agent TEXT,
  id_agent_traitant INT,
  date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_reponse DATETIME,
  CHECK (statut IN ('en_attente','en_cours','besoin_rdv','accepte','refuse','traite')),
  FOREIGN KEY (id_point_focal) REFERENCES utilisateurs(id),
  FOREIGN KEY (id_agent_traitant) REFERENCES utilisateurs(id)
);

CREATE TABLE demandes_structures (
  id_demande_structure INT PRIMARY KEY AUTO_INCREMENT,
  id_demande INT NOT NULL,
  id_structure INT NOT NULL,
  FOREIGN KEY (id_demande) REFERENCES demandes(id_demande),
  FOREIGN KEY (id_structure) REFERENCES structure(id_struc)
);
CREATE TABLE consultations (
  id_consultation INT PRIMARY KEY AUTO_INCREMENT,
  id_structure INT,
  id_createur INT,
  titre VARCHAR(255) NOT NULL,
  contenu TEXT,
  date_ouverture DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_fermeture DATETIME,
  statut ENUM( 'ouverte', 'fermee') DEFAULT 'ouverte',
  FOREIGN KEY (id_structure) REFERENCES structure(id_struc),
  FOREIGN KEY (id_createur) REFERENCES utilisateurs(id)
);
CREATE TABLE contributions (
  id_contribution INT PRIMARY KEY AUTO_INCREMENT,
  id_consultation INT NOT NULL,
  email_contributeur VARCHAR(255) NOT NULL,
  contenu TEXT NOT NULL,
  statut ENUM( 'en_attente', 'validee', 'rejetee') DEFAULT 'en_attente',
  date_contribution DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_consultation) REFERENCES consultations(id_consultation),
  CHECK (statut IN ('en_attente', 'validee', 'rejetee'))
);

CREATE TABLE votes (
    id_vote INT PRIMARY KEY AUTO_INCREMENT,
    id_contribution INT NOT NULL,
    email_votant VARCHAR(191) NOT NULL,
    valeur_vote ENUM('pour', 'contre') NOT NULL,
    date_vote DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_vote (id_contribution, email_votant),

    CONSTRAINT fk_vote_contribution
        FOREIGN KEY (id_contribution)
        REFERENCES contributions(id_contribution)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;







