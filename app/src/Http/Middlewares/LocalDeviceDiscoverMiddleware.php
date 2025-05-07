<?php
namespace App\Http\Middlewares;

use App\Helpers\Network;
use App\Helpers\Device;
use App\Database\Models\DeviceModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\HttpBadRequestException;

class LocalDeviceDiscoverMiddleware implements MiddlewareInterface 
{
    public function process(Request $request, Handler $handler): Response
    {
        $deviceIpAddress = Network::getDeviceIpAddress($request);
        $deviceMacAddress = Network::getDeviceMacAddress($deviceIpAddress);
        if (!$deviceIpAddress || !$deviceMacAddress) {
            throw new HttpBadRequestException($request, "Échec lors de l'identification de l'appareil sur le réseau.");
        }
        $kind = Device::getDeviceKind($request->getHeaderLine("User-Agent") ?? "");
        $device = DeviceModel::discover(
            $deviceIpAddress, 
            $deviceMacAddress, 
            $kind
        );
        $request = $request->withAttribute("device", $device);
        return $handler->handle($request);
    }
}
