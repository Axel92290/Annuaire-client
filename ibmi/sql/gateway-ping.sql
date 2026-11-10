-- Installation des procedures de test de la passerelle.
-- Bibliotheque autorisee : AXEL921.
--
-- Prerequis : le programme AXEL921/GWPING doit etre compile.
-- Executer ce fichier une fois.
-- Aucun acces a la table CLIENTS.

SET SCHEMA AXEL921;

-- Exposer le programme RPG comme une procedure SQL.
--
-- Les parametres doivent respecter exactement l'ordre et
-- les tailles declares dans le dcl-pi du programme :
-- CHAR(10), CHAR(30), CHAR(5).
--
-- PARAMETER STYLE GENERAL transmet les trois parametres
-- sans ajouter les parametres techniques du style SQL.
--
-- NO SQL indique que le programme externe n'execute pas de SQL.

CREATE PROCEDURE AXEL921.GW_PING (
    IN P_REQUEST CHAR(10),
    OUT P_REPLY CHAR(30),
    OUT P_RESULT_CODE CHAR(5)
)
LANGUAGE RPGLE
PARAMETER STYLE GENERAL
NO SQL
EXTERNAL NAME 'AXEL921/GWPING';


-- Adapter les sorties du programme en un jeu de resultats.
--
-- PHP fournira seulement la demande d'entree.
-- Il recevra une ligne contenant la reponse et le code applicatif.
--
-- SQL SECURITY INVOKER utilise les droits de l'appelant pour
-- les instructions SQL de cette procedure.
--
-- READS SQL DATA couvre la lecture de SYSIBM.SYSDUMMY1.
-- Aucune donnee metier n'est lue ou modifiee.

CREATE PROCEDURE AXEL921.GW_PING_CALL (
    IN P_REQUEST CHAR(10)
)
LANGUAGE SQL
DYNAMIC RESULT SETS 1
READS SQL DATA
SET OPTION USRPRF = *USER
BEGIN
    DECLARE V_REPLY CHAR(30) DEFAULT '';
    DECLARE V_RESULT_CODE CHAR(5) DEFAULT '';

    -- Le curseur reste disponible pour l'appelant apres le retour.
    --
    -- Convertir explicitement les sorties en UTF-8 avant HEX().
    -- Le pilote ODBC transportera uniquement des caracteres ASCII.
    --
    -- TRIM retire les espaces de remplissage des champs CHAR.

    DECLARE C_RESULT CURSOR WITH RETURN TO CALLER FOR
        SELECT
            HEX(
                CAST(
                    TRIM(V_REPLY)
                    AS VARCHAR(120) CCSID 1208
                )
            ) AS REPLY_HEX,
            HEX(
                CAST(
                    TRIM(V_RESULT_CODE)
                    AS VARCHAR(20) CCSID 1208
                )
            ) AS RESULT_CODE_HEX
        FROM SYSIBM.SYSDUMMY1;

    -- Executer le programme RPG via sa declaration externe.
    CALL AXEL921.GW_PING(
        P_REQUEST,
        V_REPLY,
        V_RESULT_CODE
    );

    -- Fournir les valeurs de sortie au client ODBC.
    OPEN C_RESULT;
END;