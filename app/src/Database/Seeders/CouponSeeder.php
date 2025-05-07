<?php
namespace App\Database\Seeders;

use App\Database\Connection;
use App\Database\Seeders\AbstractSeeder;
use App\Database\Models\CouponModel;
use App\Database\Factories\CouponFactory;

class CouponSeeder extends AbstractSeeder
{
    public function run() {
        $this->truncate("devices");
        $this->truncate("sessions");
        $this->truncate("coupons");
        new CouponFactory()
            ->createBatch(100);
    }
}
