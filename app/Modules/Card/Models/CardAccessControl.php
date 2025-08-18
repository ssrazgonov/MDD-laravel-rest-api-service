<?php

namespace App\Modules\Card\Models;

use App\Modules\Card\Enums\CardAccessLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\User\Models\User;
use \Illuminate\Support\Collection;

class CardAccessControl extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id',
        'card_id',
        'user_id',
        'access_level',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function canUserManageCard(User $user, Card $card): bool
    {
        return self::where('card_id', $card->id)
            ->where('user_id', $user->id)
            ->where('access_level', CardAccessLevel::MANAGE)
            ->exists();
    }

    public static function canUserViewCard(User $user, Card $card): bool
    {
        return self::where('card_id', $card->id)
            ->where('user_id', $user->id)
            ->whereIn('access_level', [CardAccessLevel::VIEW, CardAccessLevel::MANAGE])
            ->exists();
    }
}