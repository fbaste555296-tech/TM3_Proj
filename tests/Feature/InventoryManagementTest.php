<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\JobPart;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_inventory_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post('/admin/inventory', [
            ...$this->itemDetails(),
            'sku' => 'USER-PROVIDED-SKU',
        ])
            ->assertRedirect(route('inventory.index'));

        $item = InventoryItem::where('name', 'Air filter')->firstOrFail();
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'sku' => 'SKU-'.str_pad((string) $item->id, 6, '0', STR_PAD_LEFT),
            'name' => 'Air filter',
        ]);

        $this->patch('/admin/inventory/'.$item->id, [
            ...$this->itemDetails(),
            'sku' => 'CHANGED-BY-USER',
            'name' => 'Updated air filter',
            'stock' => 12,
        ])->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'sku' => 'SKU-'.str_pad((string) $item->id, 6, '0', STR_PAD_LEFT),
            'name' => 'Updated air filter',
            'stock' => 12,
        ]);

        $this->delete('/admin/inventory/'.$item->id)
            ->assertRedirect(route('inventory.index'));
        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    }

    public function test_inventory_search_matches_item_name_sku_and_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $matchingItem = InventoryItem::create([...$this->itemDetails(), 'sku' => 'SKU-SEARCH-001']);
        InventoryItem::create([
            ...$this->itemDetails(),
            'sku' => 'SKU-SEARCH-002',
            'name' => 'Compressor',
            'category' => 'Motors',
        ]);

        $this->actingAs($admin)
            ->get('/admin/inventory?search=Air%20filter')
            ->assertOk()
            ->assertSee($matchingItem->sku)
            ->assertDontSee('CMP-202');

        $this        ->get('/admin/inventory?search=SKU-SEARCH-001')
            ->assertOk()
            ->assertSee($matchingItem->name)
            ->assertDontSee('Compressor');

        $this->get('/admin/inventory?search=Electronic')
            ->assertOk()
            ->assertSee($matchingItem->name)
            ->assertDontSee('Compressor');
    }

    public function test_inventory_category_selection_has_only_the_supported_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/inventory')
            ->assertOk()
            ->assertSee('Select a category')
            ->assertSee('Electronic')
            ->assertSee('Accessory')
            ->assertSee('Pipe')
            ->assertSee('Mechanical')
            ->assertDontSee('name="category" value=', false);
    }

    public function test_inventory_rejects_categories_outside_the_supported_options(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/admin/inventory', [...$this->itemDetails(), 'category' => 'Filters'])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('inventory_items', 0);
    }

    public function test_inventory_can_be_filtered_by_availability(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        InventoryItem::create($this->itemDetails());
        InventoryItem::create([
            ...$this->itemDetails(),
            'sku' => 'LOW-101',
            'name' => 'Low stock item',
            'stock' => 2,
        ]);
        InventoryItem::create([
            ...$this->itemDetails(),
            'sku' => 'OUT-101',
            'name' => 'Out of stock item',
            'stock' => 0,
        ]);

        $this->actingAs($admin)
            ->get('/admin/inventory?availability=available')
            ->assertOk()
            ->assertSee('Air filter')
            ->assertSee('class="inventory-availability inventory-availability-available"', false)
            ->assertSee('>Available</span>', false)
            ->assertDontSee('Low stock item')
            ->assertDontSee('Out of stock item');

        $this->get('/admin/inventory?availability=low')
            ->assertOk()
            ->assertSee('Low stock item')
            ->assertSee('inventory-low-stock-row', false)
            ->assertSee('inventory-availability-low', false)
            ->assertSee('>Low stock</span>', false)
            ->assertDontSee('Air filter')
            ->assertDontSee('Out of stock item');

        $this->get('/admin/inventory?availability=out')
            ->assertOk()
            ->assertSee('Out of stock item')
            ->assertSee('inventory-availability-out', false)
            ->assertSee('>Out of stock</span>', false)
            ->assertDontSee('Air filter')
            ->assertDontSee('Low stock item');
    }

    public function test_global_job_filters_are_not_shown_or_applied_on_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $customer = Customer::create([
            'name' => 'Inventory Filter Customer',
            'phone' => '09123456789',
            'address' => 'Inventory Filter Address',
            'type' => 'Residential',
        ]);
        $job = ServiceJob::create([
            'job_number' => 'INVENTORY-FILTER-JOB',
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now(),
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
            'status' => 'Pending',
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);
        $usedItem = InventoryItem::create([
            ...$this->itemDetails(),
            'sku' => 'SKU-FILTER-USED',
            'name' => 'Job-linked filter item',
        ]);
        JobPart::create([
            'service_job_id' => $job->id,
            'inventory_item_id' => $usedItem->id,
            'quantity' => 1,
            'unit_price' => 10,
        ]);
        InventoryItem::create([
            ...$this->itemDetails(),
            'sku' => 'SKU-FILTER-UNUSED',
            'name' => 'Standalone filter item',
        ]);

        $this->actingAs($admin)
            ->get(route('inventory.index', [
                'range' => 'custom',
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'technician_id' => $technician->id,
            ]))
            ->assertOk()
            ->assertSee('Job-linked filter item')
            ->assertSee('Standalone filter item')
            ->assertDontSee('Date range')
            ->assertDontSee('Branch / Location');
    }

    public function test_inventory_item_used_by_a_job_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '09123456789',
            'address' => 'Test address',
            'type' => 'Residential',
        ]);
        $job = ServiceJob::create([
            'job_number' => 'TEST-INVENTORY-JOB',
            'customer_id' => $customer->id,
            'service_type' => 'Inspection',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'status' => 'Pending',
            'priority' => 'Normal',
            'labor_cost' => 0,
        ]);
        $item = InventoryItem::create($this->itemDetails());
        $jobPart = JobPart::create([
            'service_job_id' => $job->id,
            'inventory_item_id' => $item->id,
            'quantity' => 1,
            'unit_price' => $item->unit_price,
        ]);

        $this->actingAs($admin)
            ->delete('/admin/inventory/'.$item->id)
            ->assertSessionHasErrors('inventory');

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);
        $this->assertDatabaseHas('job_parts', ['id' => $jobPart->id, 'inventory_item_id' => $item->id]);
    }

    private function itemDetails(): array
    {
        return [
            'sku' => 'FLT-101',
            'name' => 'Air filter',
            'category' => 'Electronic',
            'stock' => 20,
            'reorder_level' => 5,
            'unit_price' => 125.50,
        ];
    }
}
