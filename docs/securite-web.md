# Sécurité web

Ce document décrit les protections du socle PHP de l’annuaire et la recette de
l’issue #14. Les écritures Db2/RPG ne sont pas encore raccordées aux formulaires.
Une protection décrite ci-dessous doit être vérifiée par les tests correspondants
avant de considérer l’issue comme terminée.

## Périmètre et accès local

L’application fonctionne sous Wamp avec un VirtualHost dont le DocumentRoot est
`web/public/`. Les sources, dépendances, configurations et journaux restent hors
de ce répertoire public.

L’accès Apache reste limité au poste local avec `Require local`. La validation
de cette configuration de développement ne vaut pas autorisation de déploiement
public. Une exposition ultérieure nécessite une revue de la configuration cible,
des droits et de la protection contre les tentatives répétées de connexion.

## Authentification

L’accès aux routes clients exige une session authentifiée. Le compte web est
indépendant des identifiants IBM i/PUB400.

- Le compte est défini dans `web/config/auth.local.php`, ignoré par Git.
- `web/config/auth.example.php` contient uniquement un exemple sans secret.
- Le mot de passe est conservé sous forme de hash produit par `password_hash()`.
- Le service `Authentication` vérifie le mot de passe avec `password_verify()`.
- Un échec de connexion produit un message générique.
- Le mot de passe saisi n’est ni réaffiché dans le formulaire, ni conservé en session.
- Les seules routes d’authentification sont `/login` et `/logout` ; la déconnexion
  accepte uniquement POST.

Le contrôle d’accès est réalisé avant le dispatch des actions clients. Pour un
visiteur non authentifié, une consultation redirige vers `/login` et un POST
protégé reçoit une réponse 403. Aucun contrôleur métier ne doit être appelé
après ce refus.

Le compte unique du socle ne définit pas encore une gestion de rôles ou de droits
par utilisateur. Ces règles devront être précisées si le périmètre évolue.

## Sessions et cookies

La session Symfony utilise `NativeSessionStorage` avec :

| Paramètre | Valeur |
|---|---|
| `cookie_httponly` | `true` |
| `cookie_secure` | Résultat de `$request->isSecure()` |
| `cookie_samesite` | `lax` |
| `cookie_lifetime` | `0`, cookie de session |
| `use_strict_mode` | `true` |
| `use_only_cookies` | `true` |

Après une authentification réussie, les données de la session non authentifiée
sont effacées et l’identifiant est renouvelé avec suppression de l’ancien.
La déconnexion invalide la session. L’identité stockée comprend une empreinte
du hash du compte : un changement de ce hash invalide les anciennes identités.

La recette doit vérifier le changement de `PHPSESSID` après connexion et la perte
d’accès après déconnexion. La valeur du cookie ne doit pas être publiée.

## HTTPS

Le domaine local est `annuaire-client.local`. Le VirtualHost HTTPS utilise un
certificat de développement généré avec mkcert et reconnu par le poste local.
Le fonctionnement HTTPS a été confirmé par l’utilisateur.

Les certificats et clés sont stockés hors du dépôt, dans un répertoire privé.
La clé privée et la clé de l’autorité locale mkcert ne doivent jamais être versionnées.

La configuration attendue redirige le VirtualHost HTTP du projet vers HTTPS :

```apache
<VirtualHost *:80>
    ServerName annuaire-client.local
    Redirect permanent / https://annuaire-client.local/
</VirtualHost>
```

Le bloc HTTPS conserve `Require local`. Une session créée puis authentifiée via
HTTPS doit posséder les attributs `Secure`, `HttpOnly` et `SameSite=Lax`.
La redirection HTTP et ces attributs restent à consigner dans la recette.

## Protection CSRF

Le gestionnaire Symfony CSRF est relié à la session via `RequestStack` et
`SessionTokenStorage`. Les intentions sont distinctes :

| Action | Intention |
|---|---|
| Connexion | `auth_login` |
| Déconnexion | `auth_logout` |
| Création client | `client_create` |
| Modification client | `client_update_{id}` |

Les formulaires transmettent le jeton dans un champ caché `_token`.
Un jeton absent, invalide ou issu d’une autre intention doit être refusé avec
une réponse 403, avant validation métier et avant tout futur appel RPG.

La fonction Twig `csrf_token` est enregistrée une seule fois. Elle retourne
une chaîne, sans option `is_safe`. La fonction `path` reste enregistrée
séparément pour générer les URL.

Le CSRF complète le contrôle d’accès ; il ne constitue pas une authentification.

## Validation des formulaires clients

`ClientInputValidator` utilise Symfony Validator et une liste explicite :

```text
nom, prenom, email, telephone, adresse, codePostal, ville
```

Le champ technique `_token` est accepté pour la vérification CSRF. Tout autre
champ doit produire une erreur globale. Les identifiants et dates ne sont jamais
des champs éditables.

- Les valeurs métier doivent être des chaînes ; les tableaux sont refusés.
- Le nom doit être non vide après trim et respecter la limite de 100 caractères.
- Les autres limites suivent `docs/modele-donnees.md`.
- L’email est vérifié lorsqu’il est renseigné.
- Les champs facultatifs vides deviennent `null` dans les données normalisées.
- Téléphone et code postal restent du texte, avec conservation des zéros initiaux.

