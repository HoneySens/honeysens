<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\SessionsService;
use HoneySens\app\services\SystemService;
use HoneySens\app\services\UsersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Sessions extends RESTResource {

    static function registerRoutes($api) {
        $api->post('', [Sessions::class, 'login']);
        $api->delete('', [Sessions::class, 'logout']);
    }

    public function login(Request $request, Response $response, SessionsService $service, UsersService $usersService, SystemService $systemService) {
        $userState = $service->create($request->getParsedBody(), $usersService, $systemService);
        $response->getBody()->write(json_encode($userState));
        return $response;
    }

    public function logout(Response $response, SessionsService $service) {
        $user = $service->delete($this->getSessionUser());
        $response->getBody()->write(json_encode($user->getState()));
        return $response;
    }
}
