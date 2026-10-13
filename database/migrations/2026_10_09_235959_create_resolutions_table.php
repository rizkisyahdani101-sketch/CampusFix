<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->restrictOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('root_cause');
            $table->text('action_taken');
            $table->text('parts_used')->nullable();
            $table->unsignedInteger('minutes_spent');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['issue_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};