Le validateur sépare les chaînes saisies, les données normalisées et les erreurs.
Un refus de validation produit une réponse 422 et conserve les saisies textuelles.
Les erreurs sont reliées aux champs et présentées dans un résumé accessible.

Les POST `/clients/preview` et `/clients/{id}/preview` restent des aperçus sans
persistance. Les contrôles métier définitifs seront également appliqués dans
les futurs programmes RPG.

## Échappement et ressources

Twig conserve `autoescape: html` et `strict_variables: true`. Les données clients
sont stockées en texte simple et échappées à l’affichage ; aucun `raw` ne doit
être utilisé sur une donnée utilisateur.

Les messages JavaScript utilisent `textContent`. La recherche asynchrone extrait
les résultats du HTML produit par les templates Twig échappés. Les saisies
utilisateur ne doivent pas être concaténées dans du HTML exécutable.

Bootstrap, les polices, les styles et les scripts sont servis localement.

## En-têtes des réponses PHP

`web/config/security-headers.php` définit la politique attendue :

```text
Content-Security-Policy:
  default-src 'self';
  base-uri 'none';
  object-src 'none';
  frame-ancestors 'none';
  script-src 'self';
  style-src 'self';
  font-src 'self';
  img-src 'self' data:;
  connect-src 'self';
  form-action 'self'

X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Cache-Control: no-store, private
```

La CSP est envoyée sur une seule ligne dans l’en-tête HTTP. L’autorisation
`data:` pour les images permet les images intégrées de certains composants
Bootstrap. Les scripts et styles inline ne sont pas autorisés par cette politique.

Vérifier les en-têtes réels dans le panneau Réseau du navigateur, ainsi que
l’absence de blocage CSP des ressources légitimes. Les réponses servies directement
par Apache nécessitent leurs propres vérifications de configuration.

## Erreurs, journaux et fichiers privés

- `display_errors` reste désactivé ; les visiteurs reçoivent des messages génériques.
- Les erreurs techniques sont journalisées hors de `web/public/`.
- `zend.exception_ignore_args` est activé pour éviter d’inclure les arguments
  dans les traces d’exceptions natives.
- Aucun mot de passe, cookie de session, corps de connexion ou secret ne doit être
  ajouté volontairement aux journaux.
- Les fichiers INI locaux, `auth.local.php`, les journaux, certificats privés et
  dépendances installées ne sont pas ajoutés aux commits.

Vérifier qu’un accès HTTP à `/config/auth.local.php`, `/config/dev.ini` ou
`/vendor/autoload.php` n’expose aucun contenu. Le DocumentRoot `web/public/`
est la protection structurelle principale.

## Recette de l’issue #14

L’authentification fonctionnelle et l’accès HTTPS ont été confirmés dans les
échanges. Les autres résultats doivent être renseignés après exécution des tests ;
leur présence dans ce document ne constitue pas une preuve de réussite.

| Contrôle | Résultat |
|---|---|
| Connexion après correction de la configuration des services | Fonctionnement confirmé par l’utilisateur |
| Accès HTTPS au site | Fonctionnement confirmé par l’utilisateur |
| Mauvais identifiants : refus générique | À confirmer |
| Consultation sans authentification : redirection vers `/login` | À confirmer |
| POST sans authentification : 403 | À confirmer |
| Identifiant de session renouvelé après connexion | À confirmer |
| Déconnexion puis perte d’accès aux pages clients | À confirmer |
| Jeton CSRF absent ou incorrect : 403 | À confirmer |
| Jeton de création utilisé pour modifier : 403 | À confirmer |
| Jeton d’un autre client : 403 | À confirmer |
| Champ supplémentaire interdit : 422 | À confirmer |
| Valeur structurée à la place d’une chaîne : 422 | À confirmer |
| Longueur excessive contrôlée côté serveur : 422 | À confirmer |
| Charges XSS conservées comme texte, sans création de balise exécutable | À confirmer |
| Saisies conservées après un refus | À confirmer |
| Aperçu valide sans sauvegarde ni faux succès | À confirmer |
| Redirection HTTP vers HTTPS | À confirmer |
| Cookies Secure, HttpOnly et SameSite sous HTTPS | À confirmer |
| En-têtes présents et ressources légitimes compatibles avec la CSP | À confirmer |
| Configurations et journaux inaccessibles par HTTP | À confirmer |
| `composer validate --strict` | À confirmer |
| `composer check-platform-reqs` | À confirmer |
| `composer audit` et traitement des éventuelles alertes | À confirmer |

Exécuter les commandes Composer depuis `web/`. Un échec réseau de l’audit ne
constitue pas un audit réussi. Documenter les éventuelles corrections et versions
retenues sans mettre à jour indistinctement toutes les dépendances.

Avant la PR, exécuter `git diff --check`, inspecter `git status --short` et
vérifier les fichiers ajoutés. Clôturer #14 après validation des critères.
Rejouer les refus d’accès et de CSRF lors du branchement des écritures RPG dans
#10 et #12 pour confirmer qu’aucun appel métier ne continue après un refus.
