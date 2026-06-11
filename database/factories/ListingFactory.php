<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'book_id' => Book::factory(),
            'price' => fake()->randomFloat(2, 15, 120),
            'quantity' => fake()->numberBetween(1, 5),
            'status' => 'active',
        ];
    }
}
