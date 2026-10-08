<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Updated" is signed by an Author and only exists together with content_updated_on.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['posts', 'slots'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('updated_by_author_id')->nullable()->after('content_updated_on')->constrained('authors')->nullOnDelete();
            });
        }

        DB::table('posts')
            ->whereNotNull('content_updated_on')
            ->update(['updated_by_author_id' => DB::raw('reviewer_id')]);

        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('reviewer_id')->nullable()->after('author_id')->constrained('authors')->nullOnDelete();
        });

        DB::table('posts')->update(['reviewer_id' => DB::raw('updated_by_author_id')]);

        foreach (['posts', 'slots'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('updated_by_author_id');
            });
        }
    }
};
