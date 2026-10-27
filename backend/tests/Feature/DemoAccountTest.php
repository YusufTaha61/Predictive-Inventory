<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountTest extends TestCase
{
    use RefreshDatabase;

    private function demoUser(): User
    {
        return User::factory()->create([
            'email' => config('demo.email'),
            'password' => Hash::make(config('demo.password')),
        ]);
    }

    public function test_demo_account_cannot_be_deleted(): void
    {
        $user = $this->demoUser();

        $this->actingAs($user)
            ->delete('/profile', ['password' => config('demo.password')])
            ->assertForbidden();

        $this->assertNotNull($user->fresh());
    }

    public function test_demo_password_cannot_be_changed(): void
    {
        $user = $this->demoUser();

        $this->actingAs($user)
            ->put('/password', [
                'current_password' => config('demo.password'),
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertForbidden();

        $this->assertTrue(Hash::check(config('demo.password'), $user->fresh()->password));
    }

    public function test_demo_profile_cannot_be_updated(): void
    {
        $user = $this->demoUser();

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'Değişti', 'email' => 'baska@example.com'])
            ->assertForbidden();

        $this->assertSame(config('demo.email'), $user->fresh()->email);
    }

    public function test_normal_user_is_not_demo(): void
    {
        $this->assertFalse(User::factory()->create()->isDemo());
    }
}
