# Contrat des données et des écrans — issue #1

Ce document fixe le contrat commun aux fixtures, aux vues PHP/Twig, à la future
table Db2 `CLIENTS` et aux programmes RPG. Il reprend les champs du
[modèle initial](diagrammes/01_Modele_de_donnees.png) et les confronte à la
[maquette de référence](maquettes/README.md). Les
[diagrammes du contrat](diagrammes/contrat-client.md) précisent les types et la
circulation des données. Ce contrat ne signifie pas que l'application est implémentée.

## Dictionnaire des champs

Les noms SQL restent en capitales ; le modèle PHP et les objets transmis à Twig
emploient les propriétés ci-dessous. Les fixtures respectent les mêmes propriétés
et types que les clients lus depuis Db2.

| Colonne Db2 | Propriété PHP/Twig | Type Db2 cible | Type PHP | Règle |
|---|---|---|---|---|
| `ID_CLIENT` | `id` | `BIGINT` | `int` | Clé primaire positive, générée par la base, non modifiable |
| `NOM` | `nom` | `VARCHAR(100) NOT NULL` | `string` | Obligatoire, non vide après trim |
| `PRENOM` | `prenom` | `VARCHAR(100) NULL` | `?string` | Facultatif |
| `EMAIL` | `email` | `VARCHAR(254) NULL` | `?string` | Facultatif, format email valide si renseigné |
| `TELEPHONE` | `telephone` | `VARCHAR(30) NULL` | `?string` | Facultatif, texte conservant zéros, préfixe et séparateurs |
| `ADRESSE` | `adresse` | `VARCHAR(255) NULL` | `?string` | Facultatif |
| `CODE_POSTAL` | `codePostal` | `VARCHAR(20) NULL` | `?string` | Facultatif, texte conservant les zéros initiaux |
| `VILLE` | `ville` | `VARCHAR(100) NULL` | `?string` | Facultatif |
| `CREE_LE` | `creeLe` | `TIMESTAMP NOT NULL` | `DateTimeImmutable` | Date de création, immuable après insertion |
| `MODIFIE_LE` | `modifieLe` | `TIMESTAMP NOT NULL` | `DateTimeImmutable` | Date de la dernière écriture réussie |

Les tailles expriment des limites de caractères, pas des limites d'octets UTF-8.
L'issue #2 doit choisir les unités de longueur et le CCSID Db2 correspondants,
et vérifier leur compatibilité avec ODBC et RPG. Aucune troncature silencieuse
n'est autorisée. Les identifiants utilisent des entiers PHP sur une plateforme
64 bits, dans la plage positive du `BIGINT` signé ; aucun passage par un flottant.

## Normalisation et validation

- Accepter des chaînes pour les sept champs éditables. Refuser les tableaux et
  autres structures reçus à leur place. Seuls ces champs alimentent l'écriture.
- Retirer les espaces aux extrémités avant validation. Conserver accents,
  apostrophes, casse et espaces internes ; vérifier les longueurs après trim.
- Un champ facultatif absent, vide ou composé uniquement d'espaces devient
  `NULL` dans Db2 et `null` dans le modèle PHP. Il ne devient ni `0` ni le texte
  `"NULL"`. Un nom absent ou vide produit une erreur.
- Contrôler le format de l'email lorsqu'il n'est pas nul, sans imposer son
  unicité : plusieurs clients peuvent partager une adresse. Cela ne prouve pas
  que la boîte existe. Aucun contrôle réseau n'est requis.
- Téléphone et code postal restent du texte. Ne pas imposer un format français
  ni convertir en nombre. Par exemple, `01 23 45 67 89`, `+33 1 23 45 67 89`,
  `01230` et `SW1A 1AA` conservent leur représentation.
- Stocker du texte simple sans pré-encodage HTML. Une apostrophe ou un `&` ne
  doit pas être transformé en entité avant stockage. Twig échappe les valeurs
  à l'affichage ; ne pas utiliser `raw` sur les données clients.
