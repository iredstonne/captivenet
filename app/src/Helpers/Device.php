<?php
namespace App\Helpers;

use App\Database\Models\DeviceModel;
use App\Enums\DeviceKind;
use Detection\MobileDetect;

class Device 
{
    public static function getDeviceKind(string $userAgent) {
        $detect = new MobileDetect();
        $detect->setUserAgent($userAgent);
        return $detect->isMobile() ? DeviceKind::Mobile : ($detect->isTablet() ? DeviceKind::Tablet : DeviceKind::Desktop);
    }
}
