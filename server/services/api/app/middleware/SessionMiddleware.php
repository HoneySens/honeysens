<?php
namespace HoneySens\app\middleware;

use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SessionMiddleware implements MiddlewareInterface {

    public function process(Request $request, RequestHandler $handler): Response {
        session_start();
        if(isset($_SESSION['last_activity'])) {
            // Handle session activity timeout
            if(time() - $_SESSION['last_activity'] > $_SESSION['timeout']) {
                session_unset();
                session_destroy();
                // 403 forbidden for API requests, '/' will be rendered regularly
                if(strpos($request->getUri()->getPath(), '/api/') === 0) {
                    http_response_code(403);
                    exit();
                }
            } else $_SESSION['last_activity'] = time();
        }
        if(!isset($_SESSION['authenticated']) || !isset($_SESSION['user'])) {
            $guestUser = new User();
            $guestUser->role = UserRole::GUEST;
            $_SESSION['authenticated'] = false;
            $_SESSION['user'] = $guestUser->getState();
        }
        return $handler->handle($request);
    }
}
