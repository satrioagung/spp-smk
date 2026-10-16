<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->string('nama_kelas', 100);
            $table->enum('tingkat', ['X', 'XI', 'XII']);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['institution_id', 'school_year_id', 'nama_kelas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
