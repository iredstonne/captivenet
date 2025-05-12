<?php
namespace App\Database\Models;

use App\Database\Connection;

class SessionModel 
{
    public int $id;
    public int $deviceId;
    public int $couponId;
    public string $startedAt;
    public ?string $endedAt;

    private function __construct(object $snapshot)
    {
        $this->id = $snapshot->id;
        $this->deviceId = $snapshot->device_id;
        $this->couponId = $snapshot->coupon_id;
        $this->startedAt = $snapshot->started_at;
        $this->endedAt = $snapshot->ended_at;
    }

    public static function connect(DeviceModel $device, CouponModel $coupon): self
    {
        $active = self::findActiveByDevice($device);
        if ($active) {
            $active->disconnect();
        }
        $startedAt = (new \DateTime())->format("Y-m-d H:i:s");
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("INSERT INTO sessions (device_id, coupon_id,started_at) VALUES (?, ?, ?)");
        $stmt->execute([$device->id, $coupon->id, $startedAt]);
        return self::findById($pdo->lastInsertId());
    }

    public function disconnect()
    {
        if ($this->endedAt === null) {
            $this->endedAt = (new \DateTime())->format("Y-m-d H:i:s");
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare("UPDATE sessions SET ended_at = ? WHERE id = ?");
            $stmt->execute([$this->endedAt, $this->id]);
        }
    }

    public static function findAllActive(): array
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE ended_at IS NULL");
        $stmt->execute();
        $snapshots = $stmt->fetchAll(\PDO::FETCH_OBJ);
        return array_map(fn($snapshot) => new self($snapshot), $snapshots);
    }

    public static function findById(int $id): ?self
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        return $snapshot ? new self($snapshot) : null;
    }

    public static function findByDevice(DeviceModel $device): ?self
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE device_id = ? LIMIT 1");
        $stmt->execute([$device->id]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        return $snapshot ? new self($snapshot) : null;
    }

    public static function findActiveByDevice(DeviceModel $device): ?self
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE device_id = ? AND ended_at IS NULL LIMIT 1");
        $stmt->execute([$device->id]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        return $snapshot ? new self($snapshot) : null;
    }

    public static function findAllByDevice(DeviceModel $device): array
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE device_id = ?");
        $stmt->execute([$device->id]);
        $snapshots = $stmt->fetchAll(\PDO::FETCH_OBJ);
        return array_map(fn($snapshot) => new self($snapshot), $snapshots);
    }

    public static function findAllActiveByDevice(DeviceModel $device): array
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE device_id = ? AND ended_at IS NULL");
        $stmt->execute([$device->id]);
        $snapshots = $stmt->fetchAll(\PDO::FETCH_OBJ);
        return array_map(fn($snapshot) => new self($snapshot), $snapshots);
    }
    
    public static function findByCoupon(CouponModel $coupon): ?self
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE coupon_id = ? LIMIT 1");
        $stmt->execute([$coupon->id]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        return $snapshot ? new self($snapshot) : null;
    }

    public static function findActiveByCoupon(CouponModel $coupon): ?self
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE coupon_id = ? AND ended_at IS NULL LIMIT 1");
        $stmt->execute([$coupon->id]);
        $snapshot = $stmt->fetch(\PDO::FETCH_OBJ);
        return $snapshot ? new self($snapshot) : null;
    }

    public static function findAllByCoupon(CouponModel $coupon): array
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE coupon_id = ?");
        $stmt->execute([$coupon->id]);
        $snapshots = $stmt->fetchAll(\PDO::FETCH_OBJ);
        return array_map(fn($snapshot) => new self($snapshot), $snapshots);
    }

    public static function findAllActiveByCoupon(CouponModel $coupon): array
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE coupon_id = ? AND ended_at IS NULL");
        $stmt->execute([$coupon->id]);
        $snapshots = $stmt->fetchAll(\PDO::FETCH_OBJ);
        return array_map(fn($snapshot) => new self($snapshot), $snapshots);
    }

    public static function findAllByCouponAndDevice(CouponModel $coupon, DeviceModel $device): array
    {
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE coupon_id = ? AND device_id = ?");
        $stmt->execute([$coupon->id, $device->id]);
        $snapshots = $stmt->fetchAll(\PDO::FETCH_OBJ);
        return array_map(fn($snapshot) => new self($snapshot), $snapshots);
    }
}
