-- Permet d'enregistrer le profil d'un citoyen dont l'email n'a pas été vérifié
-- (vérification par code désactivée) : email_verifie_le reste NULL dans ce cas.
ALTER TABLE citoyens
    MODIFY email_verifie_le DATETIME NULL DEFAULT NULL;
