<?php

namespace App\Modules\Card\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Card extends Model
{
    protected $fillable = [
        'user_id',
        'hash',
        'last_digits',
        'status',
        'comment'
    ];

    protected $casts = [
        'status' => CardStatus::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function existsForUserWithHash(string $userId, string $hashedPan): bool
    {
        $exists = self::where('user_id', $userId)
            ->where('hash', $hashedPan)
            ->exists();

        return $exists;
    }

    public function shouldAutoActivate(): bool
    {
        return !static::where('user_id', $this->user_id)
            ->where('status', CardStatus::ACTIVE)
            ->exists();
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeStatusFilter(Builder $query, ?CardStatus $status): Builder
    {
        return $status ? $query->where('status', $status->value) : $query;
    }
}