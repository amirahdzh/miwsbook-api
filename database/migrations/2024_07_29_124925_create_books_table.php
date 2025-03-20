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
        Schema::create('books', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('image')->nullable();
            $table->integer('stock')->default(1); // Stok fisik
            $table->year('year')->nullable(); // Tahun terbit
            $table->string('isbn')->nullable()->unique(); // Nomor ISBN
            $table->timestamps();
            // $table->softDeletes(); // Soft delete agar tidak benar-benar terhapus
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
