<?php
namespace App\Helpers;

use App\Database\Models\DeviceModel;

class Firewall 
{
    public static function isAuthenticated(DeviceModel $device): bool 
    {
        $escapedDeviceIpAddress = escapeshellarg($device->ipAddress);
        exec("sudo /sbin/ipset test authenticated $escapedDeviceIpAddress", $_, $exitCode);
        return $exitCode === 0;
    }

    public static function authenticate(DeviceModel $device): bool
    {
        $escapedDeviceIpAddress = escapeshellarg($device->ipAddress);
        exec("sudo /sbin/ipset add authenticated $escapedDeviceIpAddress -exist", $_, $exitCode);
        return $exitCode === 0;
    }

    public static function deauthenticate(DeviceModel $device): bool
    {
        $escapedDeviceIpAddress = escapeshellarg($device->ipAddress);
        exec("sudo /sbin/ipset del authenticated $escapedDeviceIpAddress -exist", $_, $exitCode);
        return $exitCode === 0;
    }
}
