<?php
namespace App\Services;

use App\Helpers\Firewall;
use App\Database\Models\DeviceModel;
use App\Database\Models\CouponModel;
use App\Database\Models\SessionModel;
use Slim\Exception\HttpException;

class SessionService
{
    public static function connect(DeviceModel $device, CouponModel $coupon): bool {
        if(!Firewall::authenticate($device)) {
            return false;
        } 
        SessionModel::connect($device, $coupon);
        return true;
    }

    public static function disconnect(DeviceModel $device, SessionModel $session) {
        if(!Firewall::deauthenticate($device)) {
            return false;
        } 
        $session->disconnect();
        return true;
    }
}
