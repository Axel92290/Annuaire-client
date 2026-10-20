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
| Application web | PHP MVC avec composants Symfony indépendants |
| Dépendances et autoloading | Composer |
| Base de données | Db2 for i |
| Lecture des données | SQL depuis PHP via ODBC |
| Écritures et règles métier | RPG avec SQL embarqué, sources SQLRPGLE |
| Gestion du code | Git et GitHub |

Le mécanisme d’appel des programmes RPG depuis PHP reste à définir
selon les possibilités de l’environnement IBM i.

## Architecture prévue

```text
Annuaire-client/
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
│   │   ├── bootstrap.php
│   │   ├── routes.php
│   │   ├── services.php
│   │   ├── dev.ini.example
│   │   └── dev.ini          # Local uniquement, ignoré par Git
│   ├── composer.json
│   └── composer.lock
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

### Dépendances PHP prévues

| Composant | Usage |
|---|---|
| `symfony/http-foundation` | Requêtes, réponses et sessions |
| `symfony/security-csrf` | Génération et vérification des jetons CSRF |
| `symfony/validator` | Validation des champs côté PHP |
| `symfony/routing` | Routes et méthodes HTTP autorisées |

Le projet conserve son MVC ; il n'utilise pas le framework Symfony complet.
Les versions seront choisies selon la version de PHP réellement utilisée,
puis fixées dans `composer.lock`. Les dépendances ne sont pas encore installées.

Depuis `web/`, lors de l'initialisation de l'application :

```bash
composer require symfony/http-foundation symfony/security-csrf symfony/validator symfony/routing
```

`voku/anti-xss` n'est pas retenu pour les coordonnées en texte simple.
L'échappement à la sortie reste obligatoire. Un assainisseur HTML tel que
`symfony/html-sanitizer` ne sera envisagé que si une fonctionnalité autorise
du HTML fourni par un utilisateur.

### Namespaces et autoloading

Dans `web/composer.json`, le namespace `App\` sera associé à `src/` :

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Exemple : `web/src/Controllers/ClientController.php` correspond à
`App\Controllers\ClientController`.

Après configuration, exécuter `composer dump-autoload` depuis `web/`.
Le point d'entrée chargera `web/vendor/autoload.php`.

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
- [ ] Initialiser le MVC, les namespaces PSR-4 et les composants Symfony.
- [ ] Configurer les environnements INI et le socle de sécurité web.
- [ ] Configurer la connexion ODBC à Db2.
- [ ] Afficher la liste des clients.
- [ ] Ajouter la recherche et la fiche client.
- [ ] Développer et tester le programme CLTADD.
- [ ] Connecter le formulaire d’ajout au programme RPG.
- [ ] Développer CLTUPD et la modification d’un client.
- [ ] Documenter l’installation et vérifier les scénarios d’erreur.

## Prérequis et installation

Le projet est en conception : il n'existe pas encore de procédure
d'installation complète ni de version exécutable.

Pour l'environnement prévu, PHP s'exécutera sur le PC de développement
et accédera à IBM i à distance. Il faudra :

- PHP et Composer, avec des versions compatibles avec les composants retenus ;
- les extensions PHP `PDO` et `pdo_odbc` ;
- un pilote IBM i Access ODBC installé sur la machine exécutant PHP ;
- sous Windows, le Windows Application Package d'IBM i Access Client Solutions
  pour obtenir le pilote ODBC ; le client ACS 5250 seul ne suffit pas ;
- un accès réseau autorisé à IBM i, un profil dédié et les droits requis ;
- le compilateur RPG et le précompilateur SQL nécessaires aux sources
  SQLRPGLE sur le serveur IBM i.

Le mécanisme d'appel RPG et ses éventuelles dépendances seront validés
dans l'issue #8. Une classe passerelle ne fournit pas, à elle seule,
un mécanisme d'appel distant.

## Configuration et environnements

Organisation prévue dans `web/config/` :

| Fichier | Rôle | Versionné |
|---|---|---|
| `bootstrap.php` | Initialisation, chargement de configuration et gestion des erreurs | Oui |
| `routes.php` | Routes et méthodes HTTP | Oui |
| `services.php` | Construction des objets et injection des dépendances | Oui |
| `dev.ini.example` | Exemple sans identifiants réels | Oui |
| `dev.ini` | Paramètres du développeur | Non |
| `prod.ini` | Paramètres propres à la production, si ce mode est retenu | Non |

L'environnement sera sélectionné par la configuration de démarrage
(par exemple une variable serveur `APP_ENV` autorisant seulement `dev`
ou `prod`), jamais par un paramètre de requête ou un chemin fourni
par le navigateur.

Le chargement utilisera `parse_ini_file()`, avec vérification des erreurs,
des sections et des paramètres obligatoires. Les identifiants restent
des chaînes. Les chaînes et booléens devront être interprétés explicitement.
Une configuration absente ou invalide devra provoquer un arrêt contrôlé.

Exemple de contenu prévu pour `dev.ini.example` :

```ini
[app]
debug = true

