<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class BillingExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_page_offers_excel_export_and_downloads_an_xlsx_workbook(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $otherTechnician = User::factory()->create(['role' => 'technician']);
        $customer = Customer::create([
            'name' => 'Excel Export Customer',
            'phone' => '09123456789',
            'address' => 'Export address',
            'type' => 'Residential',
        ]);
        $job = ServiceJob::create([
            'job_number' => 'EXCEL-EXPORT-JOB',
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
            'status' => 'Completed',
            'priority' => 'Normal',
            'notes' => 'Excel export test issue',
            'labor_cost' => 100,
        ]);
        Invoice::create([
            'invoice_number' => 'EXCEL-EXPORT-INVOICE',
            'service_job_id' => $job->id,
            'subtotal' => 100,
            'tax' => 12,
            'total' => 112,
            'paid_amount' => 50,
            'status' => 'Partial',
        ]);
        $otherJob = ServiceJob::create([
            'job_number' => 'EXCEL-OTHER-JOB',
            'customer_id' => $customer->id,
            'technician_id' => $otherTechnician->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
            'status' => 'Completed',
            'priority' => 'Normal',
            'labor_cost' => 80,
        ]);
        Invoice::create([
            'invoice_number' => 'EXCEL-OTHER-INVOICE',
            'service_job_id' => $otherJob->id,
            'subtotal' => 80,
            'tax' => 9.6,
            'total' => 89.6,
            'paid_amount' => 0,
            'status' => 'Pending',
        ]);

        $legacyFilters = ['range' => 'all', 'technician_id' => $technician->id];
        $this->actingAs($admin)
            ->get(route('invoices.index', $legacyFilters))
            ->assertOk()
            ->assertSee('Export Excel')
            ->assertSee(route('invoices.export'))
            ->assertSee('EXCEL-EXPORT-INVOICE')
            ->assertSee('EXCEL-OTHER-INVOICE')
            ->assertDontSee('Date range')
            ->assertDontSee('Branch / Location');

        $response = $this->get(route('invoices.export', $legacyFilters));
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition', 'attachment; filename="billing-report.xlsx"');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'billing-export-test-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        try {
            $workbook = new ZipArchive;
            $this->assertTrue($workbook->open($temporaryFile) === true);
            $sheet = $workbook->getFromName('xl/worksheets/sheet1.xml');
            $workbook->close();

            $this->assertIsString($sheet);
            $this->assertStringContainsString('EXCEL-EXPORT-INVOICE', $sheet);
            $this->assertStringContainsString('EXCEL-OTHER-INVOICE', $sheet);
            $this->assertStringContainsString('Excel Export Customer', $sheet);
            $this->assertStringContainsString('Excel export test issue', $sheet);
            $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        } finally {
            unlink($temporaryFile);
        }
    }
}
