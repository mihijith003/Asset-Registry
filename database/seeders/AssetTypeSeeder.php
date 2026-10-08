<?php

namespace Database\Seeders;

use App\Models\AssetType;
use Illuminate\Database\Seeder;

class AssetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['type_name' => 'IT Equipment', 'type_code' => 'IT'],
            ['type_name' => 'Office Furniture', 'type_code' => 'FURN'],
            ['type_name' => 'Motor Vehicles', 'type_code' => 'VEH'],
            ['type_name' => 'Machinery & Tools', 'type_code' => 'MACH'],
            ['type_name' => 'Electrical Appliances', 'type_code' => 'ELEC'],
        ];

        foreach ($types as $type) {
            AssetType::firstOrCreate(
                ['type_code' => $type['type_code']],
                ['type_name' => $type['type_name']]
            );
        }
    }
}

