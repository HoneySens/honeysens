<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\UsersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Users extends RESTResource {

    const ERROR_DUPLICATE = 1;
    const ERROR_REQUIRE_PASSWORD_CHANGE = 2;

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Users::class, 'get']);
        $api->post('', [Users::class, 'post']);
        $api->put('/{id:\d+}', [Users::class, 'put']);
        $api->put('/session', [Users::class, 'updateSelf']);
        $api->delete('/{id:\d+}', [Users::class, 'delete']);
    }

    public function get(Response $response, UsersService $service, $id = null) {
        $this->assureAllowed('get');
        $criteria = array(
            'userID' => $this->getSessionUserID(),
            'id' => $id);
        $result = $service->get($criteria);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function post(Request $request, Response $response, UsersService $service) {
        $this->assureAllowed('create');
        $user = $service->create($request->getParsedBody());
        $response->getBody()->write(json_encode($user->getState()));
        return $response;
    }

    public function put(Request $request, Response $response, UsersService $service, $id) {
        $this->assureAllowed('update');
        $user = $service->update($id, $request->getParsedBody());
        $response->getBody()->write(json_encode($user->getState()));
        return $response;
    }

    public function updateSelf(Request $request, Response $response, UsersService $service) {
        $this->assureAllowed('updateSelf');
        $user = $service->updateSelf($request->getParsedBody());
        $response->getBody()->write(json_encode($user->getState()));
        return $response;
    }

    public function delete(Response $response, UsersService $service, $id) {
        $this->assureAllowed('delete');
        $service->delete($id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }
}
