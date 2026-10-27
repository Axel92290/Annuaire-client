<?php

declare(strict_types=1);

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

// Affichage de la liste des clients.
$routes->add('clients_index', new Route(
    '/clients',
    ['_action' => 'index'],
    methods: ['GET']
));

// Création d’un nouveau client.
$routes->add('clients_new', new Route(
    '/clients/new',
    ['_action' => 'new'],
    methods: ['GET']
));

// Validation d’un aperçu uniquement, sans sauvegarde.
$routes->add('clients_preview_new', new Route(
    '/clients/preview',
    ['_action' => 'preview'],
    methods: ['POST']
));

// Prévisualisation d’un client existant, sans sauvegarde.
$routes->add('clients_preview_edit', new Route(
    '/clients/{id}/preview',
    ['_action' => 'preview'],
    ['id' => '[1-9][0-9]{0,18}'],
    methods: ['POST']
));

// Edition d’un client existant.
$routes->add('clients_edit', new Route(
    '/clients/{id}/edit',
    ['_action' => 'edit'],
    ['id' => '[1-9][0-9]{0,18}'],
    methods: ['GET']
));

// Affichage d’un client existant.
$routes->add('clients_show', new Route(
    '/clients/{id}',
    ['_action' => 'show'],
    ['id' => '[1-9][0-9]{0,18}'],
    methods: ['GET']
));

$routes->add('auth_login', new Route(
    '/login',
    ['_action' => 'login'],
    methods: ['GET', 'POST']
));

$routes->add('auth_logout', new Route(
    '/logout',
    ['_action' => 'logout'],
    methods: ['POST']
));

/*$routes->add('diagnostic_db2', new Route(
    '/diagnostics/db2',
    ['_action' => 'db2_check'],
    methods: ['GET']
)); */

return $routes;