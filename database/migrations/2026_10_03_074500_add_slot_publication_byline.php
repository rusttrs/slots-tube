<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->foreignId('published_by_user_id')->nullable()->after('author_id')->constrained('users')->nullOnDelete();
            $table->date('content_updated_on')->nullable()->after('published_at');
            $table->foreignId('updated_by_user_id')->nullable()->after('content_updated_on')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by_user_id');
            $table->dropColumn('content_updated_on');
            $table->dropConstrainedForeignId('published_by_user_id');
        });
    }
};
