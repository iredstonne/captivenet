<?php 
namespace App\Bags;

class ErrorBag
{
    protected array $storage;

    protected string $storageKey = "_errors";

    protected array $current = [];

    public function __construct() 
    {
        if(session_status() != PHP_SESSION_ACTIVE) {
            if(!session_start()) {
                throw new \RuntimeException("Failed to start session.");
            }
        }
        $this->storage = &$_SESSION;

        if(!isset($this->storage[$this->storageKey])) {
            $this->storage[$this->storageKey] = [];
        }
        $this->current = $this->storage[$this->storageKey] ?? [];
        $this->storage[$this->storageKey] = [];
    }

    public function push(string $field, string $message)
    {
        $this->storage[$this->storageKey][$field][] = $message;
    }

    public function has(string $field): bool
    {
        return !empty($this->current[$field] ?? []);
    }

    public function first(string $field, $default = null): mixed 
    {
        return ($this->current[$field] ?? [])[0] ?? $default;
    }  

    public function all(string $field): mixed 
    {
        return $this->current[$field] ?? [];
    }  
}
