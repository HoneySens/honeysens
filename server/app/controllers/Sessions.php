<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\SessionsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Sessions extends RESTResource {

    static function registerRoutes($api) {
        $api->post('', [Sessions::class, 'login']);
        $api->delete('', [Sessions::class, 'logout']);
    }

    public function login(Request $request, Response $response, SessionsService $service): Response {
        $data = $request->getParsedBody();
        V::arrayType()
            ->key('username', V::stringType())
            ->key('password', V::stringType())
            ->check($data);
        $userState = $service->create($data['username'], $data['password']);
        $response->getBody()->write(json_encode($userState));
        return $response;
    }

    public function logout(Response $response, SessionsService $service): Response {
        $user = $service->delete($this->getSessionUser());
        $response->getBody()->write(json_encode($user->getState()));
        return $response;
    }
}
