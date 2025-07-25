<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Enums\Role as RoleEnum;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RoleEnum::cases() as $enum) {
            SpatieRole::firstOrCreate(['name' => $enum->value]);
        }
    }
}
