<?php
namespace HoneySens\app\middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Restricts access to API endpoints in case the setup hasn't been completed yet,
 * so that only URIs relevant for the setup process are available.
 * Requests to all other endpoints are redirected to the landing page.
 */
class SetupCheckMiddleware implements MiddlewareInterface {

    private $app;
    private $em;

    public function __construct($app, $em) {
        $this->app = $app;
        $this->em = $em;
    }

    public function process(Request $request, RequestHandler $handler): Response {
        $alwaysAccessibleEndpoints = ['/', '/api/state', '/api/system/identify', '/api/system/install'];
        $setupRequired = !$this->em->getConnection()->getSchemaManager()->tablesExist(array('users'));
        if(!in_array($request->getURI()->getPath(), $alwaysAccessibleEndpoints) && $setupRequired)
            return $this->app->getResponseFactory()->createResponse()->withStatus(303)->withHeader('Location', '/');
        return $handler->handle($request);
    }
}
