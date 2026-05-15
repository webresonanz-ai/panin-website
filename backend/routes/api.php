<?php

use App\Controllers\AuthController;
use App\Controllers\GuestController;
use App\Controllers\WasenderController;
use App\Middleware\AuthMiddleware;

$router->options('/api/{any}', fn () => null);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
$router->post('/api/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

$router->post('/api/wasender/send-document', [WasenderController::class, 'sendDocument'], [AuthMiddleware::class]);

$router->get('/api/guests', [GuestController::class, 'index']);
$router->post('/api/guests/check-in', [GuestController::class, 'checkIn'], [AuthMiddleware::class]);
$router->get('/api/guests/{id}', [GuestController::class, 'show'], [AuthMiddleware::class]);
$router->get('/api/guests/{id}/invitation-ticket', [GuestController::class, 'invitationTicket']);
$router->post('/api/guests/{id}/send-invitation', [GuestController::class, 'sendInvitation'], [AuthMiddleware::class]);
$router->post('/api/guests/send-pending-invitations', [GuestController::class, 'sendPendingInvitations'], [AuthMiddleware::class]);
$router->post('/api/guests/check-pending-wasender-statuses', [GuestController::class, 'checkPendingWasenderStatuses'], [AuthMiddleware::class]);
$router->post('/api/guests', [GuestController::class, 'store'], [AuthMiddleware::class]);
$router->post('/api/guests/import', [GuestController::class, 'import'], [AuthMiddleware::class]);
$router->put('/api/guests/{id}', [GuestController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/api/guests/{id}', [GuestController::class, 'destroy'], [AuthMiddleware::class]);
