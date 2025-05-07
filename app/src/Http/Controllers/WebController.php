<?php
namespace App\Http\Controllers;

use App\Database\Models\SessionModel;
use App\Database\Models\CouponModel;
use App\Database\Models\DeviceModel;
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
        $this->flashes->push("message", "Connecté au code d'accès {$validator->fails()}");
        // foreach ($validator->values() as $field => $value) {
        //     $this->inputs->remember($field, $value);
        // }
        return $this->back($request, $response);
        // if ($validator->fails()) {
        //     foreach ($validator->values() as $field => $value) {
        //         $this->inputs->remember($field, $value);
        //     }
        //     foreach ($validator->errors() as $field => $message) {
        //         $this->errors->push($field, $message);
        //     }
        //     return $response;
        //     //return $this->back($request, $response);
        // } 
        // $code = $request->getParsedBody()["coupon_code"] ?? "";
        // if(!($coupon = CouponModel::findByCode($code))) {
        //     $this->errors->push("coupon_code", "Ce code d'accès est invalide.");
        //     return $this->back($request, $response);
        // }
        // if(!($coupon->isDeviceTimeExceeded($device))) {
        //     $this->errors->push("coupon_code", "Le temps accordé à votre appareil a écoulé.");
        //     return $this->back($request, $response);
        // }
        // if(!($coupon->hasReachedDeviceLimit($device))) {
        //     $this->errors->push("coupon_code", "Ce code d'accès est invalide.");
        //     return $this->back($request, $response);
        // }
        // SessionService::connect($device, $coupon);
        // $this->flashes->push("message", "Connecté au code d'accès $code");
        // return $this->redirect($response, "/session");
    }

    public function session(Request $request, Response $response, array $args): Response 
    {
        $device = $request->getAttribute("device");
        if(!($session = SessionModel::findActiveByDevice($device))) {
            throw new HttpUnauthorizedException($request, "Votre appareil n'a pas aucune session ouverte.");
        }
        if (!($coupon = CouponModel::findById($session->couponId))) {
            throw new HttpUnauthorizedException($request, "Le coupon utilisé est invalide.");
        }
        $remainingDeviceTime = CouponService::getRemainingDeviceTime($coupon, $device);
        if($remainingDeviceTime <= 0) {
            SessionService::disconnect($session);
            $this->flashes->push("message", "Le temps accordé à votre appareil par le code d'accès {$coupon->code} a écoulé.");
            return $this->redirect($response, "/");
        }
        $remainingDevices = CouponService::getRemainingDevices($coupon);
        $connectedDevices = $this->coupon->getConnectedDevices();
        return $this->view($response, "pages/session.html.twig", [
            "coupon_code" => $coupon->code,
            "device_ip_address" => $device->ipAddress,
            "device_mac_address" => $device->macAddress,
            "allowed_time" => Time::formatRemaining($coupon->allowed_time),
            "allowed_devices" => $coupon->allowed_devices,
            "remaining_device_time" => Time::formatRemaining($remainingDeviceTime),
            "remaining_devices" => $remainingDevices,
            "connected_devices" => $connectedDevices,
            "connected_devices_count" => count($connectedDevices)
        ]);
    }

    public function logout(Request $request, Response $response, array $args): Response 
    {
        $device = $request->getAttribute("device");
        if (!($session = SessionModel::findActiveByDevice($device))) {
            throw new HttpUnauthorizedException($request, "Aucune session liée à cet appareil n'est active.");
        }
        if (!($coupon = CouponModel::findById($session->couponId))) {
            throw new HttpUnauthorizedException($request, "Le coupon utilisé est invalide ou a expiré.");
        }
        SessionService::disconnect($session);
        $this->flashes->push("success", "Déconnecté du code d'accès $couponCode.");
        return $this->redirect($response, "/");
    }
}
