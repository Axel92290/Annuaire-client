# Validation de la fiche Db2 — issue 6

## Fonctionnement

La consultation réelle suit ce parcours :

ClientController::show()
→ ClientService::findById()
→ ClientRepository::findById()
→ Db2Connection.

La lecture utilise une requête préparée.
L'identifiant est validé avant tout accès Db2, puis transmis
comme paramètre avec conversion explicite en BIGINT.

Le repository retourne un objet Client ou null.
Le contrôleur transforme l'absence du client en réponse 404.

Les erreurs de lecture et l'intégration désactivée produisent
une réponse 503, sans retour silencieux aux fixtures.

## Données et présentation

Le template clients/show.html.twig est conservé.

La conversion des textes réutilise HEX() et textFromDatabase(),
validés lors de l'issue 5.

Les champs facultatifs restent null dans le modèle.
Le téléphone et le code postal restent textuels.

Les dates suivent la convention UTC à la lecture.
L'affichage utilise Europe/Paris.

La référence est présentée sur six chiffres au minimum,
sans modifier l'identifiant technique de la route.

## Résultats

À compléter après les vérifications :

- Syntaxe des trois fichiers PHP : Ok
- composer validate --strict : Ok
- composer check-platform-reqs : Ok
- composer check:db2 : Ok
- Liste réelle toujours fonctionnelle : Ok
- Accès à une fiche existante : statut 200 : Ok
- Correspondance entre la fiche et le client sélectionné : Ok
- Accents Inès et Léo : Ok
- Champs facultatifs : Ok
- Code postal 01230 : Ok
- Dates et conversion Europe/Paris : Ok
- Référence sur six chiffres au minimum : Ok
- Client inexistant : statut 404 : Ok
- Identifiants mal formés : statut 404 : Ok
- Identifiant supérieur au BIGINT signé : statut 404 : Ok
- Intégration désactivée : statut 503 : Ok
- Aucun détail technique dans la réponse : Ok
- Absence de remplacement par une fixture en mode réel : Ok
- Retour à la liste : Ok
- Lien de modification avec le bon identifiant : Ok
- Échappement HTML : Ok
- Navigation clavier : Ok
- Affichage à 390, 768 et 1280 px : Ok
- Consultation sans JavaScript : Ok
- Fiche de démonstration fonctionnelle sans Db2 : Ok
- Configuration réelle restaurée : Ok

## Limites

La modification et la création réelles restent désactivées.
Le lien de modification vise le parcours existant, encore bloqué
en mode réel à cette étape.

Le test d'intégration désactivée ne simule pas une panne réseau.

Aucune écriture n'est réalisée.
Aucun programme RPG n'est appelé.