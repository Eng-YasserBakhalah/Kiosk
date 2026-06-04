<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DigitalServiceUser;
use App\Models\TerminalDevice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::updateOrCreate(
            ['branch_code' => 'BR-001'],
            [
                'name' => 'Main Branch',
                'city' => 'Riyadh',
                'address' => 'Main service branch',
                'status' => 'ACTIVE',
            ]
        );

        TerminalDevice::updateOrCreate(
            ['device_code' => 'KIOSK-001'],
            [
                'branch_id' => $branch->id,
                'serial_number' => 'SN-KIOSK-001',
                'location_label' => 'Lobby',
                'status' => 'ACTIVE',
                'kiosk_mode_enabled' => true,
            ]
        );

        DigitalServiceUser::updateOrCreate(
            ['username' => 'USR10001'],
            [
                'bank_customer_ref' => 'BANK-100001',
                'phone_masked' => '+966*******000',
                'password_hash' => Hash::make('Password1'),
                'status' => 'ACTIVE',
            ]
        );
    }
}
