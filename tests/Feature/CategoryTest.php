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

class CategoryTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    private User $superAdmin;
    private User $delivery;
    private User $client;

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
    }

    // Index Tests
    public function test_index_returns_root_categories_with_children()
    {
        Category::factory()->create(['parent_id' => null])
            ->children()->save(Category::factory()->make());

        $response = $this->getJson('/api/category');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'children' => [
                            '*' => ['id', 'name', 'parent_id']
                        ]
                    ]
                ]
            ]);
    }

    // Store Tests
    public function test_store_requires_authentication()
    {
        $response = $this->postJson('/api/category', [
            'name' => 'Test Category'
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_creates_root_category()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/category', [
                'name' => 'Root Category'
            ]);

        $this->assertDatabaseHas('categories', ['name' => 'Root Category']);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Root Category');
    }

    public function test_store_creates_subcategory_with_valid_parent()
    {
        $parent = Category::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/category', [
                'name' => 'Sub Category',
                'parent_id' => $parent->id
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.parent_id', $parent->id);
    }

    public function test_store_rejects_invalid_parent_category()
    {
        $parent = Category::factory()->create(['parent_id' => Category::factory()->create()->id]);

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/category', [
                'name' => 'Invalid Sub Category',
                'parent_id' => $parent->id
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', "Can't create sub category under sub category");
    }

    public function test_store_handles_image_upload()
    {
        $file = UploadedFile::fake()->image('category.jpg');

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/category', [
                'name' => 'Category with Image',
                'image' => $file
            ]);

        $response->assertCreated();
        $this->assertNotNull($response->json('data.image'));
    }

    // Show Tests
    public function test_show_returns_category_with_products()
    {
        $category = Category::factory()->hasProducts(5)->create();

        $response = $this->getJson("/api/category/{$category->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'parent_id', 'image' , 'products' => [
                        'data' => [
                            '*' => ['id', 'name' , 'category_id' , 'price' , 'priceBefore' , 'quantity' , 'description' , 'live' , 'special_offer' , 'daily_offer']
                        ]
                    ]
                ]
            ]);
    }

    public function test_show_filters_live_products()
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'live' => true]);
        Product::factory()->create(['category_id' => $category->id, 'live' => false]);

        $response = $this->getJson("/api/category/{$category->id}");
        
        $response->assertJsonCount(1, 'data.products.data');
    }

    // Admin Show Tests
    public function test_admin_show_includes_non_live_products()
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'live' => false]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/api/category/show-admin/{$category->id}");

        $response->assertJsonCount(1, 'data.products.data');
    }

    // Update Tests
    public function test_update_validates_self_parenting()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/category/{$category->id}", [
                'name' => 'Updated',
                'parent_id' => $category->id
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', "Category can't be a sub category of itself");
    }

    public function test_update_replaces_image()
    {
        $category = Category::factory()->create();
        $newFile = UploadedFile::fake()->image('new-image.jpg');

        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/category/{$category->id}", [
                'name' => 'Updated',
                'image' => $newFile
            ]);
        
        $response->assertOk();
        $this->assertNotNull($response->json('data.image'));
    }

    // Destroy Tests
    public function test_destroy_soft_deletes_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/category/{$category->id}");

        $response->assertNoContent();
        $this->assertDatabaseEmpty('categories');
    }

    // Validation Tests
    public function test_store_validates_name_required()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/category', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_image_types()
    {
        $invalidFile = UploadedFile::fake()->create('invalid.pdf', 1000);

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/category', [
                'name' => 'Invalid',
                'image' => $invalidFile
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    // Authorization Tests
    public function test_regular_user_cannot_access_protected_routes()
    {
        $category = Category::factory()->create();

        $routes = [
            ['post', '/api/category'],
            ['put', "/api/category/{$category->id}"],
            ['delete', "/api/category/{$category->id}"]
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->actingAs($this->client)
                ->{$method.'Json'}($uri);
            
            $response->assertForbidden();
        }
    }
}
