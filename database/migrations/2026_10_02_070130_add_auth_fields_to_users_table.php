<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('nickname')->nullable()->after('name');
            $table->string('avatar_path')->nullable()->after('nickname');
            $table->boolean('newsletter_opt_in')->default(false)->after('avatar_path');
            $table->boolean('age_confirmed')->default(false)->after('newsletter_opt_in');
            $table->timestamp('onboarding_completed_at')->nullable()->after('age_confirmed');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'nickname',
                'avatar_path',
                'newsletter_opt_in',
                'age_confirmed',
                'onboarding_completed_at',
            ]);
        });
    }
};
