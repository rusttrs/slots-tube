<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_settings', function (Blueprint $table) {
            $table->json('blocks')->nullable()->after('faq');
        });
    }

    public function down(): void
    {
        Schema::table('page_settings', function (Blueprint $table) {
            $table->dropColumn('blocks');
        });
    }
};