- PHP valide les entrées pour fournir les erreurs du formulaire. Les programmes
  RPG contrôlent aussi les règles métier avant écriture, et la base garantit les
  contraintes de stockage. L'interface ne constitue pas une validation suffisante.

Une création initialise `creeLe` et `modifieLe` au même instant côté IBM i ; une
modification réussie conserve `creeLe` et actualise `modifieLe`. Une opération
refusée ne change aucune date. Les dates et l'identifiant ne sont jamais acceptés
comme champs éditables du formulaire. Le timestamp Db2 étant sans fuseau, les
échanges et le stockage suivent la convention UTC ; la connexion et les programmes
devront l'appliquer explicitement lors de l'intégration. L'affichage utilise
`Europe/Paris`, au format `jj/mm/aaaa, HH:mm`, comme la maquette.

## Identifiant et référence affichée

`id` reste l'identifiant technique : le client `128` utilise `/clients/128` et
`/clients/128/edit`. L'entrée de route doit représenter un entier strictement
positif dans la plage supportée ; un client inexistant aboutit à une 404.

La référence est calculée uniquement pour la présentation : compléter à gauche
avec des zéros jusqu'à **six chiffres au minimum**, sans préfixe `CLT-`.
Ainsi `47` donne `000047` et `128` donne `000128`. Un identifiant supérieur à
`999999` reste affiché intégralement, sans troncature ni collision. La référence
n'est ni une colonne supplémentaire ni une valeur utilisée dans les routes.

## Contrat des templates

Les contrôleurs fournissent explicitement le contexte à Twig avec
`strict_variables` et l'échappement HTML activés. Tous les clients, fictifs ou
réels, respectent le modèle ci-dessus. Les formulaires utilisent un contexte
séparé pour conserver exactement les saisies après un refus.

### Variables communes

| Variable | Type et valeur initiale | Usage |
|---|---|---|
| `messagesFlash` | Liste de `{type: string, message: string}`, `[]` si vide | Types autorisés : `success`, `error`, `info` ; texte échappé, affiché une seule fois |
| `modeDemo` | `bool`, explicitement fourni | Signaler les données fictives ; aucune écriture métier ni faux succès en démonstration |

Ces deux variables sont disponibles dans `base.html.twig` et ses pages filles.
Le layout inclut `partials/_flash.html.twig` avec `messagesFlash`. Aucun utilisateur
connecté ni lien de déconnexion fonctionnel n'est inventé pour remplir la maquette.

### Contexte par page

| Template | Variables propres obligatoires |
|---|---|
| `clients/index.html.twig` | `clients: list<Client>` ; `recherche: string`, `""` sans filtre |
| `clients/show.html.twig` | `client: Client`, trouvé avant le rendu |
| `clients/new.html.twig` | `client: null` ; `valeurs: map<string, string>` ; `erreurs: map<string, list<string>>` ; `actionFormulaire: string` ; `csrfToken: ?string` ; `ecritureActive: bool` |
| `clients/edit.html.twig` | Même contexte que l'ajout, mais `client: Client` |
| `clients/_form.html.twig` | Contexte du formulaire transmis explicitement par `new` ou `edit` |
| `errors/404.html.twig` | Variables communes uniquement ; réponse HTTP 404 |
| `errors/500.html.twig` | Variables communes uniquement ; réponse HTTP 500, aucun détail technique |

Les templates `show`, `404` et `500` sont des cibles à créer dans #3/#15.
Le compteur de la liste vient du nombre d'éléments de `clients`, jamais d'une
constante. Une liste vide utilise `[]` ; `recherche` conserve le filtre normalisé
utilisé pour obtenir les résultats. La recherche V1 porte sur le nom, par
sous-chaîne sans distinction de casse ; le jeu fictif de la maquette doit
retourner Martin, Marchand, Marie et Martinez pour `Mar`. La recherche Db2
reprendra ce comportement dans #7, avec une comparaison adaptée à son encodage.

