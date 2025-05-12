<?php
namespace App\Helpers;

use App\Database\Models\DeviceModel;
use Psr\Http\Message\ServerRequestInterface as Request;

class Network 
{
    public static function getDeviceIpAddress(Request $request): ?string
    {
        $server = $request->getServerParams();
        $candidates = [];
        if (!empty($server["HTTP_X_REAL_IP"])) {
            $candidates[] = $server["HTTP_X_REAL_IP"];
        }
        if (!empty($server["HTTP_X_FORWARDED_FOR"])) {
            $candidates = array_merge($candidates, explode(",", $server["HTTP_X_FORWARDED_FOR"]));
        }
        if (!empty($server["REMOTE_ADDR"])) {
            $candidates[] = $server["REMOTE_ADDR"];
        }
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $candidate;
            }
        }
        return null;
    }

    public static function getDeviceMacAddress(?string $deviceIpAddress): ?string 
    {
        $escapedDeviceIpAddress = escapeshellarg($deviceIpAddress);
        exec("grep $escapedDeviceIpAddress /var/lib/misc/dnsmasq.leases | cut -d ' ' -f 2", $output, $exitCode); // 0 = Passed, 1 = Failed, 2 = Error
        return $exitCode === 0 && !empty($output[0]) ? trim(strtolower($output[0])) : null;
    }

    public static function isDeviceConnectedToNetwork(DeviceModel $device): bool 
    {
        $deviceMacAddress = strtolower($device->macAddress);
        exec("sudo /sbin/iw dev wlan0 station dump | awk '/Station/ {print $2}' | grep -i -q ^$deviceMacAddress\$", $_, $exitCode); // 0 = Passed, 1 = Failed, 2 = Error
        return $exitCode === 0;
    }
}
