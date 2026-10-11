<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_invoices_can_be_searched_by_customer_name_or_phone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $matchingInvoice = $this->createInvoice('Maria Santos', '09123456789', 'SEARCH-MATCH');
        $otherInvoice = $this->createInvoice('Juan Dela Cruz', '09987654321', 'SEARCH-OTHER');

        $this->actingAs($admin)
            ->get(route('invoices.index', ['search' => 'Maria']))
            ->assertOk()
            ->assertSee($matchingInvoice->invoice_number)
            ->assertDontSee($otherInvoice->invoice_number);

        $this->get(route('invoices.index', ['search' => '09987654321']))
            ->assertOk()
            ->assertSee($otherInvoice->invoice_number)
            ->assertDontSee($matchingInvoice->invoice_number);
    }

    public function test_invoice_edit_records_payment_and_updates_paid_balance_and_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoice('Status Customer', '09111111111', 'STATUS-TEST');
        $invoice->update(['status' => 'Paid']);

        $this->actingAs($admin)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('Unpaid')
            ->assertSee('Record payment');

        $this->actingAs($admin)
            ->patch(route('invoices.update', $invoice), [
                'amount' => 40,
                'method' => 'Cash',
            ])
            ->assertRedirect(route('invoices.index'));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'paid_amount' => 40,
            'status' => 'Partial',
        ]);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 40,
            'method' => 'Cash',
        ]);
        $this->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('₱40.00')
            ->assertSee('₱72.00')
            ->assertSee('Partial');

        $this->patch(route('invoices.update', $invoice), [
            'amount' => 72,
            'method' => 'GCash',
        ])->assertRedirect(route('invoices.index'));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'paid_amount' => 112,
            'status' => 'Paid',
        ]);
        $this->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('₱0.00')
            ->assertSee('Paid');
    }

    public function test_invoice_edit_rejects_payments_above_the_remaining_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoice('Overpayment Customer', '09111111112', 'OVERPAY-TEST');

        $this->actingAs($admin)
            ->patch(route('invoices.update', $invoice), [
                'amount' => 112.01,
                'method' => 'Cash',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'paid_amount' => 0,
            'status' => 'Unpaid',
        ]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_invoice_can_be_deleted_with_its_recorded_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoice('Delete Customer', '09222222222', 'DELETE-TEST');
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 25,
            'method' => 'Cash',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('invoices.delete', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_completed_job_invoice_total_matches_labor_cost_without_extra_charges(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoice('Labor Customer', '09123456789', 'LABOR-UPDATE');
        $job = $invoice->job;
        $job->update(['labor_cost' => 0]);
        $invoice->update(['subtotal' => 0, 'tax' => 0, 'total' => 0]);

        $this->actingAs($admin)
            ->patch('/admin/jobs/'.$job->id, [
                'customer_id' => $job->customer_id,
                'technician_id' => null,
                'service_type' => 'Inspection',
                'priority' => 'Normal',
                'notes' => null,
                'labor_cost' => 500,
                'scheduled_date' => now()->addDays(2)->toDateString(),
                'time_slot' => '08:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'subtotal' => 500,
            'tax' => 0,
            'total' => 500,
        ]);

        $this->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('₱500.00');
    }

    public function test_completing_a_job_creates_invoice_for_labor_cost_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoice('Labor Total Customer', '09123456780', 'LABOR-TOTAL');
        $job = $invoice->job;
        $job->update(['status' => 'In Progress', 'labor_cost' => 3000]);
        $invoice->delete();

        $this->actingAs($admin)
            ->patch(route('jobs.status', $job), ['status' => 'Completed'])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'service_job_id' => $job->id,
            'subtotal' => 3000,
            'tax' => 0,
            'total' => 3000,
        ]);
    }

    private function createInvoice(string $customerName, string $phone, string $invoiceNumber): Invoice
    {
        $customer = Customer::create([
            'name' => $customerName,
            'phone' => $phone,
            'address' => 'Test address',
            'type' => 'Residential',
        ]);
        $job = ServiceJob::create([
            'job_number' => $invoiceNumber.'-JOB',
            'customer_id' => $customer->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
            'status' => 'Completed',
            'priority' => 'Normal',
            'labor_cost' => 100,
        ]);

        return Invoice::create([
            'invoice_number' => $invoiceNumber,
            'service_job_id' => $job->id,
            'subtotal' => 100,
            'tax' => 12,
            'total' => 112,
            'paid_amount' => 0,
            'status' => 'Unpaid',
        ]);
    }
}
