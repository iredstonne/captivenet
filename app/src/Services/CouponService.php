<?php
namespace App\Services;

use App\Database\Models\DeviceModel;
use App\Database\Models\CouponModel;

class CouponService
{
    public function getRemainingDevices(CouponModel $coupon): int
    {
        $usedDevices = $coupon->countUsedDevices();
        return max(0, $coupon->allowedDevices - $usedDevices);
    }

    public function getRemainingDeviceTime(CouponModel $coupon, DeviceModel $device): int
    {
        $usedDeviceTime = $coupon->getUsedTimeForDevice($device);
        return max(0, $coupon->allowedTime - $usedDeviceTime);
    }
}
