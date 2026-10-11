<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', function ($routes) {
    // ----------------------------------------------------
    // ROTAS PÚBLICAS
    // ----------------------------------------------------
    $routes->get('teste', 'Api\TestController::index');
    $routes->post('register', 'Api\AuthController::register');
    $routes->post('login', 'Api\AuthController::login');
    $routes->post('empresas/registrar', 'Api\EmpresaController::registrar');

    // Visualização e aceite de convites
    $routes->get('convites/(:segment)', 'Api\ConviteController::visualizar/$1');
    $routes->post('convites/(:segment)/aceitar', 'Api\ConviteController::aceitar/$1');

    // Webhooks externos
    $routes->post('push/webhook', 'Api\PushController::webhook');

    // ----------------------------------------------------
    // ROTAS AUTENTICADAS (Requer AuthFilter)
    // ----------------------------------------------------
    $routes->group('', ['filter' => 'authFilter'], function ($routes) {
        $routes->options('(:any)', function () {
            return service('response')->setStatusCode(204);
        });
        // Perfil e Sessão Corporativa
        $routes->get('me', 'Api\AuthController::me');
        $routes->post('me/avatar', 'Api\AuthController::updateAvatar');
        $routes->post('empresa/trocar', 'Api\AuthController::trocarEmpresa');

        // Dados da Empresa (Identidade Visual & Branding)
        $routes->get('empresa', 'Api\EmpresaController::getDados');

        // Contatos e Colaboradores da Organização
        $routes->get('empresa/colaboradores', 'Api\ColaboradorController::listar');

        // Conversas Isoladas por Empresa
        $routes->get('conversas', 'Api\ConversaController::index');
        $routes->post('conversas/privada', 'Api\ConversaController::criarPrivada');
        $routes->post('conversas/grupo', 'Api\ConversaController::criarGrupo');
        $routes->post('conversas/(:num)/ler', 'Api\ConversaController::marcarComoLida/$1');

        // Histórico e Envio de Mensagens
        $routes->get('conversas/(:num)/mensagens', 'Api\MensagemController::index/$1');
        $routes->post('mensagens', 'Api\MensagemController::enviar');

        // Presença e Status Online
        $routes->post('presenca/ping', 'Api\PresencaController::ping');
        $routes->post('presenca/offline', 'Api\PresencaController::offline');
        $routes->get('presenca/(:num)', 'Api\PresencaController::status/$1');

        // Notificações Push
        $routes->post('push/register', 'Api\PushController::register');

        // ----------------------------------------------------
        // ROTAS DE GESTÃO CORPORATIVA (Requer AdminFilter)
        // ----------------------------------------------------
        $routes->group('', ['filter' => 'adminFilter'], function ($routes) {
            // Atualização de identidade visual e dados da empresa
            $routes->put('empresa', 'Api\EmpresaController::atualizar');

            // Gestão de Convites
            $routes->post('empresa/convites', 'Api\ConviteController::criar');
            $routes->get('empresa/convites', 'Api\ConviteController::listar');
            $routes->delete('empresa/convites/(:num)', 'Api\ConviteController::cancelar/$1');

            // Gestão de Colaboradores (Status e Cargos)
            $routes->patch('empresa/colaboradores/(:num)/status', 'Api\ColaboradorController::alterarStatus/$1');
            $routes->patch('empresa/colaboradores/(:num)/cargo', 'Api\ColaboradorController::alterarCargo/$1');
        });
    });
});