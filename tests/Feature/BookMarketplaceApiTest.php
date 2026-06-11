<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookMarketplaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_book_and_filter_books(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Tecnologia', 'slug' => 'tecnologia']);

        Sanctum::actingAs($user);

        $this->postJson('/api/books', [
            'category_id' => $category->id,
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'condition' => 'good',
            'price' => 49.90,
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Clean Code');

        $this->getJson('/api/books?search=clean&author=robert&per_page=20')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('data.0.title', 'Clean Code');
    }

    public function test_buyer_can_create_order_from_listing(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $book = Book::factory()->create(['owner_id' => $seller->id]);
        $listing = Listing::factory()->create([
            'seller_id' => $seller->id,
            'book_id' => $book->id,
            'price' => 50,
            'quantity' => 2,
            'status' => 'active',
        ]);

        Sanctum::actingAs($buyer);

        $this->postJson('/api/orders', [
            'listing_id' => $listing->id,
            'quantity' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('data.total', 50)
            ->assertJsonPath('data.items.0.quantity', 1);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'quantity' => 1,
        ]);
    }
}
