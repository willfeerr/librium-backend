<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    public const CONDITIONS = ['new', 'like_new', 'good', 'fair', 'poor'];

    protected $fillable = [
        'owner_id',
        'category_id',
        'title',
        'author',
        'isbn',
        'description',
        'publisher',
        'publication_date',
        'condition',
        'price',
        'cover',
        'status',
        'rating',
        'reviews_count',
    ];

    protected function casts(): array
    {
        return [
            'publication_date' => 'date',
            'price' => 'decimal:2',
            'rating' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(BookReview::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['title'] ?? null, fn (Builder $query, string $title) => $query->where('title', 'like', "%{$title}%"))
            ->when($filters['author'] ?? null, fn (Builder $query, string $author) => $query->where('author', 'like', "%{$author}%"))
            ->when($filters['isbn'] ?? null, fn (Builder $query, string $isbn) => $query->where('isbn', 'like', "%{$isbn}%"))
            ->when($filters['publisher'] ?? null, fn (Builder $query, string $publisher) => $query->where('publisher', 'like', "%{$publisher}%"))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int|string $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['condition'] ?? null, fn (Builder $query, string $condition) => $query->where('condition', $condition))
            ->when($filters['price_min'] ?? null, fn (Builder $query, int|float|string $price) => $query->where('price', '>=', $price))
            ->when($filters['price_max'] ?? null, fn (Builder $query, int|float|string $price) => $query->where('price', '<=', $price));
    }

    public function refreshRating(): void
    {
        $rating = $this->reviews()->avg('rating') ?? 0;
        $count = $this->reviews()->count();

        $this->forceFill([
            'rating' => round($rating, 2),
            'reviews_count' => $count,
        ])->save();
    }
}
