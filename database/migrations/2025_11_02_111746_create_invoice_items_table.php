<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('qty');                 // quantity
            $table->string('description', 500);             // item description
            $table->decimal('rate', 12, 2)->default(0);     // per unit
            $table->unsignedBigInteger('rs')->default(0);   // rupees (integer part)
            $table->unsignedTinyInteger('cts')->default(0); // cents 0..99

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
