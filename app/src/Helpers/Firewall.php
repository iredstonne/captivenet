<?php
namespace App\Helpers;

use App\Database\Models\DeviceModel;

class Firewall
{
    public static function isAuthenticated(DeviceModel $device): bool
    {
        $escapedDeviceIpAddress = escapeshellarg($device->ipAddress);
        exec("sudo /usr/sbin/ipset test authenticated $escapedDeviceIpAddress -exist", $_, $exitCode);
        return $exitCode === 0;
    }

    public static function authenticate(DeviceModel $device): bool
    {
        $escapedDeviceIpAddress = escapeshellarg($device->ipAddress);
        exec("sudo /usr/sbin/ipset add authenticated $escapedDeviceIpAddress -exist", $_, $exitCodeA);
        exec("sudo /usr/sbin/ipset save | sudo tee /etc/iptables/ipsets", $_, $exitCodeB);
        return $exitCodeA === 0 && $exitCodeB === 0;
    }

    public static function deauthenticate(DeviceModel $device): bool
    {
        $escapedDeviceIpAddress = escapeshellarg($device->ipAddress);
        exec("sudo /usr/sbin/ipset del authenticated $escapedDeviceIpAddress -exist", $_, $exitCodeA);
        exec("sudo /usr/sbin/ipset save | sudo tee /etc/iptables/ipsets", $_, $exitCodeB);
        return $exitCodeA === 0 && $exitCodeB === 0;
    }
}
