<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->string('team_group', 20)->default('editors')->after('sort_order');
        });

        Schema::table('page_settings', function (Blueprint $table) {
            $table->json('texts')->nullable()->after('noindex');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn('team_group');
        });

        Schema::table('page_settings', function (Blueprint $table) {
            $table->dropColumn('texts');
        });
    }
};
