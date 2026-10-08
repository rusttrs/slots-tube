<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'content_updated_on')) {
                $table->date('content_updated_on')->nullable()->after('published_at');
            }
            if (! Schema::hasColumn('posts', 'publisher_user_id')) {
                $table->foreignId('publisher_user_id')->nullable()->after('author_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('posts', 'updated_by_user_id')) {
                $table->foreignId('updated_by_user_id')->nullable()->after('content_updated_on')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'updated_by_user_id')) {
                $table->dropConstrainedForeignId('updated_by_user_id');
            }
            if (Schema::hasColumn('posts', 'publisher_user_id')) {
                $table->dropConstrainedForeignId('publisher_user_id');
            }
            if (Schema::hasColumn('posts', 'content_updated_on')) {
                $table->dropColumn('content_updated_on');
            }
        });
    }
};
