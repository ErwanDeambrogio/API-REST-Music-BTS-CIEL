<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Middleware\JwtHelper;

class JwtMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader) {
            list($jwt) = sscanf($authHeader, 'Bearer %s');

            if ($jwt) {
                $decoded = JwtHelper::validateToken($jwt);
                if ($decoded) {
                    $request = $request->withAttribute('user', $decoded->data);
                    return $handler->handle($request);
                }
            }
        }

        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
