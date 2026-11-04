<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('invoice_no')->unique();
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('restrict');
            $table->enum('payment_method', ['CASH', 'QRIS']);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('cash_received', 12, 2)->nullable();
            $table->decimal('change', 12, 2)->nullable();
            $table->string('qris_proof_url')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['COMPLETED', 'VOID', 'DELETED'])->default('COMPLETED');
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};