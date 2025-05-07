<?php
require_once dirname(__DIR__).'/boot.php';

use App\Helpers\Network;
use App\Helpers\Firewall;
use App\Database\Models\DeviceModel;
use App\Database\Models\CouponModel;
use App\Database\Models\SessionModel;
use App\Services\SessionService;

openlog("scheduler", LOG_PID | LOG_PERROR, LOG_CRON);
syslog(LOG_INFO, "Running scheduler...");
foreach (SessionModel::findAllActive() as $session) {
    $device = DeviceModel::findById($session->deviceId);
    $coupon = CouponModel::findById($session->couponId);

    syslog(LOG_INFO, "Checking device {$device->ipAddress}...");
    if ($coupon->isTimeExceededForDevice($device)) {
        SessionService::disconnect($device);
        syslog(LOG_INFO, "Disconnecting device {$device->ipAddress} (time exceeded)");
    } else if(!Network::isDeviceConnectedToNetwork($device)) {
        SessionService::disconnect($device);
        syslog(LOG_INFO, "Disconnecting device {$device->ipAddress} (not connected to network)");
    }
}
closelog();
// to inspect syslog use journalctl -t scheduler
