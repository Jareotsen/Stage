-- Fiche structure enrichie : rôle, historique, dirigeant(e) et composition.
-- Uniquement des colonnes facultatives (NULL) : les requêtes existantes ne sont pas affectées.
-- `mission` passe de VARCHAR(1000) à TEXT (élargissement, aucune donnée perdue).

ALTER TABLE structure
    MODIFY COLUMN mission TEXT NULL,
    ADD COLUMN historique TEXT NULL AFTER mission,
    ADD COLUMN composition TEXT NULL AFTER historique,
    ADD COLUMN texte_fondateur VARCHAR(255) NULL AFTER date_creation,
    ADD COLUMN fonction_dirigeant VARCHAR(100) NULL AFTER directeur_general,
    ADD COLUMN dirigeant_depuis DATE NULL AFTER fonction_dirigeant,
    ADD COLUMN photo_dirigeant VARCHAR(255) NULL AFTER dirigeant_depuis;
