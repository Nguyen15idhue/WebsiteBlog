<?php
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use Slim\Routing\RouteCollectorProxy;

// Create controller instance
$authController = new AuthController();

// Auth routes
$app->group('/api/auth', function (RouteCollectorProxy $group) use ($authController) {
    $group->post('/register', [$authController, 'register']);
    $group->post('/login', [$authController, 'login']);
    $group->post('/verify-email', [$authController, 'verifyEmail']);
    
    // Protected routes
    $group->get('/me', [$authController, 'me'])->add(new AuthMiddleware());
});

// Add a default route for testing
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'success',
        'message' => 'API is working!'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});
