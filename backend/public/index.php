<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Application;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\CorsMiddleware;

require dirname(__DIR__) . '/src/bootstrap.php';

$config = new Config(dirname(__DIR__) . '/config');
$app = new Application($config);
$router = new Router();
$request = Request::capture();

require dirname(__DIR__) . '/routes/api.php';

try {
    $cors = $app->make(CorsMiddleware::class);
    $result = $cors->handle($request, fn ($request) => $router->dispatch($request, $app));

    if ($result !== null) {
        Response::json($result);
    }
} catch (ApiException $exception) {
    Response::json([
        'message' => $exception->getMessage(),
        'errors' => $exception->errors(),
    ], $exception->status());
} catch (Throwable $exception) {
    Response::json([
        'message' => $config->get('app.debug')
            ? $exception->getMessage()
            : 'Something went wrong on the server.',
    ], 500);
}
