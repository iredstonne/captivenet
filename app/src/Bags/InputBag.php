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
            throw new \RuntimeException("Session must be started before using InputBag.");
            exit;
        }
        $this->storage = &$_SESSION;
        if(!isset($this->storage[$this->storageKey])) {
            $this->storage[$this->storageKey] = [];
        }
        $this->current = $this->storage[$this->storageKey] ?? [];
        unset($this->storage[$this->storageKey]);
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

    public function has(string $field): bool 
    {
        return isset($this->current[$field]);
    }
}
