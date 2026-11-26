<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create([
            'username' => 'admin.uji',
            'email' => 'admin-uji@contoh.go.id',
            'password' => Hash::make('sandi-uji-123'),
            'role' => 'admin',
        ]);
    }

    public function test_phone_keyboard_capital_letter_and_trailing_space_still_log_in(): void
    {
        $this->user();

        Livewire::test(Login::class)
            ->set('login', ' Admin.Uji ')
            ->set('password', 'sandi-uji-123')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_email_is_matched_without_regard_to_case(): void
    {
        $this->user();

        Livewire::test(Login::class)
            ->set('login', 'Admin-Uji@Contoh.go.id')
            ->set('password', 'sandi-uji-123')
            ->call('authenticate')
            ->assertRedirect(route('dashboard'));
    }

    public function test_wrong_password_is_still_rejected(): void
    {
        $this->user();

        Livewire::test(Login::class)
            ->set('login', 'admin.uji')
            ->set('password', 'salah')
            ->call('authenticate')
            ->assertHasErrors('login');

        $this->assertGuest();
    }
}
