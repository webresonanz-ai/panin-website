<?php

use App\Controllers\AuthController;
use App\Controllers\GuestController;
use App\Middleware\AuthMiddleware;

$router->options('/api/{any}', fn () => null);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
$router->post('/api/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

$router->get('/api/guests', [GuestController::class, 'index']);
$router->get('/api/guests/{id}', [GuestController::class, 'show']);
$router->post('/api/guests', [GuestController::class, 'store'], [AuthMiddleware::class]);
$router->put('/api/guests/{id}', [GuestController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/api/guests/{id}', [GuestController::class, 'destroy'], [AuthMiddleware::class]);
