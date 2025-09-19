<?php

namespace App\Modules\Card\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\User\Models\GroupMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_member_id',
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
        return $this->belongsTo(GroupMember::class, 'group_member_id');
    }

    public function cardAccessControl(): HasOne
    {
        return $this->hasOne(CardAccessControl::class);
    }

    public function cardAccessControlForUser(int $memberId): HasOne
    {
        return $this->hasOne(CardAccessControl::class)->where('group_member_id', $memberId);
    }

    public static function existsForMemberWithHash(int $memberId, string $hashedPan): bool
    {
        return self::where('group_member_id', $memberId)
            ->where('hash', $hashedPan)
            ->exists();
    }

    public function shouldAutoActivate(): bool
    {
        return ! static::where('group_member_id', $this->group_member_id)
            ->where('status', CardStatus::ACTIVE)
            ->exists();
    }

    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where('group_member_id', $memberId);
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
