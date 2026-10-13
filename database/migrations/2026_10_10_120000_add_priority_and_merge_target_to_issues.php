<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table): void {
            $table->string('priority', 20)->default('medium')->after('status')->index();
            $table->foreignId('merged_into_issue_id')
                ->nullable()
                ->after('priority')
                ->constrained('issues')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('merged_into_issue_id');
            $table->dropIndex(['priority']);
            $table->dropColumn('priority');
        });
    }
};
