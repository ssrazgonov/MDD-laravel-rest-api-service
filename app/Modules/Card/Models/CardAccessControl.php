<?php

namespace App\Modules\Card\Models;

use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\User\Models\GroupMember;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardAccessControl extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'card_id',
        'group_member_id',
        'access_level',
    ];

    protected $casts = [
        'access_level' => CardAccessLevel::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function groupMember(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class);
    }

    public static function canUserManageCard(User $user, Card $card): bool
    {
        return self::where('card_id', $card->id)
            ->where('group_member_id', $user->groupMember->id)
            ->where('access_level', CardAccessLevel::MANAGE)
            ->exists();
    }

    public static function canUserViewCard(User $user, int $cardId): bool
    {
        return self::where('card_id', $cardId)
            ->where('group_member_id', $user->groupMember->id)
            ->whereIn('access_level', [CardAccessLevel::VIEW, CardAccessLevel::MANAGE])
            ->exists();
    }
}
