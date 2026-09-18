<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', function ($routes) {
    $routes->post('register', 'Api\AuthController::register');
    $routes->post('login', 'Api\AuthController::login');

    $routes->group('', ['filter' => 'authFilter'], function ($routes) {
        $routes->get('me', 'Api\AuthController::me');
        $routes->post('me/avatar', 'Api\ConversaController::updateAvatar');
    });
});