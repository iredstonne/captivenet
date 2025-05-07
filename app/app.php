<?php
require_once __DIR__."/boot.php";

if(!HTTP) {
    throw new \RuntimeException("This script must be executed from web server, not from command line.");
}

require_once __DIR__."/vite.php";

use App\Bags\InputBag;
use App\Bags\ErrorBag;
use App\Bags\FlashBag;
use App\Http\Middlewares\ErrorHandlerMiddleware;
use App\Http\Middlewares\CsrfGuardMiddleware;
use App\Http\Middlewares\LocalDeviceDiscoverMiddleware;
use DI\ContainerBuilder;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Exception\HttpInternalServerError;
use Symfony\Component\ErrorHandler\Debug;

$builder = new ContainerBuilder();
$builder->useAutowiring(true);
$container = $builder->build();
AppFactory::setContainer($container);

$app = AppFactory::create(); 

$twig = Twig::create(__DIR__."/resources/views", [
    "debug" => !PRODUCTION
]);

$inputs = new InputBag();
$errors = new ErrorBag();
$flashes = new FlashBag();

//$csrf = new CsrfGuardMiddleware();

$twig->getEnvironment()->addFunction(new \Twig\TwigFunction("vite_asset", function (string $path) {
    return vite_asset($path);
}, ["is_safe" => ["html"]]));
$twig->getEnvironment()->addFunction(new \Twig\TwigFunction("old", function (string $key, ?string $default = null) use ($inputs) {
    return $inputs->old($key, $default);
}));
$twig->getEnvironment()->addFunction(new \Twig\TwigFunction("error", function (string $field, ?string $default = null) use ($errors) {
    return $errors->first($field, $default);
}));
$twig->getEnvironment()->addFunction(new \Twig\TwigFunction("error_has", function (string $field) use ($errors) {
    return $errors->has($field);
}));
$twig->getEnvironment()->addFunction(new \Twig\TwigFunction("flash", function (string $key, ?string $default = null) use ($flashes) {
    return $flashes->first($key, $default);
}));
$twig->getEnvironment()->addFunction(new \Twig\TwigFunction("flash_has", function (string $key) use ($flashes) {
    return $flashes->has($key);
}));
$twig->getEnvironment()->addFunction(new \Twig\TwigFunction("csrf_token", function () {
    return "0"; // $csrf->getCsrfToken();
}));

$container->set(App::class, $app);
$container->set(Twig::class, $twig);
$container->set(Inputs::class, $inputs);
$container->set(Errors::class, $errors);
$container->set(Flashes::class, $flashes);

$app->add(BodyParsingMiddleware::class);
$app->add(MethodOverrideMiddleware::class);
//$app->add($csrf);
$app->add(TwigMiddleware::create($app, $twig));
$app->add(LocalDeviceDiscoverMiddleware::class);

if(PRODUCTION) {
    $app->add(ErrorHandlerMiddleware::class);
} else {
    Debug::enable();
}
return $app;
