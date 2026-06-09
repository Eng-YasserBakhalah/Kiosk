<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\DigitalServiceUser;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_load_profile_context(): void
    {
        $token = $this->loginUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile/me')
            ->assertOk()
            ->assertJsonPath('data.user.username', 'USR10001')
            ->assertJsonPath('data.user.status', 'ACTIVE')
            ->assertJsonPath('data.device.device_code', 'KIOSK-001')
            ->assertJsonPath('data.branch.branch_code', 'BR-001')
            ->assertJsonStructure([
                'data' => [
                    'session' => ['id', 'login_at', 'expires_at', 'status'],
                ],
            ]);
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $token = $this->loginUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/profile/change-password', [
                'current_password' => 'Password1',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Password changed successfully');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PASSWORD_CHANGED',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'device_id' => 'KIOSK-001',
            'username' => 'USR10001',
            'password' => 'NewPassword1',
        ])->assertOk();
    }

    public function test_change_password_rejects_invalid_current_password(): void
    {
        $token = $this->loginUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/profile/change-password', [
                'current_password' => 'WrongPassword1',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'CURRENT_PASSWORD_INVALID');
    }

    public function test_change_password_logs_out_other_active_sessions(): void
    {
        $firstToken = $this->loginUser();

        $secondLogin = $this->postJson('/api/v1/auth/login', [
            'device_id' => 'KIOSK-001',
            'username' => 'USR10001',
            'password' => 'Password1',
        ])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$firstToken)
            ->postJson('/api/v1/profile/change-password', [
                'current_password' => 'Password1',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$secondLogin->json('token'))
            ->getJson('/api/v1/services')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'SESSION_EXPIRED');
    }

    private function loginUser(): string
    {
        $branch = Branch::create([
            'branch_code' => 'BR-001',
            'name' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        TerminalDevice::create([
            'branch_id' => $branch->id,
            'device_code' => 'KIOSK-001',
            'status' => 'ACTIVE',
        ]);

        DigitalServiceUser::create([
            'bank_customer_ref' => 'BANK-100001',
            'username' => 'USR10001',
            'phone_masked' => '+966*******000',
            'password_hash' => Hash::make('Password1'),
            'status' => 'ACTIVE',
            'role' => 'CUSTOMER',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'device_id' => 'KIOSK-001',
            'username' => 'USR10001',
            'password' => 'Password1',
        ])->assertOk();

        return $login->json('token');
    }
}
