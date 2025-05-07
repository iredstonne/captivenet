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
            if(!session_start()) {
                throw new \RuntimeException('Failed to start session.');
                exit;
            }
        }
        $this->storage = &$_SESSION;
        $this->storageKey = "_flashes";
        if(!isset($this->storage[$this->storageKey])) {
            $this->storage[$this->storageKey] = [];
        }
        $this->current = $this->storage[$this->storageKey] ?? [];
        $this->storage[$this->storageKey] = [];
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
