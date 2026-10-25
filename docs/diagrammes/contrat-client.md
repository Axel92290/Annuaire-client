# Diagrammes du contrat client — issue #1

Ces diagrammes précisent le [contrat des données et des écrans](../modele-donnees.md).
Ils complètent les PNG existants sans changer l'architecture. Le PNG du modèle
est une proposition de départ et celui des classes laisse explicitement les
types facultatifs à préciser : les types définitifs sont ceux ci-dessous.

## Table CLIENTS

```mermaid
erDiagram
    CLIENTS {
        BIGINT ID_CLIENT PK "Identite generee, positif, NOT NULL"
        VARCHAR_100 NOM "NOT NULL, non vide apres trim"
        VARCHAR_100 PRENOM "NULL autorise"
        VARCHAR_254 EMAIL "NULL autorise, format si renseigne"
        VARCHAR_30 TELEPHONE "NULL autorise, texte"
        VARCHAR_255 ADRESSE "NULL autorise"
        VARCHAR_20 CODE_POSTAL "NULL autorise, texte"
        VARCHAR_100 VILLE "NULL autorise"
        TIMESTAMP CREE_LE "NOT NULL, UTC, immuable"
        TIMESTAMP MODIFIE_LE "NOT NULL, UTC, derniere ecriture"
    }
```

Une seule entité est nécessaire. Aucune colonne de référence d'affichage n'est
ajoutée. Les suffixes `100`, `254`, etc. représentent les limites de caractères.

## Types du modèle PHP

```mermaid
classDiagram
    class Client {
        -int id
        -string nom
        -string|null prenom
        -string|null email
        -string|null telephone
        -string|null adresse
        -string|null codePostal
        -string|null ville
        -DateTimeImmutable creeLe
        -DateTimeImmutable modifieLe
        +getId() int
        +getNom() string
    }
    class ClientRepository
    class ClientService
    class ClientController
    class IbmiProgramGateway
    class TwigEnvironment
    ClientRepository ..> Client : construit depuis Db2
    ClientService --> ClientRepository : lectures
    ClientService --> IbmiProgramGateway : ecritures RPG
    ClientController --> ClientService : coordonne
    ClientController --> TwigEnvironment : contexte explicite
    TwigEnvironment ..> Client : affiche avec echappement
```

`string|null` correspond à `?string` en PHP. Ces types remplacent les `string`
génériques des champs facultatifs dans le PNG des classes ; les dates utilisent
`DateTimeImmutable` plutôt que `DateTime`. Les autres responsabilités du
[diagramme de classes](02_Diagramme_de_classes.png) sont conservées.

## Données de saisie et de présentation

```mermaid
flowchart LR
    S[Sept champs saisis] --> F[Valeurs et erreurs du formulaire]
    F --> V[Validation PHP et normalisation]
    V --> R[Validation et ecriture RPG]
    R --> D[(CLIENTS dans Db2)]
    D --> C[ClientRepository puis modele Client]
    X[Fixtures au meme contrat Client] --> T[Contexte Twig]
    C --> T
    F --> T
    T --> E[Ecrans avec echappement HTML]
```

Le parcours fixtures ne passe pas par Db2. Les valeurs de formulaire restent
des chaînes, les champs métier facultatifs deviennent `null` après normalisation.
La référence sur six chiffres minimum est calculée à l'affichage. Aucun texte
pré-encodé en HTML n'est stocké.
