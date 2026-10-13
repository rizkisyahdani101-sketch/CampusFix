<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            // Laporan yang memiliki lampiran
            $table->foreignId('issue_id')
                ->constrained('issues')
                ->cascadeOnDelete();

            // Nama file asli
            $table->string('original_name');

            // Lokasi penyimpanan file di disk Laravel
            $table->string('file_path');

            // MIME type, misalnya image/jpeg
            $table->string('mime_type', 100)->nullable();

            // Ukuran file dalam byte
            $table->unsignedBigInteger('file_size')->nullable();

            $table->timestamps();

            $table->index('issue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};