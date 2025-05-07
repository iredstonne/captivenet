<?php
namespace App\Http\Controllers;

use App\Bags\InputBag;
use App\Bags\ErrorBag;
use App\Bags\FlashBag;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

abstract class AbstractWebController
{
    protected ContainerInterface $container;
    protected Twig $twig;
    protected InputBag $inputs;
    protected ErrorBag $errors;
    protected FlashBag $flashes;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->twig = $container->get(Twig::class);
        $this->inputs = $container->get(InputBag::class);
        $this->errors = $container->get(ErrorBag::class);
        $this->flashes = $container->get(FlashBag::class);
    }

    protected function view(Response $response, string $template, array $data = []): Response 
    {
        return $this->twig->render($response, $template, $data);
    }

    protected function back(Request $request, Response $response): Response 
    {
        return $this->redirect($response, $request->getHeaderLine("Referer") ?: "/");
    }

    protected function redirect(Response $response, string $to = "/"): Response 
    {
        return $response->withHeader("Location", $to)->withStatus(302);
    }
}
