<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceJobScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_jobs_accept_only_the_fixed_two_hour_slots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = $this->createCustomer();
        $date = now()->addDays(2)->toDateString();

        foreach (array_keys(ServiceJob::TIME_SLOTS) as $slot) {
            $this->actingAs($admin)->post('/admin/jobs', $this->jobDetails($customer, $date, $slot))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('service_jobs', 4);
        $this->assertSame(
            4,
            ServiceJob::where('duration_minutes', ServiceJob::SLOT_DURATION_MINUTES)->count()
        );

        $this->actingAs($admin)
            ->post('/admin/jobs', $this->jobDetails($customer, $date, '12:00'))
            ->assertSessionHasErrors('time_slot');
        $this->assertDatabaseCount('service_jobs', 4);
    }

    public function test_customer_appointments_accept_only_the_fixed_two_hour_slots(): void
    {
        $user = User::factory()->create();
        $this->createCustomer($user);
        $date = now()->addDays(2)->toDateString();

        foreach (array_keys(ServiceJob::TIME_SLOTS) as $slot) {
            $this->actingAs($user)->post('/appointments', [
                'service_type' => 'Inspection',
                'scheduled_date' => $date,
                'time_slot' => $slot,
                'address' => 'Test address',
            ])->assertOk()->assertViewIs('portal.confirmation');
        }

        $this->assertDatabaseCount('service_jobs', 4);
        $this->assertSame(
            4,
            ServiceJob::where('duration_minutes', ServiceJob::SLOT_DURATION_MINUTES)->count()
        );

        $this->actingAs($user)->post('/appointments', [
            'service_type' => 'Inspection',
            'scheduled_date' => $date,
            'time_slot' => '08:00',
            'address' => 'Test address',
        ])->assertSessionHasErrors([
            'scheduled_date' => 'This day is fully booked. Please choose another available date.',
        ]);
        $this->assertDatabaseCount('service_jobs', 4);
    }

    public function test_customer_cannot_book_a_time_slot_that_is_already_taken(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user);
        $date = now()->addDays(2)->toDateString();
        ServiceJob::create([
            'job_number' => 'BOOKED-SLOT-JOB',
            'customer_id' => $customer->id,
            'service_type' => 'Inspection',
            'scheduled_at' => $date.' 08:00:00',
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
            'status' => 'Pending',
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);

        $this->actingAs($user)->post('/appointments', [
            'service_type' => 'Inspection',
            'scheduled_date' => $date,
            'time_slot' => '08:00',
            'address' => 'Test address',
        ])->assertSessionHasErrors([
            'time_slot' => 'This time slot is already booked. Please choose another available time or date.',
        ]);
        $this->assertDatabaseCount('service_jobs', 1);
    }

    public function test_admin_job_editor_shows_fixed_slots_without_a_duration_field(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/jobs');

        $response->assertOk()
            ->assertSee('8:00 AM - 10:00 AM')
            ->assertSee('10:00 AM - 12:00 PM')
            ->assertSee('1:00 PM - 3:00 PM')
            ->assertSee('3:00 PM - 5:00 PM')
            ->assertDontSee('Duration (min)');
    }

    private function createCustomer(?User $user = null): Customer
    {
        return Customer::create([
            'name' => $user?->name ?? 'Test Customer',
            'phone' => '09123456789',
            'email' => $user?->email,
            'address' => 'Test address',
            'type' => 'Residential',
        ]);
    }

    private function jobDetails(Customer $customer, string $date, string $slot): array
    {
        return [
            'customer_id' => $customer->id,
            'service_type' => 'Inspection',
            'scheduled_date' => $date,
            'time_slot' => $slot,
            'priority' => 'Normal',
            'labor_cost' => 0,
        ];
    }
}
