<?php

namespace App\Database\Factories;

class CouponFactoryOptions
{
    public int $minAllowedTime;
    public int $maxAllowedTime;
    public int $minAllowedDevices;
    public int $maxAllowedDevices;
    public int $codeLength;

    public function __construct(
        int $minAllowedTime = 60,
        int $maxAllowedTime = 600,
        int $minAllowedDevices = 1,
        int $maxAllowedDevices = 3,
        int $codeLength = 5
    )
    {
        if ($minAllowedTime > $maxAllowedTime) {
            throw new \InvalidArgumentException("minAllowedTime must be less than or equal to maxAllowedTime.");
        }
        if ($minAllowedDevices > $maxAllowedDevices) {
            throw new \InvalidArgumentException("minAllowedDevices must be less than or equal to maxAllowedDevices.");
        }
        if ($codeLength < 1) {
            throw new \InvalidArgumentException("codeLength must be a non-null positive.");
        }

        $this->minAllowedTime = $minAllowedTime;
        $this->maxAllowedTime = $maxAllowedTime;
        $this->minAllowedDevices = $minAllowedDevices;
        $this->maxAllowedDevices = $maxAllowedDevices;
        $this->codeLength = $codeLength;
    }
}