# Passerelle IBM i — issue 8

## Mécanisme retenu

PHP appelle une procédure SQL avec PDO_ODBC.

Chaîne d'appel :

IbmiProgramGateway::ping()
→ AXEL921.GW_PING_CALL
→ AXEL921.GW_PING
→ programme AXEL921/GWPING.

GW_PING décrit l'interface du programme RPG.
GW_PING_CALL retourne ses sorties sous forme de jeu de résultats.

Aucune donnée métier n'est modifiée.

## Sources

- ibmi/qrpglesrc/GWPING.sqlrpgle
- ibmi/sql/gateway-ping.sql
- web/src/Infrastructure/IbmiProgramGateway.php
- web/bin/check-gateway.php

## Compilation du programme

La source IFS est enregistrée en UTF-8, CCSID 1208.

Commande utilisée :

CRTSQLRPGI OBJ(AXEL921/GWPING)
SRCSTMF('/home/AXEL92/GWPING.sqlrpgle')
OBJTYPE(*PGM) COMMIT(*NONE) DBGVIEW(*SOURCE)
USRPRF(*USER) REPLACE(*NO) CVTCCSID(*JOB)

La commande est à saisir sur une seule ligne.

CVTCCSID(*JOB) permet la conversion de la source Unicode
pendant la compilation.

## Installation SQL

Transférer gateway-ping.sql dans l'IFS en UTF-8.

Exécuter :

RUNSQLSTM SRCSTMF('/home/AXEL92/gateway-ping.sql')
COMMIT(*NONE) NAMING(*SQL) DFTRDBCOL(AXEL921) OUTPUT(*PRINT)

La commande est à saisir sur une seule ligne.

Le script crée les procédures : il n'est pas idempotent.
Ne pas le rejouer sans vérifier les routines déjà présentes.

Une erreur peut laisser une installation partielle.
Consulter le listing avant toute nouvelle exécution.

## Contrat du programme de test

Paramètres RPG, dans cet ordre :

- request : entrée CHAR(10), non modifiée.
- reply : sortie CHAR(30).
- resultCode : sortie CHAR(5).

La déclaration SQL externe utilise PARAMETER STYLE GENERAL.

La passerelle accepte une demande ASCII de 1 à 10 caractères,
composée de A-Z, 0-9 ou _, avec une première lettre obligatoire.

Ce contrat de test ne définit pas encore le transport
des futurs champs clients Unicode.

## Sorties

- PING : PONG, code 00000.
- Autre demande acceptée par le contrat : code B0001,
  réponse Unsupported request.

Les sorties sont converties en UTF-8 côté SQL,
transportées en hexadécimal et décodées par PHP.

## Catégories de résultat

- success : code 00000.
- business_error : code B0001, appel technique réussi.
- Échec technique : RuntimeException, aucun résultat de succès.
- Entrée invalide : InvalidArgumentException avant l'appel.

Les codes sont applicatifs ; ils ne sont pas des SQLSTATE.

## Sécurité et droits

La méthode PHP fixe le nom GW_PING_CALL côté serveur.
Aucun nom de programme ou commande ne vient du navigateur.

Le programme RPG est compilé avec USRPRF(*USER).
La procédure SQL utilise SET OPTION USRPRF = *USER.

Les appels réutilisent la connexion ODBC avec TLS.

Les identifiants restent dans la configuration privée.
Les journaux ne recopient ni DSN, ni mot de passe,
ni message brut du pilote, ni demande transmise.

Sur PUB400, le profil utilisé possède les objets du projet.
Il ne constitue donc pas un compte applicatif aux droits minimaux.

Pour un compte d'exécution distinct, les besoins à vérifier sont :
- accès nécessaire à la bibliothèque ;
- autorisation d'exécution des routines ;
- autorisation d'appel du programme externe.

Les droits de création et compilation servent à l'installation,
pas au fonctionnement courant.

Aucun droit supplémentaire à *PUBLIC n'est nécessaire
pour les tests avec le profil propriétaire.

## Validation

Déjà observé avec le diagnostic initial :
- appel PING : PONG, 00000 ; 
- appel UNKNOWN : Unsupported request, B0001.

À compléter pour la classe définitive :
- Syntaxe PHP : OK
- composer validate --strict : OK
- composer check-platform-reqs : OK
- composer check:db2 : OK
- composer check:gateway : OK
- Entrée invalide refusée : OK
- Intégration désactivée : échec technique : OK
- Configuration restaurée : OK
- Liste et fiche toujours accessibles : OK

## Limites

Ce programme ne lit ni ne modifie CLIENTS.
Il ne prouve pas encore l'ajout ou la modification d'un client.

Les futurs programmes métier nécessiteront leurs propres
contrats de paramètres, validations et tests.