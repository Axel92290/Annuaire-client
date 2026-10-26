<?php

declare(strict_types=1);

use App\Controllers\ClientController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$controller = null;
$response = null;

try {
    $config = require dirname(__DIR__)
        . '/config/bootstrap.php';

    $request = Request::createFromGlobals();

    $services = (
        require dirname(__DIR__) . '/config/services.php'
    )($config, $request);

$controller = new ClientController(
    $services['twig'],
    $services['session'],
    $config['mode_demo'],
    $services['csrf'],
    $services['validator'],
    $config['demo_scenario']
);

    $matcher = new UrlMatcher(
        $services['routes'],
        $services['context']
    );

    try {
        $parameters = $matcher->match(
            $request->getPathInfo()
        );

$response = match ($parameters['_action']) {
    'index' => $controller->index($request),
    'new' => $controller->new(),
    'show' => $controller->show($parameters['id']),
    'edit' => $controller->edit($parameters['id']),
    'preview' => $controller->preview(
        $request,
        $parameters['id'] ?? null
    ),
    default => throw new ResourceNotFoundException(),
};
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

$response->send();