Les clés de `valeurs` sont toujours présentes : `nom`, `prenom`, `email`,
`telephone`, `adresse`, `codePostal`, `ville`. À l'ajout, elles valent toutes
`""`. À l'édition initiale, elles reprennent le client, avec `null` converti
en `""` uniquement pour la saisie. Après un refus, elles conservent les chaînes
soumises sans pré-encodage HTML ; la normalisation métier se fait séparément.

`erreurs` vaut `{}` au repos. Chaque clé de champ contient une liste de messages
compréhensibles ; la clé réservée `_global` porte les erreurs non liées à un
champ. Le résumé et les messages locaux proviennent de ce même dictionnaire.
Exemples : `nom` → « Renseignez le nom du client. » ; `email` →
« L'adresse e-mail n'est pas valide. ». Les problèmes de structure d'entrée
produisent une erreur sans réinjecter un tableau dans un champ texte.

`actionFormulaire` est une URL interne construite côté serveur : `/clients`
pour la création et `/clients/{id}/edit` pour la modification, avec méthode POST.
Le jeton CSRF, lorsqu'il est nécessaire, vient du gestionnaire Symfony ; son nom
de champ est `_token` et il ne fait pas partie des données client.

En démonstration, `ecritureActive` vaut `false` et `csrfToken` peut être `null` :
le serveur interdit toute écriture, même si un POST est fabriqué manuellement.
Un scénario visuel d'erreur peut renseigner `valeurs` et `erreurs`, sans annoncer
de persistance. En intégration, `ecritureActive` ne vaut `true` que si le parcours
est raccordé et autorisé ; un jeton valide et toutes les validations restent
nécessaires. Un flash de succès et une redirection 303 n'arrivent qu'après une
écriture RPG confirmée. Les secrets et erreurs techniques restent hors du contexte.

## Correspondance avec la maquette

| Écran | Données utilisées |
|---|---|
| Liste desktop | Référence calculée, nom, prénom, email, téléphone, ville |
| Liste mobile | Nom, prénom, ville et référence calculée |
| Fiche | Identité, sept champs métier, dates de création et de modification |
| Ajout/modification | Sept champs éditables ; seul le nom est obligatoire |

Une valeur facultative absente s'affiche par `—` dans la liste et la fiche ; dans
un champ de formulaire elle s'affiche vide. Le prénom absent ne doit pas laisser
un espace superflu dans le nom complet. Aucun champ de saisie n'est ajouté pour
la référence ou les dates. Les placeholders `[MAISON]` et `[UTILISATEUR]` ne
créent pas de nouvelles données métier.

## Cas d'acceptation pour les issues d'implémentation

- Nom `"   "` : refus ; nom `"  D'Angelo & Fils  "` : texte métier
  `"D'Angelo & Fils"`, rendu échappé sans double encodage.
- Nom de 100 caractères : accepté ; 101 : refus sans troncature. Appliquer
  aussi chaque limite du dictionnaire avec des caractères accentués.
- Prénom absent et email composé d'espaces : `null` dans le modèle et la base,
  `""` dans les champs de saisie.
- Email renseigné mal formé : erreur sur `email`, autres saisies conservées.
- Téléphone commençant par `0` ou `+`, code postal `01230` : texte inchangé.
- Références `000047`, `000128` et `1000000` : calculées respectivement à partir
  de `47`, `128` et `1000000`, sans changer les identifiants des routes.
- Création réussie : dates identiques ; modification réussie : création
  conservée ; refus d'écriture : aucune date modifiée.
- Liste et recherche sans résultat : `clients = []`, compteur zéro et état vide.
- Formulaire en démonstration : aucun faux succès et aucune écriture.

Ces cas sont des critères à vérifier dans les futures implémentations, pas des
tests déjà exécutés. L'issue #2 réalise la table, #3 le socle, #15 l'interface,
puis les issues Db2/RPG prouvent la lecture et la persistance réelles.
