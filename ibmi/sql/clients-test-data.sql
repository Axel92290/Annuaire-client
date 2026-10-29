-- Jeu de cinq clients fictifs, à exécuter une fois dans une table vide.
-- Ce script n’est pas idempotent : le rejouer ajoute cinq lignes.
-- Aucun ID fourni : Db2 le génère. Ne pas supposer que les ID valent 1 à 5.
-- Les dates soustraient CURRENT_TIMEZONE pour enregistrer l’instant en UTC.
-- Les accents, NULL, apostrophe doublée et zéros initiaux servent à la recette.
SET SCHEMA AXEL921;

INSERT INTO AXEL921.CLIENTS (
    NOM,
    PRENOM,
    EMAIL,
    TELEPHONE,
    ADRESSE,
    CODE_POSTAL,
    VILLE,
    CREE_LE,
    MODIFIE_LE
)
VALUES
(
    'Martin',
    'Camille',
    'camille.martin@example.com',
    '01 23 45 67 89',
    '12 rue des Lilas',
    '69003',
    'Lyon',
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE,
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE
),
(
    'Marchand',
    'Inès',
    'ines.marchand@example.com',
    '03 45 67 89 01',
    '8 allée des Tilleuls',
    '59000',
    'Lille',
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE,
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE
),
(
    'Marie',
    'Hugo',
    'hugo.marie@example.com',
    '04 56 78 90 12',
    '24 avenue des Oliviers',
    '06000',
    'Nice',
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE,
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE
),
(
    'Martinez',
    'Léo',
    'leo.martinez@example.com',
    '02 34 56 78 90',
    '6 rue des Érables',
    '44000',
    'Nantes',
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE,
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE
),
(
    'D''Angelo & Fils',
    NULL,
    NULL,
    NULL,
    NULL,
    '01230',
    NULL,
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE,
    CURRENT_TIMESTAMP - CURRENT_TIMEZONE
);