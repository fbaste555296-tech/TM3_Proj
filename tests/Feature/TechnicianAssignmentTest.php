<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_with_a_pending_job_cannot_be_assigned_to_another_job(): void
    {
        [$admin, $customer, $technician] = $this->createAssignmentFixtures();
        $this->createJob($customer, $technician);

        $response = $this->actingAs($admin)->post('/admin/jobs', $this->jobDetails($customer, $technician));

        $response->assertSessionHasErrors([
            'technician_id' => 'This technician is busy doing another job. Choose an available technician or assign later.',
        ]);
        $this->assertDatabaseCount('service_jobs', 1);
    }

    public function test_a_technician_can_be_assigned_after_the_previous_job_is_completed(): void
    {
        [$admin, $customer, $technician] = $this->createAssignmentFixtures();
        $previousJob = $this->createJob($customer, $technician);
        $previousJob->update(['status' => 'Completed']);

        $response = $this->actingAs($admin)->post('/admin/jobs', $this->jobDetails($customer, $technician));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('service_jobs', 2);
    }

    public function test_a_technician_cannot_be_assigned_to_a_service_they_are_not_qualified_for(): void
    {
        [$admin, $customer, $technician] = $this->createAssignmentFixtures();
        $details = $this->jobDetails($customer, $technician);
        $details['service_type'] = 'Repair & Diagnostics';

        $this->actingAs($admin)
            ->post('/admin/jobs', $details)
            ->assertSessionHasErrors([
                'technician_id' => 'Choose a technician qualified for the selected service type.',
            ]);

        $this->assertDatabaseCount('service_jobs', 0);
    }

    public function test_technician_options_include_their_qualified_service_types(): void
    {
        [$admin] = $this->createAssignmentFixtures();
        User::factory()->create([
            'role' => 'technician',
            'name' => 'Repair Technician',
            'service_types' => ['Repair & Diagnostics'],
        ]);

        $this->actingAs($admin)
            ->get('/admin/jobs')
            ->assertOk()
            ->assertSee('data-service-types="[&quot;Inspection&quot;]"', false)
            ->assertSee('data-service-types="[&quot;Repair &amp; Diagnostics&quot;]"', false);
    }

    public function test_a_job_can_be_updated_without_conflicting_with_its_own_technician_assignment(): void
    {
        [$admin, $customer, $technician] = $this->createAssignmentFixtures();
        $job = $this->createJob($customer, $technician);
        $details = $this->jobDetails($customer, $technician);
        $details['scheduled_date'] = now()->addDays(3)->toDateString();

        $response = $this->actingAs($admin)->patch('/admin/jobs/'.$job->id, $details);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('service_jobs', [
            'id' => $job->id,
            'technician_id' => $technician->id,
            'scheduled_at' => $details['scheduled_date'].' 08:00:00',
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
        ]);
    }

    public function test_a_busy_technician_cannot_be_assigned_when_updating_a_job(): void
    {
        [$admin, $customer, $technician] = $this->createAssignmentFixtures();
        $anotherTechnician = User::factory()->create([
            'role' => 'technician',
            'service_types' => ['Inspection'],
        ]);
        $jobToUpdate = $this->createJob($customer, $technician);
        $this->createJob($customer, $anotherTechnician);
        $details = $this->jobDetails($customer, $anotherTechnician);

        $response = $this->actingAs($admin)->patch('/admin/jobs/'.$jobToUpdate->id, $details);

        $response->assertSessionHasErrors('technician_id');
        $this->assertDatabaseHas('service_jobs', [
            'id' => $jobToUpdate->id,
            'technician_id' => $technician->id,
        ]);
    }

    public function test_busy_technicians_are_shown_as_busy_and_disabled_in_the_assignment_form(): void
    {
        [$admin, $customer, $technician] = $this->createAssignmentFixtures();
        $this->createJob($customer, $technician);

        $response = $this->actingAs($admin)->get('/admin/jobs');

        $response->assertOk()
            ->assertSee('Busy — doing another job')
            ->assertSee('data-busy="true"', false)
            ->assertSee('disabled', false);
    }

    public function test_service_job_customer_picker_is_available_without_a_separate_search_field(): void
    {
        [$admin, $customer] = $this->createAssignmentFixtures();

        $this->actingAs($admin)
            ->get('/admin/jobs')
            ->assertOk()
            ->assertSee('id="jobCustomerPicker"', false)
            ->assertSee($customer->name)
            ->assertDontSee('Search customer');
    }

    public function test_service_jobs_can_be_searched_by_customer_or_technician_name(): void
    {
        [$admin, $customer, $technician] = $this->createAssignmentFixtures();
        $matchingJob = $this->createJob($customer, $technician);

        $otherTechnician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Another Tech',
        ]);
        $otherCustomer = Customer::create([
            'name' => 'Another Customer',
            'phone' => '09987654321',
            'address' => 'Another address',
            'type' => 'Residential',
        ]);
        $otherJob = $this->createJob($otherCustomer, $otherTechnician);

        $customerSearch = $this->actingAs($admin)->get('/admin/jobs?search=Test%20Customer');
        $customerSearch->assertOk()
            ->assertSee($matchingJob->job_number)
            ->assertDontSee($otherJob->job_number);

        $technicianSearch = $this->get('/admin/jobs?search=Tech%20Search%20Match');
        $technicianSearch->assertOk()
            ->assertSee($matchingJob->job_number)
            ->assertDontSee($otherJob->job_number);
    }

    private function createAssignmentFixtures(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create([
            'role' => 'technician',
            'name' => 'Tech Search Match',
            'service_types' => ['Inspection'],
        ]);
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '09123456789',
            'address' => 'Test address',
            'type' => 'Residential',
        ]);

        return [$admin, $customer, $technician];
    }

    private function createJob(Customer $customer, User $technician): ServiceJob
    {
        return ServiceJob::create([
            'job_number' => 'TEST-'.uniqid(),
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'status' => 'Pending',
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);
    }

    private function jobDetails(Customer $customer, User $technician): array
    {
        return [
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'service_type' => 'Inspection',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'time_slot' => '08:00',
            'priority' => 'Normal',
            'labor_cost' => 0,
        ];
    }
}
