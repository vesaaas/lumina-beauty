<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_filtered_by_combined_metadata(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->get(route('products.index', [
            'category' => 'Perfume',
            'gender' => 'Unisex',
            'size' => '100ml',
        ]));

        $response
            ->assertOk()
            ->assertSee('YSL Beauty Libre Eau de Parfum')
            ->assertDontSee("Dior J'adore Eau de Parfum");
    }
}
