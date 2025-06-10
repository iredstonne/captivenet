<?php 
namespace App\Bags;

class FlashBag
{
    protected array $storage;

    protected string $storageKey = "_flashes";

    protected array $current = [];

    public function __construct() 
    {
        if(session_status() != PHP_SESSION_ACTIVE) {
            throw new \RuntimeException("Session must be started before using FlashBag.");
            exit;
        }
        $this->storage = &$_SESSION;
        if(!isset($this->storage[$this->storageKey])) {
            $this->storage[$this->storageKey] = [];
        }
        $this->current = $this->storage[$this->storageKey] ?? [];
        unset($this->storage[$this->storageKey]);
    }

    public function push($key, $value): void
    {
        $this->storage[$this->storageKey][$key][] = $value;
    }

    public function has(string $key): bool
    {
        return !empty($this->current[$key]);
    }

    public function first(string $key, $default = null): mixed 
    {
        return $this->current[$key][0] ?? $default;
    }  

    public function all(string $key): mixed 
    {
        return $this->current[$key];
    }  
}
