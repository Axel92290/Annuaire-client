# Démarrer et vérifier l'interface de démonstration

L'interface de #15 utilise exclusivement `web/fixtures/clients.php`. Elle n'ouvre
aucune connexion Db2 et ne nécessite ni pilote IBM, ni identifiants PUB400.
Les POST `/clients/preview` et `/clients/{id}/preview` valident une saisie sans
persistance. Les futures écritures métier ne sont pas activées.

## Installation

Prérequis : PHP 8.2 à 8.x, Composer 2, extensions requises par le lockfile.
Exécuter depuis `web/` :

```sh
composer install
composer check-platform-reqs
```

Copier `config/dev.ini.example` vers `config/dev.ini` seulement si le fichier
local n'existe pas déjà. Sous PowerShell :

```powershell
if (!(Test-Path config/dev.ini)) {
    Copy-Item config/dev.ini.example config/dev.ini
}
```

`dev.ini` reste ignoré par Git et doit contenir :

```ini
[app]
debug = true
mode_demo = true
timezone = "Europe/Paris"

[demo]
scenario = "normal"
```

Le mode de démonstration est interdit en environnement `prod`. Le mode réel,
encore non raccordé, retourne 503 au lieu de charger silencieusement les fixtures.

## Serveur

Avec Wamp, utiliser un VirtualHost dont le DocumentRoot est le chemin absolu de
`web/public/`, par exemple `D:/dev/Annuaire-client/web/public`. Dans son bloc
`Directory`, configurer `DirectoryIndex index.php`, `AllowOverride All`,
`Options -Indexes -Includes -MultiViews +FollowSymLinks` et `Require local`.
Activer `rewrite_module`. Le fichier `public/.htaccess` transmet les routes à
`index.php`. Ouvrir ensuite `/clients` sur le domaine local du VirtualHost.

Pour un essai local avec le serveur PHP, depuis `web/` :

```sh
php -S 127.0.0.1:8000 -t public
```

Ouvrir `/clients` sur ce serveur. PHP doit disposer d'un répertoire de sessions
existant et accessible en écriture. Si nécessaire, préciser un répertoire local
avec `php -d session.save_path=<répertoire-existant> -S 127.0.0.1:8000 -t public`.
Le serveur intégré est réservé au développement.

## Assets et contrat des vues

Bootstrap **5.3.8**, son CSS et sa licence MIT sont dans
`web/public/assets/vendor/bootstrap/`. Les polices Libre Caslon Display et Hanken
Grotesk sont dans `assets/fonts/`, avec leurs licences OFL. Les styles du projet
sont chargés après Bootstrap ; `font-display: swap` et les polices de repli
permettent de lire les pages pendant le chargement. Aucune requête externe n'est
nécessaire à l'affichage.

Les variables de base (`modeDemo`, `messagesFlash`), les champs du modèle et le
contexte des formulaires sont définis dans le [contrat de #1](modele-donnees.md).
L'aperçu ajoute `actionApercu` et `apercuValide`. La liste reçoit
`apercuConfirmation`, vrai uniquement pour le scénario local correspondant.
Les références sont calculées sur six chiffres minimum ; les routes conservent
l'identifiant technique. Les fixtures et `Client` partagent les mêmes types.

Les cinq fiches sont entièrement fictives : Martin (128), Marchand (94), Marie
(211), Martinez (47) et D'Angelo & Fils (305). La dernière comporte des champs
facultatifs absents et un code postal commençant par zéro.

## Scénarios locaux

Modifier uniquement `[demo] scenario` dans `config/dev.ini`, puis recharger.
Les paramètres de requête ne sélectionnent aucun scénario ni fichier PHP.

| Scénario | Vérification |
|---|---|
| `normal` | Cinq fiches ; `Mar` donne quatre résultats ; `Martin` en donne deux |
| `empty` | Annuaire vide ; les fiches individuelles répondent 404 |
| `form-errors` | Ajout et modification avec nom vide, email incorrect et autres valeurs conservées |
| `confirmation` | Exemple de confirmation explicitement présenté comme fictif |
| `long-city` | Ville longue sur le client 128, sans débordement |
| `unavailable` | Page d'indisponibilité avec statut HTTP 503 |
| `error` | Page d'erreur avec statut HTTP 500 |

Rétablir `normal` après la recette. Pour un résultat vide sans changer de
configuration, chercher `introuvable`.

## Contrôles fonctionnels et accessibilité

- Visiter `/clients`, `/clients/128`, `/clients/new` et `/clients/128/edit`.
- Vérifier les valeurs absentes sur `/clients/305` et la 404 sur un client absent.
- Rechercher `Mar`, effacer la recherche et saisir rapidement plusieurs noms.
  La dernière saisie doit gagner, même si une réponse antérieure arrive en retard.
- Simuler une erreur réseau : un message visible et annoncé indique que les
  résultats précédents restent affichés. Réessayer avec le bouton Rechercher.
- Soumettre un nom vide et un email incorrect : réponse 422, saisies conservées,
  résumé des erreurs et messages reliés aux champs. Le focus rejoint le résumé
  avec JavaScript ; ses liens restent utilisables sans JavaScript.
- Vérifier un formulaire valide : aucun client n'est enregistré et la fiche
  initiale reste inchangée. Un jeton absent ou invalide reçoit une réponse 403.
- Désactiver JavaScript et refaire recherche, consultation et validation.
- Comparer au PDF à 390, 768 et 1280 px ; vérifier aussi la ville longue,
  l'absence de débordement, les labels et la navigation au clavier.
- Vérifier les cibles de 48 px, le focus visible de 2 px, le lien d'évitement
  et les contrastes. Ne pas afficher un faux utilisateur connecté.
- Vérifier les statuts 404/405/500/503 dans le panneau Réseau et l'inaccessibilité
  HTTP de `/config/dev.ini` et `/vendor/autoload.php`.

## Régression automatisée

Depuis `web/` :

```sh
php tests/smoke.php
```

Ce contrôle utilise les dépendances installées et une session en mémoire. Il
vérifie les vues, la recherche, l'échappement, les formulaires, le CSRF, les
scénarios et l'absence de modification des fixtures. Il ne nécessite ni serveur
HTTP ni configuration locale et retourne un code non nul si une vérification échoue.

Lors de la revue de #19, les contrôles PHP ont été exécutés sous PHP 8.4, et les
parcours navigateur sous Chromium. Les contrôles axe-core 4.10.3 et de débordement
ont couvert les trois largeurs de référence. Ils complètent la comparaison au PDF
et la vérification clavier ; ils ne constituent pas une certification ni un essai
avec tous les lecteurs d'écran. Wamp/PHP 8.2 doit également être vérifié localement.
