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
        if (!isset($token) || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            $this->storage[$this->storageKey] = $token;
        }
        return htmlspecialchars($token);
    }

    public function process(Request $request, Handler $handler): Response
    {
        if(strtoupper($request->getMethod()) === "POST") {
            $storedCrsfToken = htmlspecialchars($this->storage[$this->storageKey] ?? null);
            $submittedCsrfToken = htmlspecialchars($request->getParsedBody()[$this->storageKey] ?? null);
            if(!isset($storedCrsfToken) || !isset($submittedCsrfToken) || !is_string($storedCrsfToken) || !is_string($submittedCsrfToken) || !hash_equals($storedCrsfToken, $submittedCsrfToken)) {
                throw new HttpBadRequestException($request, "Echec de l'envoi du formulaire");
            }
        }
        return $handler->handle($request);
    }

}
