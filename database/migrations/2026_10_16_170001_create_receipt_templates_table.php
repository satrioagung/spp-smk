<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('nama_template', 100);
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_qr')->default(false);
            $table->enum('ukuran', ['a4', 'thermal58', 'thermal80'])->default('a4');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['institution_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_templates');
    }
};
