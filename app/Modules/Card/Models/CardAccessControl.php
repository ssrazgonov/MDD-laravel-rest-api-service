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

    public static function createForChildCard(Card $card, User $parent): static
    {
        return static::create([
            'card_id' => $card->id,
            'user_id' => $parent->id,
            'access_level' => CardAccessLevel::VIEW,
            'created_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    public static function getCardManagers(Card $card): Collection
    {
        return self::where('card_id', $card->id)
            ->where('access_level', 'manage')
            ->with('user')
            ->get()
            ->map(function (CardAccessControl $accessControl) {
                return $accessControl->user;
            });
    }
}