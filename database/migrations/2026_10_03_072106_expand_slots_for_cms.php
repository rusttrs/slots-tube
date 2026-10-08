<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->date('release_date')->nullable()->after('volatility');
            $table->string('game_type')->nullable()->after('release_date');
            $table->string('grid')->nullable()->after('game_type');
            $table->string('win_system')->nullable()->after('grid');
            $table->string('rtp_text')->nullable()->after('win_system');
            $table->decimal('rtp_min', 5, 2)->nullable()->after('rtp_text');
            $table->decimal('rtp_max', 5, 2)->nullable()->after('rtp_min');
            $table->string('max_win')->nullable()->after('rtp_max');
            $table->string('stake_range')->nullable()->after('max_win');
            $table->string('technology')->nullable()->after('stake_range');
            $table->string('wild_symbol')->nullable()->after('technology');
            $table->string('free_spins')->nullable()->after('wild_symbol');
            $table->string('progressive')->nullable()->after('free_spins');
            $table->string('bonus_buy')->nullable()->after('progressive');
            $table->string('tumbling_wins')->nullable()->after('bonus_buy');
            $table->string('gamble_feature')->nullable()->after('tumbling_wins');
            $table->string('scatter_symbol')->nullable()->after('gamble_feature');
            $table->text('features_text')->nullable()->after('scatter_symbol');
            $table->text('theme_text')->nullable()->after('features_text');
            $table->boolean('filter_bonus_buy')->default(false)->after('theme_text');
            $table->boolean('filter_free_spins')->default(false)->after('filter_bonus_buy');
            $table->boolean('filter_tumbling')->default(false)->after('filter_free_spins');
            $table->boolean('filter_scatter')->default(false)->after('filter_tumbling');
            $table->boolean('filter_gamble')->default(false)->after('filter_scatter');
            $table->boolean('filter_progressive')->default(false)->after('filter_gamble');
            $table->decimal('editorial_score', 3, 1)->nullable()->after('filter_progressive');
            $table->boolean('show_session_data')->default(false)->after('editorial_score');

            // Translatable JSON content blobs
            $table->json('intro')->nullable()->after('excerpt');
            $table->json('quick_verdict')->nullable();
            $table->json('about_text')->nullable();
            $table->json('rtp_blurb')->nullable();
            $table->json('review_overview')->nullable();
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->json('audience_intro')->nullable();
            $table->json('best_for')->nullable();
            $table->json('not_ideal_for')->nullable();
            $table->json('bonus_features_body')->nullable();
            $table->json('experience_title')->nullable();
            $table->json('experience_body')->nullable();
            $table->json('responsible_play')->nullable();
            $table->json('symbols_intro')->nullable();
            $table->json('rtp_section_text')->nullable();
            $table->json('interpret_cards')->nullable();
            $table->json('screenshots_intro')->nullable();
            $table->json('mobile_intro')->nullable();
            $table->json('mobile_checklist')->nullable();
            $table->json('similar_intro')->nullable();
            $table->json('similar_outro')->nullable();
            $table->json('similar_bonus_fit')->nullable();
            $table->json('similar_watch_out')->nullable();
            $table->json('methodology_body')->nullable();
            $table->json('faq')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            // Media / structured
            $table->json('symbols')->nullable();
            $table->json('paytable_rows')->nullable();
            $table->json('screenshots')->nullable();
            $table->json('mobile_screenshots')->nullable();
        });

        Schema::table('bonuses', function (Blueprint $table) {
            $table->boolean('is_hot')->default(false)->after('is_exclusive');
            $table->boolean('is_new')->default(false)->after('is_hot');
            $table->string('extra_text')->nullable()->after('short_text');
        });

        Schema::create('bonus_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bonus_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['slot_id', 'bonus_id']);
        });

        Schema::create('slot_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('play_mode', 16)->default('demo'); // demo|real
            $table->unsignedTinyInteger('rating')->default(4);
            $table->boolean('played_myself')->default(false);
            $table->text('body')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['slot_id', 'is_published']);
        });

        Schema::create('slot_review_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_review_id')->constrained('slot_reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['slot_review_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_review_likes');
        Schema::dropIfExists('slot_reviews');
        Schema::dropIfExists('bonus_slot');

        Schema::table('bonuses', function (Blueprint $table) {
            $table->dropColumn(['is_hot', 'is_new', 'extra_text']);
        });

        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn([
                'release_date', 'game_type', 'grid', 'win_system', 'rtp_text', 'rtp_min', 'rtp_max',
                'max_win', 'stake_range', 'technology', 'wild_symbol', 'free_spins', 'progressive',
                'bonus_buy', 'tumbling_wins', 'gamble_feature', 'scatter_symbol', 'features_text',
                'theme_text', 'filter_bonus_buy', 'filter_free_spins', 'filter_tumbling',
                'filter_scatter', 'filter_gamble', 'filter_progressive', 'editorial_score',
                'show_session_data', 'intro', 'quick_verdict', 'about_text', 'rtp_blurb',
                'review_overview', 'pros', 'cons', 'audience_intro', 'best_for', 'not_ideal_for',
                'bonus_features_body', 'experience_title', 'experience_body', 'responsible_play',
                'symbols_intro', 'rtp_section_text', 'interpret_cards', 'screenshots_intro',
                'mobile_intro', 'mobile_checklist', 'similar_intro', 'similar_outro',
                'similar_bonus_fit', 'similar_watch_out', 'methodology_body', 'faq',
                'meta_title', 'meta_description', 'symbols', 'paytable_rows', 'screenshots',
                'mobile_screenshots',
            ]);
        });
    }
};
