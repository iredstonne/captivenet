<?php 
namespace App\Bags;

class InputBag
{
    protected array $storage;

    protected string $storageKey = "_inputs";

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

    public function remember(string $field, mixed $value)
    {
        $this->storage[$this->storageKey][$field] = $value;
    }

    public function forget(string $field) 
    {
        unset($this->storage[$this->storageKey][$field]);
    }

    public function old(string $field, mixed $default = null): ?string
    {
        return $this->current[$field] ?? $default;
    }
}
