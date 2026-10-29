<?php

declare(strict_types=1);

use App\Controllers\ClientController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use App\Controllers\AuthController;
use Symfony\Component\HttpFoundation\RedirectResponse;

// Point d’entrée HTTP : configuration → services → route → autorisation → action → réponse.
// Ne pas afficher les traces au navigateur ; retirer les arguments des traces
// pour éviter d’y inclure les paramètres de connexion ou les mots de passe.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('zend.exception_ignore_args', '1');

// Ces valeurs permettent un repli en texte brut si le démarrage échoue avant Twig.
$controller = null;
$response = null;

// Appliquer aussi les en-têtes de sécurité aux réponses de démarrage en erreur.
$securityHeaders = require dirname(__DIR__)
    . '/config/security-headers.php';

foreach ($securityHeaders as $name => $value) {
    header($name . ': ' . $value);
}

try {
    $config = require dirname(__DIR__)
        . '/config/bootstrap.php';

    // Encapsule URL, méthode, paramètres et cookies dans une requête Symfony.
    $request = Request::createFromGlobals();

    // La fabrique construit des objets pour cette requête, sans ouvrir Db2.
    $services = (
        require dirname(__DIR__) . '/config/services.php'
    )($config, $request);

    $controller = new ClientController(
        $services['twig'],
        $services['session'],
        $config['mode_demo'],
        $services['csrf'],
        $services['client_input_validator'],
        $config['demo_scenario']
    );

    $authController = new AuthController(
        $services['twig'],
        $services['session'],
        $config['mode_demo'],
        $services['authentication'],
        $services['csrf']
    );

    // Le routeur vérifie à la fois le chemin, les contraintes d’ID et la méthode HTTP.
    $matcher = new UrlMatcher(
        $services['routes'],
        $services['context']
    );

    try {
        $parameters = $matcher->match(
            $request->getPathInfo()
        );

        $action = $parameters['_action'];

        // Chaque nouvelle action privée doit rejoindre cette liste avant sa distribution.
        $protectedActions = [
            'index',
            'new',
            'show',
            'edit',
            'preview',
            'logout',
            //'db2_check',
        ];

        // Un GET anonyme rejoint la connexion ; un POST est refusé sans exécuter l’action.
        if (
            in_array($action, $protectedActions, true)
            && !$services['authentication']->isAuthenticated()
        ) {
            $response = $request->isMethodSafe()
                ? new RedirectResponse('/login', 302)
                : new Response('Accès refusé.', 403);
        } else {
            $response = match ($action) {
                'login' => $authController->login($request),
                'logout' => $authController->logout($request),
                'index' => $controller->index($request),
                'new' => $controller->new(),
                'show' => $controller->show($parameters['id']),
                'edit' => $controller->edit($parameters['id']),
                'preview' => $controller->preview(
                    $request,
                    $parameters['id'] ?? null
                ),
                // Ancien diagnostic conservé en commentaire : cette branche est désactivée.
                // Le contrôle Db2 disponible reste la commande CLI bin/check-db2.php.
                /*'db2_check' => (static function () use (
                    $services,
                    $config
                ): Response {
                    if ($config['environment'] !== 'dev') {
                        return new Response('Page introuvable.', 404);
                    }

                    if (!($config['database']['enabled'] ?? false)) {
                        return new \Symfony\Component\HttpFoundation\JsonResponse(
                            ['status' => 'disabled'],
                            503
                        );
                    }

                    $pdo = $services['db2']->getConnection();

                    // Le schéma est interpolé comme identifiant SQL : bootstrap le limite
                    // aux caractères autorisés, puisqu’un paramètre SQL ne le remplacerait pas.
                    $schema = $config['database']['schema'];

                    $statement = $pdo->query(
                        'SELECT COUNT(*) FROM '
                            . $schema
                            . '.CLIENTS'
                    );

                    return new \Symfony\Component\HttpFoundation\JsonResponse([
                        'status' => 'ok',
                        'clients' => (int) $statement->fetchColumn(),
                    ]);
                })(),*/
                default => throw new ResourceNotFoundException(),
            };
        }
    // URL ou client absent : même page 404. Une mauvaise méthode conserve son Allow.
    } catch (ResourceNotFoundException) {
        $response = $controller->error(404);
    } catch (MethodNotAllowedException $exception) {
        $response = new Response(
            'Méthode non autorisée.',
            405,
            [
                'Allow' => implode(
                    ', ',
                    $exception->getAllowedMethods()
                ),
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]
        );
    }
// Dernier filet de sécurité : détails dans le journal, message générique dans la réponse.
} catch (Throwable $exception) {
    error_log((string) $exception);

    try {
        $response = $controller !== null
            ? $controller->error(500)
            : null;
    } catch (Throwable $renderException) {
        error_log((string) $renderException);
        $response = null;
    }

    if ($response === null) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Une erreur est survenue.';
        exit;
    }
}

// Empêcher la mise en cache des pages authentifiées et de leurs données personnelles.
$response->headers->set(
    'Cache-Control',
    'no-store, private'
);

// L’envoi effectif de la réponse est centralisé ici, après tous les contrôles.
$response->send();
