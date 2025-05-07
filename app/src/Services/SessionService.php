<?php
namespace App\Services;

use App\Helpers\Firewall;
use App\Database\Models\DeviceModel;
use App\Database\Models\CouponModel;
use App\Database\Models\SessionModel;
use Slim\Exception\HttpException;

class SessionService
{
    public static function connect(DeviceModel $device, CouponModel $coupon) {
        if(!Firewall::authenticate($device)) {
            throw new HttpException($request, "La connexion a échoué: Le pare-feu n'est pas parvenu a authentifié l'appareil.", 503);
        } 
        SessionModel::connect($device, $coupon);
    }

    public static function disconnect(SessionModel $session) {
        if(!Firewall::deauthenticate($device)) {
            throw new HttpException($request, "La connexion a échoué: Le pare-feu n'est pas parvenu a authentifié l'appareil.", 503);
        } 
        $session->disconnect();
    }
}
