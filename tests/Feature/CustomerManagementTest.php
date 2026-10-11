<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_customers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post('/admin/customers', [...$this->customerDetails(), 'password' => 'CustomerPass123!'])
            ->assertRedirect(route('customers.index'));

        $customer = Customer::where('email', 'first@example.com')->firstOrFail();
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'First Customer',
        ]);

        $this->patch('/admin/customers/'.$customer->id, [
            ...$this->customerDetails(),
            'name' => 'Updated Customer',
            'email' => 'updated@example.com',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
            'email' => 'updated@example.com',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'updated@example.com',
            'name' => 'Updated Customer',
            'role' => 'customer',
        ]);
    }

    public function test_admin_must_enter_a_password_for_the_customer_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $password = 'CustomerPass123!';

        $response = $this->actingAs($admin)
            ->post('/admin/customers', [...$this->customerDetails(), 'password' => $password]);

        $response->assertRedirect(route('customers.index'))
            ->assertSessionHas('customer_account_created', true)
            ->assertSessionMissing('customer_credentials');

        $customerUser = User::where('email', 'first@example.com')->firstOrFail();
        $this->assertSame('customer', $customerUser->role);
        $this->assertTrue(Hash::check($password, $customerUser->password));
        $this->assertDatabaseHas('customers', [
            'email' => 'first@example.com',
            'name' => 'First Customer',
        ]);

        $this->post('/logout');
        $this->post('/login', [
            'email' => 'first@example.com',
            'password' => $password,
        ])->assertRedirect(route('customer.dashboard'));
    }

    public function test_admin_must_provide_a_password_when_creating_a_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/admin/customers', $this->customerDetails())
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseMissing('users', ['email' => 'first@example.com']);
    }

    public function test_admin_cannot_create_a_customer_login_with_a_management_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'first@example.com',
        ]);

        $this->actingAs($admin)
            ->post('/admin/customers', $this->customerDetails())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_admin_can_search_customers_by_name_contact_or_address(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $matchingCustomer = Customer::create([
            ...$this->customerDetails(),
            'name' => 'Searchable Customer',
            'phone' => '555-0101',
            'address' => 'Searchable Street',
        ]);
        Customer::create([
            ...$this->customerDetails(),
            'name' => 'Different Customer',
            'email' => 'other@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/customers?search=Searchable')
            ->assertOk()
            ->assertSee($matchingCustomer->name)
            ->assertDontSee('Different Customer');

        $this->get('/admin/customers?search=555-0101')
            ->assertOk()
            ->assertSee($matchingCustomer->name)
            ->assertDontSee('Different Customer');

        $this->get('/admin/customers?search=Searchable%20Street')
            ->assertOk()
            ->assertSee($matchingCustomer->name)
            ->assertDontSee('Different Customer');
    }

    public function test_global_job_filters_are_not_shown_or_applied_on_customers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $matchingCustomer = Customer::create([
            ...$this->customerDetails(),
            'name' => 'Filtered Customer',
        ]);
        $otherCustomer = Customer::create([
            ...$this->customerDetails(),
            'name' => 'Unfiltered Customer',
            'email' => 'other@example.com',
        ]);
        ServiceJob::create([
            'job_number' => 'FILTERED-CUSTOMER-JOB',
            'customer_id' => $matchingCustomer->id,
            'technician_id' => $technician->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now(),
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
            'status' => 'Pending',
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);
        ServiceJob::create([
            'job_number' => 'UNFILTERED-CUSTOMER-JOB',
            'customer_id' => $otherCustomer->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now(),
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
            'status' => 'Pending',
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('customers.index', [
                'range' => 'custom',
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'technician_id' => $technician->id,
            ]))
            ->assertOk()
            ->assertSee('Filtered Customer')
            ->assertSee('Unfiltered Customer')
            ->assertDontSee('Date range')
            ->assertDontSee('Branch / Location');
    }

    public function test_admin_can_delete_a_customer_without_service_jobs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create($this->customerDetails());

        $response = $this->actingAs($admin)
            ->followingRedirects()
            ->delete('/admin/customers/'.$customer->id)
            ->assertOk()
            ->assertSee('Customer deleted.');

        $this->assertSame(1, substr_count($response->getContent(), 'Customer deleted.'));

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_customer_with_service_jobs_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create($this->customerDetails());
        $job = ServiceJob::create([
            'job_number' => 'TEST-CUSTOMER-JOB',
            'customer_id' => $customer->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'status' => 'Pending',
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);

        $this->actingAs($admin)
            ->delete('/admin/customers/'.$customer->id)
            ->assertSessionHasErrors('customer');

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('service_jobs', ['id' => $job->id, 'customer_id' => $customer->id]);
    }

    private function customerDetails(): array
    {
        return [
            'name' => 'First Customer',
            'phone' => '09123456789',
            'email' => 'first@example.com',
            'address' => '123 Test Street',
            'type' => 'Residential',
        ];
    }
}
