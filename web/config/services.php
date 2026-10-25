<?php

declare(strict_types=1);

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

return static function (
    array $config,
    Request $request
): array {
    $session = new Session(new NativeSessionStorage([
        'cookie_httponly' => true,
        'cookie_secure' => $request->isSecure(),
        'cookie_samesite' => 'lax',
        'use_strict_mode' => true,
    ]));

    $request->setSession($session);

    $requestStack = new RequestStack();
    $requestStack->push($request);

    $csrf = new CsrfTokenManager(
        null,
        new SessionTokenStorage($requestStack)
    );

    $routes = require __DIR__ . '/routes.php';

    $context = new RequestContext();
    $context->fromRequest($request);

    $urlGenerator = new UrlGenerator($routes, $context);

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

    return [
        'session' => $session,
        'csrf' => $csrf,
        'twig' => $twig,
        'routes' => $routes,
        'context' => $context,
        'validator' => Validation::createValidator(),
    ];
};