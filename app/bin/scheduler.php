<?php
require_once dirname(__DIR__).'/boot.php';

use App\Helpers\Network;
use App\Helpers\Firewall;
use App\Database\Models\DeviceModel;
use App\Database\Models\CouponModel;
use App\Database\Models\SessionModel;
use App\Services\SessionService;

openlog("scheduler", LOG_PID | LOG_PERROR, LOG_CRON);
syslog(LOG_INFO, "Checking for active sessions...");

foreach (SessionModel::findAllActive() as $session) {
    $device = DeviceModel::findById($session->deviceId);
    $coupon = CouponModel::findById($session->couponId);

    syslog(LOG_INFO, "Checking session with coupon {$coupon->code} of device {$device->macAddress} - {$device->ipAddress}...");
    syslog(LOG_INFO, "Is device connected to network ? " . (Network::isDeviceConnectedToNetwork($device) ? "Yes" : "No"));
    syslog(LOG_INFO, "Is device authenticated to firewall ? " . (Firewall::isAuthenticated($device) ? "Yes" : "No"));
    syslog(LOG_INFO, "Ran out of device time for this coupon ? " . ($coupon->isDeviceTimeExceeded($device) ? "Yes" : "No"));

    if($coupon->isDeviceTimeExceeded($device)) {
        syslog(LOG_INFO, "Disconnecting device {$device->ipAddress} (time exceeded)");
        SessionService::disconnect($device, $session);
    }

    if(!Network::isDeviceConnectedToNetwork($device)) {
        syslog(LOG_INFO, "Disconnecting device {$device->ipAddress} (not connected to network)");
        SessionService::disconnect($device, $session);
    }
}

closelog();
