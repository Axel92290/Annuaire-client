-- Annuaire clients : structure Db2 for i.
-- Bibliothèque de travail : AXEL921.
-- À exécuter une fois, après vérification de l'absence de CLIENTS.
--
-- Stockage UTF-8 : capacités physiques en octets.
-- Les contraintes appliquent les limites métier en caractères.
--
-- Les dates sont obligatoires et renseignées explicitement en UTC
-- par les instructions d'insertion et les futurs programmes RPG.

SET SCHEMA AXEL921;

CREATE TABLE AXEL921.CLIENTS (
    ID_CLIENT BIGINT
        GENERATED ALWAYS AS IDENTITY (
            START WITH 1
            INCREMENT BY 1
            NO CYCLE
        )
        NOT NULL,

    NOM VARCHAR(400) CCSID 1208 NOT NULL,

    PRENOM VARCHAR(400) CCSID 1208,

    EMAIL VARCHAR(1016) CCSID 1208,

    TELEPHONE VARCHAR(120) CCSID 1208,

    ADRESSE VARCHAR(1020) CCSID 1208,

    CODE_POSTAL FOR COLUMN CODEPOSTAL
        VARCHAR(80) CCSID 1208,

    VILLE VARCHAR(400) CCSID 1208,

    CREE_LE TIMESTAMP NOT NULL,

    MODIFIE_LE TIMESTAMP NOT NULL,

    CONSTRAINT CLT_PK
        PRIMARY KEY (ID_CLIENT),

    CONSTRAINT CLT_ID_CK
        CHECK (ID_CLIENT > 0),

    CONSTRAINT CLT_NOM_CK
        CHECK (
            CHARACTER_LENGTH(TRIM(NOM))
                BETWEEN 1 AND 100
        ),

    CONSTRAINT CLT_PRE_CK
        CHECK (
            PRENOM IS NULL
            OR CHARACTER_LENGTH(TRIM(PRENOM))
                BETWEEN 1 AND 100
        ),

    CONSTRAINT CLT_EMA_CK
        CHECK (
            EMAIL IS NULL
            OR CHARACTER_LENGTH(TRIM(EMAIL))
                BETWEEN 1 AND 254
        ),

    CONSTRAINT CLT_TEL_CK
        CHECK (
            TELEPHONE IS NULL
            OR CHARACTER_LENGTH(TRIM(TELEPHONE))
                BETWEEN 1 AND 30
        ),

    CONSTRAINT CLT_ADR_CK
        CHECK (
            ADRESSE IS NULL
            OR CHARACTER_LENGTH(TRIM(ADRESSE))
                BETWEEN 1 AND 255
        ),

    CONSTRAINT CLT_CP_CK
        CHECK (
            CODE_POSTAL IS NULL
            OR CHARACTER_LENGTH(TRIM(CODE_POSTAL))
                BETWEEN 1 AND 20
        ),

    CONSTRAINT CLT_VIL_CK
        CHECK (
            VILLE IS NULL
            OR CHARACTER_LENGTH(TRIM(VILLE))
                BETWEEN 1 AND 100
        ),

    CONSTRAINT CLT_DAT_CK
        CHECK (MODIFIE_LE >= CREE_LE)
);