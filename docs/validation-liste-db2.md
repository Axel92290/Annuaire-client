# Validation de la liste Db2 — issue 5

## Fonctionnement

La liste réelle suit ce parcours :

ClientController → ClientService → ClientRepository → Db2Connection.

ClientRepository::findAll() lit AXEL921.CLIENTS et transforme
les lignes SQL en objets Client.

Le template clients/index.html.twig est conservé.
Le compteur est calculé à partir des clients réellement servis.

Le tri utilise les colonnes originales :
NOM, PRENOM, puis ID_CLIENT.

Aucune écriture n'est réalisée.

## Encodage

Les colonnes textuelles sont stockées avec le CCSID 1208.

Le diagnostic a montré des valeurs correctes dans Db2, mais des
octets non UTF-8 lors de leur lecture textuelle par PDO_ODBC
dans l'environnement Windows utilisé.

La requête restitue donc les textes avec HEX().
Le repository reconstruit les octets avec hex2bin(), puis
vérifie leur validité UTF-8.

Cette adaptation ne modifie pas les données enregistrées.
Les valeurs NULL sont conservées.

L'échappement HTML reste effectué par Twig, à l'affichage.

## Environnement

- PHP : 8.2.13, 64 bits
- Serveur local : Apache/Wamp
- Base : Db2 for i sur PUB400
- Bibliothèque : AXEL921
- Connexion : ODBC avec TLS
- Identifiants et configuration : fichiers privés non versionnés

## Vérifications observées

- Liste réelle affichée avec cinq clients.
- Accents Inès et Léo correctement affichés après correction.
- Références techniques présentées sur six chiffres.
- Champs facultatifs absents représentés par un tiret.

## Vérifications complémentaires

À compléter avec les résultats réellement obtenus :

- Syntaxe des fichiers PHP : Ok
- composer validate --strict : Ok
- composer check-platform-reqs : Ok
- composer check:db2 : Ok
- Compteur cohérent avec Db2 : Ok
- Tri déterministe : Ok
- Échappement HTML : Ok
- Recherche Mar : Ok
- Recherche sans résultat : Ok
- Réinitialisation : Ok
- Fonctionnement sans JavaScript : Ok
- Affichage à 390, 768 et 1280 px : Ok
- Navigation clavier : Ok
- Résultat SQL vide simulé avec WHERE 1 = 0 : Ok
- Condition temporaire retirée : Ok
- Intégration désactivée en mode réel : statut 503 : Ok
- Aucun détail technique dans la réponse : Ok
- Aucun remplacement silencieux par les fixtures : Ok
- Démonstration fonctionnelle sans Db2 : Ok
- Configuration réelle restaurée : Ok

## Limites du périmètre

La recherche reste filtrée en PHP après lecture de la liste.

Les fiches et les formulaires réels ne sont pas raccordés dans
cette issue. Leur réponse 503 en mode réel est attendue à ce stade.

Le test avec WHERE 1 = 0 simule un résultat vide sans supprimer
de données.

La désactivation de l'intégration n'est pas un test de panne réseau.

Aucun programme RPG n'est appelé.