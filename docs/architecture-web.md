# Décision : Twig et BaseController pour l'annuaire

Statut : architecture retenue, à implémenter dans #3 et les issues fonctionnelles.
Le dépôt est encore en conception ; ce document n'annonce pas de code exécutable.

## Analyse ciblée du blog PHP

Fichiers examinés dans [Projet5_blog_php](https://github.com/Axel92290/Projet5_blog_php) :
composer.json, controllers/BaseController.php, ListingController.php,
CreatePostsController.php, tools/Config.php et views/listing-posts/listing.html.
Cette lecture ciblée ne constitue pas un audit exhaustif du blog.

Le blog sépare les contrôleurs et les templates. Le template de liste hérite
de base.html ; Twig et Bootstrap sont donc déjà des outils connus de l'auteur.
Le contrôleur de base mutualise aussi configuration, requête, session, Twig,
filtrage XSS et fonction CSRF.

Éléments à reprendre : héritage des templates, fragments partagés,
autoloading et helpers de rendu.

Éléments à adapter :
- Le constructeur BaseController instancie plusieurs dépendances lui-même.
  L'annuaire les construit dans config/services.php puis les injecte.
- ListingController charge Twig, rend le template et imprime le résultat.
  L'annuaire centralise cette opération dans render() et retourne une Response.
- cleanXSS() applique htmlspecialchars() avant l'enregistrement et un filtre
  anti-XSS. L'annuaire conserve des données normalisées, non pré-encodées HTML,
  puis utilise l'échappement adapté lors du rendu.
- BaseController::redirect() du blog envoie la réponse sans interrompre l'action.
  Dans CreatePostsController, checkSession() peut retourner après une redirection
  alors que createPost() continue. Un return dans la méthode auxiliaire ne
  termine pas sa méthode appelante. L'annuaire doit retourner la réponse de refus
  depuis l'action ou arrêter le dispatch au niveau du contrôle d'accès.
- Config impose config/dev.ini. L'annuaire conserve la sélection serveur
  explicite de l'environnement et la validation de la configuration.
- Le blog utilise plusieurs dépendances propres à ses besoins. L'annuaire
  conserve Symfony Routing et Security CSRF ; il ne cumule pas un second routeur
  ou une seconde bibliothèque CSRF et n'ajoute pas de bibliothèque mail sans besoin.

## Architecture retenue

La Vue est la couche de présentation. Twig est son moteur de rendu.
Une classe View personnalisée serait une abstraction supplémentaire sans besoin
actuel : Twig\Environment fournit déjà le rendu des templates.

BaseController est une classe abstraite sous App\Controllers.
Son périmètre est volontairement limité :

| Méthode protégée | Contrat |
|---|---|
| render(string template, array data, int status = 200) | Retourne une Response contenant le HTML rendu par Twig |
| redirect(string url, int status = 302) | Retourne une RedirectResponse vers une URL interne maîtrisée |

Pour un POST réussi, l'action utilise une redirection 303 et la retourne.
Le contrôleur enfant reçoit ClientService et les autres services réellement
nécessaires ; son constructeur transmet Twig au parent.
Le point d'entrée envoie exactement une réponse après le dispatch.
Les contrôles de droits et le CSRF restent explicites, avant tout appel métier.

La connexion Db2 et la passerelle RPG restent indépendantes du rendu.
Aucun nouveau BaseRepository ou modèle actif ne sera ajouté par analogie au blog :
le repository lit, le service coordonne et les programmes RPG écrivent.

## Intégration Twig

Ajouter twig/twig avec Composer, dans une version compatible avec PHP.
Construire une seule instance de FilesystemLoader et Environment au démarrage,
avec web/templates/ comme répertoire de templates.

- autoescape = html ; strict_variables = true ;
- debug activé seulement en développement ;
- cache compilé de production sous web/var/cache/twig/, non public et ignoré ;
- aucun raw ou marquage HTML sûr appliqué aux valeurs des clients ;
- aucune configuration secrète ou session complète passée globalement aux vues ;
- seules les données de présentation nécessaires sont transmises.

Enregistrer explicitement csrf_token(action) comme fonction Twig retournant une
chaîne issue du gestionnaire Symfony CSRF. Le template contient le champ caché ;
la fonction ne retourne pas du HTML et n'utilise pas is_safe.
Vérifier le jeton côté serveur avant CLTADD/CLTUPD. Une éventuelle fonction
path() doit également être enregistrée : aucun TwigBundle n'est présent.

Les templates HTML utilisent {{ valeur }} dans des contextes HTML appropriés.
Les URL doivent être maîtrisées et les contextes JavaScript/CSS ne doivent pas
recevoir directement des valeurs utilisateur par simple interpolation HTML.

## Contrôles attendus à l'implémentation

- Une action rend une Response et la page hérite de base.html.twig.
- Une redirection n'est pas suivie d'un traitement métier.
- Un visiteur non autorisé n'atteint aucun appel de création/modification.
- Les formulaires d'ajout et de modification partagent _form.html.twig.
- Un jeton absent ou invalide est refusé avant toute écriture.
- Un nom contenant des apostrophes, un & ou du HTML reste correctement affiché,
  sans double encodage ni exécution de script.
- Le cache Twig et les fichiers INI restent inaccessibles depuis le navigateur.

## Références

- [Twig : API et configuration](https://twig.symfony.com/doc/3.x/api.html)
- [Twig : héritage](https://twig.symfony.com/doc/3.x/tags/extends.html)
