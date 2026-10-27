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

return static function (
    array $config,
    Request $request
    
): array {

    $db2 = new Db2Connection(
        $config['database'] ?? ['enabled' => false]
    );
    // 1. Créer la session avant les services qui l'utilisent.
    $session = new Session(new NativeSessionStorage([
        'cookie_httponly' => true,
        'cookie_secure' => $request->isSecure(),
        'cookie_samesite' => 'lax',
        'cookie_lifetime' => 0,
        'use_strict_mode' => true,
        'use_only_cookies' => true,
    ]));

    $request->setSession($session);

    // 2. Relier le gestionnaire CSRF à la session courante.
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
    $context->fromRequest($request);

    $urlGenerator = new UrlGenerator($routes, $context);

    // 5. Préparer Twig.
    $twig = new Environment(
        new FilesystemLoader(
            dirname(__DIR__) . '/templates'
        ),
        [
            'debug' => $config['debug'],
            'strict_variables' => true,
            'autoescape' => 'html',
            'cache' => false,
        ]
    );

    $twig->addGlobal(
        'utilisateur',
        $authentication->username()
    );

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

$twig->addFunction(new TwigFunction(
    'csrf_token',
    static fn (string $id): string =>
        $csrf->getToken($id)->getValue()
));


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
