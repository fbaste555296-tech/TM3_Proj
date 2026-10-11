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

    public function test_invoice_status_can_be_updated_to_paid_unpaid_or_pending(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoice('Status Customer', '09111111111', 'STATUS-TEST');

        foreach (['Paid', 'Unpaid'] as $status) {
            $this->actingAs($admin)
                ->patch(route('invoices.update', $invoice), ['status' => $status])
                ->assertRedirect(route('invoices.index'));

            $this->assertDatabaseHas('invoices', [
                'id' => $invoice->id,
                'status' => $status,
            ]);
        }

        $this->patch(route('invoices.update', $invoice), ['status' => 'Pending'])
            ->assertSessionHasErrors('status');
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