[database]
dsn = "odbc:ANNUAIRE_DEV"
username = "UTILISATEUR_A_CONFIGURER"
password = "A_CONFIGURER"
```

Ce fichier configure l'application ; il ne remplace pas le `php.ini`
de PHP. Aucune dépendance Dotenv n'est nécessaire pour ce choix INI.

Les fichiers INI réels sont exclus de Git ; les exemples restent versionnés.
Seul `web/public/` sera exposé au navigateur. Le serveur ne doit rendre
accessibles ni la configuration, ni les sources, ni les journaux.

## Sécurité prévue

Ces protections sont des exigences de réalisation ; elles ne sont pas
encore implémentées.

| Sujet | Protection attendue |
|---|---|
| CSRF | Jetons Symfony liés à la session et à l'action, vérifiés avant chaque écriture ; une modification utilise POST, jamais GET |
| XSS | Échappement des valeurs selon le contexte de sortie ; pour du texte HTML ou un attribut cité : `htmlspecialchars(..., ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')` ; `textContent` pour les insertions de texte en JavaScript |
| Injection SQL | Requêtes préparées dans PHP et variables hôtes en RPG ; aucune concaténation d'entrées dans les commandes SQL |
| Validation | Liste explicite des champs acceptés, types et longueurs contrôlés avec Validator ; contrôles métier définitifs dans RPG |
| Sessions | Cookies HttpOnly, SameSite et Secure sous HTTPS ; mode strict et renouvellement de l'identifiant après authentification |
| Accès | Le CSRF ne remplace pas l'authentification ; avant une exposition hors du poste local, choisir et vérifier une protection d'accès authentifiée |
| IBM i | Profil dédié avec droits minimaux ; aucun profil administrateur pour l'application ; chiffrement des connexions selon les possibilités validées |
| Erreurs | Messages sobres côté utilisateur ; détails techniques dans des journaux privés sans mots de passe ni données personnelles inutiles |
| Navigateur | Politique CSP adaptée aux ressources locales, protection contre l'encadrement et `X-Content-Type-Options: nosniff` |
| Dépendances | `composer.lock` versionné, installation reproductible et `composer audit` avant livraison |

Les contrôles de droits s'appliqueront côté serveur aux consultations comme
aux écritures. La solution d'authentification et d'autorisation reste à choisir :
aucun système de comptes n'est annoncé comme déjà disponible.
Une démonstration locale utilisera uniquement des clients fictifs.

## Issues et branches

Les tâches de réalisation sont suivies dans les [issues du dépôt](https://github.com/Axel92290/Annuaire-client/issues).
Chaque issue indique ses critères de validation, ses dépendances et sa branche.

Les branches de travail existent déjà. Avant de démarrer une tâche,
intégrer la version récente de `main` à sa branche, puis vérifier que ses
issues préalables sont terminées. Les travaux seront proposés par Pull Request
vers `main` avec `Closes #numero` lorsque les critères seront satisfaits.
La création d'une issue ou d'une branche ne signifie pas que la fonctionnalité
est réalisée.

## Auteur

Axel — projet d’apprentissage PHP et IBM i.

## Licence

Ce projet est distribué sous licence MIT.
Voir le fichier [LICENSE](LICENSE).