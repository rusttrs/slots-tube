<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->json('methodology_status')->nullable()->after('methodology_body');
            $table->json('methodology_points')->nullable()->after('methodology_status');
        });
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn(['methodology_status', 'methodology_points']);
        });
    }
};
