<?php
namespace App\Http\Controllers;

use App\Database\Models\SessionModel;
use App\Database\Models\CouponModel;
use App\Database\Models\DeviceModel;
use App\Services\SessionService;
use App\Services\CouponService;
use App\Helpers\Firewall;
use App\Helpers\Time;
use App\Validators\InputValidator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpException;

class WebController extends AbstractWebController
{
    public function index(Request $request, Response $response, array $args): Response 
    {
        $device = $request->getAttribute("device");
        if($session = SessionModel::findActiveByDevice($device)) {
            if($coupon = CouponModel::findById($session->couponId)) {
                $this->flashes->push("message", "Connecté au code d'accès {$coupon->code}.");
                return $this->redirect($response, "/session");
            }
        }
        return $this->view($response, "pages/index.html.twig");
    }

    public function authenticate(Request $request, Response $response, array $args): Response 
    {
        $device = $request->getAttribute("device");
        $validator = new InputValidator();
        $validator->validate($request, [
            "coupon_code" => [
                "required" => [
                    "message" => "Vous n'avez pas fourni de code d'accès"
                ],
                "pattern" => [
                    "match" => "/^[0-9]+$/i",
                    "message" => "Ce code d'accès ne peut contenir que des chiffres."
                ],
                "maxLength" => [
                    "value" => 20,
                    "message" => "Ce code d'accès ne doit pas dépasser 20 caractères."
                ]
            ]
        ]);
        if ($validator->fails()) {
            foreach ($validator->values() as $field => $value) {
                $this->inputs->remember($field, $value);
            }
            foreach ($validator->errors() as $field => $message) {
                $this->errors->push($field, $message);
            }
            return $this->back($request, $response);
        }
        $code = $request->getParsedBody()["coupon_code"] ?? "";
        $this->inputs->remember("coupon_code", $code);
        if(!($coupon = CouponModel::findByCode($code))) {
            $this->errors->push("coupon_code", "Ce code d'accès est invalide." . \random_int(0, 255));
            return $this->back($request, $response);
        }
        if($coupon->isDeviceTimeExceeded($device)) {
            $this->errors->push("coupon_code", "Le temps accordé à votre appareil par ce code d'accès a expiré.");
            return $this->back($request, $response);
        }
        if($coupon->hasReachedDeviceLimit()) {
            $this->errors->push("coupon_code", "Trop d'appareils sont connectés à ce code.");
            return $this->back($request, $response);
        }
        if(!SessionService::connect($device, $coupon)) {
            throw new HttpException($request, "La connexion a échoué: Le pare-feu n'est pas parvenu a authentifié l'appareil.", 503);
        }
        $this->inputs->forget("coupon_code");
        $this->flashes->push("message", "Connecté au code d'accès $code");
        return $this->redirect($response, "/session");
    }

    public function session(Request $request, Response $response, array $args): Response 
    {
        $device = $request->getAttribute("device");
        if(!($session = SessionModel::findActiveByDevice($device))) {
            throw new HttpUnauthorizedException($request, "Votre appareil n'a initié aucune session.");
        }
        if (!($coupon = CouponModel::findById($session->couponId))) {
            throw new HttpUnauthorizedException($request, "Le coupon utilisé est invalide.");
        }
        if(($remainingDeviceTime = CouponService::getRemainingDeviceTime($coupon, $device)) <= 0) {
            if(!(SessionService::disconnect($device, $session))) {
                throw new HttpException($request, "La déconnexion a échoué: Le pare-feu n'est pas parvenu a déauthentifié l'appareil.", 503);
            }
            $this->flashes->push("message", "Déconnecté du code d'accès {$coupon->code}. Le temps accordé à votre appareil a écoulé.");
            return $this->redirect($response, "/");
        }
        $remainingDevices = CouponService::getRemainingDevices($coupon);
        $connectedDevices = $coupon->getConnectedDevices();
        return $this->view($response, "pages/session.html.twig", [
            "coupon_code" => $coupon->code,
            "device_ip_address" => $device->ipAddress,
            "device_mac_address" => $device->macAddress,
            "allowed_time_formatted" => Time::formatHuman($coupon->allowedTime),
            "allowed_devices" => $coupon->allowedDevices,
            "remaining_device_time" => $remainingDeviceTime,
            "remaining_device_time_formatted" => Time::formatHuman($remainingDeviceTime),
            "remaining_devices" => $remainingDevices,
            "connected_devices" => $connectedDevices,
            "connected_devices_count" => count($connectedDevices)
        ]);
    }

    public function logout(Request $request, Response $response, array $args): Response 
    {
        $device = $request->getAttribute("device");
        if (!($session = SessionModel::findActiveByDevice($device))) {
            throw new HttpUnauthorizedException($request, "Votre appareil n'a initié aucune session.");
        }
        if (!($coupon = CouponModel::findById($session->couponId))) {
            throw new HttpUnauthorizedException($request, "Le coupon utilisé est invalide ou a expiré.");
        }
        if(!(SessionService::disconnect($device, $session))) {
            throw new HttpException($request, "La déconnexion a échoué: Le pare-feu n'est pas parvenu a déauthentifié l'appareil.", 503);
        }
        $this->flashes->push("message", "Déconnecté du code d'accès {$coupon->code}.");
        return $this->redirect($response, "/");
    }
}
