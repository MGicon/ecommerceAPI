<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    private User $superAdmin;
    private User $client;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'superAdmin']);
        Role::create(['name' => 'client']);
        Role::create(['name' => 'delivery']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('superAdmin');
        
        $this->client = User::factory()->create();
        $this->client->assignRole('client');

        $this->category = Category::factory()->create();
    }

    // Index Tests
    public function test_index_returns_paginated_product()
    {
        Product::factory()->count(20)->create(['live' => true]);

        $response = $this->getJson('/api/product');
        
        dd($response->json());
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'image']
                ],
                'links', 'meta'
            ])
            ->assertJsonCount(15, 'data');
    }

    public function test_index_filters_by_category()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        
        $response = $this->getJson("/api/product?category_id={$this->category->id}");
        
        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id);
    }

    public function test_index_sorts_by_price_desc()
    {
        Product::factory()->create(['price' => 100]);
        Product::factory()->create(['price' => 200]);

        $response = $this->getJson('/api/product?sort_by=price&asc=false');
        
        $prices = collect($response->json('data'))->pluck('price');
        $this->assertEquals([200, 100], $prices->all());
    }

    // Store Tests
    public function test_store_creates_product_with_images()
    {
        $mainImage = UploadedFile::fake()->image('main.jpg');
        $additionalImages = [
            UploadedFile::fake()->image('img1.jpg'),
            UploadedFile::fake()->image('img2.jpg')
        ];

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/product', [
                'name' => 'New Product',
                'description' => 'Test description',
                'quantity' => 10,
                'live' => true,
                'price' => 99.99,
                'priceBefore' => 129.99,
                'category_id' => $this->category->id,
                'image' => $mainImage,
                'additional_images' => $additionalImages
            ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.additional_images')
            ->assertJsonPath('data.name', 'New Product');

        $this->assertDatabaseHas('product', [
            'name' => 'New Product',
            'price' => 99.99,
            'category_id' => $this->category->id
        ]);
    }

    public function test_store_validates_price_relationship()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/product', [
                'price' => 100,
                'priceBefore' => 90
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['priceBefore']);
    }

    public function test_store_rejects_invalid_images()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/product', [
                'image' => UploadedFile::fake()->create('invalid.pdf'),
                'additional_images' => [UploadedFile::fake()->create('bad.exe')]
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image', 'additional_images.0']);
    }

    // Show Tests
    public function test_show_returns_product_with_relations()
    {
        $product = Product::factory()->create(['live' => true]);

        $response = $this->getJson("/api/product/{$product->id}");
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'similarproduct', 
                    'ratings' => ['data'],
                    'additional_images'
                ]
            ]);
    }

    public function test_show_hides_non_live_product()
    {
        $product = Product::factory()->create(['live' => false]);

        $response = $this->getJson("/api/product/{$product->id}");
        
        $response->assertNotFound();
    }

    // Update Tests
    public function test_update_modifies_product_data()
    {
        $product = Product::factory()->create();
        $newImage = UploadedFile::fake()->image('new.jpg');

        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/product/{$product->id}", [
                'name' => 'Updated Name',
                'price' => 149.99,
                'priceBefore' => 199.99,
                'image' => $newImage
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.price', 149.99);

        $this->assertDatabaseHas('product', [
            'id' => $product->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_update_clears_additional_images()
    {
        $product = Product::factory()->create();
        $product->addMedia(UploadedFile::fake()->image('old.jpg'))
            ->toMediaCollection('additional_images');

        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/product/{$product->id}", [
                'additional_images' => []
            ]);

        $response->assertJsonCount(0, 'data.additional_images');
    }

    // Destroy Tests
    public function test_destroy_removes_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/product/{$product->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($product);
    }

    // Validation Tests
    public function test_required_fields_validation()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/product', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name', 'description', 'quantity', 
                'price', 'priceBefore', 'category_id'
            ]);
    }

    public function test_date_validation_for_offers()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/product', [
                'special_offer' => now()->subDay()->format('Y-m-d H:i:s A'),
                'daily_offer' => 'invalid-date'
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['special_offer', 'daily_offer']);
    }

    // Authorization Tests
    public function test_unauthorized_users_cannot_modify_product()
    {
        $product = Product::factory()->create();
        $regularUser = User::factory()->create();

        $routes = [
            ['post', '/api/product'],
            ['put', "/api/product/{$product->id}"],
            ['delete', "/api/product/{$product->id}"]
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->actingAs($regularUser)
                ->{$method.'Json'}($uri);
            
            $response->assertForbidden();
        }
    }

    // Edge Cases
    public function test_can_handle_max_additional_images()
    {
        $images = array_fill(0, 20, UploadedFile::fake()->image('img.jpg'));

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/product', [
                'additional_images' => $images
            ]);

        $response->assertCreated()
            ->assertJsonCount(20, 'data.additional_images');
    }

    public function test_rejects_too_many_additional_images()
    {
        $images = array_fill(0, 21, UploadedFile::fake()->image('img.jpg'));

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/product', [
                'additional_images' => $images
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['additional_images']);
    }
}