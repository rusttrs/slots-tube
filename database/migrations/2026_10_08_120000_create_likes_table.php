<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string, 2: string}> legacy table => [morph alias, fk column, target table] */
    private const LEGACY = [
        'post_likes' => ['post', 'post_id', 'posts'],
        'post_comment_likes' => ['post_comment', 'post_comment_id', 'post_comments'],
        'slot_review_likes' => ['slot_review', 'slot_review_id', 'slot_reviews'],
    ];

    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('likeable_type', 32);
            $table->unsignedBigInteger('likeable_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['likeable_type', 'likeable_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });

        foreach (self::LEGACY as [, , $target]) {
            Schema::table($target, function (Blueprint $table) {
                $table->unsignedInteger('likes_count')->default(0);
            });
        }

        foreach (self::LEGACY as $legacy => [$alias, $fk, $target]) {
            if (! Schema::hasTable($legacy)) {
                continue;
            }

            DB::statement(
                "INSERT INTO likes (user_id, likeable_type, likeable_id, created_at)
                 SELECT user_id, ?, {$fk}, COALESCE(created_at, CURRENT_TIMESTAMP) FROM {$legacy} ORDER BY id",
                [$alias],
            );

            DB::statement(
                "UPDATE {$target} SET likes_count = (
                    SELECT COUNT(*) FROM likes WHERE likes.likeable_type = ? AND likes.likeable_id = {$target}.id
                 )",
                [$alias],
            );

            Schema::drop($legacy);
        }
    }

    public function down(): void
    {
        Schema::create('post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('post_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_comment_id')->constrained('post_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['post_comment_id', 'user_id']);
        });

        Schema::create('slot_review_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_review_id')->constrained('slot_reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['slot_review_id', 'user_id']);
        });

        foreach (self::LEGACY as $legacy => [$alias, $fk, $target]) {
            DB::statement(
                "INSERT INTO {$legacy} (user_id, {$fk}, created_at, updated_at)
                 SELECT likes.user_id, likes.likeable_id, likes.created_at, likes.created_at
                 FROM likes JOIN {$target} ON {$target}.id = likes.likeable_id
                 WHERE likes.likeable_type = ? ORDER BY likes.id",
                [$alias],
            );

            Schema::table($target, function (Blueprint $table) {
                $table->dropColumn('likes_count');
            });
        }

        Schema::dropIfExists('likes');
    }
};
