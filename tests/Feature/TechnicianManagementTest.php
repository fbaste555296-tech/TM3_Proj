<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_delete_service_technicians(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/technicians', [
            'name' => 'Tech One',
            'email' => 'tech1@example.com',
            'phone' => '09123456789',
            'service_types' => ['Repair & Diagnostics', 'Preventive Maintenance'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'tech1@example.com', 'role' => 'technician']);

        $tech = User::where('email', 'tech1@example.com')->first();
        $this->assertSame(['Repair & Diagnostics', 'Preventive Maintenance'], $tech->service_types);

        $deleteResponse = $this->delete('/admin/technicians/' . $tech->id);
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $tech->id]);
    }

    public function test_admin_can_assign_more_than_three_technicians_to_the_same_service_type(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin2@example.com',
        ]);

        $this->actingAs($admin);

        foreach (['Tech One', 'Tech Two', 'Tech Three', 'Tech Four'] as $index => $name) {
            $response = $this->post('/admin/technicians', [
                'name' => $name,
                'email' => 'tech' . ($index + 1) . '@example.com',
                'phone' => '0912345678' . $index,
                'service_types' => ['Repair & Diagnostics'],
            ]);

            $response->assertRedirect();
        }

        $count = User::where('role', 'technician')->whereJsonContains('service_types', 'Repair & Diagnostics')->count();
        $this->assertSame(4, $count);
    }
}
