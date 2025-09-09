<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = new \Slim\Psr7\Response();
        
        $header = $request->getHeaderLine('Authorization');
        
        if (empty($header) || !preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'JWT Token not found'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        $jwt = $matches[1];
        
        try {
            // Decode JWT
            $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
            
            // Add user info to request attribute
            $request = $request->withAttribute('user', $decoded);
            
            // Continue with request
            return $handler->handle($request);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Invalid JWT Token: ' . $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }
}
