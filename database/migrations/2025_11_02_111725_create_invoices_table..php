<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('invoice_number')->unique();
            $table->date('date');
            $table->string('customer_name', 255);

            // Discount fields
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();

            // Calculated totals
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2);         // without VAT
            $table->decimal('vat', 12, 2);
            $table->decimal('total_amount', 12, 2);

            // Optional status (recommended in spec)
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue'])->default('draft');

            $table->timestamps();
            $table->softDeletes(); // keep deleted invoices
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
