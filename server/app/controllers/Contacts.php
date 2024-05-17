<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\services\ContactsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class Contacts
 * @package HoneySens\app\controllers
 *
 * Contact creation and updates are handled by the division controller,
 * because contacts always belong to a certain division.
 */
class Contacts extends RESTResource {

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Contacts::class, 'get']);
    }

    public function get(Request $request, Response $response, ContactsService $service, $id = null) {
        $this->assureAllowed('get');
        $criteria = array(
            'userID' => $this->getSessionUserID(),
            'id' => $id);
        try {
            $result = $service->get($criteria);
        } catch(\Exception $e) {
            throw new NotFoundException();
        }
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
