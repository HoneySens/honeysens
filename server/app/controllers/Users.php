<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\constants\AuthDomain;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\Utils;
use HoneySens\app\services\UsersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Users extends RESTResource {

    const ERROR_DUPLICATE = 1;
    const ERROR_REQUIRE_PASSWORD_CHANGE = 2;

    static function registerRoutes($api): void {
        $api->get('[/{id:\d+}]', [Users::class, 'getUsers']);
        $api->post('', [Users::class, 'createUser']);
        $api->put('/{id:\d+}', [Users::class, 'updateUser']);
        $api->put('/session', [Users::class, 'updateSelf']);
        $api->delete('/{id:\d+}', [Users::class, 'deleteUser']);
    }

    public function getUsers(Response $response, UsersService $service, ?int $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->getUsers($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function createUser(Request $request, Response $response, UsersService $service): Response {
        $this->assureAllowed('create');
        $data = $request->getParsedBody();
        $this->assertValidUser($data);
        $fullName = $data['fullName'] ?? null;
        // A password is only mandatory if the user is authenticating against the Local domain
        $password = null;
        $authDomain = AuthDomain::from($data['domain']);
        if($authDomain === AuthDomain::LOCAL) {
            V::key('password')->check($data);
            $password = $data['password'];
        }
        $user = $service->createUser(
            $data['name'],
            $authDomain,
            $data['email'],
            UserRole::from($data['role']),
            $data['notifyOnSystemState'],
            $data['requirePasswordChange'],
            $fullName,
            $password);
        $response->getBody()->write(json_encode($user->getState()));
        return $response;
    }

    public function updateUser(Request $request, Response $response, UsersService $service, int $id): Response {
        $this->assureAllowed('update');
        $data = $request->getParsedBody();
        $this->assertValidUser($data);
        $fullName = $data['fullName'] ?? null;
        $password = $data['password'] ?? null;
        $user = $service->updateUser(
            $id,
            $data['name'],
            AuthDomain::from($data['domain']),
            $data['email'],
            UserRole::from($data['role']),
            $data['notifyOnSystemState'],
            $data['requirePasswordChange'],
            $fullName,
            $password
        );
        $response->getBody()->write(json_encode($user->getState()));
        return $response;
    }

    /**
     * Enables logged-in users to change their own password.
     */
    public function updateSelf(Request $request, Response $response, UsersService $service): Response {
        $this->assureAllowed('updateSelf');
        $data = $request->getParsedBody();
        V::arrayType()->key('password', V::stringType()->length(6, 255))->check($data);
        $user = $service->updatePassword($this->getSessionUser()->getId(), $data['password']);
        $response->getBody()->write(json_encode($user->getState()));
        return $response;
    }

    public function deleteUser(Response $response, UsersService $service, int $id): Response {
        $this->assureAllowed('delete');
        $service->deleteUser($id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    private function assertValidUser(array $data): void {
        V::arrayType()
            ->key('name', V::alnum()->length(1, 255))
            ->key('domain', V::intType()->between(0, 1))
            ->key('fullName', V::stringType()->length(1, 255), false)
            ->key('email', Utils::emailValidator())
            ->key('role', V::intVal()->between(1, 3))
            ->key('notifyOnSystemState', V::boolType())
            ->key('requirePasswordChange', V::boolType())
            ->key('password', V::stringType()->length(6, 255), false)
            ->check($data);
    }
}
