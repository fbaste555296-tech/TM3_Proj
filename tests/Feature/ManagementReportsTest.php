<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\JobPart;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_filter_by_custom_date_technician_location_and_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician', 'name' => 'Report Technician']);
        $otherTechnician = User::factory()->create(['role' => 'technician', 'name' => 'Other Technician']);
        $customer = $this->createCustomer('Report Location');
        $otherCustomer = $this->createCustomer('Other Location');
        $completedJob = $this->createJob($customer, $technician, 'REPORT-MATCH', 'Completed', now()->subDays(2));
        $this->createInvoiceAndParts($completedJob);
        $this->createJob($otherCustomer, $otherTechnician, 'REPORT-OTHER', 'Pending', now()->subDays(2));
        $this->createJob($customer, $technician, 'REPORT-OLD', 'Completed', now()->subMonths(2));

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'range' => 'custom',
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->toDateString(),
            'technician_id' => $technician->id,
            'location' => 'Report Location',
            'status' => 'Completed',
        ]));

        $response->assertOk()
            ->assertSee('REPORT-MATCH')
            ->assertDontSee('REPORT-OTHER')
            ->assertDontSee('REPORT-OLD')
            ->assertSee('Report Location')
            ->assertSee('₱150.00')
            ->assertSee('Report Technician')
            ->assertSee('Export CSV')
            ->assertSee('inline-flex items-center rounded-lg bg-emerald-700', false)
            ->assertDontSee('Revenue mix (labor vs. parts)')
            ->assertSee('Service category breakdown')
            ->assertDontSee('Service &amp; product mix distribution')
            ->assertDontSee('Accessories / parts sales mix')
            ->assertDontSee('No parts recorded.')
            ->assertSee('Export PDF');
    }

    public function test_date_range_presets_and_filter_dropdowns_are_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createCustomer('Preset Location');
        User::factory()->create(['role' => 'technician', 'name' => 'Preset Technician']);

        $response = $this->actingAs($admin)->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('Today')
            ->assertSee('This Week')
            ->assertSee('This Month')
            ->assertSee('Custom Range')
            ->assertSee('Technician')
            ->assertSee('Branch / Location')
            ->assertSee('Job status')
            ->assertSee('Preset Technician')
            ->assertSee('Preset Location');
    }

    public function test_reports_can_be_exported_as_filtered_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician', 'name' => 'CSV Technician']);
        $customer = $this->createCustomer('CSV Location');
        $job = $this->createJob($customer, $technician, 'REPORT-CSV', 'Completed', now()->subDay());
        $this->createInvoiceAndParts($job);

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'range' => 'custom',
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->toDateString(),
            'technician_id' => $technician->id,
            'location' => 'CSV Location',
            'status' => 'Completed',
            'export' => 'csv',
        ]));

        $response->assertDownload('management-report.csv');
    }

    public function test_reports_support_all_dates_without_invalid_date_labels(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $customer = $this->createCustomer('Historical Location');
        $this->createJob($customer, $technician, 'REPORT-HISTORICAL', 'Completed', now()->subYears(2));

        $this->actingAs($admin)
            ->get(route('reports.index', ['range' => 'all']))
            ->assertOk()
            ->assertSee('All dates')
            ->assertSee('Total completed jobs')
            ->assertSee('1');
    }

    public function test_commission_ledger_entries_can_be_edited_and_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $customer = $this->createCustomer('Ledger Location');
        $job = $this->createJob($customer, $technician, 'REPORT-LEDGER', 'Completed', now());
        $this->createInvoiceAndParts($job);
        $commission = Commission::where('service_job_id', $job->id)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('reports.index', ['range' => 'all']))
            ->assertOk()
            ->assertSee('Edit commission entry')
            ->assertSee('commission-status-pending', false)
            ->assertSee('color: #92400e !important', false)
            ->assertSee('Pending')
            ->assertSee('Delete');

        $this->patch(route('commissions.update', ['commission' => $commission->id, 'range' => 'all']), [
            'rate' => 12.5,
            'amount' => 18.75,
            'status' => 'Paid',
        ])->assertRedirect(route('reports.index', ['range' => 'all']));

        $this->assertDatabaseHas('commissions', [
            'id' => $commission->id,
            'rate' => 12.5,
            'amount' => 18.75,
            'status' => 'Paid',
        ]);

        $this->delete(route('commissions.delete', ['commission' => $commission->id, 'range' => 'all']))
            ->assertRedirect(route('reports.index', ['range' => 'all']));

        $this->assertDatabaseMissing('commissions', ['id' => $commission->id]);
    }

    private function createCustomer(string $address): Customer
    {
        static $number = 0;
        $number++;

        return Customer::create([
            'name' => 'Report Customer '.$number,
            'phone' => '0912345678'.$number,
            'address' => $address,
            'type' => 'Residential',
        ]);
    }

    private function createJob(Customer $customer, User $technician, string $number, string $status, $scheduledAt): ServiceJob
    {
        return ServiceJob::create([
            'job_number' => $number,
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'service_type' => 'Repair & Diagnostics',
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
            'status' => $status,
            'priority' => 'Normal',
            'labor_cost' => 100,
        ]);
    }

    private function createInvoiceAndParts(ServiceJob $job): void
    {
        Invoice::create([
            'invoice_number' => 'INV-'.$job->job_number,
            'service_job_id' => $job->id,
            'subtotal' => 150,
            'tax' => 18,
            'total' => 168,
            'paid_amount' => 168,
            'status' => 'Paid',
        ]);
        $item = InventoryItem::create([
            'sku' => 'SKU-'.str_pad((string) $job->id, 6, '0', STR_PAD_LEFT),
            'name' => 'Replacement part',
            'category' => 'Accessories',
            'stock' => 5,
            'reorder_level' => 1,
            'unit_price' => 50,
        ]);
        JobPart::create([
            'service_job_id' => $job->id,
            'inventory_item_id' => $item->id,
            'quantity' => 1,
            'unit_price' => 50,
        ]);
        Commission::create([
            'service_job_id' => $job->id,
            'technician_id' => $job->technician_id,
            'rate' => 10,
            'amount' => 15,
            'status' => 'Pending',
        ]);
    }
}
