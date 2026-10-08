<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->foreignId('reviewer_id')
                ->nullable()
                ->after('author_id')
                ->constrained('authors')
                ->nullOnDelete();
            $table->date('last_reviewed_on')->nullable()->after('content_updated_on');
            $table->string('review_focus')->nullable()->after('last_reviewed_on');
        });
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropColumn(['last_reviewed_on', 'review_focus']);
        });
    }
};
