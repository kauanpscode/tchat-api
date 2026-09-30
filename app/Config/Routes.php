<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', function ($routes) {
    $routes->post('register', 'Api\AuthController::register');
    $routes->post('login', 'Api\AuthController::login');
    
    // Webhook pode não precisar de autenticação dependendo do provedor (Expo não manda Auth)
    $routes->post('push/webhook', 'Api\PushController::webhook');

    $routes->group('', ['filter' => 'authFilter'], function ($routes) {
        $routes->get('me', 'Api\AuthController::me');
        $routes->post('me/avatar', 'Api\ConversaController::updateAvatar'); // Devemos mover pro Auth ou User no futuro.

        // Conversas
        $routes->get('conversas', 'Api\ConversaController::index');
        $routes->post('conversas/privada', 'Api\ConversaController::criarPrivada');

        // Mensagens
        $routes->get('conversas/(:num)/mensagens', 'Api\MensagemController::index/$1');
        $routes->post('mensagens', 'Api\MensagemController::enviar');

        // Push Notifications
        $routes->post('push/register', 'Api\PushController::register');
    });
});