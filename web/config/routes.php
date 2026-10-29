<?php

declare(strict_types=1);

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

// Les noms servent à path() dans Twig ; _action est distribué par public/index.php.
// Les routes fixes précèdent les routes {id}, limitées à des entiers positifs.
// Déclarer une route ne suffit pas à la protéger : vérifier protectedActions dans index.php.
$routes = new RouteCollection();

// Affichage de la liste des clients.
$routes->add('clients_index', new Route(
    '/clients',
    ['_action' => 'index'],
    methods: ['GET']
));

// Affichage du formulaire d’ajout ; aucune création réelle par cette route GET.
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

// GET affiche le formulaire ; POST vérifie le CSRF et tente la connexion.
$routes->add('auth_login', new Route(
    '/login',
    ['_action' => 'login'],
    methods: ['GET', 'POST']
));

// Déconnexion par POST seulement, avec contrôle CSRF dans AuthController.
$routes->add('auth_logout', new Route(
    '/logout',
    ['_action' => 'logout'],
    methods: ['POST']
));

// Ancienne route de diagnostic désactivée ; ne pas la réactiver dans un déploiement.
/*$routes->add('diagnostic_db2', new Route(
    '/diagnostics/db2',
    ['_action' => 'db2_check'],
    methods: ['GET']
)); */

return $routes;