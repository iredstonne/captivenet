<?php
namespace App\Database\Models;

use App\Database\Connection;
use App\Enums\DeviceKind;

class DeviceModel
{

    public int $id;
    public string $ipAddress;
    public string $macAddress;
    public string $kind;
    public string $discoveredAt;

    private function __construct(object $snapshot) 
    {
        $this->id = $snapshot->id;
        $this->ipAddress = $snapshot->ip_address;
        $this->macAddress = $snapshot->mac_address;
        $this->kind = $snapshot->kind;
        $this->discoveredAt = $snapshot->discovered_at; 
    }

    public static function discover(string $ipAddress, string $macAddress, DeviceKind $kind): DeviceModel 
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM devices WHERE ip_address = ? AND mac_address = ? LIMIT 1");
        $stmt->execute([$ipAddress, $macAddress]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        if($snapshot) {
            // Update ip associated to mac if dhcp release
            return new self($snapshot);   
        }
        $stmt = $pdo->prepare("INSERT INTO devices (ip_address, mac_address, kind) VALUES (?, ?, ?)");
        $stmt->execute([$ipAddress, $macAddress, $kind->value]);
        return self::findById($pdo->lastInsertId());
    }

    public static function findById(int $id): ?DeviceModel
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM devices WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        return $snapshot ? new self($snapshot) : null;
    }
}
