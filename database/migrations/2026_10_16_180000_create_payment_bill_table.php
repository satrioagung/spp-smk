<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_bill', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->decimal('nominal_bayar', 12, 2);
            $table->timestamps();
            $table->unique(['payment_id', 'bill_id']);
            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_bill');
    }
};
