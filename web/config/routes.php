<?php

declare(strict_types=1);

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routes->add('clients_index', new Route(
    '/clients',
    ['_action' => 'index'],
    methods: ['GET']
));

$routes->add('clients_new', new Route(
    '/clients/new',
    ['_action' => 'new'],
    methods: ['GET']
));

$routes->add('clients_edit', new Route(
    '/clients/{id}/edit',
    ['_action' => 'edit'],
    ['id' => '[1-9][0-9]{0,18}'],
    methods: ['GET']
));

$routes->add('clients_show', new Route(
    '/clients/{id}',
    ['_action' => 'show'],
    ['id' => '[1-9][0-9]{0,18}'],
    methods: ['GET']
));

return $routes;