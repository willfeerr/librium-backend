<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'BookMarket Admin',
            'email' => 'admin@bookmarket.test',
            'is_admin' => true,
        ]);

        foreach (['Ficcao', 'Tecnologia', 'Negocios', 'Educacao', 'Biografia'] as $category) {
            Category::query()->firstOrCreate([
                'slug' => str($category)->slug()->toString(),
            ], [
                'name' => $category,
            ]);
        }
    }
}
