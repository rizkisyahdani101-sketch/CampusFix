<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_issue_reporters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('issue_id')->unique()->constrained('issues')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('email', 255)->index();
            $table->char('verification_token_hash', 64)->unique();
            $table->timestamp('email_verified_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_issue_reporters');
    }
};
