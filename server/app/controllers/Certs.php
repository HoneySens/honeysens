<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\services\CertsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Certs extends RESTResource {

    static function registerRoutes($api) {
        $api->get('/{id:\d+}', [Certs::class, 'get']);
    }

    public function get(Request $request, Response $response, CertsService $service, $id) {
        $this->assureAllowed('get');
        $criteria = array(
            'userID' => $this->getSessionUserID(),
            'id' => $id
        );
        try {
            $result = $service->get($criteria);
        } catch(\Exception $e) {
            throw new NotFoundException();
        }
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
