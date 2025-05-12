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
        $stmt = $pdo->prepare("SELECT * FROM devices WHERE mac_address = ? LIMIT 1");
        $stmt->execute([$macAddress]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        if($snapshot) {
            if($snapshot->ip_address != $ipAddress) {
                $stmt = $pdo->prepare("UPDATE devices SET ip_address = ? WHERE id = ?");
                $stmt->execute([$ipAddress, $snapshot->id]);
                $snapshot->ip_address = $ipAddress;
            }
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
