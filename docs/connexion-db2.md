# Connexion PHP à Db2 sur PUB400

## Environnement

- Wamp et PHP 64 bits.
- Extension PDO_ODBC activée dans le PHP du terminal et d’Apache.
- Pilote IBM i Access ODBC Driver 64 bits.
- Serveur : pub400.com.
- Bibliothèque : AXEL921.
- Table : AXEL921.CLIENTS.

## Configuration Windows

Le DSN ANNUAIRE_PUB400 est une source de données système 64 bits.

La connexion utilise SSL, sans fenêtre interactive de connexion.
Les identifiants sont fournis par la configuration privée PHP.

## Confiance TLS

Le magasin utilisé par le pilote est :

C:\Users\Public\Documents\IBM\Client Access\cwbssldf.kdb

La racine Sectigo Public Server Authentication Root E46
a été vérifiée contre le magasin Mozilla/NSS puis importée.

Empreinte SHA-256 :

C9:0F:26:F0:FB:1B:40:18:B2:22:27:51:9B:5C:A2:B5:3E:2C:A5:B3:BE:5C:F1:8E:FE:1B:EF:47:38:0C:53:83

cwbping a confirmé une connexion TLS 1.3 au service
as-database sur le port 9471.

Le magasin IBM, son fichier de mot de passe et les sauvegardes
restent hors du dépôt.

## Configuration PHP

La section database est définie dans config/dev.ini,
fichier ignoré par Git.

dev.ini.example fournit un exemple sans identifiants secrets,
avec l’intégration désactivée.

Db2Connection ne crée la connexion PDO que lors de
l’appel à getConnection().

## Démonstration et intégration

La démonstration utilise les fixtures PHP et ne dépend pas de Db2.

PDO est déclaré dans require.
PDO_ODBC est signalé dans suggest et exigé par le contrôle
d’intégration composer check:db2.

Activer database.enabled ne branche pas automatiquement
les écrans sur la base.

## Vérification

Depuis web/ :

composer check:db2

Le contrôle effectue uniquement une lecture de compteur.

## Résultats de validation

- Configuration Composer : valide.
- Prérequis de plateforme : vérifiés.
- Contrôle `composer check:db2` : réussi.
- Connexion chiffrée au service IBM i : réussie.
- Protocole observé avec `cwbping` : TLS 1.3.
- Certificat : chaîne Sectigo vérifiée et racine E46 ajoutée
  au magasin de confiance IBM.
- Lecture depuis le PHP du terminal : réussie.
- Lecture depuis Apache/Wamp : réussie.
- Table vérifiée : AXEL921.CLIENTS.
- Nombre de clients : 5.
- Démonstration avec Db2 désactivé : validée.
- Gestion d’un échec de connexion sans exposition de secret : validée.
- Route temporaire de diagnostic : retirée après vérification.

### Droits du compte — contrainte PUB400

La connexion utilise le profil PUB400 attribué AXEL92,
avec la bibliothèque de travail AXEL921.

L’environnement public ne permet pas à l’utilisateur de créer
un profil applicatif supplémentaire. Aucun compte dédié limité
à SELECT n’a donc été créé.

Les contrôles d’intégration exécutent uniquement des lectures
sur AXEL921.CLIENTS. Aucun privilège système supplémentaire
ni droit à *PUBLIC n’a été accordé.

Le profil reste propriétaire des objets du projet :
la restriction aux lectures relève actuellement du code utilisé,
pas d’une séparation des droits côté serveur.

Pour un déploiement sur un environnement administrable,
prévoir un profil applicatif dédié avec les droits nécessaires
aux opérations réellement exposées.

### Limites

Les écrans utilisent encore les fixtures PHP.
Leur alimentation depuis Db2 sera réalisée dans les issues suivantes.

Les configurations privées, mots de passe, magasins de clés
et sauvegardes ne sont pas versionnés.