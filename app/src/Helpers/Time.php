<?php
namespace App\Helpers;

class Time 
{
    public static function formatRemaining(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        $parts = [];
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0 || $hours > 0) {
            $parts[] = "{$minutes}m";
        }
        $parts[] = "{$seconds}s";
        return implode(" ", $parts);
    }
}
