<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('jenjang', ['TK', 'SD', 'SMP', 'SMA', 'SMK']);
            $table->string('logo')->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('warna_tema', 20)->default('#1d4ed8');
            $table->text('footer_struk')->nullable();
            $table->string('prefix_nomor_struk', 20)->default('SPP');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
