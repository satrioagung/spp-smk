<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->string('nama', 100);
            $table->string('kode', 20);
            $table->decimal('nominal_default', 12, 2)->default(0);
            $table->enum('tipe', ['bulanan', 'tahunan', 'sekali', 'bebas']);
            $table->boolean('bisa_cicil')->default(false);
            $table->boolean('wajib')->default(true);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['institution_id', 'school_year_id', 'kode'], 'payment_types_institution_year_code_unique');
            $table->index(['institution_id', 'school_year_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_types');
    }
};
