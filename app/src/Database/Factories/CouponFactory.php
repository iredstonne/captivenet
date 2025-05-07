<?php
namespace App\Database\Factories;

use App\Database\Connection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\ByteString;

class CouponFactory
{
    private \PDO $pdo;
    private CouponFactoryOptions $options;
    private ConsoleOutput $cout;
    private SymfonyStyle $io;

    public function __construct(CouponFactoryOptions $options = new CouponFactoryOptions())
    {
        $this->pdo = Connection::getPDO();
        $this->options = $options;
        $this->cout = new ConsoleOutput();
        $this->io = new SymfonyStyle(new ArrayInput([]), $this->cout);
    }

    private function generateUniqueRandomCode(int $length): string
    {
        for ($i = 0; $i < 5; $i++) {
            $code = ByteString::fromRandom($length, "0123456789")->toString();
            $pdo = Connection::getPDO();
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
            $stmt->execute([$code]);
            if (!$stmt->fetch()) {
                return $code;
            }
        }
        throw new \RuntimeException("Unable to generate a unique random code within 5 attempts.");
    }

    public function create(CouponFactoryOptions $options = null): self 
    {
        $options = $options ?? $this->options;
        $code = $this->generateUniqueRandomCode($options->codeLength);
        try {
            $allowedTime = random_int($options->minAllowedTime, $options->maxAllowedTime);
            $allowedDevices = random_int($options->minAllowedDevices, $options->maxAllowedDevices);

            $stmt = $this->pdo->prepare("INSERT INTO coupons (code, allowed_time, allowed_devices) VALUES (?, ?, ?)");
            $stmt->execute([$code, $allowedTime, $allowedDevices]);
        } catch(\Exception $e) {
            $this->io->error("Coupon $code generation failed: {$e->getMessage()}");
        }
        return $this;
    }

    public function createBatch(int $amount, CouponFactoryOptions $options = null): self 
    {
        try {
            $this->pdo->beginTransaction();
            for ($i = 0; $i < $amount; $i++) {
                $this->create($options);
            }
            $this->pdo->commit();
            $this->io->success("Coupon batch generation completed");
        } catch(\Exception $e) {
            $this->pdo->rollBack();
            $this->io->error("Coupon atch generation failed: {$e->getMessage()}");
        }
        return $this;
    } 
}
