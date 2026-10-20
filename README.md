# Annuaire clients — PHP MVC & IBM i

Projet pédagogique de gestion de clients associant une interface web
en PHP à une base Db2 sur IBM i et à des traitements métier en RPG.

L’objectif est d’apprendre progressivement à faire communiquer
une application web avec IBM i, en séparant la présentation,
la coordination des opérations et les règles métier.

> État du projet : conception.
> Les fonctionnalités et l’architecture présentées ci-dessous
> constituent le périmètre prévu.

## Fonctionnalités prévues

- Afficher la liste des clients.
- Rechercher un client par son nom.
- Consulter la fiche d’un client.
- Ajouter un client.
- Modifier les informations d’un client.
- Afficher les erreurs de validation.

La suppression des clients ne fait pas partie de la première version.

## Technologies

| Partie | Technologie |
|---|---|
| Interface | HTML, CSS et JavaScript |
| Application web | PHP avec une architecture MVC |
| Dépendances et autoloading | Composer |
| Base de données | Db2 for i |
| Lecture des données | SQL depuis PHP via ODBC |
| Écritures et règles métier | RPG avec SQL embarqué, sources SQLRPGLE |
| Gestion du code | Git et GitHub |

Le mécanisme d’appel des programmes RPG depuis PHP reste à définir
selon les possibilités de l’environnement IBM i.

## Architecture prévue

```text
annuaire-clients-ibmi/
├── docs/
│   └── diagrammes/
├── web/
│   ├── public/
│   │   ├── index.php
│   │   └── assets/
│   ├── src/
│   │   ├── Controllers/
│   │   │   └── ClientController.php
│   │   ├── Models/
│   │   │   └── Client.php
│   │   ├── Repositories/
│   │   │   └── ClientRepository.php
│   │   ├── Services/
│   │   │   └── ClientService.php
│   │   └── Infrastructure/
│   │       ├── Db2Connection.php
│   │       └── IbmiProgramGateway.php
│   ├── views/
│   │   ├── layout.php
│   │   └── clients/
│   │       ├── index.php
│   │       ├── show.php
│   │       └── form.php
│   ├── config/
│   └── composer.json
├── ibmi/
│   ├── sql/
│   │   └── clients.sql
│   └── qrpglesrc/
│       ├── CLTADD.sqlrpgle
│       └── CLTUPD.sqlrpgle
├── .gitignore
├── LICENSE
└── README.md
```

### Rôle des composants

- **ClientController** reçoit les requêtes et prépare les réponses.
- **Client** représente les données d’un client.
- **ClientService** contrôle les données du formulaire et coordonne les opérations.
- **ClientRepository** lit les clients dans Db2 avec SQL.
- **Db2Connection** fournit la connexion à la base.
- **IbmiProgramGateway** centralise les appels aux programmes RPG.
- **CLTADD** contrôle les données métier et ajoute un client.
- **CLTUPD** contrôle les données métier et modifie un client.
- **Les vues** affichent les pages et les formulaires.

Seul le dossier `web/public/` doit être exposé par le serveur web.

## Circulation des données

### Consultation

Interface web → Contrôleur → Service → Repository → Db2.

### Création et modification

Interface web → Contrôleur → Service → Passerelle IBM i
→ Programme RPG → Db2.

Les contrôles PHP permettent un retour rapide dans le formulaire.
Les programmes RPG effectuent les contrôles métier définitifs
avant toute écriture.

## Modèle de données prévu

La première version utilise une table `CLIENTS`.

| Champ | Rôle |
|---|---|
| ID_CLIENT | Identifiant unique généré |
| NOM | Nom obligatoire |
| PRENOM | Prénom |
| EMAIL | Adresse électronique |
| TELEPHONE | Numéro de téléphone |
| ADRESSE | Adresse postale |
| CODE_POSTAL | Code postal |
| VILLE | Ville |
| CREE_LE | Date et heure de création |
| MODIFIE_LE | Date et heure de dernière modification |

Les tailles, la gestion des valeurs facultatives et les contraintes SQL
seront précisées avant la création de la table.

## Diagrammes

Les diagrammes de conception seront conservés dans `docs/diagrammes/` :

- modèle de données ;
- diagramme de classes ;
- diagramme de séquence pour l’ajout d’un client.

## Étapes de réalisation

- [x] Définir l’architecture générale.
- [x] Préparer une première version des diagrammes.
- [ ] Valider les champs et les règles métier.
- [ ] Créer la table CLIENTS dans Db2.
- [ ] Initialiser l’application PHP et l’autoloading Composer.
- [ ] Configurer la connexion ODBC à Db2.
- [ ] Afficher la liste des clients.
- [ ] Ajouter la recherche et la fiche client.
- [ ] Développer et tester le programme CLTADD.
- [ ] Connecter le formulaire d’ajout au programme RPG.
- [ ] Développer CLTUPD et la modification d’un client.
- [ ] Documenter l’installation et vérifier les scénarios d’erreur.

## Installation

Les instructions seront ajoutées lorsque la première version
exécutable sera disponible.

L’environnement de développement devra disposer de PHP,
de Composer, d’un pilote ODBC compatible et d’un accès autorisé
à IBM i. La compilation des sources RPG nécessite également
les outils et les droits correspondants sur IBM i.

## Configuration et données

Les identifiants de connexion restent dans une configuration locale
exclue de Git.

Les fichiers de configuration d’exemple contiendront uniquement
des valeurs fictives.

Les exemples et démonstrations utiliseront des clients fictifs.

## Auteur

Axel — projet d’apprentissage PHP et IBM i.

## Licence

Ce projet est distribué sous licence MIT.
Voir le fichier [LICENSE](LICENSE).