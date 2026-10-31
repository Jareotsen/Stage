-- Comptes de démonstration : un compte par profil authentifié.
-- Identifiants et mots de passe : voir Comptes_de_demonstration_MRRI.docx.
-- À SUPPRIMER avant toute mise en production (requêtes en fin de fichier).
-- Le script peut être relancé : il remet simplement les mots de passe de démonstration.

INSERT INTO utilisateurs (username, password, role) VALUES
    ('demo_admin', '$2y$12$zdVhRXdgMwVBEhmCnuk4w.MeU.No9Nn2W04d5Y3QPgjrNMM9YIte.', 'super_admin'),
    ('demo_agent', '$2y$12$Mo/T2zkTqCwlasar7G78fu6JtDnh0KvELHQBJNdzPg00xiBC3NUIG', 'agent_ministere'),
    ('demo_pf',    '$2y$12$nuV9fulhgzNCSWpCvf0oPe2Ydj1tDoEv12wjpuyhDZ0JPBhoZw2CS', 'point_focal')
ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role);

-- Le point focal de démonstration est rattaché à la CNDH (structure n° 9),
-- seulement si aucun autre point focal n'en est déjà responsable
UPDATE structure
SET id_responsable = (SELECT id FROM (SELECT id FROM utilisateurs WHERE username = 'demo_pf') AS demo)
WHERE id_struc = 9 AND id_responsable IS NULL;

-- ---------------------------------------------------------------
-- Suppression des comptes de démonstration (avant la mise en production) :
--
-- UPDATE structure SET id_responsable = NULL
--     WHERE id_responsable IN (SELECT id FROM (SELECT id FROM utilisateurs WHERE username LIKE 'demo\_%') AS demo);
-- DELETE FROM utilisateurs WHERE username IN ('demo_admin', 'demo_agent', 'demo_pf');
-- ---------------------------------------------------------------
