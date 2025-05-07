<?php
require_once dirname(__DIR__).'/boot.php';

use App\Database\Connection;
use App\Database\Seeders\CouponSeeder;

CouponSeeder::instance()->seed();
