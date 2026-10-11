<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_navigation_has_one_generic_sign_in_link(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sign In')
            ->assertDontSee('Admin Login')
            ->assertDontSee('Customer Sign In');
    }

    public function test_sign_in_page_has_generic_heading(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<h1>Sign In</h1>', false)
            ->assertDontSee('Admin login')
            ->assertDontSee('Customer sign in');
    }

    public function test_management_user_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'Password123!',
        ]);

        $this->from(route('customers.index'))
            ->post(route('login'), [
                'email' => $admin->email,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('dashboard'));
    }

    public function test_customer_is_redirected_to_customer_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'password' => 'Password123!',
        ]);

        $this->from(route('dashboard'))
            ->post(route('login'), [
                'email' => $customer->email,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('customer.dashboard'));
    }
}
