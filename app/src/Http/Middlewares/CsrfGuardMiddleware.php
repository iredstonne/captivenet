<?php
namespace App\Http\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\HttpBadRequestException;

class CsrfGuardMiddleware implements MiddlewareInterface
{
    private array $storage;

    private string $storageKey;

    public function __construct() 
    {
        if(session_status() != PHP_SESSION_ACTIVE) {
            if(!session_start()) {
                throw new \RuntimeException('Session could not be started.');
                exit;
            }
        }
        $this->storage = &$_SESSION;
        $this->storageKey = "_csrf_token";
    }

    public function getCsrfToken()
    {
        $token = $this->storage[$this->storageKey] ?? null;
        if(!is_string($token)) {
            return $this->generateCsrfToken();
        }
        return htmlspecialchars($token);
    }

    private function generateCsrfToken() 
    {
        $token = bin2hex(random_bytes(32));
        $this->storage[$this->storageKey] = $token;
        return $token;
    }

    public function process(Request $request, Handler $handler): Response
    {
        if(strtoupper($request->getMethod()) === "POST") {
            $sessionCrsfToken = $this->storage[$this->storageKey] ?? null;
            $currentCsrfToken = $request->getParsedBody()[$this->storageKey] ?? null;
            unset($this->storage[$this->storageKey]);
            if(!$sessionCrsfToken || !$currentCsrfToken || !hash_equals($sessionCrsfToken, $currentCsrfToken)) {
                throw new HttpBadRequestException($request, "Echec de l'envoi du formulaire");
            }
        }
        return $handler->handle($request);
    }

}
