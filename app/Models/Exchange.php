<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exchange extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'accepted', 'rejected', 'cancelled', 'completed'];

    protected $fillable = [
        'requester_id',
        'recipient_id',
        'offered_book_id',
        'requested_book_id',
        'status',
        'message',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function offeredBook(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'offered_book_id');
    }

    public function requestedBook(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'requested_book_id');
    }
}
