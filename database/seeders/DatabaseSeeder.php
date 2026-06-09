<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchServiceSetting;
use App\Models\DigitalService;
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
                'role' => 'ADMIN',
            ]
        );

        $services = [
            [
                'service_code' => 'ACCOUNTS_LIST',
                'service_name' => 'Accounts List',
                'category' => 'accounts',
                'api_endpoint_key' => 'accounts.list',
            ],
            [
                'service_code' => 'BALANCE_INQUIRY',
                'service_name' => 'Balance Inquiry',
                'category' => 'accounts',
                'api_endpoint_key' => 'accounts.balance',
            ],
            [
                'service_code' => 'SHORT_STATEMENT',
                'service_name' => 'Short Statement',
                'category' => 'accounts',
                'api_endpoint_key' => 'accounts.statement',
            ],
            [
                'service_code' => 'INTERNAL_TRANSFER',
                'service_name' => 'Internal Transfer',
                'category' => 'transfers',
                'api_endpoint_key' => 'transfers.internal',
                'requires_otp' => true,
                'requires_password' => true,
                'min_amount' => 1,
                'max_amount' => 50000,
            ],
            [
                'service_code' => 'MOBILE_TOPUP',
                'service_name' => 'Mobile Top-up',
                'category' => 'payments',
                'api_endpoint_key' => 'payments.mobile_topup',
                'requires_password' => true,
                'min_amount' => 5,
                'max_amount' => 500,
            ],
            [
                'service_code' => 'BILL_PAYMENT',
                'service_name' => 'Bill Payment',
                'category' => 'payments',
                'api_endpoint_key' => 'payments.bill_payment',
                'requires_password' => true,
                'min_amount' => 1,
                'max_amount' => 10000,
            ],
            [
                'service_code' => 'REMITTANCE_INQUIRY',
                'service_name' => 'Remittance Inquiry',
                'category' => 'remittances',
                'api_endpoint_key' => 'remittances.inquiry',
                'requires_password' => true,
            ],
        ];

        $createdServices = [];

        foreach ($services as $serviceData) {
            $service = DigitalService::updateOrCreate(
                ['service_code' => $serviceData['service_code']],
                array_merge([
                    'requires_otp' => false,
                    'requires_password' => false,
                    'requires_biometric' => false,
                    'enabled' => true,
                ], $serviceData)
            );

            $createdServices[] = $service;
        }

        Branch::where('status', 'ACTIVE')->each(function (Branch $activeBranch) use ($createdServices): void {
            foreach ($createdServices as $service) {
                BranchServiceSetting::updateOrCreate(
                    [
                        'branch_id' => $activeBranch->id,
                        'service_id' => $service->id,
                    ],
                    ['enabled' => true]
                );
            }
        });
    }
}
