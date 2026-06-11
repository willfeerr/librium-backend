<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->isbn13(),
            'description' => fake()->paragraph(),
            'publisher' => fake()->company(),
            'publication_date' => fake()->date(),
            'condition' => fake()->randomElement(Book::CONDITIONS),
            'price' => fake()->randomFloat(2, 10, 150),
            'cover' => null,
            'status' => 'available',
        ];
    }
}
