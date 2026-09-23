<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('phone'); $table->string('email')->nullable();
            $table->text('address'); $table->string('type')->default('Residential'); $table->timestamps();
        });
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id(); $table->string('sku')->unique(); $table->string('name'); $table->string('category')->nullable();
            $table->unsignedInteger('stock')->default(0); $table->unsignedInteger('reorder_level')->default(5);
            $table->decimal('unit_price', 12, 2)->default(0); $table->timestamps();
        });
        Schema::create('service_jobs', function (Blueprint $table) {
            $table->id(); $table->string('job_number')->unique(); $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service_type'); $table->dateTime('scheduled_at'); $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('status')->default('Pending'); $table->string('priority')->default('Normal');
            $table->text('notes')->nullable(); $table->decimal('labor_cost', 12, 2)->default(0); $table->timestamps();
        });
        Schema::create('job_parts', function (Blueprint $table) {
            $table->id(); $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2); $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->string('invoice_number')->unique(); $table->foreignId('service_job_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0); $table->decimal('tax', 12, 2)->default(0); $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0); $table->string('status')->default('Unpaid'); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('invoice_id')->constrained()->cascadeOnDelete(); $table->decimal('amount', 12, 2);
            $table->string('method')->default('Cash'); $table->dateTime('paid_at'); $table->string('reference')->nullable(); $table->timestamps();
        });
        Schema::create('commissions', function (Blueprint $table) {
            $table->id(); $table->foreignId('service_job_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete(); $table->decimal('rate', 5, 2)->default(10);
            $table->decimal('amount', 12, 2)->default(0); $table->string('status')->default('Pending'); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('commissions'); Schema::dropIfExists('payments'); Schema::dropIfExists('invoices'); Schema::dropIfExists('job_parts'); Schema::dropIfExists('service_jobs'); Schema::dropIfExists('inventory_items'); Schema::dropIfExists('customers'); }
};
