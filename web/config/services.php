<?php

declare(strict_types=1);

use App\Services\Authentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use App\Services\ClientInputValidator;
use App\Infrastructure\Db2Connection;

/**
 * Fabrique les services partagés d’une requête HTTP.
 *
 * Reçoit la configuration déjà validée et la requête à laquelle attacher la
 * session. Le tableau retourné sert de registre simple dans public/index.php.
 * L’ordre session → CSRF/authentification → Twig est nécessaire.
 * @param array<string, mixed> $config Configuration issue de bootstrap.php.
 * @return array<string, mixed> Services nommés ; db2 reste une connexion différée.
 */
return static function (
    array $config,
    Request $request
    
): array {

    // Le service est disponible même sans IBM i ; seul getConnection() ouvre le réseau.
    $db2 = new Db2Connection(
        $config['database'] ?? ['enabled' => false]
    );
    // 1. Créer la session avant les services qui l'utilisent.
    $session = new Session(new NativeSessionStorage([
        'cookie_httponly' => true,
        // Secure suit HTTPS ; HttpOnly bloque l’accès JS au cookie, SameSite limite
        // son envoi entre sites. Ces options ne remplacent pas les jetons CSRF.
        'cookie_secure' => $request->isSecure(),
        'cookie_samesite' => 'lax',
        'cookie_lifetime' => 0,
        'use_strict_mode' => true,
        'use_only_cookies' => true,
    ]));

    $request->setSession($session);

    // 2. Relier le gestionnaire CSRF à la session courante.
    // SessionTokenStorage retrouve la session via la requête courante dans cette pile.
    $requestStack = new RequestStack();
    $requestStack->push($request);

    $csrf = new CsrfTokenManager(
        null,
        new SessionTokenStorage($requestStack)
    );

    // 3. Charger le compte web local.
    $authPath = __DIR__ . '/auth.local.php';

    if (!is_file($authPath)) {
        throw new RuntimeException(
            'Configuration d’authentification absente.'
        );
    }

    // Compte web privé contenant un hash de mot de passe, distinct des identifiants Db2.
    $account = require $authPath;

    if (!is_array($account)) {
        throw new RuntimeException(
            'Configuration d’authentification invalide.'
        );
    }

    $authentication = new Authentication(
        $session,
        $account
    );

    // 4. Préparer les routes.
    $routes = require __DIR__ . '/routes.php';

    $context = new RequestContext();
    // Réutilise le contexte HTTP pour générer les URL des liens Twig.
    $context->fromRequest($request);

    $urlGenerator = new UrlGenerator($routes, $context);

    // 5. Préparer Twig.
    $twig = new Environment(
        new FilesystemLoader(
            dirname(__DIR__) . '/templates'
        ),
        [
            'debug' => $config['debug'],
            // Détecter les variables oubliées ; échapper les valeurs au rendu HTML.
            'strict_variables' => true,
            'autoescape' => 'html',
            'cache' => false,
        ]
    );

    $twig->addGlobal(
        'utilisateur',
        $authentication->username()
    );

// Fonctions Twig enregistrées une seule fois : URL nommées et jetons liés à la session.
// path(nom, parametres) retourne une URL generee depuis la collection de routes.
$twig->addFunction(new TwigFunction(
    'path',
    static fn (
        string $name,
        array $parameters = []
    ): string => $urlGenerator->generate(
        $name,
        $parameters
    )
));

// csrf_token(id) retourne le jeton de la session courante pour une action precise.
$twig->addFunction(new TwigFunction(
    'csrf_token',
    static fn (string $id): string =>
        $csrf->getToken($id)->getValue()
));


    // Le moteur Symfony exécute les contraintes définies dans le validateur métier.
    $validator = Validation::createValidator();

    $clientInputValidator = new ClientInputValidator(
        $validator
    );

    // 6. Retourner les services.
    return [
        'session' => $session,
        'csrf' => $csrf,
        'authentication' => $authentication,
        'twig' => $twig,
        'routes' => $routes,
        'context' => $context,
        'validator' => $validator,
        'client_input_validator' => $clientInputValidator,
        'db2' => $db2,
        
    ];
};
