<?php

namespace Database\Seeders;

use App\Models\CoverageArea;
use App\Models\Odp;
use App\Models\Village;
use Illuminate\Database\Seeder;

class BozonkNetSeeder extends Seeder
{
    public function run(): void
    {
        CoverageArea::query()->updateOrCreate(
            ['name' => 'Hub Utama Bojonggede'],
            [
                'center_latitude' => -6.4406000,
                'center_longitude' => 106.8083000,
                'available_radius_meters' => 1900,
                'expansion_radius_meters' => 3800,
                'is_active' => true,
            ],
        );

        $villages = [
            ['name' => 'Bojonggede', 'latitude' => -6.4406, 'longitude' => 106.8083, 'status' => 'available'],
            ['name' => 'Ragajaya', 'latitude' => -6.4467, 'longitude' => 106.8140, 'status' => 'available'],
            ['name' => 'Waringinjaya', 'latitude' => -6.4328, 'longitude' => 106.8005, 'status' => 'available'],
            ['name' => 'Pabuaran', 'latitude' => -6.4480, 'longitude' => 106.7980, 'status' => 'expansion'],
            ['name' => 'Rawa Panjang', 'latitude' => -6.4210, 'longitude' => 106.8190, 'status' => 'expansion'],
            ['name' => 'Cimanggis', 'latitude' => -6.4780, 'longitude' => 106.8420, 'status' => 'unavailable'],
            ['name' => 'Kedung Waringin', 'latitude' => -6.4080, 'longitude' => 106.7800, 'status' => 'unavailable'],
            ['name' => 'Susukan', 'latitude' => -6.4850, 'longitude' => 106.8050, 'status' => 'unavailable'],
        ];

        foreach ($villages as $village) {
            Village::query()->updateOrCreate(
                ['name' => $village['name'], 'district' => 'Bojonggede'],
                $village,
            );
        }

        $odps = [
            ['code' => 'HUB-BJG-01', 'name' => 'Hub Utama BozonkNet', 'address' => 'Kp. Pos, Bojonggede', 'latitude' => -6.4406, 'longitude' => 106.8083, 'total_ports' => 24, 'used_ports' => 14, 'status' => 'active'],
            ['code' => 'ODP-RGJ-01', 'name' => 'ODP Ragajaya 01', 'address' => 'Ragajaya', 'latitude' => -6.4454, 'longitude' => 106.8132, 'total_ports' => 8, 'used_ports' => 5, 'status' => 'active'],
            ['code' => 'ODP-WRJ-01', 'name' => 'ODP Waringinjaya 01', 'address' => 'Waringinjaya', 'latitude' => -6.4340, 'longitude' => 106.8020, 'total_ports' => 8, 'used_ports' => 3, 'status' => 'active'],
            ['code' => 'ODP-PBR-01', 'name' => 'Rencana ODP Pabuaran', 'address' => 'Pabuaran', 'latitude' => -6.4480, 'longitude' => 106.7980, 'total_ports' => 8, 'used_ports' => 0, 'status' => 'planned'],
        ];

        foreach ($odps as $odp) {
            Odp::query()->updateOrCreate(['code' => $odp['code']], $odp);
        }
    }
}
