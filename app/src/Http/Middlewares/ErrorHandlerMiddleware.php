<?php
namespace App\Http\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Views\Twig;
use Slim\Exception\HttpException;
use Symfony\Component\ErrorHandler\Debug;

// TODO: Improve error detection (add json output)
class ErrorHandlerMiddleware implements MiddlewareInterface 
{
    private ContainerInterface $container;
    private App $app;
    private Twig $twig;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->app = $this->container->get(App::class);
        $this->twig = $this->container->get(Twig::class);
    }

    private function registerGlobalHandlers()
    {
        set_error_handler(function ($severity, $message, $filename, $line) {
            if(!(error_reporting() && $severity)) {
                return;
            }
            throw new \ErrorException($message, 0, $severity, $filename, $line);
        });
    }

    private function handleException(Throwable $throwable)
    {

    }

    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch(\Throwable $throwable) {
           // handleException($request, $response, $throwable);

            $response = $this->app->getResponseFactory()->createResponse();
            $isHttp = $throwable instanceof HTTPException;
            $code =  $throwable->getCode() ?? 500;
            $message = $throwable->getMessage();

            return $this->twig->render($response, 'pages/error.html.twig', [
                'code' => $code,
                'cause' => $isHttp ? $message : (!PRODUCTION ? $message : "Non spécifié."),
                'is_client' => $isHttp && $code >= 400 && $code < 500
            ]);
        }
    }
}
