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
            $table->string('location_detail', 255)->nullable()->after('location_id');
        });

        Schema::table('pending_guest_reports', function (Blueprint $table): void {
            $table->string('location_detail', 255)->nullable()->after('location_id');
        });
    }

    public function down(): void
    {
        Schema::table('pending_guest_reports', function (Blueprint $table): void {
            $table->dropColumn('location_detail');
        });

        Schema::table('issues', function (Blueprint $table): void {
            $table->dropColumn('location_detail');
        });
    }
};
