# Validation de la recherche Db2 — issue 7

## Fonctionnement

Le formulaire GET existant utilise toujours le paramètre q.

Le contrôleur normalise la saisie via ClientService.
La longueur maximale est de 100 caractères côté serveur.

Une recherche vide retourne toute la liste.
Une recherche non vide appelle ClientRepository::findByName().

En mode réel, le filtrage est effectué dans Db2.
La démonstration conserve un filtrage PHP sur les fixtures.

## Encodage et paramètres

Le filtre est transmis dans un document JSON ASCII,
avec les caractères Unicode échappés.

JSON_VALUE reconstruit le motif en UTF-8 dans Db2.

Le transport a été vérifié pour :
- Martin ;
- Inès ;
- Léo ;
- É ;
- les caractères %_! ;
- du texte japonais ;
- un emoji.

Le document JSON est lié comme paramètre SQL.

Les colonnes retournées conservent le transport HEX()
et le décodage validés dans les issues précédentes.

## Règles de recherche

La recherche porte sur le nom uniquement, par sous-chaîne.

UPPER() est appliqué au nom et au motif pour ignorer la casse
selon les règles Db2. Les accents ne sont pas supprimés.

Les caractères %, _ et ! saisis sont échappés pour être
recherchés littéralement.

Le tri reste NOM, PRENOM, puis ID_CLIENT.

## Résultats

À compléter après exécution :

- Syntaxe PHP : OK
- composer validate --strict : OK
- composer check-platform-reqs : OK
- composer check:db2 : OK
- Recherche vide : OK
- Recherche Mar : OK
- Recherches mar et MAR : OK
- Recherche martin : OK
- Recherche avec apostrophe : OK
- Recherche sans résultat : OK
- Caractères % et _ traités littéralement : OK
- Caractère ! traité littéralement : OK
- Espaces aux extrémités : OK
- Recherche composée uniquement d'espaces : OK
- Compteur cohérent : OK
- Réinitialisation : OK
- Réaffichage échappé : OK
- Recherche de plus de 100 caractères : statut 400 : OK
- Paramètre tableau : statut 400 : OK
- Recherche asynchrone : OK
- Fonctionnement sans JavaScript : OK
- Consultation d'une fiche depuis les résultats : OK
- Démonstration sans Db2 : OK
- Configuration réelle restaurée : OK

## Limites

Le diagnostic valide le transport Unicode des paramètres.
Les noms du jeu de données actuel ne couvrent pas tous
les cas de comparaison de noms accentués.

Le déplacement du filtre dans SQL ne supprime pas
le temps d'ouverture de la connexion distante observé
dans l'issue 5.

Aucune écriture n'est réalisée.
Aucun programme RPG n'est appelé.