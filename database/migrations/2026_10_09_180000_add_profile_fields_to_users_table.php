<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nickname');
            $table->timestamp('nickname_changed_at')->nullable()->after('slug');
        });

        DB::table('users')->orderBy('id')->select(['id', 'name', 'nickname', 'email'])->each(function ($row) {
            $base = (new User((array) $row))->displayName();
            DB::table('users')->where('id', $row->id)->update(['slug' => User::uniqueSlug($base, (int) $row->id)]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'nickname_changed_at']);
        });
    }
};
