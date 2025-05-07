<?php
use App\Http\Controllers\WebController;
use App\Http\Controllers\ApiController;
USE App\Http\Middlewares\ForceJsonAcceptMiddleware;
use Slim\Routing\RouteCollectorProxy;

$app = require_once dirname(__DIR__)."/app.php";
$app->get("/", [WebController::class, "index"]);
$app->post("/", [WebController::class, "authenticate"]);
$app->get("/session", [WebController::class, "session"]);
$app->delete("/session", [WebController::class, "logout"]);
$app->run();
