<?php
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Slim\Middleware\ErrorMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Set up Container
$containerBuilder = new ContainerBuilder();

// Add container definitions
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Fix path issue with VS Code PHP Server
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/public/') === 0) {
    // Remove '/public' from the beginning of the path if it exists
    $app->setBasePath('/public');
} else {
    // Check if running in a subdirectory
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if (strlen($scriptDir) > 1 && $scriptDir !== '/public') {
        $app->setBasePath($scriptDir);
    }
}

// Register Middleware
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(new \App\Middleware\CorsMiddleware());

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Set a custom error handler for 404 errors
$errorMiddleware->setErrorHandler(
    Slim\Exception\HttpNotFoundException::class,
    function (Psr\Http\Message\ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails) {
        $response = new Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Route not found: ' . $request->getUri()->getPath(),
            'method' => $request->getMethod()
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    }
);

// Register routes
require __DIR__ . '/../config/routes.php';

// Run App
$app->run();
