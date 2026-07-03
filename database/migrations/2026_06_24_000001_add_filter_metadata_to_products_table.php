<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->nullable()->after('description');
            $table->json('properties')->nullable()->after('product_type');
            $table->string('gender')->nullable()->after('properties');
            $table->string('size')->nullable()->after('gender');
        });

        foreach ($this->metadata() as $slug => $metadata) {
            DB::table('products')
                ->where('slug', $slug)
                ->update([
                    'product_type' => $metadata['product_type'],
                    'properties' => json_encode($metadata['properties']),
                    'gender' => $metadata['gender'],
                    'size' => $metadata['size'],
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'properties', 'gender', 'size']);
        });
    }

    private function metadata(): array
    {
        return [
            'the-ordinary-niacinamide-10-zinc-1' => [
                'product_type' => 'Serum',
                'properties' => ['Oil Control', 'Brightening'],
                'gender' => 'Unisex',
                'size' => '30ml',
            ],
            'olaplex-no-3-hair-perfector' => [
                'product_type' => 'Hair Mask',
                'properties' => ['Anti-Aging', 'Hydrating'],
                'gender' => 'Unisex',
                'size' => '100ml',
            ],
            'charlotte-tilbury-pillow-talk-matte-revolution-lipstick' => [
                'product_type' => 'Lipstick',
                'properties' => ['Long Lasting'],
                'gender' => 'Women',
                'size' => '30ml',
            ],
            'dior-jadore-eau-de-parfum' => [
                'product_type' => 'Eau de Parfum',
                'properties' => ['Long Lasting'],
                'gender' => 'Women',
                'size' => '100ml',
            ],
            'sol-de-janeiro-brazilian-bum-bum-cream' => [
                'product_type' => 'Moisturizer',
                'properties' => ['Hydrating'],
                'gender' => 'Women',
                'size' => '200ml',
            ],
            'cerave-moisturising-cream' => [
                'product_type' => 'Moisturizer',
                'properties' => ['Hydrating', 'Sensitive Skin'],
                'gender' => 'Unisex',
                'size' => '200ml',
            ],
            'la-roche-posay-anthelios-uvmune-400-invisible-fluid-spf50' => [
                'product_type' => 'Moisturizer',
                'properties' => ['Sensitive Skin', 'Oil Control'],
                'gender' => 'Unisex',
                'size' => '50ml',
            ],
            'fenty-beauty-gloss-bomb-universal-lip-luminizer' => [
                'product_type' => 'Lipstick',
                'properties' => ['Hydrating', 'Long Lasting'],
                'gender' => 'Women',
                'size' => '30ml',
            ],
            'rare-beauty-soft-pinch-liquid-blush' => [
                'product_type' => 'Blush',
                'properties' => ['Long Lasting', 'Brightening'],
                'gender' => 'Women',
                'size' => '30ml',
            ],
            'ysl-beauty-libre-eau-de-parfum' => [
                'product_type' => 'Eau de Parfum',
                'properties' => ['Long Lasting'],
                'gender' => 'Unisex',
                'size' => '100ml',
            ],
        ];
    }
};
