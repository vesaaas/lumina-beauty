<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('skin_types')->nullable()->after('size');
            $table->json('hair_types')->nullable()->after('skin_types');
            $table->json('concerns')->nullable()->after('hair_types');
            $table->json('benefits')->nullable()->after('concerns');
            $table->json('target_areas')->nullable()->after('benefits');
            $table->json('key_ingredients')->nullable()->after('target_areas');
            $table->string('routine_step')->nullable()->after('key_ingredients');
            $table->text('usage_instructions')->nullable()->after('routine_step');
            $table->string('usage_frequency')->nullable()->after('usage_instructions');
            $table->boolean('am_suitable')->nullable()->after('usage_frequency');
            $table->boolean('pm_suitable')->nullable()->after('am_suitable');
            $table->boolean('fragrance_free')->nullable()->after('pm_suitable');
            $table->boolean('cruelty_free')->nullable()->after('fragrance_free');
            $table->boolean('vegan')->nullable()->after('cruelty_free');
            $table->boolean('alcohol_free')->nullable()->after('vegan');
            $table->boolean('non_comedogenic')->nullable()->after('alcohol_free');
            $table->json('works_well_with')->nullable()->after('non_comedogenic');
            $table->json('avoid_combining_with')->nullable()->after('works_well_with');
            $table->text('knowledge_warnings')->nullable()->after('avoid_combining_with');

            $table->index(['is_active', 'stock']);
            $table->index('routine_step');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'stock']);
            $table->dropIndex(['routine_step']);
            $table->dropColumn([
                'skin_types',
                'hair_types',
                'concerns',
                'benefits',
                'target_areas',
                'key_ingredients',
                'routine_step',
                'usage_instructions',
                'usage_frequency',
                'am_suitable',
                'pm_suitable',
                'fragrance_free',
                'cruelty_free',
                'vegan',
                'alcohol_free',
                'non_comedogenic',
                'works_well_with',
                'avoid_combining_with',
                'knowledge_warnings',
            ]);
        });
    }
};
