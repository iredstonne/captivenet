<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Http\Controllers\WebController;

$app = require_once dirname(__DIR__)."/app.php";
$app->get("/", [WebController::class, "index"]);
$app->post("/", [WebController::class, "authenticate"]);
$app->get("/session", [WebController::class, "session"]);
$app->delete("/session", [WebController::class, "logout"]);
$app->any("/{routes:.+}", function (Request $request, Response $response) {
    return $response
        ->withStatus(302)
        ->withHeader("Cache-Control", "no-cache, no-store, must-revalidate, max-age=0")
        ->withHeader("Pragma", "no-cache")
        ->withHeader("Expires", "0")
        ->withHeader("Content-Length", "0")
        ->withHeader("Refresh", "0; url=http://captivenet.local")
        ->withHeader("Location", "http://captivenet.local");
});
$app->run();
