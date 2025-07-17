<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->decimal('total', 10, 2);
            $table->date('sale_date');
            $table->time('sale_time');
            $table->string('payment_method')->default('dinheiro')->after('total');
            $table->decimal('amount_received', 10, 2)->default(0.00)->after('payment_method');
            $table->decimal('change_amount', 10, 2)->default(0.00)->after('amount_received');
            $table->string('customer_name')->nullable()->after('owner_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
