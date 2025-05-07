<?php
namespace App\Database\Models;

use App\Database\Connection;

class CouponModel 
{
    public int $id;
    public string $code;
    public int $allowedTime;
    public int $allowedDevices;
    public string $createdAt;

    private function __construct(object $snapshot)
    {
        $this->id = $snapshot->id;
        $this->code = $snapshot->code;
        $this->allowedTime = $snapshot->allowed_time;
        $this->allowedDevices = $snapshot->allowed_devices;
        $this->createdAt = $snapshot->created_at;
    }

    public static function findById(int $id): ?self
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        return $snapshot ? new self($snapshot) : null;
    }

    public static function findByCode(string $code): ?self
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->execute([$code]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        return $snapshot ? new self($snapshot) : null;
    }

    public function countUsedDeviceTime(DeviceModel $device): int
    {
        $total = 0;
        $sessions = SessionModel::findAllByCouponAndDevice($this, $device);
        foreach ($sessions as $session) {
            $startAtTimestamp = strtotime($session->startedAt);
            $endAtTimestamp = $session->endedAt ? strtotime($session->endedAt) : time();
            if ($endAtTimestamp > $startAtTimestamp) {
                $total += $endAtTimestamp - $startAtTimestamp;
            }
        }
        return $total;
    }

    public function isDeviceTimeExceeded(DeviceModel $device): bool 
    {
        return $this->isTimeLimited() && $this->countUsedDeviceTime($device) >= $this->allowedTime;
    }

    public function isTimeLimited() 
    {
        return $this->allowedTime > 0;
    }

    public function countUsedDevices() 
    {
        return count(SessionModel::findAllActiveByCoupon($this));
    }

    public function getConnectedDevices(): array
    {
        $sessions = SessionModel::findAllActiveByCoupon($this);
        return array_map(fn($session) => DeviceModel::findById($session->deviceId), $sessions);
    }

    public function hasReachedDeviceLimit(): bool 
    {
        return $this->isDeviceLimited() && $this->countUsedDevices() >= $this->allowedDevices;
    }
    
    public function isDeviceLimited() 
    {
        return $this->allowedDevices > 0;
    }

}
