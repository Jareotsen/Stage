-- Coordonnées GPS des structures (source : OpenStreetMap + presse gabonaise)
-- N'écrase que les coordonnées encore vides.
-- CGE (14) et ANIF (15) restent NULL : aucune adresse physique publique trouvée.

UPDATE structure SET latitude = 0.3968726, longitude = 9.4398916 WHERE id_struc IN (1, 3) AND latitude IS NULL; -- Cour Constitutionnelle, Bd de l'Indépendance
UPDATE structure SET latitude = 0.4084610, longitude = 9.4428560 WHERE id_struc = 2  AND latitude IS NULL; -- Sénat, Palais Omar Bongo Ondimba
UPDATE structure SET latitude = 0.4891613, longitude = 9.3869353 WHERE id_struc = 4  AND latitude IS NULL; -- Conseil d'État, Bd Georges Rawiri (Sablière)
UPDATE structure SET latitude = 0.3492620, longitude = 9.4763110 WHERE id_struc = 5  AND latitude IS NULL; -- Cour des Comptes, ACAE
UPDATE structure SET latitude = 0.4258140, longitude = 9.4523600 WHERE id_struc = 6  AND latitude IS NULL; -- Cour de Cassation, Palais de Justice av. Jean Aveno Davin
UPDATE structure SET latitude = 0.4375530, longitude = 9.4172160 WHERE id_struc = 7  AND latitude IS NULL; -- CESE
UPDATE structure SET latitude = 0.4317310, longitude = 9.4238050 WHERE id_struc = 9  AND latitude IS NULL; -- CNDH, quartier Kalikak (approx.)
UPDATE structure SET latitude = 0.4317310, longitude = 9.4238050 WHERE id_struc = 11 AND latitude IS NULL; -- Médiateur, imm. Plage Océan, Kalikak (approx.)
UPDATE structure SET latitude = 0.4180960, longitude = 9.4507520 WHERE id_struc = 12 AND latitude IS NULL; -- Conseil National de la Démocratie
UPDATE structure SET latitude = 0.3898150, longitude = 9.4503550 WHERE id_struc = 13 AND latitude IS NULL; -- CNLCEI, rue Jacques Akiremy, London (approx.)
UPDATE structure SET latitude = 0.3390810, longitude = 9.4768460 WHERE id_struc = 17 AND latitude IS NULL; -- ARTF, Pont Nomba, Owendo (approx.)
UPDATE structure SET latitude = 0.4319113, longitude = 9.4198444 WHERE id_struc = 18 AND latitude IS NULL; -- ARCEP, av. Paul Moukambi
UPDATE structure SET latitude = 0.4080170, longitude = 9.4392760 WHERE id_struc = 19 AND latitude IS NULL; -- ARMP, Immeuble Arambo, Bd Triomphal
