# Annuaire client

Projet pédagogique d’annuaire clients en PHP MVC, avec des lectures dans Db2 sur IBM i et des écritures réalisées par des programmes RPG.

**État du dépôt : conception et préparation.** L’arborescence et les dépendances ci-dessous sont la cible à implémenter. Le dépôt ne fournit pas encore une application démarrable.

## Ordre de réalisation

La [nouvelle maquette UI/UX](docs/maquettes/README.md) sert de référence : titres Libre Caslon Display, texte Hanken Grotesk, palette neutre, filets et angles droits. Le PDF et les notes de parcours sont versionnés dans docs/maquettes/. On construit d’abord une interface Twig et Bootstrap fonctionnelle avec des données fictives, puis on branche les lectures Db2 et les écritures RPG sur les mêmes écrans.

| Étape | Livrable | Issues |
|---|---|---|
| 1 | Champs partagés, socle PHP/Twig, routes et configuration locale | [#1](../../issues/1), [#3](../../issues/3) |
| 2 | Tous les écrans de la maquette, responsive et états d’interface, sur des données fictives | [#15](../../issues/15) |
| 3 | Table Db2, connexion ODBC, liste, fiche et recherche réelles | [#2](../../issues/2), [#4](../../issues/4), [#5](../../issues/5), [#6](../../issues/6), [#7](../../issues/7) |
| 4 | Sécurité, passerelle RPG, ajout et modification réels | [#14](../../issues/14), [#8](../../issues/8), [#9](../../issues/9), [#10](../../issues/10), [#11](../../issues/11), [#12](../../issues/12) |
| 5 | Recette complète et documentation d’installation | [#13](../../issues/13) |
| Après V1 | TypeScript, seulement si les besoins JavaScript le justifient | [#16](../../issues/16) |

**Prochaine tâche de développement : #3, en parallèle de la validation des champs dans #1, puis #15.**
La préparation de la table et un essai ODBC peuvent avancer en parallèle. Leur disponibilité ne doit pas bloquer l’interface.

Voir la [feuille de route détaillée](docs/roadmap.md) pour les critères de passage entre les étapes et la [décision d’architecture](docs/architecture-web.md) pour Twig et BaseController.

## Périmètre de la V1

- Liste des clients et recherche par nom.
- Consultation d’une fiche client.
- Ajout et modification d’un client.
- Affichage mobile, navigation clavier et messages d’erreur utiles.
- Aucune suppression prévue dans la V1.

| Route cible | Usage |
|---|---|
| GET /clients | Liste et recherche |
| GET /clients/new | Formulaire d’ajout |
| GET /clients/{id} | Fiche client |
| GET /clients/{id}/edit | Formulaire de modification |
| POST /clients | Création réelle après connexion à CLTADD |
| POST /clients/{id}/edit | Modification réelle après connexion à CLTUPD |

Les routes fixes comme /clients/new doivent être résolues avant la route dynamique ; le format de l’identifiant est contraint.

## Choix techniques

| Élément | Choix |
|---|---|
| Serveur web local | Wamp sous Windows, avec Apache et PHP |
| Architecture | MVC léger, namespace App et autoload PSR-4 |
| Requêtes, réponses, sessions | symfony/http-foundation |
| Routes | symfony/routing |
| Protection des formulaires | symfony/security-csrf |
| Validation serveur | symfony/validator |
| Templates | twig/twig, héritage et échappement HTML automatique |
| Interface | Bootstrap 5.3 adapté à la maquette, assets et polices locaux, styles du projet |
| JavaScript | JavaScript natif, limité aux interactions utiles |
| Lectures réelles | PDO_ODBC et pilote IBM i Access ODBC vers Db2 |
| Écritures réelles | Programmes SQLRPGLE CLTADD et CLTUPD via une passerelle à valider |

Les composants Symfony sont utilisés indépendamment : le framework complet et TwigBundle ne sont pas requis. Une classe View supplémentaire n’est pas nécessaire ; BaseController mutualise le rendu Twig et les redirections en retournant des Response.

TypeScript et Node/npm ne sont pas nécessaires pour commencer. Une bibliothèque de nettoyage HTML comme voku/anti-xss n’est pas prévue pour les champs textuels simples : les données sont normalisées et validées à l’entrée, puis échappées selon leur contexte à l’affichage. Ne pas pré-encoder les valeurs avant stockage.

## Environnements de travail

### Interface locale

Pour #3 et #15 : Git/GitHub Desktop, Wamp avec un PHP compatible avec les versions Composer retenues, et Composer.

Les pages utilisent un jeu de données PHP fictives. Le chargement du socle et des vues ne doit pas ouvrir de connexion Db2, imposer le pilote ODBC ou demander des identifiants PUB400. L’extension pdo_odbc ne devient une exigence d’installation qu’à l’étape d’intégration.

La recherche peut fonctionner sur les données fictives. Les formulaires permettent de vérifier la présentation et les états ; ils ne doivent pas annoncer une écriture réussie avant le branchement RPG.

### Intégration IBM i

- Un compte IBM i/PUB400 et les autorisations nécessaires pour la bibliothèque du projet.
- Le pilote IBM i Access ODBC et l’extension PHP PDO_ODBC, compatibles avec l’architecture du PHP exécuté par Wamp.
- Une configuration ODBC et des identifiants conservés hors du dépôt.
- Un terminal 5250, par exemple Mocha ou tn5250j, pour travailler sur IBM i.
- Une preuve de lecture Db2, puis une preuve d’appel RPG et de récupération des erreurs.

PUB400 fournit l’environnement distant. Le terminal 5250 sert à y travailler ; il ne connecte pas l’application PHP à Db2. Wamp héberge l’application en local. MySQL fourni par Wamp n’est pas la base métier de ce projet.

Le mécanisme d’appel des programmes RPG doit être validé dans #8 avant de considérer l’intégration des écritures comme acquise.

## Arborescence cible

```text
docs/
  architecture-web.md
  roadmap.md
  diagrammes/
  maquettes/
    README.md
    maquette-ui-ux.pdf
    parcours-accessibilite.png
sql/
rpg/
web/
  composer.json
  config/
    bootstrap.php
    routes.php
    services.php
    dev.ini.example
  fixtures/
    clients.php
  public/
    index.php
    assets/
      css/
      js/
      vendor/
  src/
    Controllers/
      BaseController.php
      ClientController.php
    Repositories/
      ClientRepository.php
    Services/
      ClientService.php
      RpgGateway.php
  templates/
    base.html.twig
    clients/
      index.html.twig
      show.html.twig
      new.html.twig
      edit.html.twig
      _form.html.twig
    errors/
      404.html.twig
      500.html.twig
  var/
    cache/
    log/
```

Cette arborescence décrit la cible ; les classes d’intégration sont ajoutées lors des issues correspondantes. Les données fictives sont chargées côté PHP et partagent le contrat des vues avec les données Db2. Elles ne sont jamais choisies via un chemin transmis par l’utilisateur.

Seul **web/public/** doit être exposé par Apache. Les templates, fichiers INI, sources, dépendances et journaux restent hors du répertoire public.

## Configuration et dépendances

Composer sera initialisé dans web/ dans #3, avec un autoload PSR-4 pour App vers src/.
Les dépendances directes prévues sont :

```text
symfony/http-foundation
symfony/routing
symfony/security-csrf
symfony/validator
twig/twig
```

Les versions seront choisies selon le PHP installé, puis composer.lock sera commité. Les dépendances réellement nécessaires à l’intégration ODBC seront déclarées dans #4.

- config/bootstrap.php charge l’environnement et valide la configuration.
- config/services.php construit les dépendances et injecte Twig une seule fois.
- config/routes.php déclare les routes.
- dev.ini.example décrit les paramètres, sans secret.
- dev.ini et prod.ini restent locaux et ignorés par Git.
- L’environnement est sélectionné côté serveur, jamais depuis un paramètre HTTP.
- La configuration de démonstration est réservée au développement ; une configuration de production ne doit pas utiliser silencieusement des données fictives.
- Les paramètres Db2 sont exigés uniquement lorsque les fonctionnalités réelles sont activées.

Les commandes d’installation et la configuration Apache seront documentées avec le code réellement livré, dans #3 puis #13.

## Règles d’architecture et de sécurité

- Le contrôleur de base reste limité au rendu et aux redirections. Les contrôleurs reçoivent leurs services par injection.
- Les actions retournent une Response ; le point d’entrée l’envoie une seule fois.
- Twig conserve autoescape HTML et strict_variables ; pas de raw sur les données clients.
- Les POST vérifient les autorisations, le jeton CSRF et les données avant tout appel métier.
- Les écritures passent par CLTADD/CLTUPD ; les lectures utilisent des requêtes préparées.
- Une redirection ou un refus interrompt réellement l’action.
- Après une écriture confirmée, redirection 303 et message de succès ; un échec ne produit pas de faux succès.
- Identifiants, mots de passe, configurations locales, vendor/, caches et journaux ne sont pas versionnés.
- Erreurs techniques dans les journaux ; messages compréhensibles dans l’interface.
- Cookies de session HttpOnly et SameSite, Secure sous HTTPS ; contrôle d’accès avant toute exposition à des utilisateurs.
- Conserver la licence des assets Bootstrap distribués localement.

La sécurité est amorcée dès #3 et finalisée dans #14 avant les écritures. L’authentification et les droits doivent être définis avant toute exposition hors de l’environnement de développement.

## Organisation GitHub

Les issues #1 à #16 conservent leur numéro. Toutes sont assignées à Axel92290 et disposent d’un label, de critères de validation et de références de dépendance.

Les branches existantes liées aux issues #1 à #15 sont conservées, y compris feat/15-interface-bootstrap pour l’intégration de la maquette. #16 reste une évolution facultative sans branche active.

Pour chaque tâche : mettre à jour la branche avec main avant de commencer, développer le périmètre de l’issue, puis ouvrir une PR avec Closes #numéro et les vérifications effectuées. Les liens natifs Relationships et Development complètent les indications du corps des issues.

## Licence

Le code est distribué sous la licence MIT du dépôt. Les dépendances et assets conservent leurs licences respectives.
