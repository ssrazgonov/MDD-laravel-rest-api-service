<?php

namespace App\Modules\Card\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\User\Models\GroupMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'hash',
        'last_digits',
        'status',
        'comment',
    ];

    protected $casts = [
        'status' => CardStatus::class,
    ];

    protected $appends = [
    ];

    public function groupMember(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class, 'member_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class, 'member_id');
    }

    public function cardAccessControlForUser(string $userId)
    {
        return $this->hasOne(CardAccessControl::class)->where('user_id', $userId);
    }

    public function user(): BelongsTo
    {
        return $this->member->user();
    }

    public static function existsForMemberWithHash(int $memberId, string $hashedPan): bool
    {
        return self::where('member_id', $memberId)
            ->where('hash', $hashedPan)
            ->exists();
    }

    public function shouldAutoActivate(): bool
    {
        return ! static::where('member_id', $this->member_id)
            ->where('status', CardStatus::ACTIVE)
            ->exists();
    }

    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->whereHas('member', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeStatusFilter(Builder $query, ?CardStatus $status): Builder
    {
        return $status instanceof CardStatus ? $query->where('status', $status->value) : $query;
    }
}
