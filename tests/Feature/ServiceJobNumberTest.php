<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceJobNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_job_uses_the_next_available_number_for_today(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '09123456789',
            'address' => 'Test address',
            'type' => 'Residential',
        ]);

        foreach (range(1, 13) as $sequence) {
            ServiceJob::create([
                'job_number' => 'TM3-'.now()->subDay()->format('ymd').'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'service_type' => 'Inspection',
                'scheduled_at' => now()->addDay(),
                'duration_minutes' => 60,
                'priority' => 'Normal',
                'labor_cost' => 0,
            ]);
        }

        ServiceJob::create([
            'job_number' => 'TM3-'.now()->format('ymd').'-015',
            'customer_id' => $customer->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);

        $response = $this->actingAs($admin)->post('/admin/jobs', [
            'customer_id' => $customer->id,
            'technician_id' => null,
            'service_type' => 'Inspection',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'time_slot' => '08:00',
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('service_jobs', [
            'job_number' => 'TM3-'.now()->format('ymd').'-016',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_customer_booking_uses_the_next_available_number_for_today(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => $user->name,
            'phone' => '09123456789',
            'email' => $user->email,
            'address' => 'Test address',
            'type' => 'Residential',
        ]);

        ServiceJob::create([
            'job_number' => 'TM3-'.now()->format('ymd').'-015',
            'customer_id' => $customer->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);

        $response = $this->actingAs($user)->post('/appointments', [
            'service_type' => 'Inspection',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'time_slot' => '08:00',
            'address' => 'Test address',
            'notes' => 'Please call first',
        ]);

        $response->assertOk()->assertViewIs('portal.confirmation');
        $this->assertDatabaseHas('service_jobs', [
            'job_number' => 'TM3-'.now()->format('ymd').'-016',
            'customer_id' => $customer->id,
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
        ]);
    }
}
