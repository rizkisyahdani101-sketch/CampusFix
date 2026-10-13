<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();

            // Identitas laporan
            $table->string('title', 150);
            $table->text('description');

            // Relasi kategori dan lokasi
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('location_id')
                ->constrained('locations')
                ->restrictOnDelete();

            // Status awal laporan
            $table->string('status', 30)->default('reported');

            // Informasi kondisi laporan
            $table->boolean('safety_flag')->default(false);
            $table->boolean('class_blocked')->default(false);

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
